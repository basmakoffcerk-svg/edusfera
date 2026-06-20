import fs from 'node:fs';
import path from 'node:path';
import puppeteer from 'puppeteer';

const baseUrl = process.env.BASE_URL || 'http://127.0.0.1:8000';
const stamp = new Date().toISOString().replace(/[-:]/g, '').replace('T', '_').slice(0, 15);
const outputDirArg = process.argv[2];
const outputDir = outputDirArg
  ? path.resolve(outputDirArg)
  : path.resolve(`screenshots/site_${stamp}`);

const seedPaths = [
  '/',
  '/tutors',
  '/for-tutors',
  '/contacts',
  '/offer',
  '/refund-policy',
  '/privacy-policy',
  '/admin/login',
  '/admin/register',
  '/site-admin/login',
];

const blockedPatterns = [
  /^\/livewire\//,
  /^\/storage\//,
  /\/logout$/,
  /\/payments\/webhook$/,
  /^\/up$/,
];

const maxPages = 120;
const delayMs = 700;

function normalizePath(raw) {
  try {
    const base = new URL(baseUrl);
    const url = new URL(raw, base);
    if (url.origin !== base.origin) {
      return null;
    }
    let pathname = url.pathname || '/';
    pathname = pathname.replace(/\/+$/, '');
    if (!pathname) {
      pathname = '/';
    }
    return pathname;
  } catch {
    return null;
  }
}

function isBlocked(pathname) {
  return blockedPatterns.some((pattern) => pattern.test(pathname));
}

function toFilename(pathname) {
  if (pathname === '/') {
    return 'home.png';
  }
  return (
    pathname
      .replace(/^\/+/, '')
      .replace(/\//g, '__')
      .replace(/[^a-zA-Z0-9_-]/g, '_') + '.png'
  );
}

async function run() {
  fs.mkdirSync(outputDir, { recursive: true });

  const queue = [...new Set(seedPaths)];
  const visited = new Set();
  const captured = [];

  const browser = await puppeteer.launch({ headless: true });
  const page = await browser.newPage();
  await page.setViewport({ width: 1440, height: 2200 });

  while (queue.length > 0 && captured.length < maxPages) {
    const requestedPath = normalizePath(queue.shift());
    if (!requestedPath || visited.has(requestedPath) || isBlocked(requestedPath)) {
      continue;
    }

    visited.add(requestedPath);
    const targetUrl = new URL(requestedPath, baseUrl).toString();

    try {
      await page.goto(targetUrl, { waitUntil: 'domcontentloaded', timeout: 45000 });
      await page.waitForTimeout(delayMs);
    } catch (error) {
      captured.push({
        requestedPath,
        finalPath: null,
        file: null,
        error: String(error?.message || error),
      });
      continue;
    }

    const finalPath = normalizePath(page.url()) || requestedPath;
    if (isBlocked(finalPath)) {
      continue;
    }

    const filename = toFilename(requestedPath);
    const filePath = path.join(outputDir, filename);
    await page.screenshot({ path: filePath, fullPage: true });

    captured.push({
      requestedPath,
      finalPath,
      file: filename,
      status: 'ok',
    });

    const links = await page.$$eval('a[href]', (nodes) =>
      nodes
        .map((node) => node.getAttribute('href'))
        .filter((href) => typeof href === 'string' && href.length > 0),
    );

    for (const href of links) {
      const nextPath = normalizePath(href);
      if (!nextPath || visited.has(nextPath) || queue.includes(nextPath) || isBlocked(nextPath)) {
        continue;
      }
      queue.push(nextPath);
    }
  }

  await browser.close();

  const manifest = {
    baseUrl,
    outputDir,
    capturedCount: captured.filter((item) => item.status === 'ok').length,
    attemptedCount: captured.length,
    pages: captured,
  };

  fs.writeFileSync(path.join(outputDir, 'manifest.json'), JSON.stringify(manifest, null, 2));

  process.stdout.write(`${outputDir}\n`);
  process.stdout.write(`captured=${manifest.capturedCount}\n`);
}

run().catch((error) => {
  process.stderr.write(`${String(error?.stack || error)}\n`);
  process.exitCode = 1;
});
