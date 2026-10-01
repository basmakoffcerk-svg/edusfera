const { chromium } = require('playwright');
const { execSync } = require('child_process');

async function testLiveKit() {
    console.log('Testing LiveKit token with Chromium...');
    const token = execSync(`php -r '
        require "vendor/autoload.php";
        $apiKey = "APImGcEym6iCNi2";
        $apiSecret = "FeZJ5u0Or5srE0yMCZsAg1JFneHVOeeGdKcW1NAKYjfD";
        $now = time();
        $payload = [
            "iss" => $apiKey,
            "sub" => "tutor_11",
            "name" => "Виктор",
            "nbf" => $now - 5,
            "exp" => $now + 7200,
            "video" => [
                "room" => "edusfera_lesson_23",
                "roomJoin" => true,
                "canPublish" => true,
                "canSubscribe" => true,
                "canPublishData" => true,
                "roomAdmin" => true,
            ],
        ];
        echo Firebase\\JWT\\JWT::encode($payload, $apiSecret, "HS256");
    '`).toString().trim();

    const browser = await chromium.launch({ channel: 'chrome', headless: true });
    const page = await browser.newPage();

    // Use livekit-client from bundle or cdn
    await page.goto('https://edusfera.by/login');
    const result = await page.evaluate(async ({ token }) => {
        return new Promise((resolve) => {
            const script = document.createElement('script');
            script.src = 'https://cdn.jsdelivr.net/npm/livekit-client/dist/livekit-client.umd.min.js';
            script.onload = async () => {
                try {
                    const room = new LivekitClient.Room();
                    await room.connect('wss://edusfera-iae194s8.livekit.cloud', token);
                    const roomName = room.name;
                    const state = room.state;
                    await room.disconnect();
                    resolve({ success: true, roomName, state });
                } catch (err) {
                    resolve({ success: false, error: err.message });
                }
            };
            script.onerror = () => resolve({ success: false, error: 'Failed to load livekit-client from CDN' });
            document.head.appendChild(script);
        });
    }, { token });

    console.log('LiveKit connection result:', JSON.stringify(result, null, 2));
    await browser.close();
}

testLiveKit();
