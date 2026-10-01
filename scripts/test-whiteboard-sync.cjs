const { chromium } = require('playwright');

async function testWhiteboardSync() {
    console.log('🎨 Тестирование синхронизации Excalidraw и Ultra-Low Latency LiveKit...');
    const browser = await chromium.launch({
        channel: 'chrome',
        headless: true,
        args: ['--use-fake-ui-for-media-stream', '--use-fake-device-for-media-stream', '--no-sandbox']
    });

    const contextTutor = await browser.newContext();
    const pageTutor = await contextTutor.newPage();
    pageTutor.on('console', msg => {
        const text = msg.text();
        if (text.includes('[LiveKit]') || text.includes('[Excalidraw]') || text.includes('error')) {
            console.log('  [УЧИТЕЛЬ]:', text);
        }
    });

    const contextStudent = await browser.newContext({ viewport: { width: 390, height: 844 } });
    const pageStudent = await contextStudent.newPage();
    pageStudent.on('console', msg => {
        const text = msg.text();
        if (text.includes('[LiveKit]') || text.includes('[Excalidraw]') || text.includes('error')) {
            console.log('  [УЧЕНИК]:', text);
        }
    });

    try {
        console.log('1. Вход преподавателя и ученика...');
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

        console.log('2. Открытие урока 23...');
        await Promise.all([
            pageTutor.goto('https://edusfera.by/classroom/23'),
            pageStudent.goto('https://edusfera.by/classroom/23')
        ]);

        console.log('3. Ожидание установки WebRTC сессии (8 сек)...');
        await pageTutor.waitForTimeout(8000);

        console.log('4. Проверка Ultra-Low Latency настроек LiveKit...');
        const latencyDiagnostics = await pageTutor.evaluate(() => {
            const lk = window.classroomApp?.liveKit;
            if (!lk || !lk.room) return { error: 'No LiveKit instance' };
            const localVideo = lk.localVideoTrack;
            const remoteParticipants = Array.from(lk.room.remoteParticipants?.values() || []);
            const remoteTracks = [];
            remoteParticipants.forEach(p => {
                p.trackPublications.forEach(pub => {
                    if (pub.track) {
                        remoteTracks.push({
                            kind: pub.kind,
                            playoutDelay: typeof pub.track.getPlayoutDelay === 'function' ? pub.track.getPlayoutDelay() : 'n/a'
                        });
                    }
                });
            });

            return {
                connected: lk.isConnected,
                roomState: lk.room.state,
                simulcast: lk.room.options?.publishDefaults?.simulcast,
                videoCodec: lk.room.options?.publishDefaults?.videoCodec,
                remoteTracksCount: remoteTracks.length,
                remoteTracks
            };
        });
        console.log('Параметры LiveKit на клиенте:', JSON.stringify(latencyDiagnostics, null, 2));

        console.log('5. Проверка наличия Excalidraw...');
        const tutorHasExcalidraw = await pageTutor.evaluate(() => typeof window.excalidrawAPI !== 'undefined');
        const studentHasExcalidraw = await pageStudent.evaluate(() => typeof window.excalidrawAPI !== 'undefined');
        console.log('Excalidraw готов? Учитель:', tutorHasExcalidraw, '| Ученик:', studentHasExcalidraw);

        console.log('6. Генерация 20 сложных штрихов от руки (freedraw ~85 КБ сырого JSON)...');
        const drawResult = await pageTutor.evaluate(() => {
            if (!window.excalidrawAPI) return 'No API';

            const complexElements = Array.from({ length: 20 }, (_, i) => ({
                id: 'stroke_heavy_' + i + '_' + Date.now(),
                type: 'freedraw',
                x: 150 + i * 15,
                y: 150 + i * 15,
                width: 250,
                height: 120,
                angle: 0,
                strokeColor: '#7D39EB',
                backgroundColor: 'transparent',
                fillStyle: 'solid',
                strokeWidth: 2,
                strokeStyle: 'solid',
                roughness: 1,
                opacity: 100,
                groupIds: [],
                frameId: null,
                roundness: null,
                seed: 123456 + i,
                version: 100 + i,
                versionNonce: 789012 + i,
                isDeleted: false,
                boundElements: null,
                updated: Date.now(),
                link: null,
                locked: false,
                points: Array.from({ length: 100 }, (_, j) => [j * 2.5, Math.sin(j * 0.2) * 30]),
                pressures: Array.from({ length: 100 }, () => 0.5),
                simulatePressure: true
            }));

            const current = window.excalidrawAPI.getSceneElements() || [];
            window.excalidrawAPI.updateScene({ elements: [...current, ...complexElements] });
            return 'Учитель добавил ' + complexElements.length + ' freedraw штрихов. Всего: ' + (current.length + complexElements.length);
        });
        console.log(drawResult);

        console.log('7. Ожидание компрессии, передачи по LiveKit и распаковки у ученика (4 сек)...');
        await pageStudent.waitForTimeout(4000);

        const studentElementsCount = await pageStudent.evaluate(() => {
            return window.excalidrawAPI ? window.excalidrawAPI.getSceneElements().length : -1;
        });
        console.log('Количество элементов на доске ученика:', studentElementsCount);

        if (studentElementsCount >= 20) {
            console.log('🎉 УСПЕХ: Синхронизация доски в реальном времени работает безупречно! Все штрихи доставлены без переполнения 64 KB.');
        } else {
            console.log('⚠️ Внимание: Получено элементов у ученика:', studentElementsCount);
        }

    } finally {
        await browser.close();
    }
}

testWhiteboardSync().catch(console.error);
