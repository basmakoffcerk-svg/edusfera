const { chromium } = require('playwright');
const path = require('path');

const ARTIFACT_DIR = '/Users/sergei/.gemini/antigravity-ide/brain/a6cb9301-315a-4eed-b354-92d8d6997900';

async function main() {
    console.log('🚀 Запуск тестирования полноэкранного видеозвонка и сенсорного управления...');
    const browser = await chromium.launch({
        channel: 'chrome',
        headless: true,
        args: ['--no-sandbox', '--disable-setuid-sandbox', '--disable-web-security', '--ignore-certificate-errors'],
    });

    // Mobile iPhone 13/14 portrait context (390 x 844)
    const mobileContext = await browser.newContext({
        viewport: { width: 390, height: 844 },
        deviceScaleFactor: 2,
        isMobile: true,
        hasTouch: true,
        ignoreHTTPSErrors: true,
    });
    const page = await mobileContext.newPage();

    try {
        console.log('1. Вход в систему под учеником...');
        await page.goto('https://edusfera.by/admin/login', { waitUntil: 'networkidle', timeout: 30000 });
        await page.waitForSelector('input[name="loginIdentifier"]', { timeout: 15000 });
        await page.fill('input[name="loginIdentifier"]', 'student-test@edusfera.by');
        await page.fill('input[name="password"]', 'Password123!');
        await Promise.all([
            page.waitForNavigation({ timeout: 20000 }).catch(() => {}),
            page.click('button[type="submit"]')
        ]);
        await page.waitForTimeout(2000);
        console.log('✓ Авторизован, текущий URL:', page.url());

        console.log('2. Переход на урок 17 на смартфоне (390x844)...');
        await page.goto('https://edusfera.by/classroom/17', { waitUntil: 'networkidle', timeout: 45000 });
        await page.waitForTimeout(3500);

        // Screenshot 1: Mobile Board View with floating video and touch optimizations
        const mobileBoardPath = path.join(ARTIFACT_DIR, 'classroom_mobile_touch_board.png');
        await page.screenshot({ path: mobileBoardPath });
        console.log(`✓ Скриншот мобильной доски сохранен: ${mobileBoardPath}`);

        // Check right-side Excalidraw palette is hidden
        const rightPaletteHidden = await page.evaluate(() => {
            const el = document.querySelector('.cr-excalidraw-surface .excalidraw .layer-ui__wrapper__top-right');
            if (!el) return true;
            const style = window.getComputedStyle(el);
            return style.display === 'none';
        });
        console.log(`✓ Правая панель Excalidraw скрыта для сенсора: ${rightPaletteHidden}`);

        // 3. Trigger Fullscreen Video Call
        console.log('3. Разворачивание видеосвязи на весь экран (50/50 вертикальный сплит)...');
        await page.evaluate(() => {
            window.classroomApp?.toggleFullscreenVideo();
        });
        await page.waitForTimeout(1000);

        // Verify fullscreen state
        const isFs = await page.evaluate(() => {
            const island = document.getElementById('cr-video-island');
            return island?.classList.contains('cr-island-video--fullscreen');
        });
        console.log(`✓ Полноэкранный режим активен: ${isFs}`);

        // Screenshot 2: Mobile Fullscreen Call (matching VK / FaceTime reference)
        const mobileFsPath = path.join(ARTIFACT_DIR, 'classroom_mobile_fullscreen_call.png');
        await page.screenshot({ path: mobileFsPath });
        console.log(`✓ Скриншот полноэкранного звонка сохранен: ${mobileFsPath}`);

        // Check 50/50 split layout
        const layoutDetails = await page.evaluate(() => {
            const tiles = document.querySelector('.cr-island-video--fullscreen .cr-video-tiles');
            const tileEls = document.querySelectorAll('.cr-island-video--fullscreen .cr-video-tile');
            const header = document.querySelector('.cr-fs-call-header');
            const dock = document.querySelector('.cr-fs-call-dock');
            const backBtn = document.querySelector('.cr-fs-back-btn');
            
            return {
                tilesFlexDirection: tiles ? window.getComputedStyle(tiles).flexDirection : null,
                tile1Height: tileEls[0] ? tileEls[0].getBoundingClientRect().height : null,
                tile2Height: tileEls[1] ? tileEls[1].getBoundingClientRect().height : null,
                headerVisible: header ? window.getComputedStyle(header).display !== 'none' : false,
                dockVisible: dock ? window.getComputedStyle(dock).display !== 'none' : false,
                backBtnText: backBtn ? backBtn.textContent.trim() : null,
            };
        });
        console.log('✓ Параметры 50/50 сплита:', JSON.stringify(layoutDetails, null, 2));

        // 4. Test Return to Whiteboard ("К доске")
        console.log('4. Проверка возврата к доске по кнопке "К доске"...');
        const backBtn = await page.$('.cr-fs-back-btn');
        if (backBtn) {
            await backBtn.click();
            await page.waitForTimeout(800);
            const isFsAfterBack = await page.evaluate(() => {
                const island = document.getElementById('cr-video-island');
                return island?.classList.contains('cr-island-video--fullscreen');
            });
            console.log(`✓ Успешный возврат к доске (fullscreen off): ${!isFsAfterBack}`);
        }

        // 5. Test Double Tap on Video Island to re-enter fullscreen
        console.log('5. Проверка дабл-тапа пальцем по плавающему видео...');
        const videoIsland = await page.$('#cr-video-island');
        if (videoIsland) {
            const box = await videoIsland.boundingBox();
            if (box) {
                await page.touchscreen.tap(box.x + box.width / 2, box.y + box.height / 2);
                await page.waitForTimeout(100);
                await page.touchscreen.tap(box.x + box.width / 2, box.y + box.height / 2);
                await page.waitForTimeout(1000);

                const isFsAfterDblTap = await page.evaluate(() => {
                    const island = document.getElementById('cr-video-island');
                    return island?.classList.contains('cr-island-video--fullscreen');
                });
                console.log(`✓ Дабл-тап успешно развернул видеозвонок: ${isFsAfterDblTap}`);
            }
        }

    } catch (e) {
        console.error('Ошибка верификации:', e);
    } finally {
        await browser.close();
    }
}

main();
