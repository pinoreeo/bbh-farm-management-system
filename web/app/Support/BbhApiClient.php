<?php

namespace App\Support;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;

class BbhApiClient
{
    private string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = rtrim(trim((string) config('services.bbh_api.base_url')), '/');
    }

    /**
     * @param  array<string, mixed>  $query
     */
    public function get(string $path, array $query = [], ?string $token = null): Response
    {
        return $this->request($token)->get($this->url($path), $query);
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array{ok:bool,data:array<int, mixed>,response:?Response,truncated:bool}
     */
    public function paginatedData(string $path, array $query = [], ?string $token = null, int $maxPages = 50): array
    {
        $items = [];
        $page = 1;
        $lastPage = 1;
        $response = null;
        $query['per_page'] = 100;

        do {
            $response = $this->get($path, array_merge($query, ['page' => $page]), $token);

            if (! $response->successful()) {
                return [
                    'ok' => false,
                    'data' => [],
                    'response' => $response,
                    'truncated' => false,
                ];
            }

            $pageItems = $response->json('data', []);
            if (! is_array($pageItems)) {
                break;
            }

            array_push($items, ...$pageItems);

            $currentPage = max(1, (int) $response->json('current_page', $page));
            $lastPage = max($currentPage, (int) $response->json('last_page', $currentPage));
            $page = $currentPage + 1;
        } while ($page <= $lastPage && $page <= $maxPages);

        return [
            'ok' => true,
            'data' => $items,
            'response' => $response,
            'truncated' => $page <= $lastPage,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function post(string $path, array $payload = [], ?string $token = null): Response
    {
        return $this->request($token)->post($this->url($path), $payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, UploadedFile>  $files
     */
    public function postMultipart(string $path, array $payload = [], array $files = [], ?string $token = null): Response
    {
        $request = $this->request($token);

        foreach ($files as $field => $file) {
            $request = $request->attach($field, file_get_contents($file->getRealPath()), $file->getClientOriginalName(), [
                'Content-Type' => $file->getMimeType() ?: 'application/octet-stream',
            ]);
        }

        return $request->post($this->url($path), $payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function put(string $path, array $payload = [], ?string $token = null): Response
    {
        return $this->request($token)->put($this->url($path), $payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, UploadedFile>  $files
     */
    public function putMultipart(string $path, array $payload = [], array $files = [], ?string $token = null): Response
    {
        $payload['_method'] = 'PUT';

        return $this->postMultipart($path, $payload, $files, $token);
    }

    public function delete(string $path, ?string $token = null): Response
    {
        return $this->request($token)->delete($this->url($path));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function postPdf(string $path, UploadedFile $file, string $field = 'pdf', array $payload = []): Response
    {
        return $this->request()
            ->attach($field, file_get_contents($file->getRealPath()), $file->getClientOriginalName(), [
                'Content-Type' => $file->getMimeType() ?: 'application/pdf',
            ])
            ->post($this->url($path), $payload);
    }

    private function request(?string $token = null): PendingRequest
    {
        $request = Http::acceptJson()
            ->connectTimeout((int) config('services.bbh_api.connect_timeout', 5))
            ->timeout((int) config('services.bbh_api.timeout', 20))
            ->retry(
                (int) config('services.bbh_api.retry_times', 1),
                (int) config('services.bbh_api.retry_sleep', 150),
                throw: false
            );

        if ($token !== null && $token !== '') {
            $request = $request->withToken($token);
        }

        return $request;
    }

    private function url(string $path): string
    {
        return $this->baseUrl.'/'.ltrim($path, '/');
    }
}
