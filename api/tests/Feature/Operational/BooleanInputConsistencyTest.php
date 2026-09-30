<?php

namespace Tests\Feature\Operational;

use App\Models\Breed;
use Tests\Feature\Support\ApiTestCase;

class BooleanInputConsistencyTest extends ApiTestCase
{
    public function test_null_active_status_is_rejected_for_user_and_pen_updates(): void
    {
        $this->actingAsAdmin();
        $pen = $this->createPen();

        $this->putJson('/api/v1/users/'.$this->admin->id, ['is_active' => null])->assertUnprocessable();
        $this->putJson('/api/v1/colony-pens/'.$pen->id, ['is_active' => null])->assertUnprocessable();

        $this->assertTrue($this->admin->fresh()->is_active);
        $this->assertTrue($pen->fresh()->is_active);
    }

    public function test_false_query_returns_only_inactive_breeds_and_certificate_types(): void
    {
        $this->actingAsAdmin();
        $breed = Breed::query()->create(['breed_name' => 'Ras Nonaktif', 'is_active' => false]);
        $type = $this->certificateType('KELAHIRAN');
        $type->update(['is_active' => false]);

        $breeds = $this->getJson('/api/v1/breeds?is_active=false')->assertOk()->json('data');
        $types = $this->getJson('/api/v1/certificate-types?is_active=false')->assertOk()->json('data');

        $this->assertSame([$breed->id], array_column($breeds, 'id'));
        $this->assertSame([$type->id], array_column($types, 'id'));
    }
}
