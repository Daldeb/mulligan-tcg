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
    defaultViewport: { width: 1920, height: 1080 },
    args: [
      '--no-sandbox', 
      '--disable-setuid-sandbox',
      '--disable-dev-shm-usage',
      '--disable-accelerated-2d-canvas',
      '--no-first-run',
      '--no-zygote',
      '--single-process',
      '--disable-gpu'
    ]
  });

  const page = await browser.newPage();
  
  // Définir User-Agent plus réaliste
  await page.setUserAgent('Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
  
  // Définir des headers supplémentaires
  await page.setExtraHTTPHeaders({
    'Accept-Language': 'en-US,en;q=0.9',
    'Accept-Encoding': 'gzip, deflate, br',
    'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8'
  });

  console.log('🚀 Navigation vers:', targetUrl);
  await page.goto(targetUrl, { 
    waitUntil: 'networkidle2',
    timeout: 60000 
  });
  console.log('✅ Page chargée:', targetUrl);

  // Debug: Screenshot de la page initiale
  await page.screenshot({ path: path.join(outputDir, 'debug_initial_page.png'), fullPage: true });
  console.log('📸 Screenshot debug initial sauvegardé');

  // Debug: Vérifier le contenu HTML de la page
  const pageContent = await page.content();
  console.log('📄 Longueur du HTML:', pageContent.length);
  
  // Vérifier si la page contient des éléments attendus
  const hasDecks = await page.evaluate(() => {
    return document.querySelector('.deck-card, .card-image, .deck-root') !== null;
  });
  console.log('🔍 Decks présents immédiatement:', hasDecks);

  // Vérifier la présence d'éléments de chargement
  const loadingElements = await page.evaluate(() => {
    const selectors = ['.loading', '.spinner', '[class*="load"]', '[class*="spinner"]'];
    return selectors.map(sel => ({
      selector: sel,
      found: document.querySelector(sel) !== null
    }));
  });
  console.log('⏳ Éléments de chargement:', loadingElements);

  // 🍪 Popup cookies avec timeout plus long
  try {
    await new Promise(resolve => setTimeout(resolve, 5000));
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
      console.log('🍪 Bouton "Accept" cliqué');
      await new Promise(resolve => setTimeout(resolve, 3000));
    } else {
      console.log('ℹ️ Pas de popup détectée');
    }
  } catch (err) {
    console.log('⚠️ Erreur fermeture popup :', err.message);
  }

  // Attendre plus longtemps que le contenu dynamique se charge
  console.log('⏳ Attente du chargement dynamique...');
  await new Promise(resolve => setTimeout(resolve, 10000));

  // Vérifier à nouveau la présence de decks
  const hasDecksAfterWait = await page.evaluate(() => {
    return document.querySelector('.deck-card, .card-image, .deck-root') !== null;
  });
  console.log('🔍 Decks présents après attente:', hasDecksAfterWait);

  // Debug: Screenshot après attente
  await page.screenshot({ path: path.join(outputDir, 'debug_after_wait.png'), fullPage: true });
  console.log('📸 Screenshot debug après attente sauvegardé');

  // 📜 Scroll infini avec plus de patience
  const scrollUntilEnoughDecks = async (minCount = 40) => {
    let previousCount = 0;
    let stableCount = 0;
    
    for (let i = 0; i < 30; i++) {
      const deckCount = await page.$$eval('.deck-card, .card-image, .deck-root', els => els.length);
      console.log(`➡️ Scroll ${i + 1} → ${deckCount} decks visibles`);
      
      if (deckCount === previousCount) {
        stableCount++;
        if (stableCount >= 3) {
          console.log('🛑 Nombre de decks stable, arrêt du scroll');
          break;
        }
      } else {
        stableCount = 0;
      }
      
      if (deckCount >= minCount) break;
      previousCount = deckCount;

      await page.evaluate(() => {
        window.scrollTo(0, document.body.scrollHeight);
      });
      
      // Attente plus longue entre les scrolls
      await new Promise(resolve => setTimeout(resolve, 8000));
      
      // Screenshot de debug pour chaque scroll
      await page.screenshot({ path: path.join(outputDir, `scroll_debug_${i}.png`) });
    }
  };

  await scrollUntilEnoughDecks(40);

  // Tentative avec waitForSelector avec timeout plus long
  try {
    await page.waitForSelector('.deck-card, .card-image, .deck-root', { timeout: 60000 });
  } catch (error) {
    console.log('⚠️ Timeout sur waitForSelector, continuons avec les éléments disponibles...');
    
    // Debug: Lister tous les sélecteurs présents sur la page
    const availableSelectors = await page.evaluate(() => {
      const elements = document.querySelectorAll('*');
      const selectors = new Set();
      
      elements.forEach(el => {
        if (el.className) {
          el.className.split(' ').forEach(cls => {
            if (cls.trim()) selectors.add(`.${cls.trim()}`);
          });
        }
        if (el.id) {
          selectors.add(`#${el.id}`);
        }
      });
      
      return Array.from(selectors).slice(0, 50); // Limiter pour éviter un output trop gros
    });
    
    console.log('🔍 Sélecteurs disponibles sur la page:', availableSelectors);
  }

  const deckElements = await page.$$('.deck-card, .card-image, .deck-root');
  console.log(`🔍 ${deckElements.length} decks trouvés`);

  // Si aucun deck trouvé, sauvegarder le HTML pour debug
  if (deckElements.length === 0) {
    fs.writeFileSync(path.join(outputDir, 'debug_page_source.html'), await page.content());
    console.log('💾 HTML source sauvegardé pour debug');
  }

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