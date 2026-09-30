<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\AdminTableViewData;
use App\Support\BbhApiClient;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Throwable;

class AdminSearchController extends Controller
{
    public function __invoke(Request $request, AdminTableViewData $tableData, BbhApiClient $api)
    {
        $query = trim((string) $request->query('q', ''));
        $page = max(1, $request->integer('page', 1));
        $matches = [];
        $total = 0;
        $failureMessage = null;

        if ($query !== '') {
            try {
                $response = $api->get('admin/search', ['q' => $query, 'page' => $page], session('bbh_api_token'));
                if (! $response->successful() || ! is_array($response->json('data'))) {
                    $failureMessage = 'Pencarian belum dapat dilakukan. Silakan coba lagi.';
                } else {
                    $total = (int) $response->json('total', 0);
                    foreach ($response->json('data') as $entry) {
                        if (! is_array($entry) || ! is_array($entry['item'] ?? null)) {
                            continue;
                        }
                        $slug = $entry['slug'] ?? null;
                        $pageConfig = is_string($slug) ? config('admin.pages.'.$slug) : null;
                        if (! is_array($pageConfig)) {
                            continue;
                        }
                        $matches[] = $this->match($slug, $pageConfig, $entry['item'], $query, $tableData);
                    }
                }
            } catch (Throwable) {
                $failureMessage = 'Pencarian belum dapat dilakukan. Silakan coba lagi.';
            }
        }

        $results = new LengthAwarePaginator($matches, $total, 10, $page, [
            'path' => $request->url(), 'query' => $request->except('page'),
        ]);

        return view('pages.admin.search', [
            'query' => $query,
            'results' => $results,
            'failureMessage' => $failureMessage,
            'dataTruncated' => false,
        ]);
    }

    /**
     * @param array<int, mixed> $pageConfig
     * @param array<string, mixed> $item
     * @return array<string, mixed>
     */
    private function match(string $slug, array $pageConfig, array $item, string $query, AdminTableViewData $tableData): array
    {
        [$title, $description, $columns] = array_pad($pageConfig, 3, []);
        $record = $tableData->recordFromItem($slug, $item);
        $cells = $record['cells'];
        $terms = collect(preg_split('/\s+/', mb_strtolower($query)) ?: [])->filter()->values();
        $fields = collect($cells)->map(fn ($value, $index) => [
            'label' => $columns[$index] ?? 'Data', 'value' => (string) $value,
        ]);
        if ($slug === 'users' && ! empty($item['email'])) {
            $fields->push(['label' => 'Email', 'value' => (string) $item['email']]);
        }
        $haystack = mb_strtolower($fields->pluck('value')->implode(' '));
        $matchedFields = $fields
            ->filter(fn (array $field) => $terms->contains(fn (string $term) => str_contains(mb_strtolower($field['value']), $term)))
            ->take(3)->values()->all();

        return [
            'title' => $title,
            'description' => $description,
            'slug' => $slug,
            'id' => $record['id'],
            'primary' => $cells[0] ?? $title,
            'secondary' => collect($cells)->skip(1)->take(3)->filter()->implode(' | '),
            'matchedFields' => $matchedFields,
            'score' => $terms->sum(fn (string $term) => str_contains($haystack, $term) ? 1 : 0),
            'listRoute' => route('admin.'.$slug, ['q' => $query]),
            'detailRoute' => $slug === 'animals' && ! empty($item['tag_number'])
                ? route('admin.animals.show', ['tag' => $item['tag_number']])
                : route('admin.resource.show', ['resource' => $slug, 'id' => $record['id']]),
        ];
    }
}
