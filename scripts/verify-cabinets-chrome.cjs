const { chromium } = require('playwright');
const path = require('path');
const fs = require('fs');

const BASE_URL = 'https://edusfera.by';
const OUT_DIR = path.resolve(__dirname, '../ad-assets/verification');

if (!fs.existsSync(OUT_DIR)) {
    fs.mkdirSync(OUT_DIR, { recursive: true });
}

async function verify() {
    console.log(`🚀 Запуск проверки через локальный Chrome на ${BASE_URL}...`);
    const browser = await chromium.launch({
        executablePath: '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
        headless: true
    });

    const context = await browser.newContext({
        viewport: { width: 1440, height: 900 },
        deviceScaleFactor: 2
    });
    const page = await context.newPage();

    try {
        // 1. Login as Student
        console.log('1. Вход учеником (student-test@edusfera.by)...');
        await page.goto(`${BASE_URL}/login`, { waitUntil: 'networkidle', timeout: 30000 });
        
        // Switch to "Вход" tab
        const loginTab = await page.$('button:has-text("Вход")');
        if (loginTab) {
            await loginTab.click();
            await page.waitForTimeout(600);
        }
        await page.fill('input[name="loginIdentifier"], input[type="text"], input[type="email"]', 'student-test@edusfera.by');
        await page.fill('input[name="password"]', 'Password123!');
        await page.click('button[type="submit"]');
        await page.waitForURL('**/admin**', { timeout: 20000 });
        await page.waitForTimeout(2500);

        console.log('   📸 Скриншот кабинета ученика...');
        await page.screenshot({ path: path.join(OUT_DIR, '01_student_dashboard.png'), fullPage: true });

        // Open AI Copilot drawer
        console.log('   🤖 Открытие Edusfera Copilot...');
        const copilotBtn = await page.$('#ai-copilot-trigger-btn');
        if (copilotBtn) {
            await copilotBtn.click();
            await page.waitForTimeout(1000);
            await page.screenshot({ path: path.join(OUT_DIR, '02_student_copilot_drawer.png') });
            console.log('   ✓ Скриншот AI Copilot сохранен');
        } else {
            console.log('   ⚠️ Кнопка AI Copilot не найдена по ID #ai-copilot-trigger-btn');
        }

        // 2. Check Lessons Page
        console.log('2. Проверка раздела уроков ученика...');
        await page.goto(`${BASE_URL}/admin/lessons`, { waitUntil: 'networkidle', timeout: 20000 });
        await page.waitForTimeout(1500);
        await page.screenshot({ path: path.join(OUT_DIR, '03_student_lessons.png') });

        // 3. Logout & Login as Tutor
        console.log('3. Вход репетитором (tutor-test@edusfera.by)...');
        await context.clearCookies();
        await page.goto(`${BASE_URL}/login`, { waitUntil: 'networkidle', timeout: 20000 });
        const tutorLoginTab = await page.$('button:has-text("Вход")');
        if (tutorLoginTab) {
            await tutorLoginTab.click();
            await page.waitForTimeout(600);
        }
        await page.fill('input[name="loginIdentifier"], input[type="text"], input[type="email"]', 'tutor-test@edusfera.by');
        await page.fill('input[name="password"]', 'Password123!');
        await page.click('button[type="submit"]');
        await page.waitForURL('**/admin**', { timeout: 20000 });
        await page.waitForTimeout(2500);

        console.log('   📸 Скриншот кабинета репетитора...');
        await page.screenshot({ path: path.join(OUT_DIR, '04_tutor_dashboard.png'), fullPage: true });

        // Open Tutor AI Assistant page
        console.log('4. Страница ИИ-Ассистент репетитора...');
        await page.goto(`${BASE_URL}/admin/tutor-ai-assistant`, { waitUntil: 'networkidle', timeout: 20000 });
        await page.waitForTimeout(1500);
        await page.screenshot({ path: path.join(OUT_DIR, '05_tutor_ai_assistant_page.png'), fullPage: true });

        // Check Tutor Copilot Drawer
        const tutorCopilotBtn = await page.$('#ai-copilot-trigger-btn');
        if (tutorCopilotBtn) {
            await tutorCopilotBtn.click();
            await page.waitForTimeout(1000);
            await page.screenshot({ path: path.join(OUT_DIR, '06_tutor_copilot_drawer.png') });
            console.log('   ✓ Скриншот ИИ-Методиста сохранен');
        }

        console.log('✅ Все проверки и скриншоты успешно завершены!');
    } catch (err) {
        console.error('❌ Ошибка в процессе проверки:', err);
    } finally {
        await browser.close();
    }
}

verify();
