<?php

namespace Tests\Unit;

use App\Support\AdminResourceViewData;
use GuzzleHttp\Psr7\Response as PsrResponse;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AdminResponseMessageTest extends TestCase
{
    #[DataProvider('responses')]
    public function test_user_messages_match_the_response(int $status, array $body, string $expected): void
    {
        $response = new Response(new PsrResponse($status, [], json_encode($body)));

        $this->assertSame([$expected], app(AdminResourceViewData::class)->failureMessages($response, 'Gagal: Data belum berhasil disimpan. Silakan coba lagi.'));
    }

    public static function responses(): array
    {
        return [
            'no repeated label' => [422, ['errors' => ['name' => ['Peringatan: Nama pengguna wajib diisi.']]], 'Peringatan: Nama pengguna wajib diisi.'],
            'preserve limit' => [422, ['errors' => ['photo' => ['Peringatan: Ukuran foto maksimal 4 MB.']]], 'Peringatan: Ukuran foto maksimal 4 MB.'],
            'english minimum' => [422, ['errors' => ['password' => ['The password field must be at least 8 characters.']]], 'Peringatan: Password minimal 8 karakter.'],
            'english maximum' => [422, ['errors' => ['name' => ['The name field must not be greater than 255 characters.']]], 'Peringatan: Nama pengguna maksimal 255 karakter.'],
            'english integer' => [422, ['errors' => ['offspring_count' => ['The offspring count field must be an integer.']]], 'Peringatan: Jumlah Anak harus berupa angka bulat.'],
            'admin is not minimum' => [422, ['errors' => ['role' => ['Minimal harus ada satu super admin aktif.']]], 'Peringatan: Minimal harus tersedia satu akun super admin aktif agar pengelolaan sistem tetap dapat dilakukan.'],
            'expired session' => [401, ['message' => 'Unauthenticated.'], 'Sesi Berakhir: Sesi login Anda telah berakhir. Silakan login kembali.'],
            'no permission' => [403, ['message' => 'Unauthorized.'], 'Peringatan: Akun Anda tidak memiliki izin untuk melakukan tindakan ini.'],
            'server failure' => [500, ['message' => 'SQLSTATE internal failure'], 'Gagal: Layanan belum dapat diakses. Silakan coba lagi.'],
            'missing data' => [404, ['message' => 'No query results for model'], 'Gagal: Data tidak ditemukan. Silakan muat ulang halaman.'],
            'signing failure' => [422, ['message' => 'Signing failed.'], 'Gagal: Sertifikat belum berhasil ditandatangani. Silakan coba lagi.'],
        ];
    }

    public function test_exit_success_describes_a_saved_record(): void
    {
        $this->assertSame('Sukses: Catatan keluar betina dari periode perkawinan berhasil disimpan.', app(AdminResourceViewData::class)->successMessage('breeding-females', 'exit'));
    }

    public function test_animal_form_only_sends_death_date_while_status_is_dead(): void
    {
        Http::fake(['*' => Http::response(['data' => []])]);

        $resource = app(AdminResourceViewData::class);
        $resource->update('animals', 1, ['life_status' => 'alive', 'status_date' => '2026-09-01'], 'token');
        $resource->update('animals', 1, ['life_status' => 'dead', 'status_date' => '2026-09-01'], 'token');

        $requests = Http::recorded()->map(fn ($record) => $record[0]->data())->all();
        $this->assertArrayNotHasKey('status_date', $requests[0]);
        $this->assertSame('2026-09-01', $requests[1]['status_date']);
    }

    public function test_dead_animal_form_does_not_offer_alive_status(): void
    {
        $fields = app(AdminResourceViewData::class)->fields('animals', [
            ['name' => 'life_status', 'options' => ['alive' => 'Hidup', 'dead' => 'Mati']],
        ], 'token', ['life_status' => 'dead']);

        $this->assertSame(['dead' => 'Mati'], $fields[0]['options']);
    }

    public function test_birth_edit_preserves_parents_after_pregnancy_is_completed(): void
    {
        $fields = app(AdminResourceViewData::class)->fields('birth-events', [
            ['name' => 'dam_id', 'type' => 'select'],
            ['name' => 'sire_id', 'type' => 'select', 'depends_on' => 'dam_id'],
        ], 'token', ['dam_id' => 10, 'sire_id' => 20, 'dam' => ['tag_number' => 'DAM-10'], 'sire' => ['tag_number' => 'SIRE-20']]);

        $this->assertTrue($fields[0]['readonly']);
        $this->assertSame([10 => 'DAM-10'], $fields[0]['options']);
        $this->assertSame([20 => 'SIRE-20'], $fields[1]['options']);
        $this->assertArrayNotHasKey('depends_on', $fields[1]);
    }
}
