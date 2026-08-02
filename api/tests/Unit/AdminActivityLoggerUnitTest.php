<?php

namespace Tests\Unit;

use App\Models\AdminActivityLog;
use App\Services\AdminActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Tests\Feature\Support\ApiTestCase;

class AdminActivityLoggerUnitTest extends ApiTestCase
{
    public function test_should_log_filters_read_requests_admin_log_routes_and_server_errors(): void
    {
        $logger = app(AdminActivityLogger::class);

        $this->assertFalse($logger->shouldLog(Request::create('/api/v1/animals', 'GET'), 200));
        $this->assertFalse($logger->shouldLog(Request::create('/api/v1/animals', 'HEAD'), 200));
        $this->assertFalse($logger->shouldLog(Request::create('/api/v1/animals', 'OPTIONS'), 200));
        $this->assertFalse($logger->shouldLog(Request::create('/api/v1/admin-activity-logs', 'POST'), 201));
        $this->assertFalse($logger->shouldLog(Request::create('/api/v1/animals', 'POST'), 500));
        $this->assertTrue($logger->shouldLog(Request::create('/api/v1/animals', 'POST'), 201));
        $this->assertTrue($logger->shouldLog(Request::create('/api/v1/animals/1', 'DELETE'), 422));
    }

    public function test_logger_writes_admin_activity_with_safe_metadata_and_response_subject(): void
    {
        $logger = app(AdminActivityLogger::class);
        $request = Request::create('/api/v1/animals', 'POST', [
            'tag_number' => 'LOG-ANIMAL-001',
            'password' => 'secret',
            'notes' => str_repeat('A', 600),
        ]);

        $request->setUserResolver(fn () => $this->admin);
        $request->headers->set('User-Agent', 'Unit Test Agent');
        $response = new JsonResponse([
            'data' => [
                'id' => 10,
                'tag_number' => 'LOG-ANIMAL-001',
            ],
        ], 201);

        $logger->log($request, 201, response: $response);

        $log = AdminActivityLog::query()->firstOrFail();

        $this->assertSame($this->admin->id, $log->admin_id);
        $this->assertSame('create', $log->action);
        $this->assertSame('animals', $log->module);
        $this->assertSame(201, $log->status_code);
        $this->assertSame(10, $log->subject_id);
        $this->assertSame('LOG-ANIMAL-001', $log->metadata['subject_label']);
        $this->assertArrayNotHasKey('password', $log->metadata['payload']);
        $this->assertStringEndsWith('...', $log->metadata['payload']['notes']);
        $this->assertStringContainsString('menyimpan data kambing', strtolower($log->description));
    }

    public function test_logger_records_failed_action_description_for_error_response(): void
    {
        $logger = app(AdminActivityLogger::class);
        $request = Request::create('/api/v1/certificates/1/revoke', 'POST', [
            'reason' => 'Tidak valid',
        ]);

        $request->setUserResolver(fn () => $this->admin);

        $logger->log($request, 422);

        $log = AdminActivityLog::query()->firstOrFail();

        $this->assertSame('revoke', $log->action);
        $this->assertSame('certificates', $log->module);
        $this->assertSame(422, $log->status_code);
        $this->assertStringContainsString('permintaan admin rio untuk mencabut pada modul akte dan sertifikat', strtolower($log->description));
        $this->assertStringContainsString('ditolak karena melanggar kebijakan sistem', strtolower($log->description));
    }
}
