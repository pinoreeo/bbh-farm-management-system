<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\AdminTableViewData;
use Illuminate\Http\Request;

class AdminSearchController extends Controller
{
    public function __invoke(Request $request, AdminTableViewData $tableData)
    {
        $query = trim((string) $request->query('q', ''));
        $results = $query === '' ? [] : $this->searchData($query, $tableData);

        return view('pages.admin.search', [
            'query' => $query,
            'results' => $results,
            'failureMessage' => $tableData->failureMessage(),
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

        return collect($this->searchableSlugs())
            ->flatMap(function (array $page, string $slug) use ($query, $terms, $tableData) {
                [$title, $description, $columns, $fallbackRows] = array_pad($page, 4, []);

                return collect($tableData->records($slug, $fallbackRows, session('bbh_api_token'), 2, ['search' => $query]))
                    ->map(function (array $record) use ($slug, $title, $description, $columns, $query, $terms) {
                        $cells = $record['cells'] ?? [];
                        $haystack = mb_strtolower(implode(' ', $cells));
                        $score = $terms->sum(fn (string $term) => str_contains($haystack, $term) ? 1 : 0);

                        if ($score === 0) {
                            return null;
                        }

                        $matchedFields = collect($cells)
                            ->map(fn ($value, int $index) => [
                                'label' => $columns[$index] ?? 'Data',
                                'value' => (string) $value,
                            ])
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
                            'listRoute' => route('admin.'.$slug, ['search' => $query]),
                            'detailRoute' => ! empty($record['id']) ? route('admin.resource.show', ['resource' => $slug, 'id' => $record['id']]) : route('admin.'.$slug, ['search' => $query]),
                        ];
                    })
                    ->filter();
            })
            ->sortByDesc('score')
            ->take(30)
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
