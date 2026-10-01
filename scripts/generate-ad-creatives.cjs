const { chromium } = require('playwright');
const path = require('path');
const fs = require('fs');

const OUT_DIR = path.resolve(__dirname, '../ad-assets/04_social_ad_creatives');

// Helper to convert any image file to base64 Data URI
function getBase64Image(filePath) {
    if (!fs.existsSync(filePath)) {
        console.warn(`⚠️ Warning: Image file not found: ${filePath}`);
        return '';
    }
    const ext = path.extname(filePath).toLowerCase();
    const mime = ext === '.svg' ? 'image/svg+xml' : ext === '.jpg' || ext === '.jpeg' ? 'image/jpeg' : 'image/png';
    const data = fs.readFileSync(filePath).toString('base64');
    return `data:${mime};base64,${data}`;
}

// Find existing image from multiple candidates
function findFirstExistingBase64(candidates) {
    for (const c of candidates) {
        const fullPath = path.resolve(__dirname, '..', c);
        if (fs.existsSync(fullPath)) {
            return getBase64Image(fullPath);
        }
    }
    return '';
}

async function generate() {
    fs.mkdirSync(OUT_DIR, { recursive: true });
    console.log('🎨 Запуск генерации рекламных креативов (без CORS/broken images)...');

    // Preload screenshot images as base64
    const tutorMobileImg = findFirstExistingBase64([
        'ad-assets/03_mobile_screens/04_mobile_for_tutors_hero.png',
        'ad-assets/02_mobile_screens/02_for_tutors_mobile.png',
        'ad-assets/03_mobile_screens/01_mobile_home_hero_aurora.png'
    ]);

    const diagnosticMobileImg = findFirstExistingBase64([
        'ad-assets/03_mobile_screens/10_mobile_diagnostic_intro.png',
        'ad-assets/02_mobile_screens/03_ai_diagnostic_mobile.png',
        'ad-assets/03_mobile_screens/01_mobile_home_hero_aurora.png'
    ]);

    const catalogMobileImg = findFirstExistingBase64([
        'ad-assets/03_mobile_screens/08_mobile_catalog_list.png',
        'ad-assets/02_mobile_screens/04_tutors_catalog_mobile.png'
    ]);

    const desktopHeroImg = findFirstExistingBase64([
        'ad-assets/02_desktop_key_blocks/01_home_hero_first_screen.png',
        'ad-assets/01_platform_screens/01_home_hero_desktop.png',
        'ad-assets/01_desktop_full_pages/01_home_page_full.png'
    ]);

    const desktopTutorsHeroImg = findFirstExistingBase64([
        'ad-assets/02_desktop_key_blocks/07_for_tutors_hero.png',
        'ad-assets/01_platform_screens/03_for_tutors_desktop.png'
    ]);

    const BANNER_TEMPLATES = [
        // 1. Stories / Reels 9:16 (1080x1920) — Репетиторам
        {
            name: '01_story_for_tutors_9x16.png',
            width: 1080,
            height: 1920,
            html: `
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset="utf-8">
                <link href="https://fonts.googleapis.com/css2?family=Geist:wght@400;600;700;800;900&family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
                <style>
                    * { box-sizing: border-box; margin: 0; padding: 0; }
                    body {
                        width: 1080px; height: 1920px; background: #050508; color: #fff;
                        font-family: 'Geist', sans-serif; display: flex; flex-direction: column;
                        justify-content: space-between; padding: 90px 70px 80px; position: relative; overflow: hidden;
                    }
                    .glow-orb-1 { position: absolute; top: -100px; left: -100px; width: 600px; height: 600px; background: radial-gradient(circle, rgba(125,57,235,0.4) 0%, transparent 70%); border-radius: 50%; filter: blur(80px); }
                    .glow-orb-2 { position: absolute; bottom: 200px; right: -150px; width: 700px; height: 700px; background: radial-gradient(circle, rgba(198,255,51,0.25) 0%, transparent 70%); border-radius: 50%; filter: blur(100px); }
                    .grid-bg { position: absolute; inset: 0; background-size: 40px 40px; background-image: linear-gradient(to right, rgba(255,255,255,0.03) 1px, transparent 1px), linear-gradient(to bottom, rgba(255,255,255,0.03) 1px, transparent 1px); }
                    
                    .header { position: relative; z-index: 10; display: flex; align-items: center; justify-content: space-between; }
                    .brand { display: flex; align-items: center; gap: 16px; }
                    .brand-logo { width: 56px; height: 56px; border-radius: 16px; background: #7D39EB; display: flex; align-items: center; justify-content: center; }
                    .brand-name { font-size: 32px; font-weight: 900; letter-spacing: 2px; }
                    .badge { background: rgba(198,255,51,0.15); border: 1.5px solid rgba(198,255,51,0.4); color: #C6FF33; padding: 10px 22px; border-radius: 99px; font-weight: 800; font-size: 20px; }

                    .content { position: relative; z-index: 10; margin-top: 35px; }
                    .tag { display: inline-flex; align-items: center; gap: 10px; padding: 10px 24px; border-radius: 99px; background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15); font-size: 20px; font-weight: 700; color: #cbd5e1; margin-bottom: 24px; }
                    .tag-dot { width: 10px; height: 10px; border-radius: 50%; background: #C6FF33; }
                    h1 { font-size: 68px; line-height: 1.08; font-weight: 900; letter-spacing: -1.5px; margin-bottom: 22px; }
                    h1 span { color: #C6FF33; text-shadow: 0 0 35px rgba(198,255,51,0.4); }
                    p.lead { font-size: 26px; line-height: 1.45; color: #94a3b8; font-weight: 400; max-width: 900px; }

                    .phone-frame {
                        position: relative; z-index: 10; margin: 30px auto 0;
                        width: 600px; height: 720px; border-radius: 44px;
                        border: 5px solid rgba(255,255,255,0.2);
                        box-shadow: 0 30px 90px rgba(0,0,0,0.85), 0 0 60px rgba(125,57,235,0.35);
                        overflow: hidden; background: #0c0d14;
                    }
                    .phone-speaker { position: absolute; top: 14px; left: 50%; transform: translateX(-50%); width: 110px; height: 16px; background: #18181b; border-radius: 99px; z-index: 20; }
                    .phone-screen { width: 100%; height: 100%; object-fit: cover; object-position: top center; display: block; }

                    .footer-cta { position: relative; z-index: 10; margin-top: 35px; background: rgba(255,255,255,0.03); border: 1.5px solid rgba(255,255,255,0.1); border-radius: 32px; padding: 32px 42px; display: flex; align-items: center; justify-content: space-between; }
                    .cta-left h3 { font-size: 30px; font-weight: 800; margin-bottom: 6px; }
                    .cta-left p { font-size: 20px; color: #a1a1aa; }
                    .cta-btn { background: #C6FF33; color: #000; padding: 20px 40px; border-radius: 20px; font-size: 24px; font-weight: 900; text-transform: uppercase; letter-spacing: 0.5px; }
                </style>
            </head>
            <body>
                <div class="glow-orb-1"></div>
                <div class="glow-orb-2"></div>
                <div class="grid-bg"></div>

                <div class="header">
                    <div class="brand">
                        <div class="brand-logo">
                            <svg width="32" height="32" viewBox="0 0 64 64"><path d="M32 10L54 32L32 54L10 32L32 10Z" fill="none" stroke="#C6FF33" stroke-width="6"/><path d="M32 22L42 32L32 42L22 32L32 22Z" fill="#C6FF33"/></svg>
                        </div>
                        <span class="brand-name">EDUSFERA</span>
                    </div>
                    <div class="badge">РЕПЕТИТОРАМ 2026</div>
                </div>

                <div class="content">
                    <div class="tag"><span class="tag-dot"></span> 0 BYN КОМИССИИ ЗА УРОКИ</div>
                    <h1>Больше не ищите учеников. <span>Преподавайте.</span></h1>
                    <p class="lead">Платформа приводит заявки от родителей, ведёт расписание, напоминает об уроках и автоматически выгружает чеки НПД.</p>
                </div>

                <div class="phone-frame">
                    <div class="phone-speaker"></div>
                    <img class="phone-screen" src="${tutorMobileImg}">
                </div>

                <div class="footer-cta">
                    <div class="cta-left">
                        <h3>Первый месяц — 0 BYN</h3>
                        <p>Подключение за 2 минуты без кредитных карт</p>
                    </div>
                    <div class="cta-btn">В СТОРИС ↗</div>
                </div>
            </body>
            </html>
            `
        },

        // 2. Stories / Reels 9:16 (1080x1920) — Родителям и абитуриентам (ИИ-Диагностика)
        {
            name: '02_story_for_students_9x16.png',
            width: 1080,
            height: 1920,
            html: `
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset="utf-8">
                <link href="https://fonts.googleapis.com/css2?family=Geist:wght@400;600;700;800;900&family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
                <style>
                    * { box-sizing: border-box; margin: 0; padding: 0; }
                    body {
                        width: 1080px; height: 1920px; background: #050508; color: #fff;
                        font-family: 'Geist', sans-serif; display: flex; flex-direction: column;
                        justify-content: space-between; padding: 90px 70px 80px; position: relative; overflow: hidden;
                    }
                    .glow-orb-1 { position: absolute; top: -100px; right: -100px; width: 650px; height: 650px; background: radial-gradient(circle, rgba(198,255,51,0.3) 0%, transparent 70%); border-radius: 50%; filter: blur(90px); }
                    .glow-orb-2 { position: absolute; bottom: 300px; left: -100px; width: 650px; height: 650px; background: radial-gradient(circle, rgba(125,57,235,0.4) 0%, transparent 70%); border-radius: 50%; filter: blur(100px); }
                    .grid-bg { position: absolute; inset: 0; background-size: 40px 40px; background-image: linear-gradient(to right, rgba(255,255,255,0.03) 1px, transparent 1px), linear-gradient(to bottom, rgba(255,255,255,0.03) 1px, transparent 1px); }
                    
                    .header { position: relative; z-index: 10; display: flex; align-items: center; justify-content: space-between; }
                    .brand { display: flex; align-items: center; gap: 16px; }
                    .brand-logo { width: 56px; height: 56px; border-radius: 16px; background: #7D39EB; display: flex; align-items: center; justify-content: center; }
                    .brand-name { font-size: 32px; font-weight: 900; letter-spacing: 2px; }
                    .badge { background: rgba(125,57,235,0.25); border: 1.5px solid rgba(125,57,235,0.5); color: #c49aff; padding: 10px 22px; border-radius: 99px; font-weight: 800; font-size: 20px; }

                    .content { position: relative; z-index: 10; margin-top: 35px; }
                    .tag { display: inline-flex; align-items: center; gap: 10px; padding: 10px 24px; border-radius: 99px; background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15); font-size: 20px; font-weight: 700; color: #cbd5e1; margin-bottom: 24px; }
                    .tag-dot { width: 10px; height: 10px; border-radius: 50%; background: #38bdf8; }
                    h1 { font-size: 68px; line-height: 1.08; font-weight: 900; letter-spacing: -1.5px; margin-bottom: 22px; }
                    h1 span { color: #C6FF33; text-shadow: 0 0 35px rgba(198,255,51,0.4); }
                    p.lead { font-size: 26px; line-height: 1.45; color: #94a3b8; font-weight: 400; max-width: 900px; }

                    .phone-frame {
                        position: relative; z-index: 10; margin: 30px auto 0;
                        width: 600px; height: 720px; border-radius: 44px;
                        border: 5px solid rgba(255,255,255,0.2);
                        box-shadow: 0 30px 90px rgba(0,0,0,0.85), 0 0 60px rgba(56,189,248,0.35);
                        overflow: hidden; background: #0c0d14;
                    }
                    .phone-speaker { position: absolute; top: 14px; left: 50%; transform: translateX(-50%); width: 110px; height: 16px; background: #18181b; border-radius: 99px; z-index: 20; }
                    .phone-screen { width: 100%; height: 100%; object-fit: cover; object-position: top center; display: block; }

                    .footer-cta { position: relative; z-index: 10; margin-top: 35px; background: rgba(255,255,255,0.03); border: 1.5px solid rgba(255,255,255,0.1); border-radius: 32px; padding: 32px 42px; display: flex; align-items: center; justify-content: space-between; }
                    .cta-left h3 { font-size: 30px; font-weight: 800; margin-bottom: 6px; }
                    .cta-left p { font-size: 20px; color: #a1a1aa; }
                    .cta-btn { background: #7D39EB; color: #fff; padding: 20px 40px; border-radius: 20px; font-size: 24px; font-weight: 900; text-transform: uppercase; letter-spacing: 0.5px; }
                </style>
            </head>
            <body>
                <div class="glow-orb-1"></div>
                <div class="glow-orb-2"></div>
                <div class="grid-bg"></div>

                <div class="header">
                    <div class="brand">
                        <div class="brand-logo">
                            <svg width="32" height="32" viewBox="0 0 64 64"><path d="M32 10L54 32L32 54L10 32L32 10Z" fill="none" stroke="#C6FF33" stroke-width="6"/><path d="M32 22L42 32L32 42L22 32L32 22Z" fill="#C6FF33"/></svg>
                        </div>
                        <span class="brand-name">EDUSFERA</span>
                    </div>
                    <div class="badge">СТАНДАРТ РИКЗ 2026</div>
                </div>

                <div class="content">
                    <div class="tag"><span class="tag-dot"></span> ТЕСТИРОВАНИЕ ПО МАТЕМАТИКЕ, ФИЗИКЕ, ЯЗЫКАМ</div>
                    <h1>Какой реальный балл на ЦТ/ЦЭ? <span>Узнайте за 15 мин.</span></h1>
                    <p class="lead">Нейросеть выявит пробелы школьной программы и подберет топ-репетитора для гарантированного поступления на бюджет.</p>
                </div>

                <div class="phone-frame">
                    <div class="phone-speaker"></div>
                    <img class="phone-screen" src="${diagnosticMobileImg}">
                </div>

                <div class="footer-cta">
                    <div class="cta-left">
                        <h3>Бесплатный онлайн-тест</h3>
                        <p>Мгновенный персональный отчёт с разбором тем</p>
                    </div>
                    <div class="cta-btn">ПРОЙТИ ТЕСТ ↗</div>
                </div>
            </body>
            </html>
            `
        },

        // 3. Stories / Reels 9:16 (1080x1920) — Каталог топ-репетиторов Беларуси
        {
            name: '03_story_catalog_tutors_9x16.png',
            width: 1080,
            height: 1920,
            html: `
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset="utf-8">
                <link href="https://fonts.googleapis.com/css2?family=Geist:wght@400;600;700;800;900&family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
                <style>
                    * { box-sizing: border-box; margin: 0; padding: 0; }
                    body {
                        width: 1080px; height: 1920px; background: #050508; color: #fff;
                        font-family: 'Geist', sans-serif; display: flex; flex-direction: column;
                        justify-content: space-between; padding: 90px 70px 80px; position: relative; overflow: hidden;
                    }
                    .glow-orb-1 { position: absolute; top: -100px; left: -100px; width: 650px; height: 650px; background: radial-gradient(circle, rgba(125,57,235,0.4) 0%, transparent 70%); filter: blur(90px); }
                    .glow-orb-2 { position: absolute; bottom: 250px; right: -100px; width: 650px; height: 650px; background: radial-gradient(circle, rgba(198,255,51,0.25) 0%, transparent 70%); filter: blur(100px); }
                    .grid-bg { position: absolute; inset: 0; background-size: 40px 40px; background-image: linear-gradient(to right, rgba(255,255,255,0.03) 1px, transparent 1px), linear-gradient(to bottom, rgba(255,255,255,0.03) 1px, transparent 1px); }
                    
                    .header { position: relative; z-index: 10; display: flex; align-items: center; justify-content: space-between; }
                    .brand { display: flex; align-items: center; gap: 16px; }
                    .brand-logo { width: 56px; height: 56px; border-radius: 16px; background: #7D39EB; display: flex; align-items: center; justify-content: center; }
                    .brand-name { font-size: 32px; font-weight: 900; letter-spacing: 2px; }
                    .badge { background: rgba(198,255,51,0.15); border: 1.5px solid rgba(198,255,51,0.4); color: #C6FF33; padding: 10px 22px; border-radius: 99px; font-weight: 800; font-size: 20px; }

                    .content { position: relative; z-index: 10; margin-top: 35px; }
                    .tag { display: inline-flex; align-items: center; gap: 10px; padding: 10px 24px; border-radius: 99px; background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15); font-size: 20px; font-weight: 700; color: #cbd5e1; margin-bottom: 24px; }
                    .tag-dot { width: 10px; height: 10px; border-radius: 50%; background: #C6FF33; }
                    h1 { font-size: 68px; line-height: 1.08; font-weight: 900; letter-spacing: -1.5px; margin-bottom: 22px; }
                    h1 span { color: #C6FF33; text-shadow: 0 0 35px rgba(198,255,51,0.4); }
                    p.lead { font-size: 26px; line-height: 1.45; color: #94a3b8; font-weight: 400; max-width: 900px; }

                    .phone-frame {
                        position: relative; z-index: 10; margin: 30px auto 0;
                        width: 600px; height: 720px; border-radius: 44px;
                        border: 5px solid rgba(255,255,255,0.2);
                        box-shadow: 0 30px 90px rgba(0,0,0,0.85), 0 0 60px rgba(198,255,51,0.25);
                        overflow: hidden; background: #0c0d14;
                    }
                    .phone-speaker { position: absolute; top: 14px; left: 50%; transform: translateX(-50%); width: 110px; height: 16px; background: #18181b; border-radius: 99px; z-index: 20; }
                    .phone-screen { width: 100%; height: 100%; object-fit: cover; object-position: top center; display: block; }

                    .footer-cta { position: relative; z-index: 10; margin-top: 35px; background: rgba(255,255,255,0.03); border: 1.5px solid rgba(255,255,255,0.1); border-radius: 32px; padding: 32px 42px; display: flex; align-items: center; justify-content: space-between; }
                    .cta-left h3 { font-size: 30px; font-weight: 800; margin-bottom: 6px; }
                    .cta-left p { font-size: 20px; color: #a1a1aa; }
                    .cta-btn { background: #C6FF33; color: #000; padding: 20px 40px; border-radius: 20px; font-size: 24px; font-weight: 900; text-transform: uppercase; letter-spacing: 0.5px; }
                </style>
            </head>
            <body>
                <div class="glow-orb-1"></div>
                <div class="glow-orb-2"></div>
                <div class="grid-bg"></div>

                <div class="header">
                    <div class="brand">
                        <div class="brand-logo">
                            <svg width="32" height="32" viewBox="0 0 64 64"><path d="M32 10L54 32L32 54L10 32L32 10Z" fill="none" stroke="#C6FF33" stroke-width="6"/><path d="M32 22L42 32L32 42L22 32L32 22Z" fill="#C6FF33"/></svg>
                        </div>
                        <span class="brand-name">EDUSFERA</span>
                    </div>
                    <div class="badge">БЕЛАРУСЬ 2026</div>
                </div>

                <div class="content">
                    <div class="tag"><span class="tag-dot"></span> ПРОВЕРЕННЫЕ РЕПЕТИТОРЫ С РЕЙТИНГОМ</div>
                    <h1>Найдите идеального преподавателя. <span>Без переплат.</span></h1>
                    <p class="lead">Рейтинг по баллам ЦТ учеников, видео-визитки, открытые отзывы и прямая связь в один клик.</p>
                </div>

                <div class="phone-frame">
                    <div class="phone-speaker"></div>
                    <img class="phone-screen" src="${catalogMobileImg || tutorMobileImg}">
                </div>

                <div class="footer-cta">
                    <div class="cta-left">
                        <h3>Смотреть анкеты репетиторов</h3>
                        <p>Бесплатный подбор за 2 минуты</p>
                    </div>
                    <div class="cta-btn">ВЫБРАТЬ ↗</div>
                </div>
            </body>
            </html>
            `
        },

        // 4. Square Post 1:1 (1080x1080) — Instagram/VK/Telegram
        {
            name: '04_square_ad_tutors_1x1.png',
            width: 1080,
            height: 1080,
            html: `
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset="utf-8">
                <link href="https://fonts.googleapis.com/css2?family=Geist:wght@400;600;700;800;900&display=swap" rel="stylesheet">
                <style>
                    * { box-sizing: border-box; margin: 0; padding: 0; }
                    body {
                        width: 1080px; height: 1080px; background: #050508; color: #fff;
                        font-family: 'Geist', sans-serif; display: flex; flex-direction: column;
                        justify-content: space-between; padding: 70px 70px 60px; position: relative; overflow: hidden;
                    }
                    .glow { position: absolute; top: 150px; right: -100px; width: 600px; height: 600px; background: radial-gradient(circle, rgba(198,255,51,0.2) 0%, rgba(125,57,235,0.2) 50%, transparent 70%); filter: blur(90px); }
                    .grid-bg { position: absolute; inset: 0; background-size: 32px 32px; background-image: linear-gradient(to right, rgba(255,255,255,0.03) 1px, transparent 1px), linear-gradient(to bottom, rgba(255,255,255,0.03) 1px, transparent 1px); }
                    
                    .header { position: relative; z-index: 10; display: flex; align-items: center; justify-content: space-between; }
                    .brand { display: flex; align-items: center; gap: 14px; font-size: 26px; font-weight: 900; letter-spacing: 1.5px; }
                    .logo-box { width: 44px; height: 44px; background: #7D39EB; border-radius: 12px; display: flex; align-items: center; justify-content: center; }
                    .alfa-tag { font-size: 15px; color: #a1a1aa; display: flex; align-items: center; gap: 8px; font-weight: 600; }

                    .main-box { position: relative; z-index: 10; margin-top: 30px; }
                    .badge { display: inline-block; background: rgba(198,255,51,0.15); border: 1.5px solid rgba(198,255,51,0.4); color: #C6FF33; padding: 8px 18px; border-radius: 99px; font-weight: 800; font-size: 16px; margin-bottom: 20px; }
                    h1 { font-size: 58px; font-weight: 900; line-height: 1.12; margin-bottom: 24px; letter-spacing: -1px; }
                    h1 span { color: #C6FF33; }
                    
                    .grid-props { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-top: 36px; }
                    .prop-card { background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.1); border-radius: 20px; padding: 22px 24px; }
                    .prop-val { font-size: 32px; font-weight: 900; color: #fff; margin-bottom: 4px; font-family: monospace; }
                    .prop-val.green { color: #C6FF33; }
                    .prop-desc { font-size: 16px; color: #a1a1aa; line-height: 1.35; }

                    .bottom-bar { position: relative; z-index: 10; display: flex; align-items: center; justify-content: space-between; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 30px; }
                    .btm-info { font-size: 18px; color: #cbd5e1; font-weight: 600; }
                    .btm-btn { background: #C6FF33; color: #000; font-weight: 900; padding: 16px 36px; border-radius: 16px; font-size: 18px; }
                </style>
            </head>
            <body>
                <div class="glow"></div>
                <div class="grid-bg"></div>

                <div class="header">
                    <div class="brand">
                        <div class="logo-box">
                            <svg width="24" height="24" viewBox="0 0 64 64"><path d="M32 10L54 32L32 54L10 32L32 10Z" fill="none" stroke="#C6FF33" stroke-width="6"/><path d="M32 22L42 32L32 42L22 32L32 22Z" fill="#C6FF33"/></svg>
                        </div>
                        <span>EDUSFERA</span>
                    </div>
                    <div class="alfa-tag">Эквайринг ЗАО «Альфа-Банк» · РБ</div>
                </div>

                <div class="main-box">
                    <div class="badge">РЕПЕТИТОРАМ БЕЛАРУСИ</div>
                    <h1>Зарабатывайте больше. <br><span>Без комиссий за уроки.</span></h1>
                    
                    <div class="grid-props">
                        <div class="prop-card">
                            <div class="prop-val green">0 BYN</div>
                            <div class="prop-desc">100% стоимости каждого урока остаётся вам</div>
                        </div>
                        <div class="prop-card">
                            <div class="prop-val">Авто-НПД</div>
                            <div class="prop-desc">Автоматическое формирование чеков в МНС РБ</div>
                        </div>
                        <div class="prop-card">
                            <div class="prop-val green">30 дней</div>
                            <div class="prop-desc">Бесплатный ознакомительный период в подарок</div>
                        </div>
                        <div class="prop-card">
                            <div class="prop-val">-90%</div>
                            <div class="prop-desc">Срывов уроков благодаря PWA-напоминаниям</div>
                        </div>
                    </div>
                </div>

                <div class="bottom-bar">
                    <div class="btm-info">edusfera.by/for-tutors · Минск, РБ</div>
                    <div class="btm-btn">ПОДКЛЮЧИТЬСЯ →</div>
                </div>
            </body>
            </html>
            `
        },

        // 5. Square Post 1:1 (1080x1080) — Для родителей и учеников
        {
            name: '05_square_ad_students_1x1.png',
            width: 1080,
            height: 1080,
            html: `
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset="utf-8">
                <link href="https://fonts.googleapis.com/css2?family=Geist:wght@400;600;700;800;900&display=swap" rel="stylesheet">
                <style>
                    * { box-sizing: border-box; margin: 0; padding: 0; }
                    body {
                        width: 1080px; height: 1080px; background: #050508; color: #fff;
                        font-family: 'Geist', sans-serif; display: flex; flex-direction: column;
                        justify-content: space-between; padding: 70px 70px 60px; position: relative; overflow: hidden;
                    }
                    .glow { position: absolute; top: 120px; left: -100px; width: 600px; height: 600px; background: radial-gradient(circle, rgba(125,57,235,0.3) 0%, rgba(56,189,248,0.2) 50%, transparent 70%); filter: blur(90px); }
                    .grid-bg { position: absolute; inset: 0; background-size: 32px 32px; background-image: linear-gradient(to right, rgba(255,255,255,0.03) 1px, transparent 1px), linear-gradient(to bottom, rgba(255,255,255,0.03) 1px, transparent 1px); }
                    
                    .header { position: relative; z-index: 10; display: flex; align-items: center; justify-content: space-between; }
                    .brand { display: flex; align-items: center; gap: 14px; font-size: 26px; font-weight: 900; letter-spacing: 1.5px; }
                    .logo-box { width: 44px; height: 44px; background: #7D39EB; border-radius: 12px; display: flex; align-items: center; justify-content: center; }
                    .alfa-tag { font-size: 15px; color: #a1a1aa; font-weight: 600; }

                    .main-box { position: relative; z-index: 10; margin-top: 30px; }
                    .badge { display: inline-block; background: rgba(125,57,235,0.2); border: 1.5px solid rgba(125,57,235,0.5); color: #c49aff; padding: 8px 18px; border-radius: 99px; font-weight: 800; font-size: 16px; margin-bottom: 20px; }
                    h1 { font-size: 56px; font-weight: 900; line-height: 1.12; margin-bottom: 24px; letter-spacing: -1px; }
                    h1 span { color: #C6FF33; }
                    
                    .grid-props { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-top: 36px; }
                    .prop-card { background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.1); border-radius: 20px; padding: 22px 24px; }
                    .prop-val { font-size: 32px; font-weight: 900; color: #fff; margin-bottom: 4px; font-family: monospace; }
                    .prop-val.green { color: #C6FF33; }
                    .prop-val.purple { color: #c49aff; }
                    .prop-desc { font-size: 16px; color: #a1a1aa; line-height: 1.35; }

                    .bottom-bar { position: relative; z-index: 10; display: flex; align-items: center; justify-content: space-between; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 30px; }
                    .btm-info { font-size: 18px; color: #cbd5e1; font-weight: 600; }
                    .btm-btn { background: #7D39EB; color: #fff; font-weight: 900; padding: 16px 36px; border-radius: 16px; font-size: 18px; }
                </style>
            </head>
            <body>
                <div class="glow"></div>
                <div class="grid-bg"></div>

                <div class="header">
                    <div class="brand">
                        <div class="logo-box">
                            <svg width="24" height="24" viewBox="0 0 64 64"><path d="M32 10L54 32L32 54L10 32L32 10Z" fill="none" stroke="#C6FF33" stroke-width="6"/><path d="M32 22L42 32L32 42L22 32L32 22Z" fill="#C6FF33"/></svg>
                        </div>
                        <span>EDUSFERA</span>
                    </div>
                    <div class="alfa-tag">ЦТ / ЦЭ 2026 · Беларусь</div>
                </div>

                <div class="main-box">
                    <div class="badge">ПОСТУПЛЕНИЕ НА БЮДЖЕТ</div>
                    <h1>Умная подготовка. <br><span>С гарантией результата.</span></h1>
                    
                    <div class="grid-props">
                        <div class="prop-card">
                            <div class="prop-val green">86+</div>
                            <div class="prop-desc">Средний балл наших учеников на ЦТ 2025 года</div>
                        </div>
                        <div class="prop-card">
                            <div class="prop-val purple">15 минут</div>
                            <div class="prop-desc">Бесплатная ИИ-диагностика реальных пробелов</div>
                        </div>
                        <div class="prop-card">
                            <div class="prop-val">100%</div>
                            <div class="prop-desc">Проверенные дипломы и квалификация репетиторов</div>
                        </div>
                        <div class="prop-card">
                            <div class="prop-val green">0 BYN</div>
                            <div class="prop-desc">Первый пробный урок с выбранным наставником</div>
                        </div>
                    </div>
                </div>

                <div class="bottom-bar">
                    <div class="btm-info">edusfera.by · Минск, ул. В. Хоружей 6А</div>
                    <div class="btm-btn">ВЫБРАТЬ РЕПЕТИТОРА →</div>
                </div>
            </body>
            </html>
            `
        },

        // 6. Landscape Banner 16:9 (1200x630) — Facebook / VK / Display Ads
        {
            name: '06_landscape_banner_16x9.png',
            width: 1200,
            height: 630,
            html: `
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset="utf-8">
                <link href="https://fonts.googleapis.com/css2?family=Geist:wght@400;600;700;800;900&display=swap" rel="stylesheet">
                <style>
                    * { box-sizing: border-box; margin: 0; padding: 0; }
                    body {
                        width: 1200px; height: 630px; background: #050508; color: #fff;
                        font-family: 'Geist', sans-serif; display: flex; flex-direction: column;
                        justify-content: space-between; padding: 45px 55px 40px; position: relative; overflow: hidden;
                    }
                    .glow-left { position: absolute; top: -50px; left: -50px; width: 450px; height: 450px; background: radial-gradient(circle, rgba(125,57,235,0.35) 0%, transparent 70%); filter: blur(80px); }
                    .glow-right { position: absolute; bottom: -50px; right: 250px; width: 500px; height: 500px; background: radial-gradient(circle, rgba(198,255,51,0.2) 0%, transparent 70%); filter: blur(90px); }
                    .grid-bg { position: absolute; inset: 0; background-size: 32px 32px; background-image: linear-gradient(to right, rgba(255,255,255,0.03) 1px, transparent 1px), linear-gradient(to bottom, rgba(255,255,255,0.03) 1px, transparent 1px); }

                    .top-row { position: relative; z-index: 10; display: flex; align-items: center; justify-content: space-between; }
                    .brand { display: flex; align-items: center; gap: 12px; font-size: 24px; font-weight: 900; }
                    .logo-icon { width: 38px; height: 38px; background: #7D39EB; border-radius: 10px; display: flex; align-items: center; justify-content: center; }
                    .badge { background: rgba(198,255,51,0.15); border: 1px solid rgba(198,255,51,0.4); color: #C6FF33; padding: 6px 16px; border-radius: 99px; font-weight: 700; font-size: 14px; }

                    .mid-row { position: relative; z-index: 10; display: flex; align-items: center; justify-content: space-between; gap: 36px; margin-top: 10px; }
                    .left-col { max-width: 580px; }
                    h1 { font-size: 44px; font-weight: 900; line-height: 1.12; margin-bottom: 16px; letter-spacing: -0.5px; }
                    h1 span { color: #C6FF33; }
                    p.desc { font-size: 18px; color: #94a3b8; line-height: 1.45; margin-bottom: 22px; }

                    .badges-row { display: flex; gap: 12px; }
                    .b-item { background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; padding: 8px 16px; font-size: 14px; font-weight: 600; color: #e2e8f0; }

                    .browser-mockup {
                        width: 480px; height: 290px; border-radius: 18px;
                        border: 2px solid rgba(255,255,255,0.15); overflow: hidden;
                        box-shadow: 0 20px 50px rgba(0,0,0,0.85), 0 0 40px rgba(125,57,235,0.25);
                        background: #0f1016; display: flex; flex-direction: column; flex-shrink: 0;
                    }
                    .browser-header { height: 28px; background: rgba(255,255,255,0.06); display: flex; align-items: center; padding: 0 12px; gap: 6px; border-bottom: 1px solid rgba(255,255,255,0.08); }
                    .dot { width: 8px; height: 8px; border-radius: 50%; background: rgba(255,255,255,0.2); }
                    .browser-body { flex: 1; overflow: hidden; }
                    .browser-body img { width: 100%; height: 100%; object-fit: cover; object-position: top center; display: block; }

                    .btm-row { position: relative; z-index: 10; display: flex; align-items: center; justify-content: space-between; border-top: 1px solid rgba(255,255,255,0.08); padding-top: 18px; }
                    .btm-text { font-size: 14px; color: #64748b; }
                    .cta-btn { background: #C6FF33; color: #000; font-weight: 900; padding: 12px 28px; border-radius: 12px; font-size: 16px; }
                </style>
            </head>
            <body>
                <div class="glow-left"></div>
                <div class="glow-right"></div>
                <div class="grid-bg"></div>

                <div class="top-row">
                    <div class="brand">
                        <div class="logo-icon">
                            <svg width="22" height="22" viewBox="0 0 64 64"><path d="M32 10L54 32L32 54L10 32L32 10Z" fill="none" stroke="#C6FF33" stroke-width="6"/><path d="M32 22L42 32L32 42L22 32L32 22Z" fill="#C6FF33"/></svg>
                        </div>
                        <span>EDUSFERA</span>
                    </div>
                    <div class="badge">БЕЛОРУССКИЙ EDTECH 2026</div>
                </div>

                <div class="mid-row">
                    <div class="left-col">
                        <h1>Умная подготовка к ЦТ/ЦЭ <span>с ИИ и топ-репетиторами</span></h1>
                        <p class="desc">Точная диагностика пробелов по стандартам РИКЗ, встроенный виртуальный класс и безопасная оплата уроков.</p>
                        <div class="badges-row">
                            <div class="b-item">⚡ 86+ средний балл</div>
                            <div class="b-item">🛡️ Альфа-Банк эквайринг</div>
                            <div class="b-item">🎯 РИКЗ 2026</div>
                        </div>
                    </div>

                    <div class="browser-mockup">
                        <div class="browser-header">
                            <div class="dot" style="background:#ef4444"></div>
                            <div class="dot" style="background:#eab308"></div>
                            <div class="dot" style="background:#22c55e"></div>
                        </div>
                        <div class="browser-body">
                            <img src="${desktopHeroImg}">
                        </div>
                    </div>
                </div>

                <div class="btm-row">
                    <div class="btm-text">ООО «Эдусфера» · УНП 192854899 · Минск, ул. Веры Хоружей, 6А</div>
                    <div class="cta-btn">ПЕРЕЙТИ НА САЙТ →</div>
                </div>
            </body>
            </html>
            `
        }
    ];

    const browser = await chromium.launch({
        executablePath: '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
        headless: true
    });

    for (const t of BANNER_TEMPLATES) {
        console.log(`  🖼️ Рендеринг ${t.name} (${t.width}x${t.height})...`);
        const page = await browser.newPage({
            viewport: { width: t.width, height: t.height },
            deviceScaleFactor: 2
        });

        await page.setContent(t.html, { waitUntil: 'load' });
        await page.waitForTimeout(600);

        const outPath = path.join(OUT_DIR, t.name);
        await page.screenshot({ path: outPath });
        console.log(`     ✓ Сохранен рекламный креатив: ${outPath}`);
        await page.close();
    }

    await browser.close();
    console.log('\n✅ Все рекламные креативы успешно созданы с кристально чистыми превью (без черных квадратов)!');
}

generate().catch(err => {
    console.error('Fatal error in banner generator:', err);
    process.exit(1);
});
