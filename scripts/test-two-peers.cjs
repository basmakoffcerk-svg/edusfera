const { chromium } = require('playwright');

async function testWebRTC() {
    console.log('🚀 Запуск симуляции двустороннего урока (Учитель + Ученик)...');
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

    const contextTutor = await browser.newContext();
    const pageTutor = await contextTutor.newPage();
    pageTutor.on('console', msg => console.log('  [УЧИТЕЛЬ]:', msg.type(), msg.text()));
    pageTutor.on('pageerror', err => console.error('  [УЧИТЕЛЬ ERROR]:', err));

    const contextStudent = await browser.newContext({
        viewport: { width: 390, height: 844 },
        isMobile: true,
        hasTouch: true
    });
    const pageStudent = await contextStudent.newPage();
    pageStudent.on('console', msg => console.log('  [УЧЕНИК]:', msg.type(), msg.text()));
    pageStudent.on('pageerror', err => console.error('  [УЧЕНИК ERROR]:', err));
    pageStudent.on('response', async res => {
        if (res.status() >= 400) {
            console.log(`  [УЧЕНИК HTTP ${res.status()}]:`, res.url());
            try {
                const body = await res.text();
                console.log(`  [УЧЕНИК ERROR BODY]:`, body);
                console.log(`  [УЧЕНИК REQUEST BODY]:`, res.request().postData());
            } catch (e) {}
        }
    });

    try {
        console.log('1. Вход учителя (Виктор)...');
        await pageTutor.goto('https://edusfera.by/login');
        await pageTutor.fill('input[name="loginIdentifier"]', 'tutor-test@edusfera.by');
        await pageTutor.fill('input[name="password"]', 'Password123!');
        await Promise.all([
            pageTutor.waitForNavigation({ timeout: 20000 }).catch(() => {}),
            pageTutor.click('button[type="submit"]')
        ]);

        console.log('2. Вход ученика (Максим)...');
        await pageStudent.goto('https://edusfera.by/login');
        await pageStudent.fill('input[name="loginIdentifier"]', 'student-test@edusfera.by');
        await pageStudent.fill('input[name="password"]', 'Password123!');
        await Promise.all([
            pageStudent.waitForNavigation({ timeout: 20000 }).catch(() => {}),
            pageStudent.click('button[type="submit"]')
        ]);

        console.log('3. Открытие урока 23 обоими участниками...');
        await Promise.all([
            pageTutor.goto('https://edusfera.by/classroom/23', { waitUntil: 'networkidle', timeout: 30000 }),
            pageStudent.goto('https://edusfera.by/classroom/23', { waitUntil: 'networkidle', timeout: 30000 }),
        ]);

        console.log('4. Ожидание WebRTC согласования (10 сек)...');
        await pageTutor.waitForTimeout(10000);

        const tutorState = await pageTutor.evaluate(() => ({
            connected: window.classroomApp?.isConnected,
            remoteConnected: window.classroomApp?.remoteConnected,
            p2pState: window.classroomApp?.p2p?.pc?.connectionState,
            iceState: window.classroomApp?.p2p?.pc?.iceConnectionState,
            diagnostics: window.classroomApp?.p2p?.diagnostics,
        }));

        const studentState = await pageStudent.evaluate(() => ({
            connected: window.classroomApp?.isConnected,
            remoteConnected: window.classroomApp?.remoteConnected,
            p2pState: window.classroomApp?.p2p?.pc?.connectionState,
            iceState: window.classroomApp?.p2p?.pc?.iceConnectionState,
            diagnostics: window.classroomApp?.p2p?.diagnostics,
        }));

        console.log('✓ Статус Учителя:', JSON.stringify(tutorState, null, 2));
        console.log('✓ Статус Ученика:', JSON.stringify(studentState, null, 2));

    } catch (e) {
        console.error('Ошибка теста:', e);
    } finally {
        await browser.close();
    }
}

testWebRTC();
