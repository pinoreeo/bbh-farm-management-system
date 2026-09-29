<?php

namespace Tests\Feature;

use DOMDocument;
use DOMXPath;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class AdminVisualConsistencyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        view()->share('errors', new ViewErrorBag);
    }

    public function test_special_forms_place_actions_after_the_panel(): void
    {
        $pages = [
            ['pages.admin.pregnancy-form', ['mode' => 'create', 'id' => null, 'values' => []]],
            ['pages.admin.breeding-female-mating', ['id' => 1, 'context' => ['breeding_female' => []]]],
            ['pages.admin.breeding-female-exit', ['id' => 1, 'context' => ['breeding_female' => [], 'pen_options' => [], 'reason_options' => []]]],
            ['pages.admin.user-edit', ['pageTitle' => 'Edit Pengguna', 'recordTitle' => 'Admin', 'id' => 1, 'user' => ['name' => 'Admin', 'email' => 'admin@example.test', 'is_active' => true]]],
        ];

        foreach ($pages as [$page, $data]) {
            $xpath = $this->xpath(view($page, $data)->render());

            $this->assertCount(1, $xpath->query('//form[contains(concat(" ", normalize-space(@class), " "), " admin-form ")]/div[contains(concat(" ", normalize-space(@class), " "), " admin-form-actions ")]'), $page);
            $this->assertCount(0, $xpath->query('//section[contains(concat(" ", normalize-space(@class), " "), " admin-panel ")]//div[contains(concat(" ", normalize-space(@class), " "), " admin-form-actions ")]'), $page);
        }
    }

    public function test_mobile_search_and_settings_headings_use_shared_patterns(): void
    {
        $topbar = view('components.admin.topbar')->render();
        $profile = view('pages.admin.profile', [
            'user' => ['name' => 'Admin', 'email' => 'admin@example.test'],
            'userProfileMessage' => null,
            'passwordMessage' => null,
        ])->render();

        $this->assertStringContainsString('class="admin-icon-button md:hidden"', $topbar);
        $this->assertStringContainsString('href="'.route('admin.search').'"', $topbar);
        $this->assertStringNotContainsString('Ctrl K', $topbar);
        $this->assertStringNotContainsString('admin@bbhfarm.id', $topbar);
        $this->assertStringContainsString('class="admin-section-title">Profil pengguna', $profile);
        $this->assertStringContainsString('class="admin-section-title">Password', $profile);
    }

    public function test_revoked_certificate_preview_has_negative_status_and_one_frame(): void
    {
        $html = view('pages.admin.certificate-preview', [
            'id' => 1,
            'row' => [2 => 'Sertifikat', 4 => 'Dicabut'],
        ])->render();
        $xpath = $this->xpath($html);

        $this->assertStringContainsString('data-tone="negative"', $html);
        $this->assertCount(1, $xpath->query('//iframe[@title="Pratinjau sertifikat"]'));
        $this->assertCount(0, $xpath->query('//section[contains(concat(" ", normalize-space(@class), " "), " admin-panel ")]//div[contains(concat(" ", normalize-space(@class), " "), " rounded-lg ")]'));

        $unknownStatus = view('pages.admin.certificate-preview', ['id' => 1, 'row' => []])->render();
        $this->assertStringContainsString('data-tone="neutral"', $unknownStatus);
    }

    public function test_special_pages_use_record_breadcrumbs(): void
    {
        $pages = [
            ['pages.admin.pregnancy-form', ['mode' => 'create', 'id' => null, 'values' => []], 'Kebuntingan'],
            ['pages.admin.breeding-female-mating', ['id' => 1, 'context' => ['breeding_female' => []]], 'Betina Kawin'],
            ['pages.admin.breeding-female-exit', ['id' => 1, 'context' => ['breeding_female' => [], 'pen_options' => [], 'reason_options' => []]], 'Betina Kawin'],
            ['pages.admin.certificate-preview', ['id' => 1, 'row' => []], 'Akte & Sertifikat'],
        ];

        foreach ($pages as [$page, $data, $collection]) {
            $xpath = $this->xpath(view($page, $data)->render());

            $this->assertCount(1, $xpath->query('//header[contains(concat(" ", normalize-space(@class), " "), " admin-record-page-header ")]'), $page);
            $this->assertCount(1, $xpath->query('//nav[@aria-label="Breadcrumb"]//a[normalize-space()="'.$collection.'"]'), $page);
        }
    }

    public function test_sidebar_formats_account_role_for_reading(): void
    {
        $html = view('components.admin.sidebar-user-card', [
            'adminUser' => ['name' => 'John Doe', 'role' => 'super_admin'],
        ])->render();

        $this->assertStringContainsString('John Doe', $html);
        $this->assertStringContainsString('Super Admin', $html);
        $this->assertStringNotContainsString('super_admin', $html);
        $this->assertStringNotContainsString('Demo Admin', $html);
    }

    public function test_add_admin_uses_invitation_form_and_shared_header(): void
    {
        Http::fake(['*' => Http::response(['id' => 1, 'role' => 'super_admin'])]);

        $this->withSession(['bbh_api_token' => 'test-token'])
            ->get(route('admin.resource.create', ['resource' => 'users']))
            ->assertOk()
            ->assertViewIs('pages.admin.user-invitation')
            ->assertSee('Tambah Admin')
            ->assertSee('Kirim undangan email');

        $html = view('pages.admin.user-invitation', ['pageTitle' => 'Tambah Admin'])->render();
        $xpath = $this->xpath($html);

        $this->assertCount(1, $xpath->query('//header[contains(concat(" ", normalize-space(@class), " "), " admin-record-page-header ")]'));
        $this->assertCount(1, $xpath->query('//nav[@aria-label="Breadcrumb"]//a[normalize-space()="Manajemen Pengguna"]'));
        $this->assertCount(1, $xpath->query('//form[@action="'.route('admin.resource.store', ['resource' => 'users']).'"]'));
        $this->assertFalse(Route::has('admin.users.request-otp'));
        $this->assertFalse(Route::has('admin.users.complete-registration'));
    }

    public function test_add_admin_submits_invitation_without_a_password(): void
    {
        Http::fake(function (Request $request) {
            if (str_ends_with($request->url(), '/auth/me')) {
                return Http::response(['id' => 1, 'role' => 'super_admin']);
            }

            return Http::response(['data' => ['id' => 2]], 201);
        });

        $this->withSession(['bbh_api_token' => 'test-token'])
            ->post(route('admin.resource.store', ['resource' => 'users']), [
                'name' => 'Admin Baru',
                'email' => 'baru@example.test',
                'phone' => '081234567890',
            ])
            ->assertRedirect(route('admin.users'))
            ->assertSessionHas('formMessage', 'Sukses: Undangan untuk membuat password telah dikirim ke email admin.');

        Http::assertSent(fn (Request $request) => str_ends_with($request->url(), '/users')
            && $request->method() === 'POST'
            && $request['email'] === 'baru@example.test'
            && ! isset($request['password']));
    }

    public function test_pregnancy_form_has_no_example_ids_and_requires_core_fields(): void
    {
        $html = view('pages.admin.pregnancy-form', [
            'mode' => 'create',
            'id' => null,
            'values' => [],
        ])->render();
        $xpath = $this->xpath($html);

        $this->assertCount(1, $xpath->query('//input[@name="check_date" and @required]'));
        $this->assertCount(1, $xpath->query('//select[@name="is_pregnant" and @required]'));
        $this->assertCount(1, $xpath->query('//select[@name="method" and @required]'));
        $this->assertCount(1, $xpath->query('//form[contains(concat(" ", normalize-space(@class), " "), " admin-form ")]//div[contains(concat(" ", normalize-space(@class), " "), " admin-form-actions ")]//a[@href="'.route('admin.pregnancy-checks').'"]'));
        $this->assertStringNotContainsString('PRD-001', $html);
    }

    public function test_empty_record_history_has_no_panel(): void
    {
        $html = view('components.admin.record-history', [
            'history' => [],
            'collection' => 'Data Kambing',
        ])->render();

        $this->assertSame('', trim($html));
    }

    public function test_required_select_is_required_in_the_browser(): void
    {
        $html = view('components.admin.form-field', [
            'field' => [
                'name' => 'breed_id',
                'label' => 'Ras Kambing',
                'type' => 'select',
                'placeholder' => 'Pilih ras kambing',
                'options' => [1 => 'Saanen'],
                'required' => true,
            ],
            'mode' => 'create',
            'values' => [],
        ])->render();

        $this->assertCount(1, $this->xpath($html)->query('//select[@name="breed_id" and @required]'));
    }

    public function test_notification_filters_are_buttons_not_incomplete_tabs(): void
    {
        $html = view('pages.admin.notifications', [
            'notifications' => collect(),
            'unreadCount' => 0,
            'filter' => 'all',
        ])->render();

        $this->assertStringNotContainsString('role="tablist"', $html);
        $this->assertStringContainsString('aria-pressed="true"', $html);
    }

    private function xpath(string $html): DOMXPath
    {
        $document = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML($html);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        return new DOMXPath($document);
    }
}
