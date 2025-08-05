const puppeteer = require('puppeteer');
const fs = require('fs');
const path = require('path');

const [,, targetUrlRaw, outputDir, metadataPath] = process.argv;

if (!targetUrlRaw || !outputDir || !metadataPath) {
  console.error('❌ Usage: node scraper-limitless.js <url> <outputDir> <metadataPath>');
  process.exit(1);
}

const targetUrl = targetUrlRaw.includes('show=') ? targetUrlRaw : `${targetUrlRaw}&show=100`;

if (!fs.existsSync(outputDir)) {
  fs.mkdirSync(outputDir, { recursive: true });
  console.log(`📁 Dossier créé : ${outputDir}`);
}

fs.readdirSync(outputDir).forEach(file => {
  const fullPath = path.join(outputDir, file);
  if (fs.lstatSync(fullPath).isFile() && file.endsWith('.png')) {
    fs.unlinkSync(fullPath);
  }
});
console.log(`🧹 Anciennes captures supprimées dans : ${outputDir}`);

(async () => {
  const browser = await puppeteer.launch({
    headless: 'new',
    defaultViewport: null,
    args: ['--no-sandbox', '--disable-setuid-sandbox']
  });

  const page = await browser.newPage();
  await page.setViewport({
    width: 1920,
    height: 1080,
    deviceScaleFactor: 2
  });

  console.log('🌐 Chargement de la page cible...');
  await page.goto(targetUrl, { waitUntil: 'domcontentloaded' });
  await new Promise(resolve => setTimeout(resolve, 2000));

  try {
    const acceptBtn = await page.$x("//button[contains(., 'Accept')]");
    if (acceptBtn.length) {
      await acceptBtn[0].click();
      console.log('🍪 Popup cookies accepté (page liste)');
      await new Promise(resolve => setTimeout(resolve, 1000));
    }
  } catch {
    console.log('✅ Pas de popup cookies sur la page principale');
  }

  const html = await page.content();
  fs.writeFileSync('limitless_debug.html', html);
  console.log('💾 HTML dump enregistré dans limitless_debug.html');

  try {
    await page.waitForSelector('table.data-table tbody tr', { timeout: 5000 });
  } catch (e) {
    console.error('❌ Le tableau principal n’a pas été trouvé.');
    await browser.close();
    return;
  }

  console.log('✅ Page chargée:', targetUrl);

  const deckRows = await page.$$eval('table.data-table tbody tr', rows => {
    return Array.from(rows).map(row => {
      const cells = row.querySelectorAll('td');
      const titleLink = cells[2]?.querySelector('a[href^="/decks/list/"]');
      const title = titleLink?.childNodes[0]?.textContent.trim() || null;
      const url = titleLink ? 'https://limitlesstcg.com' + titleLink.getAttribute('href') : null;
      const player = cells[2]?.querySelector('.annotation')?.textContent.trim().replace(/^by /, '') || null;
      const rank = cells[3]?.textContent.trim() || null;
      const tournament = cells[4]?.textContent.trim() || null;

      return { title, url, player, rank, tournament, format: 'standard' };
    }).filter(d => d.title && d.url);
  });

  console.log(`🔍 ${deckRows.length} decks trouvés`);

  const decks = [];
  let count = 0;

  for (const deck of deckRows) {
    const { title, url, player, rank, tournament } = deck;
    const safeTitle = title.toLowerCase().replace(/[^a-z0-9]+/gi, '_').slice(0, 30);
    const filename = `deck__${safeTitle}_${count}.png`;
    const imagePath = path.join(outputDir, filename);

    try {
      console.log(`📥 Accès à ${url}`);
      await page.goto(url, { waitUntil: 'networkidle2' });
      await new Promise(resolve => setTimeout(resolve, 1000));

      // Clic sur "Open as Image"
      await page.evaluate(() => {
        const btn = document.querySelector('button.tool-link[data-target="/tools/imggen"]');
        if (btn) btn.click();
      });

      // Attente + gestion popup cookies (visuel)
      try {
        await page.waitForXPath("//button[contains(., 'Accept')]", { timeout: 5000 });
        const acceptBtn = await page.$x("//button[contains(., 'Accept')]");
        if (acceptBtn.length) {
          await acceptBtn[0].click();
          console.log('🍪 Popup cookies accepté (après image)');
          await new Promise(resolve => setTimeout(resolve, 1000));
        }
      } catch {
        console.log('✅ Pas de popup cookies après clic ou déjà géré');
      }

      // ✅ Forcer images XL et zoom
      await page.evaluate(() => {
        const cards = document.querySelectorAll('.decklist-visual.standalone img.card-picture');
        cards.forEach(img => {
          img.src = img.src.replace('_XS.png', '_XL.png');
        });
        document.body.style.zoom = '1.3';
      });

      await page.waitForSelector('.decklist-visual.standalone', { timeout: 10000 });
      const element = await page.$('.decklist-visual.standalone');
      if (!element) throw new Error('❌ Bloc visuel introuvable');

      await element.screenshot({ path: imagePath });
      console.log(`📸 Screenshot : ${filename}`);

      const deckData = {
        title,
        player,
        rank,
        tournament,
        url,
        format: 'standard',
        image: filename
      };

      decks.push(deckData);
      fs.writeFileSync(metadataPath, JSON.stringify(decks, null, 2));
      console.log(`✅ JSON mis à jour : ${title}`);

      count++;
    } catch (err) {
      console.warn(`⚠️ Erreur pour ${url} :`, err.message);
    }
  }

  await browser.close();
})();
