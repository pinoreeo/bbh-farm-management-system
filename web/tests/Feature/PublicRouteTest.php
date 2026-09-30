<?php

namespace Tests\Feature;

use Tests\TestCase;

class PublicRouteTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_root_redirects_to_default_locale(): void
    {
        $this->get('/')->assertRedirect('/id-id');
    }

    public function test_login_only_offers_email_invitation_activation(): void
    {
        $this->get('/login')->assertOk()->assertDontSee('kode SMS')->assertDontSee('/aktifkan-akun"', false);
        $this->get('/aktifkan-akun')->assertNotFound();
    }

    public function test_public_home_page_renders(): void
    {
        $this->get('/id-id')->assertOk();
    }

    public function test_company_profile_preserves_public_sections_and_services_in_each_language(): void
    {
        foreach (['id-id', 'en-id'] as $locale) {
            $response = $this->get('/'.$locale)->assertOk();

            foreach (['beranda', 'tentang', 'fokus', 'galeri', 'kegiatan', 'verifikasi', 'sertifikat', 'lokasi'] as $section) {
                $response->assertSee('id="'.$section.'"', false);
            }

            $response
                ->assertSee('class="bbh-hero-title">Bumiku Bumimu Hijau Farm</h1>', false)
                ->assertSee('hero-landing-app.webp')
                ->assertSee('action="'.url('/'.$locale.'/verifikasi').'"', false)
                ->assertSee('name="certificate_number"', false)
                ->assertSee('name="pdf"', false)
                ->assertSee('https://wa.me/')
                ->assertSee('href="'.url('/'.$locale).'#sertifikat"', false)
                ->assertDontSee('bbh-hero-pattern')
                ->assertDontSee('bbh-hero-actions')
                ->assertDontSee('bbh-about-photo')
                ->assertDontSee('bbh-focus-icon');
        }
    }

    public function test_public_home_includes_location_in_each_language(): void
    {
        foreach (['id-id' => 'Desa Darmakradenan', 'en-id' => 'Darmakradenan Village'] as $locale => $address) {
            $this->get('/'.$locale)
                ->assertOk()
                ->assertSee('id="lokasi"', false)
                ->assertSee($address)
                ->assertSee(url('/'.$locale).'#lokasi', false)
                ->assertDontSee('href="'.url('/'.$locale.'/lokasi').'"', false);
        }
    }

    public function test_old_location_page_redirects_to_home_location_section(): void
    {
        foreach (['id-id', 'en-id'] as $locale) {
            $this->get('/'.$locale.'/lokasi')
                ->assertStatus(301)
                ->assertRedirect(url('/'.$locale).'#lokasi');
        }
    }

    public function test_certificate_information_follows_verification_in_each_language(): void
    {
        foreach (['id-id' => 'Sertifikat BBH Farm', 'en-id' => 'BBH Farm certificates'] as $locale => $title) {
            $response = $this->get('/'.$locale)
                ->assertOk()
                ->assertSee($title)
                ->assertSee('data-public-section-link="sertifikat"', false)
                ->assertDontSee('href="'.route('certificate.info', ['locale' => $locale]).'"', false)
                ->assertDontSee('RSA-SHA256');

            $response->assertSeeInOrder(['id="verifikasi"', 'id="sertifikat"', 'id="lokasi"'], false);
        }
    }

    public function test_old_certificate_page_redirects_to_home_certificate_section(): void
    {
        foreach (['id-id', 'en-id'] as $locale) {
            $this->get('/'.$locale.'/sertifikat-elektronik')
                ->assertStatus(301)
                ->assertRedirect(url('/'.$locale).'#sertifikat');
        }
    }
}
