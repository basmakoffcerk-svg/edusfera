const { chromium } = require('playwright');

async function startStudentPeer() {
    console.log('🎓 Запуск тестового ученика (Максим Ученик) для подключения к уроку 23...');
    
    const browser = await chromium.launch({
        channel: 'chrome',
        headless: true,
        args: [
            '--use-fake-ui-for-media-stream',
            '--use-fake-device-for-media-stream',
            '--no-sandbox',
            '--disable-web-security',
        ]
    });

    const context = await browser.newContext({
        viewport: { width: 390, height: 844 },
        isMobile: true,
        hasTouch: true,
        permissions: ['camera', 'microphone']
    });

    const page = await context.newPage();

    page.on('console', msg => {
        const text = msg.text();
        if (text.includes('[WebRTC') || text.includes('[Classroom]') || text.includes('P2P') || text.includes('unmute') || text.includes('SDP') || text.includes('маршрут')) {
            console.log(`  [УЧЕНИК]:`, text);
        }
    });

    page.on('pageerror', err => console.error('  [УЧЕНИК ОШИБКА]:', err.message));

    try {
        console.log('1. Вход под учетной записью ученика (student-test@edusfera.by)...');
        await page.goto('https://edusfera.by/login', { waitUntil: 'domcontentloaded', timeout: 30000 });
        await page.fill('input[name="loginIdentifier"]', 'student-test@edusfera.by');
        await page.fill('input[name="password"]', 'Password123!');
        await Promise.all([
            page.waitForNavigation({ timeout: 20000 }).catch(() => {}),
            page.click('button[type="submit"]')
        ]);

        console.log('2. Вход в урок 23 (https://edusfera.by/classroom/23)...');
        await page.goto('https://edusfera.by/classroom/23', { waitUntil: 'networkidle', timeout: 30000 });

        console.log('✅ Тестовый ученик вошел в комнату с камерой и микрофоном.');
        console.log('📡 Ожидание преподавателя...');

        let lastStatus = '';
        const startTime = Date.now();
        const durationMinutes = 20; // 20 минут активной сессии
        const endTime = startTime + durationMinutes * 60 * 1000;

        while (Date.now() < endTime) {
            await page.waitForTimeout(3000);
            
            const state = await page.evaluate(() => ({
                isConnected: window.classroomApp?.isConnected,
                remoteConnected: window.classroomApp?.remoteConnected,
                p2pState: window.classroomApp?.p2p?.pc?.connectionState,
                iceState: window.classroomApp?.p2p?.pc?.iceConnectionState,
                isCameraOn: window.classroomApp?.isCameraOn,
                isMicOn: window.classroomApp?.isMicOn,
                remoteVideoOn: window.classroomApp?.remoteVideoOn,
                remoteAudioOn: window.classroomApp?.remoteAudioOn,
            })).catch(() => null);

            if (!state) continue;

            const statusSummary = `P2P: ${state.p2pState || 'idle'} | ICE: ${state.iceState || 'idle'} | Учитель подключен: ${state.remoteConnected ? 'ДА (видеосвязь активна ✅)' : 'нет (ожидание...)'}`;
            if (statusSummary !== lastStatus) {
                console.log(`⏱️ [${new Date().toLocaleTimeString()}] ${statusSummary}`);
                lastStatus = statusSummary;
            }
        }

        console.log('⌛ 20 минут тестового звонка завершены.');
    } catch (err) {
        console.error('❌ Ошибка во время тестового звонка:', err);
    } finally {
        await browser.close();
        console.log('🛑 Тестовый звонок завершен.');
    }
}

startStudentPeer();
