<?php

namespace Tests\Feature;

use Tests\TestCase;

class PublicRouteTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_root_redirects_to_default_locale(): void
    {
        $this->get('/')->assertRedirect('/id-id');
    }

    public function test_public_home_page_renders(): void
    {
        $this->get('/id-id')->assertOk();
    }
}
