<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

class PromoPageTest extends TestCase
{
    /**
     * Test that public promo html file exists and is accessible.
     */
    public function test_promo_index_file_exists(): void
    {
        $filePath = public_path('promo/index.html');

        $this->assertFileExists($filePath);
        $this->assertIsReadable($filePath);
    }

    /**
     * Test that promo page contains essential SEO, viewport and OpenGraph tags.
     */
    public function test_promo_page_contains_seo_and_meta_tags(): void
    {
        $content = file_get_contents(public_path('promo/index.html'));

        $this->assertStringContainsString('<meta name="viewport" content="width=device-width, initial-scale=1">', $content);
        $this->assertStringContainsString('<title>Edusfera', $content);
        $this->assertStringContainsString('og:title', $content);
        $this->assertStringContainsString('og:description', $content);
    }

    /**
     * Test that promo page contains Belarusian GetCourse positioning and comparison table.
     */
    public function test_promo_page_contains_getcourse_positioning_and_comparison(): void
    {
        $content = file_get_contents(public_path('promo/index.html'));

        $this->assertStringContainsString('ПЛАТФОРМА ДЛЯ ПРЕПОДАВАНИЯ', $content);
        $this->assertStringContainsString('id="comparison"', $content);
        $this->assertStringContainsString('GetCourse / Zoom', $content);
        $this->assertStringContainsString('ЕРИП, БЕЛКАРТ, Visa/Mastercard (BYN)', $content);
    }



    /**
     * Test that promo page contains Waitlist lead magnet and social proof badge.
     */
    public function test_promo_page_contains_waitlist_lead_magnet_and_social_proof(): void
    {
        $content = file_get_contents(public_path('promo/index.html'));

        $this->assertStringContainsString('Уже подали заявку 140+ преподавателей и центров', $content);
        $this->assertStringContainsString('3 месяца бесплатного Premium-доступа + 5 ГБ хранилища навсегда', $content);
        $this->assertStringContainsString('Получить ранний доступ', $content);
    }

    /**
     * Test that promo page contains beta registration form with all required fields.
     */
    public function test_promo_page_contains_beta_registration_form(): void
    {
        $content = file_get_contents(public_path('promo/index.html'));

        $this->assertStringContainsString('id="js-beta-form"', $content);
        $this->assertStringContainsString('name="name"', $content);
        $this->assertStringContainsString('name="phone"', $content);
        $this->assertStringContainsString('name="email"', $content);
        $this->assertStringContainsString('name="subject"', $content);
    }

    /**
     * Test that promo page contains required legal, company, and payment compliance information.
     */
    public function test_promo_page_contains_legal_and_payment_compliance_information(): void
    {
        $content = file_get_contents(public_path('promo/index.html'));

        $this->assertStringContainsString('192854899', $content); // УНП
        $this->assertStringContainsString('ООО «Эдусфера»', $content);
        $this->assertStringContainsString('04.05.2026', $content);
        $this->assertStringContainsString('+375 (29) 519-08-21', $content);
        $this->assertStringContainsString('Visa', $content);
        $this->assertStringContainsString('Mastercard', $content);
        $this->assertStringContainsString('БЕЛКАРТ', $content);
        $this->assertStringContainsString('Альфа-Банк', $content);
        $this->assertStringNotContainsString('payment-logos-full.svg', $content);
        $this->assertStringContainsString('offer.html', $content);
        $this->assertStringContainsString('privacy-policy.html', $content);
        $this->assertStringContainsString('payment-security.html', $content);
        $this->assertStringContainsString('refund-policy.html', $content);
    }

    /**
     * Test that promo page incorporates Contrast design system CSS variables.
     */
    public function test_promo_page_includes_variables_css(): void
    {
        $content = file_get_contents(public_path('promo/index.html'));

        $this->assertStringContainsString('--color-signal-coral', $content);
        $this->assertFileExists(base_path('variables.css'));
    }

    /**
     * Test that mobile layout styles prevent horizontal overflow.
     */
    public function test_promo_page_has_mobile_overflow_protection(): void
    {
        $content = file_get_contents(public_path('promo/index.html'));

        $this->assertStringContainsString('overflow-x: hidden', $content);
        $this->assertStringContainsString('max-width: 100vw', $content);
    }
}
