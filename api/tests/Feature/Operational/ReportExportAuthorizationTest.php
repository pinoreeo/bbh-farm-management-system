<?php

namespace Tests\Feature\Operational;

use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Tests\Feature\Support\ApiTestCase;

class ReportExportAuthorizationTest extends ApiTestCase
{
    public function test_activity_log_export_is_restricted_to_super_admin(): void
    {
        $this->admin->update(['role' => 'admin']);
        $this->actingAsAdmin();

        $this->get('/api/v1/reports/activity-logs/xlsx')->assertForbidden();

        $this->admin->update(['role' => 'super_admin']);
        $response = $this->get('/api/v1/reports/activity-logs/xlsx')->assertOk();
        $binary = $response->baseResponse;
        $this->assertInstanceOf(BinaryFileResponse::class, $binary);

        $path = $binary->getFile()->getPathname();
        $this->assertGreaterThan(0, filesize($path));
        unlink($path);
    }
}
