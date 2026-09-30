<?php

namespace Tests\Feature\Operational;

use Tests\Feature\Support\ApiTestCase;

class PaginationValidationTest extends ApiTestCase
{
    public function test_invalid_page_size_is_a_validation_error(): void
    {
        $this->actingAsAdmin();

        $this->getJson('/api/v1/animals?per_page=abc')->assertUnprocessable()->assertJsonValidationErrors('per_page');
        $this->getJson('/api/v1/animals?per_page=0')->assertUnprocessable()->assertJsonValidationErrors('per_page');
        $this->getJson('/api/v1/animals?per_page=200')->assertOk()->assertJsonPath('per_page', 100);
    }
}
