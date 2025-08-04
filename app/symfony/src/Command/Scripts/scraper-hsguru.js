const puppeteer = require('puppeteer');
const fs = require('fs');
const path = require('path');

const [,, targetUrl, outputDir, metadataPath] = process.argv;

if (!targetUrl || !outputDir || !metadataPath) {
  console.error('❌ Usage: node scraper-hsguru.js <url> <outputDir> <metadataPath>');
  process.exit(1);
}

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
  await page.goto(targetUrl, { waitUntil: 'networkidle2' });
  console.log('✅ Page chargée:', targetUrl);

  // 🍪 Popup cookies
  try {
    await new Promise(resolve => setTimeout(resolve, 3000));
    const popupVisible = await page.evaluate(() => {
      const popup = document.querySelector('.ncmp__banner');
      return popup && window.getComputedStyle(popup).display !== 'none';
    });

    if (popupVisible) {
      console.log('👀 Popup détectée');
      await page.evaluate(() => {
        const buttons = Array.from(document.querySelectorAll('button.ncmp__btn'));
        const target = buttons.find(btn =>
          !btn.className.includes('btn-border') &&
          btn.textContent.trim().toLowerCase().includes('accept')
        );
        if (target) target.click();
      });
      console.log('🍪 Bouton “Accept” cliqué');
      await new Promise(resolve => setTimeout(resolve, 1500));
    } else {
      console.log('ℹ️ Pas de popup détectée');
    }
  } catch (err) {
    console.log('⚠️ Erreur fermeture popup :', err.message);
  }

  // 📜 Scroll infini
  const scrollUntilEnoughDecks = async (minCount = 40) => {
    let previousCount = 0;
    for (let i = 0; i < 25; i++) {
      const deckCount = await page.$$eval('.deck-card, .card-image, .deck-root', els => els.length);
      console.log(`➡️ Scroll ${i + 1} → ${deckCount} decks visibles`);
      if (deckCount >= minCount || deckCount === previousCount) break;
      previousCount = deckCount;

      await page.evaluate(() => {
        window.scrollTo(0, document.body.scrollHeight);
      });
      await new Promise(resolve => setTimeout(resolve, 6000));
      await page.screenshot({ path: path.join(outputDir, `scroll_debug_${i}.png`) });
    }
  };

  await scrollUntilEnoughDecks(40);

  await page.waitForSelector('.deck-card, .card-image, .deck-root');
  const deckElements = await page.$$('.deck-card, .card-image, .deck-root');
  console.log(`🔍 ${deckElements.length} decks trouvés`);

  const decks = [];
  let count = 0;

  for (const deckEl of deckElements) {
    const boundingBox = await deckEl.boundingBox();
    if (!boundingBox) continue;

    const deckData = await deckEl.evaluate(el => {
      const getText = (selector) => {
        const node = el.querySelector(selector);
        return node ? node.textContent.trim() : null;
      };

      const title = getText('a.basic-black-text');
      const deckcodeNode = el.querySelector('a.basic-black-text + span');
      const deckcode = deckcodeNode ? deckcodeNode.textContent.trim() : null;

      const winrateText = getText('.tag.column .basic-black-text');
      const winrate = winrateText ? parseFloat(winrateText.replace('%', '')) : null;

      const gamesText = Array.from(el.querySelectorAll('.column.tag'))
        .map(e => e.textContent.trim())
        .find(txt => txt.toLowerCase().includes('games:'));
      const games = gamesText ? parseInt(gamesText.replace(/[^0-9]/g, '')) : null;

      const className = Array.from(el.classList).find(c =>
        ['deathknight','demonhunter','druid','hunter','mage','paladin','priest','rogue','shaman','warlock','warrior']
        .includes(c.toLowerCase())
      );

      const href = el.querySelector('a.basic-black-text')?.getAttribute('href') || null;

      return {
        title,
        deckcode,
        winrate,
        games,
        class: className,
        url: href ? `https://www.hsguru.com${href}` : null
      };
    });

    const safeTitle = deckData.title?.toLowerCase().replace(/[^a-z0-9]+/gi, '_').slice(0, 30) || `deck_${count}`;
    const filename = `deck__${safeTitle}_${count}.png`;
    const imagePath = path.join(outputDir, filename);

    await deckEl.screenshot({ path: imagePath });
    console.log(`📸 Screenshot: ${filename}`);

    decks.push({
      ...deckData,
      image: filename
    });

    count++;
  }

  fs.writeFileSync(metadataPath, JSON.stringify(decks, null, 2));
  console.log(`📝 Métadonnées enregistrées dans: ${metadataPath}`);

  await browser.close();
})();
