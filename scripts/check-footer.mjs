import puppeteer from 'puppeteer';

const browser = await puppeteer.launch({ headless: true });
const page = await browser.newPage();
await page.setViewport({ width: 1280, height: 900 });
await page.goto('http://127.0.0.1:8000/tutors', { waitUntil: 'networkidle0' });

const data = await page.evaluate(() => {
  const footer = document.querySelector('footer.ed-footer');
  if (!footer) return { error: 'no footer' };

  const inner = footer.querySelector('.ed-footer__inner');
  const grid = footer.querySelector('.ed-footer__grid');
  const list = footer.querySelector('.ed-footer__col-list');
  const liItems = footer.querySelectorAll('.ed-footer__col-list li');
  const links = footer.querySelectorAll('.ed-footer__col-list a');
  const title = footer.querySelector('.ed-footer__col-title');
  const brandText = footer.querySelector('.ed-footer__brand-text');
  const status = footer.querySelector('.ed-footer__status');

  const cs = (el) => {
    if (!el) return null;
    const s = getComputedStyle(el);
    const r = el.getBoundingClientRect();
    return {
      tag: el.tagName,
      class: el.className.toString(),
      margin: s.margin,
      padding: s.padding,
      display: s.display,
      gap: s.gap,
      rowGap: s.rowGap,
      gridTemplateColumns: s.gridTemplateColumns,
      width: r.width,
      height: r.height,
      fontSize: s.fontSize,
    };
  };

  return {
    footerWidth: footer.getBoundingClientRect().width,
    inner: cs(inner),
    grid: cs(grid),
    title: cs(title),
    brandText: cs(brandText),
    status: cs(status),
    list: cs(list),
    liCount: liItems.length,
    li1: cs(liItems[1]),
    li2: cs(liItems[2]),
    a1: cs(links[0]),
  };
});

console.log(JSON.stringify(data, null, 2));
await browser.close();
