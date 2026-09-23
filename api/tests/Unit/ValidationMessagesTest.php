<?php

namespace Tests\Unit;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\AnimalStoreRequest;
use App\Support\ValidationMessages;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ValidationMessagesTest extends TestCase
{
    #[DataProvider('invalidValues')]
    public function test_messages_explain_the_actual_constraint(array $data, array $rules, string $expected): void
    {
        $validator = Validator::make($data, $rules, ValidationMessages::messages(), ValidationMessages::attributes());

        $this->assertTrue($validator->fails());
        $this->assertSame($expected, $validator->errors()->first());
    }

    public static function invalidValues(): array
    {
        return [
            'required' => [[], ['name' => 'required'], 'Peringatan: Nama pengguna wajib diisi.'],
            'email' => [['email' => 'wrong'], ['email' => 'email'], 'Peringatan: Masukkan alamat email yang benar.'],
            'password length' => [['password' => 'short'], ['password' => 'string|min:8'], 'Peringatan: Password minimal 8 karakter.'],
            'confirmation' => [['password' => 'password', 'password_confirmation' => 'different'], ['password' => 'confirmed'], 'Peringatan: Konfirmasi password harus sama dengan password baru.'],
            'name length' => [['name' => str_repeat('a', 256)], ['name' => 'string|max:255'], 'Peringatan: Nama pengguna maksimal 255 karakter.'],
            'integer' => [['offspring_count' => 1.5], ['offspring_count' => 'integer'], 'Peringatan: Jumlah anak harus berupa angka bulat.'],
            'numeric' => [['weight_kg' => 'wrong'], ['weight_kg' => 'numeric'], 'Peringatan: Bobot harus berupa angka.'],
            'numeric minimum' => [['weight_kg' => -1], ['weight_kg' => 'numeric|min:0'], 'Peringatan: Bobot minimal 0.'],
            'numeric maximum' => [['offspring_count' => 6], ['offspring_count' => 'integer|max:5'], 'Peringatan: Jumlah anak maksimal 5.'],
            'array minimum' => [['female_animal_ids' => []], ['female_animal_ids' => 'array|min:1'], 'Peringatan: Pilih minimal 1 data untuk Tag Betina.'],
            'date relation' => [['start_date' => '2026-02-02', 'end_date' => '2026-02-01'], ['start_date' => 'before_or_equal:end_date'], 'Peringatan: Tanggal Mulai tidak boleh setelah Tanggal Selesai.'],
            'future date' => [['record_date' => '2999-01-01'], ['record_date' => 'before_or_equal:today'], 'Peringatan: Tanggal timbang tidak boleh setelah hari ini.'],
        ];
    }

    public function test_photo_size_is_described_in_megabytes(): void
    {
        $validator = Validator::make(['photo' => UploadedFile::fake()->create('photo.jpg', 4097)], ['photo' => 'max:4096'], ValidationMessages::messages());

        $this->assertSame('Peringatan: Ukuran foto maksimal 4 MB.', $validator->errors()->first());
    }

    public function test_missing_selection_and_duplicate_eartag_have_specific_messages(): void
    {
        $presence = \Mockery::mock(\Illuminate\Validation\PresenceVerifierInterface::class);
        $presence->shouldReceive('getCount')->with('animal_breeds', 'id', 999, null, null, [])->andReturn(0);
        $presence->shouldReceive('getCount')->with('animals', 'tag_number', 'BBH-001', null, null, [])->andReturn(1);
        Validator::setPresenceVerifier($presence);

        $validator = Validator::make([
            'breed_id' => 999,
            'tag_number' => 'BBH-001',
        ], [
            'breed_id' => 'exists:animal_breeds,id',
            'tag_number' => 'unique:animals,tag_number',
        ], ValidationMessages::messages(), ValidationMessages::attributes());

        $this->assertSame([
            'breed_id' => ['Peringatan: Ras kambing yang dipilih tidak ditemukan. Pilih kembali ras yang tersedia.'],
            'tag_number' => ['Peringatan: Eartag ini sudah digunakan.'],
        ], $validator->errors()->messages());
    }

    public function test_form_requests_and_controller_validation_share_the_same_messages(): void
    {
        $request = new AnimalStoreRequest;
        $this->assertSame(ValidationMessages::messages(), $request->messages());
        $this->assertSame(ValidationMessages::attributes(), $request->attributes());

        $controller = new class extends Controller
        {
            public function check(Request $request): array
            {
                return $this->validated($request, ['weight_kg' => 'numeric|min:0']);
            }
        };

        try {
            $controller->check(Request::create('/', 'POST', ['weight_kg' => -1]));
            $this->fail('The negative weight should fail validation.');
        } catch (ValidationException $exception) {
            $this->assertSame(['weight_kg' => ['Peringatan: Bobot minimal 0.']], $exception->errors());
        }
    }
}
