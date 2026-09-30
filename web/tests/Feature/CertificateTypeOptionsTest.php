<?php

namespace Tests\Feature;

use App\Support\AdminResourceViewData;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CertificateTypeOptionsTest extends TestCase
{
    public function test_new_certificate_only_offers_active_types_but_edit_keeps_existing_inactive_type(): void
    {
        Http::fake([
            '*/certificate-types*' => Http::response([
                'data' => [
                    ['id' => 1, 'type_code' => 'BIBIT_UNGGUL', 'type_name' => 'Sertifikat Bibit Unggul', 'is_active' => true],
                    ['id' => 2, 'type_code' => 'KELAHIRAN', 'type_name' => 'Akta Kelahiran Ternak', 'is_active' => false],
                ],
                'current_page' => 1,
                'last_page' => 1,
            ]),
        ]);

        $resources = app(AdminResourceViewData::class);
        $field = [['name' => 'certificate_type_id']];

        $this->assertSame(['1' => 'Sertifikat Bibit Unggul'], $resources->fields('certificates', $field, 'token')[0]['options']);
        $this->assertSame([
            '1' => 'Sertifikat Bibit Unggul',
            '2' => 'Akta Kelahiran Ternak',
        ], $resources->fields('certificates', $field, 'token', ['certificate_type_id' => 2])[0]['options']);
    }
}
