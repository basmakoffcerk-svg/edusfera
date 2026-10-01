const { chromium } = require('playwright');
const path = require('path');
const fs = require('fs');

const BASE_URL = process.env.BASE_URL || 'https://edusfera.by';
const OUT_DIR = path.resolve(__dirname, '../ad-assets');

function ensureDirs() {
    const dirs = [
        path.join(OUT_DIR, '01_desktop_full_pages'),
        path.join(OUT_DIR, '02_desktop_key_blocks'),
        path.join(OUT_DIR, '03_mobile_screens'),
        path.join(OUT_DIR, '04_social_ad_creatives'),
        path.join(OUT_DIR, '05_brand_assets')
    ];
    for (const d of dirs) {
        fs.mkdirSync(d, { recursive: true });
    }
}

async function safeElementScreenshot(page, selector, outputPath) {
    try {
        const el = await page.$(selector);
        if (el) {
            await el.scrollIntoViewIfNeeded();
            await page.waitForTimeout(400);
            await el.screenshot({ path: outputPath });
            return true;
        }
    } catch (e) {
        console.warn(`       ⚠️ Could not capture selector ${selector}: ${e.message}`);
    }
    return false;
}

async function safeViewportScreenshot(page, outputPath, scrollY = 0) {
    try {
        if (scrollY > 0) {
            await page.evaluate((y) => window.scrollTo(0, y), scrollY);
            await page.waitForTimeout(500);
        }
        await page.screenshot({ path: outputPath });
        return true;
    } catch (e) {
        console.warn(`       ⚠️ Could not capture viewport at scroll ${scrollY}: ${e.message}`);
    }
    return false;
}

async function capture() {
    ensureDirs();
    console.log(`🚀 Начинаем расширенный захват экранов платформы с ${BASE_URL}...`);

    const browser = await chromium.launch({
        executablePath: '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
        headless: true
    });

    // ─────────────────────────────────────────────────────────────
    // 1. DESKTOP SCREENS (1440x900 @2x Retina)
    // ─────────────────────────────────────────────────────────────
    console.log('\n🖥️ Снятие десктопных страниц и ключевых блоков (Retina @2x)...');
    const desktopContext = await browser.newContext({
        viewport: { width: 1440, height: 900 },
        deviceScaleFactor: 2
    });
    const page = await desktopContext.newPage();

    // 1.1 HOME PAGE
    console.log('  ➜ Главная страница (/)...');
    try {
        await page.goto(`${BASE_URL}/`, { waitUntil: 'networkidle', timeout: 45000 });
        await page.waitForTimeout(1000);

        await page.screenshot({ path: path.join(OUT_DIR, '01_desktop_full_pages', '01_home_page_full.png'), fullPage: true });
        console.log('     ✓ 01_home_page_full.png');

        await safeViewportScreenshot(page, path.join(OUT_DIR, '02_desktop_key_blocks', '01_home_hero_first_screen.png'), 0);
        console.log('     ✓ 01_home_hero_first_screen.png');

        // Search and filters
        await safeViewportScreenshot(page, path.join(OUT_DIR, '02_desktop_key_blocks', '02_home_search_and_filters.png'), 400);
        console.log('     ✓ 02_home_search_and_filters.png');

        // Subjects block
        await safeElementScreenshot(page, '#subjects', path.join(OUT_DIR, '02_desktop_key_blocks', '03_home_subjects_cards.png'))
            || await safeViewportScreenshot(page, path.join(OUT_DIR, '02_desktop_key_blocks', '03_home_subjects_cards.png'), 900);
        console.log('     ✓ 03_home_subjects_cards.png');

        // Features & steps
        await safeViewportScreenshot(page, path.join(OUT_DIR, '02_desktop_key_blocks', '04_home_features_bento.png'), 1700);
        console.log('     ✓ 04_home_features_bento.png');

        // Step by step
        await safeViewportScreenshot(page, path.join(OUT_DIR, '02_desktop_key_blocks', '05_home_step_by_step.png'), 2500);
        console.log('     ✓ 05_home_step_by_step.png');

        // Guarantees / Social proof
        await safeViewportScreenshot(page, path.join(OUT_DIR, '02_desktop_key_blocks', '06_home_social_proof.png'), 3300);
        console.log('     ✓ 06_home_social_proof.png');
    } catch (e) {
        console.error('     ❌ Ошибка на главной:', e.message);
    }

    // 1.2 FOR TUTORS PAGE
    console.log('  ➜ Страница для репетиторов (/for-tutors)...');
    try {
        await page.goto(`${BASE_URL}/for-tutors`, { waitUntil: 'networkidle', timeout: 45000 });
        await page.waitForTimeout(1000);

        await page.screenshot({ path: path.join(OUT_DIR, '01_desktop_full_pages', '02_for_tutors_page_full.png'), fullPage: true });
        console.log('     ✓ 02_for_tutors_page_full.png');

        await safeViewportScreenshot(page, path.join(OUT_DIR, '02_desktop_key_blocks', '07_for_tutors_hero.png'), 0);
        console.log('     ✓ 07_for_tutors_hero.png');

        // Pains
        await safeElementScreenshot(page, '#pains', path.join(OUT_DIR, '02_desktop_key_blocks', '08_for_tutors_pains_cards.png'))
            || await safeViewportScreenshot(page, path.join(OUT_DIR, '02_desktop_key_blocks', '08_for_tutors_pains_cards.png'), 850);
        console.log('     ✓ 08_for_tutors_pains_cards.png');

        // Calculator
        await safeElementScreenshot(page, '#calculator', path.join(OUT_DIR, '02_desktop_key_blocks', '09_for_tutors_calculator_income.png'))
            || await safeViewportScreenshot(page, path.join(OUT_DIR, '02_desktop_key_blocks', '09_for_tutors_calculator_income.png'), 1700);
        console.log('     ✓ 09_for_tutors_calculator_income.png');

        // Pricing
        await safeElementScreenshot(page, '#pricing', path.join(OUT_DIR, '02_desktop_key_blocks', '10_for_tutors_pricing_table.png'))
            || await safeViewportScreenshot(page, path.join(OUT_DIR, '02_desktop_key_blocks', '10_for_tutors_pricing_table.png'), 2800);
        console.log('     ✓ 10_for_tutors_pricing_table.png');

        // NPD tax integration
        await safeViewportScreenshot(page, path.join(OUT_DIR, '02_desktop_key_blocks', '11_for_tutors_npd_integration.png'), 3700);
        console.log('     ✓ 11_for_tutors_npd_integration.png');

        // FAQ
        await safeElementScreenshot(page, '#faq', path.join(OUT_DIR, '02_desktop_key_blocks', '12_for_tutors_faq.png'))
            || await safeViewportScreenshot(page, path.join(OUT_DIR, '02_desktop_key_blocks', '12_for_tutors_faq.png'), 4400);
        console.log('     ✓ 12_for_tutors_faq.png');
    } catch (e) {
        console.error('     ❌ Ошибка на странице репетиторов:', e.message);
    }

    // 1.3 TUTORS CATALOG (/tutors)
    console.log('  ➜ Каталог репетиторов (/tutors)...');
    try {
        await page.goto(`${BASE_URL}/tutors`, { waitUntil: 'networkidle', timeout: 45000 });
        await page.waitForTimeout(1000);

        await page.screenshot({ path: path.join(OUT_DIR, '01_desktop_full_pages', '03_catalog_page_full.png'), fullPage: true });
        await safeViewportScreenshot(page, path.join(OUT_DIR, '02_desktop_key_blocks', '13_catalog_tutor_cards.png'), 0);
        console.log('     ✓ 03_catalog_page_full.png & 13_catalog_tutor_cards.png');
    } catch (e) {
        console.error('     ❌ Ошибка в каталоге:', e.message);
    }

    // 1.4 AI DIAGNOSTICS (/diagnostic)
    console.log('  ➜ ИИ-диагностика знаний (/diagnostic)...');
    try {
        await page.goto(`${BASE_URL}/diagnostic`, { waitUntil: 'networkidle', timeout: 45000 });
        await page.waitForTimeout(1000);

        await page.screenshot({ path: path.join(OUT_DIR, '01_desktop_full_pages', '05_diagnostic_page_full.png'), fullPage: true });
        await safeViewportScreenshot(page, path.join(OUT_DIR, '02_desktop_key_blocks', '14_diagnostic_start_card.png'), 0);
        console.log('     ✓ 05_diagnostic_page_full.png & 14_diagnostic_start_card.png');
    } catch (e) {
        console.error('     ❌ Ошибка в диагностике:', e.message);
    }

    // 1.5 ABOUT COMPANY (/about)
    console.log('  ➜ Страница «О компании» (/about)...');
    try {
        await page.goto(`${BASE_URL}/about`, { waitUntil: 'networkidle', timeout: 45000 });
        await page.waitForTimeout(1000);

        await page.screenshot({ path: path.join(OUT_DIR, '01_desktop_full_pages', '04_about_page_full.png'), fullPage: true });
        console.log('     ✓ 04_about_page_full.png');

        await safeViewportScreenshot(page, path.join(OUT_DIR, '02_desktop_key_blocks', '15_about_company_hero_blueprint.png'), 0);
        console.log('     ✓ 15_about_company_hero_blueprint.png');

        await safeViewportScreenshot(page, path.join(OUT_DIR, '02_desktop_key_blocks', '16_about_bento_products.png'), 950);
        console.log('     ✓ 16_about_bento_products.png');

        await safeViewportScreenshot(page, path.join(OUT_DIR, '02_desktop_key_blocks', '17_about_engineering_stack.png'), 1800);
        console.log('     ✓ 17_about_engineering_stack.png');

        await safeViewportScreenshot(page, path.join(OUT_DIR, '02_desktop_key_blocks', '18_about_official_requisites.png'), 2500);
        console.log('     ✓ 18_about_official_requisites.png');
    } catch (e) {
        console.error('     ❌ Ошибка на странице О компании:', e.message);
    }

    // 1.6 CONTACTS & PAYMENT SECURITY
    console.log('  ➜ Контакты и Безопасность платежей...');
    try {
        await page.goto(`${BASE_URL}/contacts`, { waitUntil: 'networkidle', timeout: 35000 });
        await page.screenshot({ path: path.join(OUT_DIR, '01_desktop_full_pages', '06_contacts_page_full.png'), fullPage: true });

        await page.goto(`${BASE_URL}/payment-security`, { waitUntil: 'networkidle', timeout: 35000 });
        await page.screenshot({ path: path.join(OUT_DIR, '01_desktop_full_pages', '07_payment_security_page_full.png'), fullPage: true });
        console.log('     ✓ 06_contacts_page_full.png & 07_payment_security_page_full.png');
    } catch (e) {
        console.error('     ❌ Ошибка в контактах/безопасности:', e.message);
    }

    // 1.7 AUTH PAGES (/login, /register)
    console.log('  ➜ Страницы авторизации (/login, /register)...');
    try {
        await page.goto(`${BASE_URL}/login`, { waitUntil: 'networkidle', timeout: 35000 });
        await page.screenshot({ path: path.join(OUT_DIR, '01_desktop_full_pages', '08_login_page_full.png'), fullPage: true });

        await page.goto(`${BASE_URL}/register`, { waitUntil: 'networkidle', timeout: 35000 });
        await page.screenshot({ path: path.join(OUT_DIR, '01_desktop_full_pages', '09_register_page_full.png'), fullPage: true });
        console.log('     ✓ 08_login_page_full.png & 09_register_page_full.png');
    } catch (e) {
        console.error('     ❌ Ошибка в страницах авторизации:', e.message);
    }

    await desktopContext.close();

    // ─────────────────────────────────────────────────────────────
    // 2. MOBILE SCREENS (iPhone 14/15 Pro 390x844 @3x)
    // ─────────────────────────────────────────────────────────────
    console.log('\n📱 Снятие мобильных экранов (iPhone @3x)...');
    const mobileContext = await browser.newContext({
        viewport: { width: 390, height: 844 },
        deviceScaleFactor: 3,
        isMobile: true,
        hasTouch: true,
        userAgent: 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1'
    });
    const mobPage = await mobileContext.newPage();

    // Mobile Home
    try {
        console.log('  ➜ Мобильная главная...');
        await mobPage.goto(`${BASE_URL}/`, { waitUntil: 'networkidle', timeout: 45000 });
        await mobPage.waitForTimeout(1000);

        await safeViewportScreenshot(mobPage, path.join(OUT_DIR, '03_mobile_screens', '01_mobile_home_hero_aurora.png'), 0);
        console.log('     ✓ 01_mobile_home_hero_aurora.png');

        await safeViewportScreenshot(mobPage, path.join(OUT_DIR, '03_mobile_screens', '02_mobile_home_subjects.png'), 720);
        console.log('     ✓ 02_mobile_home_subjects.png');

        await safeViewportScreenshot(mobPage, path.join(OUT_DIR, '03_mobile_screens', '03_mobile_home_features.png'), 1500);
        console.log('     ✓ 03_mobile_home_features.png');
    } catch (e) {
        console.error('     ❌ Ошибка в мобильной главной:', e.message);
    }

    // Mobile For Tutors
    try {
        console.log('  ➜ Мобильная для репетиторов...');
        await mobPage.goto(`${BASE_URL}/for-tutors`, { waitUntil: 'networkidle', timeout: 45000 });
        await mobPage.waitForTimeout(1000);

        await safeViewportScreenshot(mobPage, path.join(OUT_DIR, '03_mobile_screens', '04_mobile_for_tutors_hero.png'), 0);
        console.log('     ✓ 04_mobile_for_tutors_hero.png');

        await safeViewportScreenshot(mobPage, path.join(OUT_DIR, '03_mobile_screens', '05_mobile_for_tutors_pains.png'), 750);
        console.log('     ✓ 05_mobile_for_tutors_pains.png');

        await safeElementScreenshot(mobPage, '#calculator', path.join(OUT_DIR, '03_mobile_screens', '06_mobile_for_tutors_calculator.png'))
            || await safeViewportScreenshot(mobPage, path.join(OUT_DIR, '03_mobile_screens', '06_mobile_for_tutors_calculator.png'), 1500);
        console.log('     ✓ 06_mobile_for_tutors_calculator.png');

        await safeElementScreenshot(mobPage, '#pricing', path.join(OUT_DIR, '03_mobile_screens', '07_mobile_for_tutors_pricing.png'))
            || await safeViewportScreenshot(mobPage, path.join(OUT_DIR, '03_mobile_screens', '07_mobile_for_tutors_pricing.png'), 2800);
        console.log('     ✓ 07_mobile_for_tutors_pricing.png');
    } catch (e) {
        console.error('     ❌ Ошибка в мобильной для репетиторов:', e.message);
    }

    // Mobile Catalog
    try {
        console.log('  ➜ Мобильный каталог...');
        await mobPage.goto(`${BASE_URL}/tutors`, { waitUntil: 'networkidle', timeout: 45000 });
        await mobPage.waitForTimeout(1000);

        await safeViewportScreenshot(mobPage, path.join(OUT_DIR, '03_mobile_screens', '08_mobile_catalog_list.png'), 0);
        console.log('     ✓ 08_mobile_catalog_list.png');

        await safeViewportScreenshot(mobPage, path.join(OUT_DIR, '03_mobile_screens', '09_mobile_tutor_card_detail.png'), 450);
        console.log('     ✓ 09_mobile_tutor_card_detail.png');
    } catch (e) {
        console.error('     ❌ Ошибка в мобильном каталоге:', e.message);
    }

    // Mobile Diagnostic
    try {
        console.log('  ➜ Мобильная диагностика...');
        await mobPage.goto(`${BASE_URL}/diagnostic`, { waitUntil: 'networkidle', timeout: 45000 });
        await mobPage.waitForTimeout(1000);

        await safeViewportScreenshot(mobPage, path.join(OUT_DIR, '03_mobile_screens', '10_mobile_diagnostic_intro.png'), 0);
        console.log('     ✓ 10_mobile_diagnostic_intro.png');
    } catch (e) {
        console.error('     ❌ Ошибка в мобильной диагностике:', e.message);
    }

    // Mobile About Company
    try {
        console.log('  ➜ Мобильная «О компании»...');
        await mobPage.goto(`${BASE_URL}/about`, { waitUntil: 'networkidle', timeout: 45000 });
        await mobPage.waitForTimeout(1000);

        await safeViewportScreenshot(mobPage, path.join(OUT_DIR, '03_mobile_screens', '11_mobile_about_company.png'), 0);
        console.log('     ✓ 11_mobile_about_company.png');

        await safeViewportScreenshot(mobPage, path.join(OUT_DIR, '03_mobile_screens', '12_mobile_about_bento.png'), 800);
        console.log('     ✓ 12_mobile_about_bento.png');
    } catch (e) {
        console.error('     ❌ Ошибка в мобильной О компании:', e.message);
    }

    // Mobile Login
    try {
        console.log('  ➜ Мобильный логин...');
        await mobPage.goto(`${BASE_URL}/login`, { waitUntil: 'networkidle', timeout: 35000 });
        await mobPage.waitForTimeout(800);

        await safeViewportScreenshot(mobPage, path.join(OUT_DIR, '03_mobile_screens', '13_mobile_auth_login.png'), 0);
        console.log('     ✓ 13_mobile_auth_login.png');
    } catch (e) {
        console.error('     ❌ Ошибка в мобильном логине:', e.message);
    }

    // Mobile Contacts
    try {
        console.log('  ➜ Мобильные контакты...');
        await mobPage.goto(`${BASE_URL}/contacts`, { waitUntil: 'networkidle', timeout: 35000 });
        await mobPage.waitForTimeout(800);

        await safeViewportScreenshot(mobPage, path.join(OUT_DIR, '03_mobile_screens', '14_mobile_contacts.png'), 0);
        console.log('     ✓ 14_mobile_contacts.png');
    } catch (e) {
        console.error('     ❌ Ошибка в мобильных контактах:', e.message);
    }

    await mobileContext.close();
    await browser.close();

    // ─────────────────────────────────────────────────────────────
    // 3. COPY BRAND ASSETS TO 05_brand_assets
    // ─────────────────────────────────────────────────────────────
    const brandSrcDir = path.join(OUT_DIR, '04_brand_assets');
    const brandDstDir = path.join(OUT_DIR, '05_brand_assets');
    if (fs.existsSync(brandSrcDir)) {
        const files = fs.readdirSync(brandSrcDir);
        for (const f of files) {
            fs.copyFileSync(path.join(brandSrcDir, f), path.join(brandDstDir, f));
        }
        console.log(`\n💎 Скопировано ${files.length} бренд-ассетов в 05_brand_assets`);
    }

    console.log('\n🎉 Все экраны и блоки платформы успешно сохранены в ad-assets/ !');
}

capture().catch(err => {
    console.error('Fatal error during capture:', err);
    process.exit(1);
});
