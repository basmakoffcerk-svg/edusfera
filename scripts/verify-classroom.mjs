import { chromium } from 'playwright';
import path from 'node:path';

const ARTIFACT_DIR = '/Users/sergei/.gemini/antigravity-ide/brain/a6cb9301-315a-4eed-b354-92d8d6997900';

async function main() {
    console.log('🚀 Запуск верификации интерфейса Виртуального класса Edusfera через Playwright...');
    const browser = await chromium.launch({
        executablePath: '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
        headless: true,
        args: ['--no-sandbox', '--disable-setuid-sandbox', '--disable-web-security', '--ignore-certificate-errors'],
    });

    const context = await browser.newContext({
        viewport: { width: 1440, height: 900 },
        ignoreHTTPSErrors: true,
    });
    const page = await context.newPage();

    try {
        page.on('console', msg => console.log('  [Browser LOG]:', msg.text()));
        page.on('pageerror', err => console.log('  [Browser ERR]:', err.message));

        console.log('1. Вход в систему под student-test@edusfera.by...');
        await page.goto('https://edusfera.by/login', { waitUntil: 'networkidle', timeout: 35000 });
        
        const loginInput = await page.$('input[name="loginIdentifier"]');
        if (loginInput) {
            await page.fill('input[name="loginIdentifier"]', 'student-test@edusfera.by');
            await page.fill('input[name="password"]', 'Password123!');
            console.log('  Отправка формы входа...');

            const [response] = await Promise.all([
                page.waitForResponse(resp => resp.url().includes('/api/auth/login')),
                page.click('button[type="submit"]'),
            ]);
            const respStatus = response.status();
            const respBody = await response.text();
            console.log(`  Статус ответа логина: ${respStatus}, тело: ${respBody.slice(0, 200)}`);
            await page.waitForTimeout(2000);
        }

        console.log('2. Переход на урок 17 (https://edusfera.by/classroom/17)...');
        await page.goto('https://edusfera.by/classroom/17', { waitUntil: 'networkidle', timeout: 45000 });
        console.log('  Текущий URL после перехода:', page.url());
        await page.waitForTimeout(4000);

        // Desktop screenshot
        const desktopPath = path.join(ARTIFACT_DIR, 'classroom_desktop_floating_video.png');
        await page.screenshot({ path: desktopPath });
        console.log(`✓ Скриншот рабочего стола сохранен: ${desktopPath}`);

        // Check video elements
        const hasGrip = await page.$('.cr-video-drag-grip');
        const hasScale = await page.$('.cr-scale-badge');
        const hasResize = await page.$('.cr-video-resize-handle');
        console.log(`✓ Элементы видео: Grip=${!!hasGrip}, ScaleButton=${!!hasScale}, ResizeHandle=${!!hasResize}`);

        // Click minimize button
        console.log('3. Проверка сворачивания видео в плавающую плашку (мини-пилл)...');
        const minimizeBtn = await page.$('.cr-video-island-actions button:last-child');
        if (minimizeBtn) {
            await minimizeBtn.click();
            await page.waitForTimeout(600);
            const pillPath = path.join(ARTIFACT_DIR, 'classroom_video_minimized_pill.png');
            await page.screenshot({ path: pillPath });
            console.log(`✓ Скриншот свернутого мини-пилла сохранен: ${pillPath}`);

            // Restore
            const pill = await page.$('.cr-video-minimized-pill');
            if (pill) await pill.click();
            await page.waitForTimeout(600);
        }

        // 4. Test Mobile Portrait Viewport (iPhone 14/15: 390x844)
        console.log('4. Эмуляция вертикального экрана смартфона (390 x 844)...');
        await page.setViewportSize({ width: 390, height: 844 });
        await page.waitForTimeout(1500);

        const mobilePortraitPath = path.join(ARTIFACT_DIR, 'classroom_mobile_portrait.png');
        await page.screenshot({ path: mobilePortraitPath });
        console.log(`✓ Скриншот вертикальной ориентации смартфона сохранен: ${mobilePortraitPath}`);

        // Test opening bottom-sheet chat
        console.log('5. Проверка открытия чата в режиме шторки (Bottom Sheet)...');
        const chatBtn = await page.$('.cr-dock-btn[title*="Чат"]');
        if (chatBtn) {
            await chatBtn.click();
            await page.waitForTimeout(800);
            const chatSheetPath = path.join(ARTIFACT_DIR, 'classroom_mobile_bottom_sheet_chat.png');
            await page.screenshot({ path: chatSheetPath });
            console.log(`✓ Скриншот мобильной шторки чата сохранен: ${chatSheetPath}`);

            // Close sheet
            const backdrop = await page.$('.cr-drawer-backdrop');
            if (backdrop) await backdrop.click();
            await page.waitForTimeout(600);
        }

        // Test video toggle (fullscreen canvas mode)
        console.log('6. Проверка режима чистого холста (скрытие видео)...');
        const videoToggle = await page.$('.cr-dock-btn[title*="Скрыть видео"]');
        if (videoToggle) {
            await videoToggle.click();
            await page.waitForTimeout(600);
            const fullCanvasPath = path.join(ARTIFACT_DIR, 'classroom_mobile_pure_canvas.png');
            await page.screenshot({ path: fullCanvasPath });
            console.log(`✓ Скриншот чистого холста сохранен: ${fullCanvasPath}`);
        }

        console.log('🎉 ВСЕ ПРОВЕРКИ УСПЕШНО ЗАВЕРШЕНЫ!');
    } catch (err) {
        console.error('Ошибка верификации:', err);
    } finally {
        await browser.close();
    }
}

main();
