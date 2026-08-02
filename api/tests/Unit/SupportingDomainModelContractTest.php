<?php

namespace Tests\Unit;

use App\Models\AdminActivityLog;
use App\Models\Animal;
use App\Models\BirthEvent;
use App\Models\Breed;
use App\Models\BreedingFemale;
use App\Models\BreedingPeriod;
use App\Models\Certificate;
use App\Models\CertificateRevocation;
use App\Models\CertificateSignature;
use App\Models\CertificateVerificationLog;
use App\Models\ColonyPen;
use App\Models\HealthTreatment;
use App\Models\OffspringBirth;
use App\Models\PostnatalCareRecord;
use App\Models\PregnancyCheck;
use App\Models\RsaKey;
use App\Models\User;
use App\Models\Vaccination;
use App\Models\WeightRecord;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\File;
use Tests\Feature\Support\ApiTestCase;

class SupportingDomainModelContractTest extends ApiTestCase
{
    public function test_master_data_reproduction_birth_health_and_certificate_relationship_contracts(): void
    {
        $this->assertInstanceOf(HasMany::class, (new Breed)->animals());
        $this->assertInstanceOf(BelongsTo::class, (new Animal)->breed());
        $this->assertInstanceOf(HasMany::class, (new Animal)->birthEventsAsDam());
        $this->assertInstanceOf(HasMany::class, (new Animal)->birthEventsAsSire());
        $this->assertInstanceOf(HasMany::class, (new Animal)->offspringBirths());
        $this->assertInstanceOf(HasMany::class, (new Animal)->weightRecords());
        $this->assertInstanceOf(HasMany::class, (new Animal)->healthTreatments());
        $this->assertInstanceOf(HasMany::class, (new Animal)->vaccinations());
        $this->assertInstanceOf(HasMany::class, (new Animal)->certificates());
        $this->assertInstanceOf(HasMany::class, (new ColonyPen)->breedingPeriods());
        $this->assertInstanceOf(BelongsTo::class, (new BreedingPeriod)->colonyPen());
        $this->assertInstanceOf(BelongsTo::class, (new BreedingPeriod)->maleAnimal());
        $this->assertInstanceOf(HasMany::class, (new BreedingPeriod)->females());
        $this->assertInstanceOf(HasMany::class, (new BreedingPeriod)->pregnancyChecks());
        $this->assertInstanceOf(BelongsTo::class, (new BreedingFemale)->breedingPeriod());
        $this->assertInstanceOf(BelongsTo::class, (new BreedingFemale)->femaleAnimal());
        $this->assertInstanceOf(BelongsTo::class, (new PregnancyCheck)->breedingPeriod());
        $this->assertInstanceOf(BelongsTo::class, (new PregnancyCheck)->breedingFemale());
        $this->assertInstanceOf(BelongsTo::class, (new PregnancyCheck)->femaleAnimal());
        $this->assertInstanceOf(BelongsTo::class, (new BirthEvent)->dam());
        $this->assertInstanceOf(BelongsTo::class, (new BirthEvent)->sire());
        $this->assertInstanceOf(HasMany::class, (new BirthEvent)->offspringBirths());
        $this->assertInstanceOf(HasMany::class, (new BirthEvent)->postnatalCareRecords());
        $this->assertInstanceOf(HasMany::class, (new BirthEvent)->certificates());
        $this->assertInstanceOf(BelongsTo::class, (new OffspringBirth)->birthEvent());
        $this->assertInstanceOf(BelongsTo::class, (new OffspringBirth)->offspringAnimal());
        $this->assertInstanceOf(BelongsTo::class, (new PostnatalCareRecord)->birthEvent());
        $this->assertInstanceOf(BelongsTo::class, (new PostnatalCareRecord)->offspringBirth());
        $this->assertInstanceOf(BelongsTo::class, (new PostnatalCareRecord)->targetAnimal());
        $this->assertInstanceOf(BelongsTo::class, (new WeightRecord)->animal());
        $this->assertInstanceOf(BelongsTo::class, (new HealthTreatment)->animal());
        $this->assertInstanceOf(BelongsTo::class, (new Vaccination)->animal());
        $this->assertInstanceOf(BelongsTo::class, (new Certificate)->animal());
        $this->assertInstanceOf(BelongsTo::class, (new Certificate)->certificateType());
        $this->assertInstanceOf(BelongsTo::class, (new Certificate)->birthEvent());
        $this->assertInstanceOf(HasOne::class, (new Certificate)->signature());
        $this->assertInstanceOf(HasOne::class, (new Certificate)->revocation());
        $this->assertInstanceOf(HasMany::class, (new Certificate)->verificationLogs());
        $this->assertInstanceOf(BelongsTo::class, (new CertificateSignature)->certificate());
        $this->assertInstanceOf(BelongsTo::class, (new CertificateSignature)->rsaKey());
        $this->assertInstanceOf(BelongsTo::class, (new CertificateSignature)->signedByUser());
        $this->assertInstanceOf(BelongsTo::class, (new CertificateRevocation)->certificate());
        $this->assertInstanceOf(BelongsTo::class, (new CertificateVerificationLog)->certificate());
        $this->assertInstanceOf(HasMany::class, (new RsaKey)->signatures());
        $this->assertInstanceOf(BelongsTo::class, (new RsaKey)->user());
        $this->assertInstanceOf(BelongsTo::class, (new AdminActivityLog)->admin());
    }

    public function test_domain_models_define_expected_fillable_casts_hidden_and_appended_attributes(): void
    {
        $this->assertContains('tag_number', (new Animal)->getFillable());
        $this->assertContains('is_impor', (new Animal)->getFillable());
        $this->assertSame('boolean', (new Animal)->getCasts()['is_impor']);
        $this->assertContains('umur', (new Animal)->getAppends());
        $this->assertContains('kategori_umur', (new Animal)->getAppends());

        $this->assertContains('capacity', (new ColonyPen)->getFillable());
        $this->assertSame('integer', (new ColonyPen)->getCasts()['capacity']);
        $this->assertSame('date', (new BreedingPeriod)->getCasts()['start_date']);
        $this->assertSame('date', (new BreedingFemale)->getCasts()['entry_date']);
        $this->assertSame('boolean', (new PregnancyCheck)->getCasts()['is_pregnant']);
        $this->assertSame('integer', (new PregnancyCheck)->getCasts()['estimated_gestation_days']);
        $this->assertSame('integer', (new BirthEvent)->getCasts()['offspring_count']);
        $this->assertSame('decimal:2', (new OffspringBirth)->getCasts()['birth_weight_kg']);
        $this->assertSame('decimal:2', (new PostnatalCareRecord)->getCasts()['volume_ml']);
        $this->assertSame('decimal:2', (new WeightRecord)->getCasts()['weight_kg']);
        $this->assertSame('date', (new HealthTreatment)->getCasts()['treatment_date']);
        $this->assertSame('date', (new Vaccination)->getCasts()['vaccination_date']);
        $this->assertContains('category_name', (new Vaccination)->getFillable());

        $this->assertContains('user_id', (new RsaKey)->getFillable());
        $this->assertContains('private_key_path', (new RsaKey)->getHidden());
        $this->assertContains('has_private_key', (new RsaKey)->getAppends());
        $this->assertSame('boolean', (new RsaKey)->getCasts()['is_active']);
        $this->assertSame('datetime', (new RsaKey)->getCasts()['retired_at']);
        $this->assertSame('datetime', (new RsaKey)->getCasts()['compromised_at']);
        $this->assertSame('datetime', (new RsaKey)->getCasts()['last_used_at']);
        $this->assertContains('password', (new User)->getHidden());
        $this->assertContains('remember_token', (new User)->getHidden());
        $this->assertContains('first_name', (new User)->getFillable());
        $this->assertContains('last_name', (new User)->getFillable());
    }

    public function test_active_scopes_and_animal_age_accessors_return_expected_domain_values(): void
    {
        $activeBreed = Breed::query()->create([
            'breed_name' => 'Unit Active Breed',
            'is_active' => true,
        ]);
        $inactiveBreed = Breed::query()->create([
            'breed_name' => 'Unit Inactive Breed',
            'is_active' => false,
        ]);

        $this->assertTrue(Breed::query()->active()->pluck('id')->contains($activeBreed->id));
        $this->assertFalse(Breed::query()->active()->pluck('id')->contains($inactiveBreed->id));

        $cempe = new Animal([
            'sex' => 'female',
            'birth_date' => now()->subMonths(2)->toDateString(),
        ]);
        $youngFemale = new Animal([
            'sex' => 'female',
            'birth_date' => now()->subMonths(8)->toDateString(),
        ]);
        $adultFemale = new Animal([
            'sex' => 'female',
            'birth_date' => now()->subMonths(18)->toDateString(),
        ]);
        $youngMale = new Animal([
            'sex' => 'male',
            'birth_date' => now()->subMonths(8)->toDateString(),
        ]);
        $adultMale = new Animal([
            'sex' => 'male',
            'birth_date' => now()->subMonths(18)->toDateString(),
        ]);
        $unknown = new Animal;

        $this->assertSame('cempe', $cempe->kategori_umur);
        $this->assertSame('dere', $youngFemale->kategori_umur);
        $this->assertSame('betina dewasa', $adultFemale->kategori_umur);
        $this->assertSame('pejantan muda', $youngMale->kategori_umur);
        $this->assertSame('pejantan dewasa', $adultMale->kategori_umur);
        $this->assertSame('unknown', $unknown->kategori_umur);
        $this->assertNull($unknown->umur);
        $this->assertIsString($cempe->umur);
    }

    public function test_rsa_key_model_helpers_validate_fingerprint_activity_and_private_key_visibility(): void
    {
        RsaKey::query()->where('user_id', $this->admin->id)->delete();

        $publicKeyPem = $this->publicKeyPem();

        $rsaKey = RsaKey::query()->create([
            'user_id' => $this->admin->id,
            'key_identifier' => 'UNIT-RSA-HELPER',
            'public_key_pem' => $publicKeyPem,
            'algorithm' => 'RSA',
            'key_length' => 2048,
            'fingerprint_sha256' => hash('sha256', preg_replace('/\s+/', '', $publicKeyPem) ?? ''),
            'is_active' => 1,
        ]);

        $this->assertSame(hash('sha256', preg_replace('/\s+/', '', $publicKeyPem) ?? ''), $rsaKey->generateFingerprint());
        $this->assertSame($publicKeyPem, $rsaKey->getPublicKeyPem());
        $this->assertTrue($rsaKey->isActive());
        $this->assertFalse($rsaKey->has_private_key);

        $rsaKey->deactivate();
        $this->assertFalse($rsaKey->fresh()->isActive());
        $this->assertSame('retired', $rsaKey->fresh()->key_status);

        $rsaKey->activate();
        $this->assertTrue($rsaKey->fresh()->isActive());
        $this->assertSame('active', $rsaKey->fresh()->key_status);

        $privateKeyPath = storage_path('app/keys/rsa/unit_private_key.pem');
        File::ensureDirectoryExists(dirname($privateKeyPath));
        File::put($privateKeyPath, 'unit-private-key');

        $rsaKey->forceFill(['private_key_path' => $privateKeyPath])->save();
        $this->assertTrue($rsaKey->fresh()->has_private_key);

        $validator = RsaKey::validateKey([
            'key_identifier' => 'UNIT-RSA-VALIDATOR',
            'user_id' => $this->admin->id + 1000,
            'public_key_pem' => $publicKeyPem,
            'algorithm' => 'RSA',
            'key_length' => 2048,
            'fingerprint_sha256' => hash('sha256', 'validator'),
            'is_active' => true,
            'key_status' => 'active',
        ]);

        $this->assertTrue($validator->passes());

        File::delete($privateKeyPath);
    }

    private function publicKeyPem(): string
    {
        $resource = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ] + $this->opensslConfigArgs());

        $details = openssl_pkey_get_details($resource);

        return trim((string) $details['key']);
    }

    /**
     * @return array<string, string>
     */
    private function opensslConfigArgs(): array
    {
        $configPath = config('bbh.openssl_conf');

        if (is_string($configPath) && $configPath !== '' && is_file($configPath)) {
            return ['config' => $configPath];
        }

        return [];
    }
}
