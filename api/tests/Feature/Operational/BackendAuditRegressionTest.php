<?php

namespace Tests\Feature\Operational;

use App\Models\FarmProfile;
use App\Services\CertificateSigningService;
use Tests\Feature\Support\ApiTestCase;

class BackendAuditRegressionTest extends ApiTestCase
{
    public function test_postnatal_care_cannot_be_recorded_after_death_or_in_the_future(): void
    {
        $this->actingAsAdmin();
        $workflow = $this->createBirthWorkflow();
        $offspring = $workflow['offspring'];
        $birth = $workflow['offspringBirth'];
        $offspring->forceFill(['life_status' => 'dead', 'status_date' => '2026-05-18'])->save();

        $this->postJson('/api/v1/postnatal-care-records', [
            'offspring_birth_id' => $birth->id,
            'care_date' => '2026-05-19',
        ])->assertUnprocessable();

        $recordId = $this->postJson('/api/v1/postnatal-care-records', [
            'offspring_birth_id' => $birth->id,
            'care_date' => '2026-05-18',
        ])->assertCreated()->json('data.id');

        $this->putJson('/api/v1/postnatal-care-records/'.$recordId, [
            'care_date' => '2026-05-19',
        ])->assertUnprocessable();

        $this->postJson('/api/v1/postnatal-care-records', [
            'offspring_birth_id' => $birth->id,
            'care_date' => now()->addDay()->toDateString(),
        ])->assertUnprocessable()->assertJsonValidationErrors('care_date');
    }

    public function test_signing_rechecks_the_locked_certificate_instead_of_stale_model(): void
    {
        $this->actingAsAdmin();
        $certificate = $this->issueCertificate();
        $stale = $certificate->fresh();
        $certificate->forceFill(['status' => 'revoked'])->save();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Only active certificates can be signed.');
        app(CertificateSigningService::class)->sign($stale, true);
    }

    public function test_farm_profile_keeps_one_primary_record_without_deleting_legacy_rows(): void
    {
        $this->actingAsAdmin();
        $legacy = FarmProfile::query()->create(['farm_name' => 'Profil lama']);
        $primary = FarmProfile::query()->create(['singleton_key' => 1, 'farm_name' => 'Profil utama']);

        $this->getJson('/api/v1/farm')->assertOk()->assertJsonPath('farm_name', 'Profil utama');
        $this->putJson('/api/v1/farm', ['farm_name' => 'Profil diperbarui'])->assertOk();

        $this->assertSame('Profil lama', $legacy->fresh()->farm_name);
        $this->assertSame('Profil diperbarui', $primary->fresh()->farm_name);
        $this->assertSame(2, FarmProfile::query()->count());
    }
}
