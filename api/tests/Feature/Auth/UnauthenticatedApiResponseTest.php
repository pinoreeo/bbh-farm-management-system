<?php

namespace Tests\Feature\Auth;

use Tests\Feature\Support\ApiTestCase;

class UnauthenticatedApiResponseTest extends ApiTestCase
{
    public function test_api_request_without_json_accept_header_returns_unauthorized(): void
    {
        $this->get('/api/v1/breeds')->assertUnauthorized()
            ->assertJsonPath('message', 'Unauthenticated.');
    }
}
