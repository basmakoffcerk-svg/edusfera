const { chromium } = require('playwright');
const path = require('path');
const fs = require('fs');

const BASE_URL = process.env.BASE_URL || 'https://edusfera.by';
const OUT_DIR = path.resolve(__dirname, '../ad-assets');

const DESKTOP_PAGES = [
    { name: '01_home_hero_desktop.png', url: '/', clip: { x: 0, y: 0, width: 1440, height: 900 } },
    { name: '02_home_full_desktop.png', url: '/', fullPage: true },
    { name: '03_for_tutors_desktop.png', url: '/for-tutors', clip: { x: 0, y: 0, width: 1440, height: 960 } },
    { name: '04_tutors_catalog_desktop.png', url: '/tutors', clip: { x: 0, y: 0, width: 1440, height: 900 } },
    { name: '05_ai_diagnostic_desktop.png', url: '/diagnostic', clip: { x: 0, y: 0, width: 1440, height: 900 } },
    { name: '06_about_company_desktop.png', url: '/about', clip: { x: 0, y: 0, width: 1440, height: 960 } }
];

const MOBILE_PAGES = [
    { name: '01_home_mobile.png', url: '/', clip: { x: 0, y: 0, width: 390, height: 844 } },
    { name: '02_for_tutors_mobile.png', url: '/for-tutors', clip: { x: 0, y: 0, width: 390, height: 844 } },
    { name: '03_ai_diagnostic_mobile.png', url: '/diagnostic', clip: { x: 0, y: 0, width: 390, height: 844 } },
    { name: '04_tutors_catalog_mobile.png', url: '/tutors', clip: { x: 0, y: 0, width: 390, height: 844 } },
    { name: '05_about_company_mobile.png', url: '/about', clip: { x: 0, y: 0, width: 390, height: 844 } }
];

async function capture() {
    console.log(`🚀 Запуск захвата скриншотов с ${BASE_URL}...`);
    const browser = await chromium.launch({
        executablePath: '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
        headless: true
    });

    // 1. Desktop Screenshots (Retina 2x)
    console.log('\n🖥️ Снятие десктопных экранов (Retina 1440px @2x)...');
    const desktopContext = await browser.newContext({
        viewport: { width: 1440, height: 900 },
        deviceScaleFactor: 2
    });
    const desktopPage = await desktopContext.newPage();

    for (const item of DESKTOP_PAGES) {
        const dest = path.join(OUT_DIR, '01_platform_screens', item.name);
        try {
            console.log(`  📸 ${item.name} (${BASE_URL}${item.url})...`);
            await desktopPage.goto(`${BASE_URL}${item.url}`, { waitUntil: 'networkidle', timeout: 35000 });
            await desktopPage.waitForTimeout(1000); // Allow smooth transitions to settle
            if (item.fullPage) {
                await desktopPage.screenshot({ path: dest, fullPage: true });
            } else if (item.clip) {
                await desktopPage.screenshot({ path: dest, clip: item.clip });
            } else {
                await desktopPage.screenshot({ path: dest });
            }
            console.log(`     ✓ Сохранено: ${dest}`);
        } catch (err) {
            console.error(`     ❌ Ошибка захвата ${item.name}: ${err.message}`);
        }
    }
    await desktopContext.close();

    // 2. Mobile Screenshots (iPhone 14 Pro 390x844 @3x)
    console.log('\n📱 Снятие мобильных экранов (iPhone 390x844 @3x)...');
    const mobileContext = await browser.newContext({
        viewport: { width: 390, height: 844 },
        deviceScaleFactor: 3,
        isMobile: true,
        hasTouch: true,
        userAgent: 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1'
    });
    const mobilePage = await mobileContext.newPage();

    for (const item of MOBILE_PAGES) {
        const dest = path.join(OUT_DIR, '02_mobile_screens', item.name);
        try {
            console.log(`  📸 ${item.name} (${BASE_URL}${item.url})...`);
            await mobilePage.goto(`${BASE_URL}${item.url}`, { waitUntil: 'networkidle', timeout: 35000 });
            await mobilePage.waitForTimeout(1000);
            if (item.clip) {
                await mobilePage.screenshot({ path: dest, clip: item.clip });
            } else {
                await mobilePage.screenshot({ path: dest });
            }
            console.log(`     ✓ Сохранено: ${dest}`);
        } catch (err) {
            console.error(`     ❌ Ошибка захвата ${item.name}: ${err.message}`);
        }
    }
    await mobileContext.close();

    await browser.close();
    console.log('\n✅ Все скриншоты платформы успешно сняты и сохранены!');
}

capture().catch(err => {
    console.error('Fatal error:', err);
    process.exit(1);
});
