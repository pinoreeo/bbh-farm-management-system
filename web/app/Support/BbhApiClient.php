<?php

namespace App\Support;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Pool;
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
     * @param  array<string, array{path:string,query?:array<string, mixed>}>  $requests
     * @return array<string, Response>
     */
    public function getMany(array $requests, ?string $token = null): array
    {
        if ($requests === []) {
            return [];
        }

        $connectTimeout = (int) config('services.bbh_api.connect_timeout', 5);
        $timeout = (int) config('services.bbh_api.timeout', 20);
        $retryTimes = (int) config('services.bbh_api.retry_times', 1);
        $retrySleep = (int) config('services.bbh_api.retry_sleep', 150);

        /** @var array<string, Response> $responses */
        $responses = Http::pool(function (Pool $pool) use ($requests, $token, $connectTimeout, $timeout, $retryTimes, $retrySleep): array {
            $pooled = [];

            foreach ($requests as $key => $request) {
                $pendingRequest = $pool->as((string) $key)
                    ->acceptJson()
                    ->connectTimeout($connectTimeout)
                    ->timeout($timeout)
                    ->retry($retryTimes, $retrySleep, throw: false);

                if ($token !== null && $token !== '') {
                    $pendingRequest = $pendingRequest->withToken($token);
                }

                $pooled[] = $pendingRequest->get($this->url($request['path']), $request['query'] ?? []);
            }

            return $pooled;
        });

        return $responses;
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
     * @param  array<string, array{path:string,query?:array<string, mixed>}>  $requests
     * @return array<string, array{ok:bool,data:array<int, mixed>,response:?Response,truncated:bool}>
     */
    public function paginatedBatchData(array $requests, ?string $token = null, int $maxPages = 50): array
    {
        $results = [];
        $firstPageRequests = [];
        $baseQueries = [];

        foreach ($requests as $key => $request) {
            $query = $request['query'] ?? [];
            $query['per_page'] = 100;
            $baseQueries[$key] = $query;
            $firstPageRequests[$key] = [
                'path' => $request['path'],
                'query' => array_merge($query, ['page' => 1]),
            ];
        }

        $firstPageResponses = $this->getMany($firstPageRequests, $token);
        $followUpRequests = [];
        $followUpOwners = [];

        foreach ($requests as $key => $request) {
            $response = $firstPageResponses[$key] ?? null;

            if (! $response instanceof Response) {
                $results[$key] = [
                    'ok' => false,
                    'data' => [],
                    'response' => null,
                    'truncated' => false,
                ];

                continue;
            }

            if (! $response->successful()) {
                $results[$key] = [
                    'ok' => false,
                    'data' => [],
                    'response' => $response,
                    'truncated' => false,
                ];

                continue;
            }

            $pageItems = $response->json('data', []);
            $pageItems = is_array($pageItems) ? $pageItems : [];
            $currentPage = max(1, (int) $response->json('current_page', 1));
            $lastPage = max($currentPage, (int) $response->json('last_page', $currentPage));

            $results[$key] = [
                'ok' => true,
                'data' => $pageItems,
                'response' => $response,
                'truncated' => $lastPage > $maxPages,
            ];

            for ($page = $currentPage + 1; $page <= $lastPage && $page <= $maxPages; $page++) {
                $followUpKey = $key.'::'.$page;
                $followUpOwners[$followUpKey] = $key;
                $followUpRequests[$followUpKey] = [
                    'path' => $request['path'],
                    'query' => array_merge($baseQueries[$key], ['page' => $page]),
                ];
            }
        }

        foreach ($this->getMany($followUpRequests, $token) as $followUpKey => $response) {
            $owner = $followUpOwners[$followUpKey] ?? null;
            if (! is_string($owner) || ! isset($results[$owner])) {
                continue;
            }

            $results[$owner]['response'] = $response;

            if (! $response->successful()) {
                $results[$owner]['ok'] = false;
                $results[$owner]['data'] = [];

                continue;
            }

            $pageItems = $response->json('data', []);
            if (is_array($pageItems)) {
                array_push($results[$owner]['data'], ...$pageItems);
            }
        }

        return $results;
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
