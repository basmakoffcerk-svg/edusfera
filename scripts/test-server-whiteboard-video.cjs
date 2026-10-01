const { chromium } = require('playwright');

async function verifyServerWhiteboardAndVideo() {
    console.log('🔍 Запуск комплексного теста: видео репетитора у ученика + серверная синхронизация доски...');
    const browser = await chromium.launch({
        channel: 'chrome',
        headless: true,
        args: ['--use-fake-ui-for-media-stream', '--use-fake-device-for-media-stream', '--no-sandbox']
    });

    const contextTutor = await browser.newContext();
    const pageTutor = await contextTutor.newPage();

    const contextStudent = await browser.newContext({ viewport: { width: 390, height: 844 } });
    const pageStudent = await contextStudent.newPage();

    try {
        console.log('1. Вход пользователей...');
        await pageTutor.goto('https://edusfera.by/login');
        await pageTutor.fill('input[name="loginIdentifier"]', 'tutor-test@edusfera.by');
        await pageTutor.fill('input[name="password"]', 'Password123!');
        await Promise.all([
            pageTutor.waitForNavigation({ timeout: 20000 }).catch(() => {}),
            pageTutor.click('button[type="submit"]')
        ]);

        await pageStudent.goto('https://edusfera.by/login');
        await pageStudent.fill('input[name="loginIdentifier"]', 'student-test@edusfera.by');
        await pageStudent.fill('input[name="password"]', 'Password123!');
        await Promise.all([
            pageStudent.waitForNavigation({ timeout: 20000 }).catch(() => {}),
            pageStudent.click('button[type="submit"]')
        ]);

        console.log('2. Переход в урок 23...');
        await Promise.all([
            pageTutor.goto('https://edusfera.by/classroom/23'),
            pageStudent.goto('https://edusfera.by/classroom/23')
        ]);

        console.log('3. Ожидание 8 секунд для полной синхронизации WebRTC и ServerWhiteboard...');
        await pageStudent.waitForTimeout(8000);

        console.log('4. Проверка состояния видео репетитора на стороне УЧЕНИКА:');
        const studentVideoState = await pageStudent.evaluate(() => {
            const videoEl = document.getElementById('cr-tutor-remote-video');
            const placeholderEl = document.querySelector('.cr-video-placeholder');
            const app = window.classroomApp;
            return {
                videoExists: !!videoEl,
                videoDisplay: videoEl ? window.getComputedStyle(videoEl).display : null,
                videoVisibility: videoEl ? window.getComputedStyle(videoEl).visibility : null,
                videoPaused: videoEl ? videoEl.paused : null,
                hasSrcObject: videoEl && !!videoEl.srcObject,
                remoteConnected: app?.remoteConnected,
                remoteVideoOn: app?.remoteVideoOn,
                remoteAudioOn: app?.remoteAudioOn,
                serverWhiteboardActive: !!app?.serverWhiteboard && !app.serverWhiteboard.isStopped
            };
        });
        console.log('Состояние видео у ученика:', JSON.stringify(studentVideoState, null, 2));

        if (!studentVideoState.videoPaused && studentVideoState.hasSrcObject && studentVideoState.remoteVideoOn) {
            console.log('✅ ВИДЕО РЕПЕТИТОРА АКТИВНО И ВОСПРОИЗВОДИТСЯ НА СТОРОНЕ УЧЕНИКА!');
        } else {
            console.log('⚠️ Предупреждение по видео ученика:', studentVideoState);
        }

        console.log('5. Проверка серверной синхронизации доски через Edusfera ServerWhiteboardManager...');
        const syncTest = await pageTutor.evaluate(async () => {
            const app = window.classroomApp;
            if (!app?.serverWhiteboard) return { error: 'No serverWhiteboard' };
            const testElements = [
                { id: 'server_test_1', type: 'rectangle', x: 50, y: 50, width: 100, height: 100, version: Date.now() }
            ];
            app.serverWhiteboard.broadcast(testElements, Date.now());
            return { sent: true, version: app.serverWhiteboard.lastVersion };
        });
        console.log('Учитель отправил тестовые элементы через сервер:', syncTest);

        console.log('6. Ожидание опроса сервера учеником (2 сек)...');
        await pageStudent.waitForTimeout(2000);

        const studentBoardState = await pageStudent.evaluate(() => {
            const app = window.classroomApp;
            return {
                serverWhiteboardVersion: app?.serverWhiteboard?.lastVersion,
                isBoardLocked: app?.isBoardLocked,
            };
        });
        console.log('Состояние доски ученика после синхронизации через сервер:', studentBoardState);

        console.log('7. Тестирование блокировки доски через сервер...');
        await pageTutor.evaluate(() => {
            window.classroomApp?.toggleBoardLock();
        });
        await pageStudent.waitForTimeout(1500);

        const lockState = await pageStudent.evaluate(() => window.classroomApp?.isBoardLocked);
        console.log('Статус блокировки доски у ученика (после переключения учителем):', lockState);

        if (lockState === true) {
            console.log('✅ БЛОКИРОВКА ДОСКИ СИНХРОНИЗИРОВАНА ЧЕРЕЗ СЕРВЕР!');
        }

    } finally {
        await browser.close();
    }
}

verifyServerWhiteboardAndVideo().catch(console.error);
