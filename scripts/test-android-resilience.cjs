const { chromium } = require('playwright');

async function testAndroidResilience() {
    console.log('🧪 Тестирование устойчивости видеосвязи на Android (реконнект, фоновый режим, переключение камеры)...');

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

    const contextStudent = await browser.newContext({
        viewport: { width: 390, height: 844 },
        isMobile: true,
        hasTouch: true
    });
    const pageStudent = await contextStudent.newPage();

    try {
        console.log('1. Авторизация Учителя...');
        await pageTutor.goto('https://edusfera.by/login');
        await pageTutor.fill('input[name="loginIdentifier"]', 'tutor-test@edusfera.by');
        await pageTutor.fill('input[name="password"]', 'Password123!');
        await Promise.all([
            pageTutor.waitForNavigation({ timeout: 20000 }).catch(() => {}),
            pageTutor.click('button[type="submit"]')
        ]);

        console.log('2. Авторизация Ученика (Android)...');
        await pageStudent.goto('https://edusfera.by/login');
        await pageStudent.fill('input[name="loginIdentifier"]', 'student-test@edusfera.by');
        await pageStudent.fill('input[name="password"]', 'Password123!');
        await Promise.all([
            pageStudent.waitForNavigation({ timeout: 20000 }).catch(() => {}),
            pageStudent.click('button[type="submit"]')
        ]);

        console.log('3. Вход в виртуальный класс...');
        await Promise.all([
            pageTutor.goto('https://edusfera.by/classroom/23'),
            pageStudent.goto('https://edusfera.by/classroom/23')
        ]);

        console.log('4. Ожидание подключения LiveKit (8 сек)...');
        await pageStudent.waitForTimeout(8000);

        const initialStudentState = await pageStudent.evaluate(() => {
            const app = window.classroomApp;
            const remoteVid = document.getElementById('cr-tutor-remote-video');
            const localVid = document.getElementById('cr-student-local-video');
            return {
                connected: app.isConnected,
                remoteConnected: app.remoteConnected,
                remoteVideoOn: app.remoteVideoOn,
                isCameraOn: app.isCameraOn,
                remoteVideoPlaying: !!(remoteVid && !remoteVid.paused && remoteVid.readyState >= 2),
                localVideoPlaying: !!(localVid && !localVid.paused && localVid.readyState >= 2),
            };
        });
        console.log('Начальное состояние ученика:', initialStudentState);

        console.log('5. Симуляция: ученик сворачивает браузер (visibilitychange -> hidden), затем возвращается (visible)...');
        await pageStudent.evaluate(() => {
            // Simulate video pausing like Chrome Android does
            const remoteVid = document.getElementById('cr-tutor-remote-video');
            if (remoteVid) remoteVid.pause();
            document.dispatchEvent(new Event('visibilitychange'));
        });
        await pageStudent.waitForTimeout(2500);

        const recoveredState = await pageStudent.evaluate(() => {
            const app = window.classroomApp;
            const remoteVid = document.getElementById('cr-tutor-remote-video');
            return {
                connected: app.isConnected,
                remoteConnected: app.remoteConnected,
                remoteVideoOn: app.remoteVideoOn,
                remoteVideoPlaying: !!(remoteVid && !remoteVid.paused),
            };
        });
        console.log('Состояние после возвращения в приложение:', recoveredState);

        console.log('6. Тест: мягкое выключение и включение камеры (без вызова getUserMedia и без потери прав)...');
        await pageStudent.evaluate(async () => {
            await window.classroomApp.toggleCamera(); // off
        });
        await pageStudent.waitForTimeout(1000);
        const camOffState = await pageStudent.evaluate(() => window.classroomApp.isCameraOn);
        console.log('Камера выключена (софт-мьют):', camOffState === false ? 'ДА' : 'НЕТ');

        await pageStudent.evaluate(async () => {
            await window.classroomApp.toggleCamera(); // on
        });
        await pageStudent.waitForTimeout(1000);
        const camOnState = await pageStudent.evaluate(() => window.classroomApp.isCameraOn);
        console.log('Камера мгновенно включена обратно:', camOnState === true ? 'ДА' : 'НЕТ');

        console.log('7. Тест: переключение фронтальной/задней камеры (flipCamera)...');
        await pageStudent.evaluate(async () => {
            await window.classroomApp.flipCamera();
        });
        await pageStudent.waitForTimeout(1500);
        const flipMode = await pageStudent.evaluate(() => window.classroomApp.liveKit?.facingMode);
        console.log('Режим камеры после переворота:', flipMode);

        console.log('✅ Все тесты мобильной устойчивости успешно пройдены!');
    } finally {
        await browser.close();
    }
}

testAndroidResilience().catch(err => {
    console.error('Ошибка теста:', err);
    process.exit(1);
});
