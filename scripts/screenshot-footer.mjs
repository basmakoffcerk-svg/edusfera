import puppeteer from 'puppeteer';

const browser = await puppeteer.launch({ headless: true });
const page = await browser.newPage();
await page.setViewport({ width: 1280, height: 900 });
await page.goto('http://127.0.0.1:8000/tutors', { waitUntil: 'networkidle0' });

const footer = await page.$('footer.ed-footer');
await footer.scrollIntoView();
await new Promise((r) => setTimeout(r, 300));
await footer.screenshot({ path: 'screenshots/footer-tutors.png' });

console.log('saved screenshots/footer-tutors.png');
await browser.close();
