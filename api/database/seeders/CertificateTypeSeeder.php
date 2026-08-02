<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CertificateTypeSeeder extends Seeder
{
    /**
     * Seed certificate type master data.
     */
    public function run(): void
    {
        foreach ($this->certificateTypes() as $type) {
            DB::table('cert_types')->updateOrInsert(
                ['type_code' => $type['type_code']],
                array_merge($type, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function certificateTypes(): array
    {
        return [
            [
                'type_code' => 'KELAHIRAN',
                'type_name' => 'Akta Kelahiran Ternak',
                'description' => 'Akta kelahiran untuk ternak yang lahir di kandang.',
                'template_version' => 'v1',
                'is_active' => true,
            ],
            [
                'type_code' => 'KEMATIAN',
                'type_name' => 'Akta Kematian Ternak',
                'description' => 'Akta kematian ternak.',
                'template_version' => 'v1',
                'is_active' => true,
            ],
            [
                'type_code' => 'BIBIT_UNGGUL',
                'type_name' => 'Sertifikat Bibit Unggul',
                'description' => 'Sertifikat bibit unggul ternak kambing perah.',
                'template_version' => 'v1',
                'is_active' => true,
            ],
        ];
    }
}
