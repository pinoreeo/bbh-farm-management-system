<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\AdminTableViewData;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class AdminSearchController extends Controller
{
    public function __invoke(Request $request, AdminTableViewData $tableData)
    {
        $query = trim((string) $request->query('q', ''));
        $results = $query === '' ? [] : $this->searchData($query, $tableData);
        $page = min(max(1, (int) $request->query('page', 1)), max(1, (int) ceil(count($results) / 10)));
        $results = new LengthAwarePaginator(
            array_slice($results, ($page - 1) * 10, 10),
            count($results),
            10,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('pages.admin.search', [
            'query' => $query,
            'results' => $results,
            'failureMessage' => $tableData->failureMessage(),
            'dataTruncated' => $tableData->isTruncated(),
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function searchData(string $query, AdminTableViewData $tableData): array
    {
        $normalizedQuery = mb_strtolower($query);
        $terms = collect(preg_split('/\s+/', $normalizedQuery) ?: [])
            ->filter()
            ->values();
        $pages = $this->searchableSlugs();
        $recordsBySlug = $tableData->recordsBatch(
            collect($pages)->map(fn (array $page) => $page[3] ?? [])->all(),
            session('bbh_api_token')
        );

        return collect($pages)
            ->flatMap(function (array $page, string $slug) use ($query, $terms, $recordsBySlug) {
                [$title, $description, $columns, $fallbackRows] = array_pad($page, 4, []);

                return collect($recordsBySlug[$slug] ?? [])
                    ->map(function (array $record) use ($slug, $title, $description, $columns, $query, $terms) {
                        $cells = $record['cells'] ?? [];
                        $fields = collect($cells)->map(fn ($value, int $index) => [
                            'label' => $columns[$index] ?? 'Data',
                            'value' => (string) $value,
                        ]);
                        if ($slug === 'users' && ! empty(data_get($record, 'raw.email'))) {
                            $fields->push(['label' => 'Email', 'value' => (string) data_get($record, 'raw.email')]);
                        }
                        $haystack = mb_strtolower($fields->pluck('value')->implode(' '));
                        $score = $terms->sum(fn (string $term) => str_contains($haystack, $term) ? 1 : 0);

                        if ($score === 0) {
                            return null;
                        }

                        $matchedFields = $fields
                            ->filter(fn (array $field) => $terms->contains(fn (string $term) => str_contains(mb_strtolower($field['value']), $term)))
                            ->take(3)
                            ->values()
                            ->all();

                        return [
                            'title' => $title,
                            'description' => $description,
                            'slug' => $slug,
                            'id' => $record['id'] ?? null,
                            'primary' => $cells[0] ?? $title,
                            'secondary' => collect($cells)->skip(1)->take(3)->filter()->implode(' | '),
                            'matchedFields' => $matchedFields,
                            'score' => $score,
                            'listRoute' => route('admin.'.$slug, ['q' => $query]),
                            'detailRoute' => $slug === 'animals' && ! empty(data_get($record, 'raw.tag_number'))
                                ? route('admin.animals.show', ['tag' => data_get($record, 'raw.tag_number')])
                                : (! empty($record['id']) ? route('admin.resource.show', ['resource' => $slug, 'id' => $record['id']]) : route('admin.'.$slug, ['q' => $query])),
                        ];
                    })
                    ->filter();
            })
            ->sortByDesc('score')
            ->values()
            ->all();
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function searchableSlugs(): array
    {
        $pages = config('admin.pages', []);
        $slugs = [
            'animals',
            'pens',
            'breeding-periods',
            'breeding-females',
            'birth-events',
            'offspring-births',
            'weight-records',
            'health-treatments',
            'vaccinations',
            'certificates',
        ];

        if ((session('bbh_admin_user.role') ?? null) === 'super_admin') {
            $slugs[] = 'users';
            $slugs[] = 'rsa-keys';
        }

        return collect($slugs)
            ->filter(fn (string $slug) => isset($pages[$slug]))
            ->mapWithKeys(fn (string $slug) => [$slug => $pages[$slug]])
            ->all();
    }
}
