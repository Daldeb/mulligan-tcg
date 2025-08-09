// scraper-limitless.js
// Usage: node scraper-limitless.js <url> <outputDir> <metadataPath>

const puppeteer = require('puppeteer');
const fs = require('fs');
const path = require('path');
const { performance } = require('perf_hooks');

// ---------- CLI ----------
const [,, targetUrlRaw, outputDir, metadataPath] = process.argv;
if (!targetUrlRaw || !outputDir || !metadataPath) {
  console.error('❌ Usage: node scraper-limitless.js <url> <outputDir> <metadataPath>');
  process.exit(1);
}
const targetUrl = targetUrlRaw.includes('show=') ? targetUrlRaw : `${targetUrlRaw}&show=100`;

// ---------- Tuning ----------
const VIEWPORT = { width: 1920, height: 1080, deviceScaleFactor: 2 };
const TIMEOUT_MED = 20000;
const TIMEOUT_LONG = 30000;

// ---------- UI ----------
const bold = s => `\x1b[1m${s}\x1b[0m`;
const green = s => `\x1b[32m${s}\x1b[0m`;
const red   = s => `\x1b[31m${s}\x1b[0m`;
const cyan  = s => `\x1b[36m${s}\x1b[0m`;

// ---------- Utils ----------
const sleep = ms => new Promise(r=>setTimeout(r, ms));

async function waitForImagesLoaded(page, selector) {
  await page.waitForFunction(sel => {
    const root = document.querySelector(sel);
    if (!root) return false;
    const imgs = Array.from(root.querySelectorAll('img'));
    return imgs.length > 0 && imgs.every(img => img.complete && img.naturalWidth > 0);
  }, { timeout: TIMEOUT_LONG }, selector);
}

async function waitForLayoutStable(page, selector, delay = 300) {
  const stable = await page.evaluate(async (sel, d) => {
    const el = document.querySelector(sel);
    if (!el) return false;
    const r1 = el.getBoundingClientRect();
    await new Promise(res => setTimeout(res, d));
    const r2 = el.getBoundingClientRect();
    return r1.width === r2.width && r1.height === r2.height;
  }, selector, delay);
  if (!stable) await sleep(delay);
}

async function clickByTextVisible(page, regex) {
  return page.evaluate(reStr => {
    const re = new RegExp(reStr, 'i');
    const els = document.querySelectorAll('button, a, [role="button"]');
    for (const el of els) {
      const txt = (el.textContent || '').trim();
      if (re.test(txt) && el.offsetParent) {
        el.click();
        return true;
      }
    }
    return false;
  }, regex.source);
}

// ---------- Accept cookies (renforcé) ----------
async function acceptCookiesDeep(page, retries = 4) {
  const tryInFrame = async (frame) => {
    try {
      return await frame.evaluate(() => {
        const texts = /(accept|agree|tout accepter|j['’]accepte|accept all)/i;
        const candidates = Array.from(document.querySelectorAll('button, a, [role="button"], [aria-label]'));
        const el = candidates.find(n =>
          texts.test(((n.textContent || '') + ' ' + (n.ariaLabel || '')).trim()) &&
          n.offsetParent !== null
        );
        if (el) { el.click(); return true; }
        return false;
      });
    } catch { return false; }
  };

  for (let i = 0; i < retries; i++) {
    let clicked = await tryInFrame(page.mainFrame());
    if (!clicked) {
      for (const f of page.frames()) {
        if (f === page.mainFrame()) continue;
        clicked = await tryInFrame(f);
        if (clicked) break;
      }
    }
    if (clicked) { await sleep(500); return true; }
    await sleep(250);
  }
  return false;
}

async function ensureAcceptedAndClear(page, targetSel = '.decklist-visual, .decklist-visual.standalone') {
  await acceptCookiesDeep(page);

  const overlaySelectors = [
    '[role="dialog"]',
    '#onetrust-banner-sdk', '#sp_message_container_', '#sp_veil',
    '.ot-sdk-container', '.ot-sdk-row',
    '.cookie', '.cookies', '.cookie-banner', '.cookie-consent',
    '.gdpr', '.modal', '.overlay'
  ];
  for (const sel of overlaySelectors) {
    const exists = await page.$(sel);
    if (exists) { try { await page.waitForSelector(sel, { hidden: true, timeout: 2500 }); } catch {} }
  }

  const obscured = await page.evaluate((sel) => {
    const el = document.querySelector(sel);
    if (!el) return true;
    const r = el.getBoundingClientRect();
    const cx = Math.max(0, Math.floor(r.left + r.width / 2));
    const cy = Math.max(0, Math.floor(r.top + 10));
    const top = document.elementFromPoint(cx, cy);
    return !!(top && !el.contains(top) && top !== el);
  }, targetSel);

  if (!obscured) return true;

  await acceptCookiesDeep(page);
  try { await page.keyboard.press('Escape'); } catch {}
  await sleep(300);

  const stillObscured = await page.evaluate((sel) => {
    const el = document.querySelector(sel);
    if (!el) return true;
    const r = el.getBoundingClientRect();
    const cx = Math.max(0, Math.floor(r.left + r.width / 2));
    const cy = Math.max(0, Math.floor(r.top + 10));
    const top = document.elementFromPoint(cx, cy);
    return !!(top && !el.contains(top) && top !== el);
  }, targetSel);

  if (stillObscured) {
    await page.evaluate(() => {
      const killers = ['#onetrust-banner-sdk', '#sp_message_container_', '.cookie-banner', '.ot-sdk-container'];
      killers.forEach(sel => document.querySelectorAll(sel).forEach(n => { n.style.display = 'none'; }));
    });
    await sleep(120);
  }
  return true;
}

// ---------- Puppeteer ----------
async function createBrowser() {
  return puppeteer.launch({
    headless: 'new',
    executablePath: '/usr/bin/google-chrome-stable',
    args: [
      '--no-sandbox',
      '--disable-setuid-sandbox',
      '--disable-dev-shm-usage',
      '--disable-accelerated-2d-canvas',
      '--no-first-run',
      '--no-zygote',
      '--disable-gpu'
    ]
  });
}

async function preparePage(page) {
  await page.setViewport(VIEWPORT);
  if (typeof page.route === 'function') {
    await page.route('**/*', route => {
      const req = route.request();
      const url = req.url();
      const type = req.resourceType();
      const isFont = type === 'font';
      const isMedia = type === 'media';
      const isPing = type === 'ping';
      const isAnalytics = /google-analytics|gtm|doubleclick|facebook|hotjar/i.test(url);
      // ⚠️ on NE bloque PAS les images
      if (isFont || isMedia || isPing || isAnalytics) return route.abort();
      return route.continue();
    });
  } else {
    await page.setRequestInterception(true);
    page.removeAllListeners('request');
    page.on('request', req => {
      const url = req.url();
      const type = req.resourceType();
      const isFont = type === 'font';
      const isMedia = type === 'media';
      const isPing = type === 'ping';
      const isAnalytics = /google-analytics|gtm|doubleclick|facebook|hotjar/i.test(url);
      if (isFont || isMedia || isPing || isAnalytics) return req.abort();
      return req.continue();
    });
  }
}

// ---------- Main ----------
(async () => {
  if (!fs.existsSync(outputDir)) fs.mkdirSync(outputDir, { recursive: true });
  for (const f of fs.readdirSync(outputDir)) {
    if (f.endsWith('.png')) fs.unlinkSync(path.join(outputDir, f));
  }

  const browser = await createBrowser();
  const indexPage = await browser.newPage();
  await preparePage(indexPage);

  console.log(`🌐 Chargement liste: ${cyan(targetUrl)}`);
  await indexPage.goto(targetUrl, { waitUntil: 'domcontentloaded', timeout: TIMEOUT_LONG });
  await acceptCookiesDeep(indexPage);

  fs.writeFileSync('limitless_debug.html', await indexPage.content());
  console.log('✔ Dump liste → limitless_debug.html');

  await indexPage.waitForSelector('table.data-table tbody tr', { timeout: TIMEOUT_MED });
  const decks = await indexPage.$$eval('table.data-table tbody tr', rows => {
    return Array.from(rows).map(row => {
      const cells = row.querySelectorAll('td');
      const link = cells[2]?.querySelector('a[href^="/decks/list/"]');
      if (!link) return null;
      return {
        title: link.childNodes[0]?.textContent.trim() || '',
        url: 'https://limitlesstcg.com' + link.getAttribute('href'),
        player: cells[2]?.querySelector('.annotation')?.textContent.trim().replace(/^by /, '') || '',
        rank: cells[3]?.textContent.trim() || '',
        tournament: cells[4]?.textContent.trim() || '',
        format: 'standard'
      };
    }).filter(Boolean);
  });
  console.log(`✔ ${decks.length} decks trouvés`);

  const results = [];
  let done = 0;

  for (const deck of decks) {
    done++;
    const page = await browser.newPage();
    await preparePage(page);

    console.log(`\n[${done}/${decks.length}] ${cyan(deck.title)}`);
    console.log(`   • Joueur     : ${deck.player || '-'}`);
    console.log(`   • Rang       : ${deck.rank || '-'}`);
    console.log(`   • Tournoi    : ${deck.tournament || '-'}`);
    console.log(`   • Format     : ${deck.format}`);
    console.log(`   • URL        : ${deck.url}`);

    await page.goto(deck.url, { waitUntil: 'domcontentloaded', timeout: TIMEOUT_LONG });
    await acceptCookiesDeep(page); // 1) premier passage

    // clic visuel ou open image
    let clicked = await clickByTextVisible(page, /\bvisual\b/i);
    if (!clicked) clicked = await clickByTextVisible(page, /open as image/i);

    await page.waitForSelector('.decklist-visual, .decklist-visual.standalone', { timeout: TIMEOUT_LONG });

    // ⚠️ s'assurer qu'on a bien accepté et qu'aucun overlay ne masque le deck
    await ensureAcceptedAndClear(page); // 2) avant la préparation du screenshot

    // déclenchement du chargement complet sans forcer _XL
    await page.evaluate(() => {
      const root = document.querySelector('.decklist-visual, .decklist-visual.standalone');
      if (!root) return;
      root.querySelectorAll('img').forEach(img => {
        img.loading = 'eager';
        img.decoding = 'sync';
        if (img.sizes !== undefined) img.sizes = '100vw';
      });
      document.body.style.zoom = '1.15';
      root.scrollTop = root.scrollHeight;
      root.scrollTop = 0;
    });

    await waitForImagesLoaded(page, '.decklist-visual, .decklist-visual.standalone');
    await waitForLayoutStable(page, '.decklist-visual, .decklist-visual.standalone');

    // dernier filet de sécurité avant la capture
    await ensureAcceptedAndClear(page); // 3) juste avant screenshot

    const safeTitle = deck.title.toLowerCase().replace(/[^a-z0-9]+/gi, '_').slice(0, 30);
    const filename = `deck__${safeTitle}_${done}.png`;
    const imagePath = path.join(outputDir, filename);
    const element = await page.$('.decklist-visual.standalone') || await page.$('.decklist-visual');
    await element.screenshot({ path: imagePath });
    console.log(green(`✔ Screenshot → ${filename}`));

    results.push({ ...deck, image: filename });
    fs.writeFileSync(metadataPath, JSON.stringify(results, null, 2));

    await page.close();
  }

  await indexPage.close();
  await browser.close();
  console.log(green('\n🎉 Terminé !'));
})();
