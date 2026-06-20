import puppeteer from 'puppeteer';

const browser = await puppeteer.launch({ headless: true });
const page = await browser.newPage();
await page.setViewport({ width: 1280, height: 900 });
await page.goto('http://127.0.0.1:8000/tutors', { waitUntil: 'networkidle0' });

// scroll to footer
await page.evaluate(() => {
  const f = document.querySelector('footer.ed-footer');
  f.scrollIntoView({ block: 'start' });
  window.scrollBy(0, -200);
});
await new Promise((r) => setTimeout(r, 300));

await page.screenshot({ path: 'screenshots/footer-cta-gap.png' });
console.log('saved');
await browser.close();
