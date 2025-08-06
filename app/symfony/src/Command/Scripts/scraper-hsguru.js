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
  let browser = null;
  
  try {
    console.log('🔍 Détection de Chrome...');
    
    // Configuration ultra-robuste pour conteneurs Docker
    const launchOptions = {
      headless: 'new',
      defaultViewport: null,
      args: [
        '--no-sandbox',
        '--disable-setuid-sandbox',
        '--disable-dev-shm-usage',
        '--disable-accelerated-2d-canvas',
        '--no-first-run',
        '--no-zygote',
        '--single-process',
        '--disable-gpu',
        '--disable-background-timer-throttling',
        '--disable-backgrounding-occluded-windows',
        '--disable-renderer-backgrounding',
        '--disable-features=TranslateUI',
        '--disable-extensions',
        '--disable-plugins',
        '--disable-images',
        '--disable-javascript',
        '--disable-web-security',
        '--memory-pressure-off',
        '--max_old_space_size=512',
        '--aggressive-cache-discard',
        '--disable-sync',
        '--disable-default-apps'
      ]
    };

    // Essayer de détecter Chrome/Chromium disponible
    const possibleExecutables = [
      '/usr/bin/google-chrome-stable',
      '/usr/bin/google-chrome',
      '/usr/bin/chromium-browser', 
      '/usr/bin/chromium',
      '/snap/bin/chromium'
    ];

    let executablePath = null;
    for (const path of possibleExecutables) {
      if (fs.existsSync(path)) {
        executablePath = path;
        console.log(`✅ Chrome trouvé: ${path}`);
        break;
      }
    }

    if (executablePath) {
      launchOptions.executablePath = executablePath;
    }

    console.log('🚀 Lancement de Puppeteer...');
    browser = await puppeteer.launch(launchOptions);
    console.log('✅ Browser lancé');

  } catch (launchError) {
    console.error('💥 Erreur de lancement de Puppeteer:', launchError.message);
    
    // Fallback: Essayer avec configuration minimale
    console.log('🔄 Tentative avec configuration minimale...');
    try {
      browser = await puppeteer.launch({
        headless: true,
        args: [
          '--no-sandbox',
          '--disable-setuid-sandbox',
          '--single-process',
          '--no-zygote'
        ]
      });
      console.log('✅ Browser lancé en mode fallback');
    } catch (fallbackError) {
      console.error('💥 Échec même en mode fallback:', fallbackError.message);
      
      // Dernière tentative: mode ultra-minimaliste
      console.log('🔄 Dernière tentative ultra-minimaliste...');
      browser = await puppeteer.launch({
        headless: true,
        args: ['--no-sandbox']
      });
      console.log('✅ Browser lancé en mode ultra-minimaliste');
    }
  }

  if (!browser) {
    console.error('❌ Impossible de lancer Chrome');
    process.exit(1);
  }

  try {
    const page = await browser.newPage();
    console.log('📄 Page créée');

    // Configuration page minimaliste mais efficace
    await page.setUserAgent('Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
    
    console.log('🚀 Navigation vers:', targetUrl);
    await page.goto(targetUrl, { 
      waitUntil: 'domcontentloaded',
      timeout: 30000 
    });
    console.log('✅ Page chargée');

    // Attendre que le contenu se charge
    await new Promise(resolve => setTimeout(resolve, 8000));

    // Gérer popup cookies
    try {
      const popupVisible = await page.evaluate(() => {
        const popup = document.querySelector('.ncmp__banner');
        return popup && window.getComputedStyle(popup).display !== 'none';
      });

      if (popupVisible) {
        console.log('👀 Popup détectée, fermeture...');
        await page.evaluate(() => {
          const buttons = Array.from(document.querySelectorAll('button.ncmp__btn'));
          const target = buttons.find(btn =>
            !btn.className.includes('btn-border') &&
            btn.textContent.trim().toLowerCase().includes('accept')
          );
          if (target) target.click();
        });
        await new Promise(resolve => setTimeout(resolve, 2000));
        console.log('✅ Popup fermée');
      }
    } catch (popupError) {
      console.log('⚠️ Erreur popup:', popupError.message);
    }

    // Scroll pour charger plus de contenu
    console.log('📜 Scroll pour charger le contenu...');
    for (let i = 0; i < 10; i++) {
      const deckCount = await page.$$eval('.deck-card, .card-image, .deck-root', els => els.length);
      console.log(`➡️ Scroll ${i + 1} → ${deckCount} decks visibles`);
      
      if (deckCount >= 30) break;

      await page.evaluate(() => {
        window.scrollTo(0, document.body.scrollHeight);
      });
      await new Promise(resolve => setTimeout(resolve, 3000));
    }

    // Récupérer les éléments decks
    const deckElements = await page.$$('.deck-card, .card-image, .deck-root');
    console.log(`🎯 ${deckElements.length} decks trouvés`);

    if (deckElements.length === 0) {
      console.log('⚠️ Aucun deck trouvé, sauvegarde du HTML...');
      const html = await page.content();
      fs.writeFileSync(path.join(outputDir, 'debug_no_decks.html'), html);
      console.log('💾 HTML sauvegardé dans debug_no_decks.html');
    }

    const decks = [];
    let count = 0;

    for (const deckEl of deckElements) {
      try {
        const deckData = await deckEl.evaluate(el => {
          const getText = (selector) => {
            const node = el.querySelector(selector);
            return node ? node.textContent.trim() : null;
          };

          const title = getText('a.basic-black-text') || `Deck ${Math.random().toString(36).substr(2, 9)}`;
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

        const safeTitle = deckData.title.toLowerCase().replace(/[^a-z0-9]+/gi, '_').slice(0, 30);
        const filename = `deck__${safeTitle}_${count}.png`;
        const imagePath = path.join(outputDir, filename);

        await deckEl.screenshot({ path: imagePath });
        console.log(`📸 Screenshot: ${filename}`);

        decks.push({
          ...deckData,
          image: filename
        });

        count++;
        
        if (count >= 50) break; // Limiter pour éviter les timeouts
        
      } catch (deckError) {
        console.log(`⚠️ Erreur deck ${count}:`, deckError.message);
        continue;
      }
    }

    fs.writeFileSync(metadataPath, JSON.stringify(decks, null, 2));
    console.log(`📝 ${decks.length} métadonnées sauvegardées dans: ${metadataPath}`);

  } catch (pageError) {
    console.error('💥 Erreur dans la page:', pageError.message);
    throw pageError;
  } finally {
    if (browser) {
      await browser.close();
      console.log('🔒 Browser fermé');
    }
  }
})();