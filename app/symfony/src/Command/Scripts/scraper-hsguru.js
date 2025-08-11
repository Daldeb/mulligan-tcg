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
    
    // Configuration BULLETPROOF pour containers sans GPU
    const launchOptions = {
      headless: true,
      defaultViewport: { width: 800, height: 600 },
      args: [
        '--no-sandbox',
        '--disable-setuid-sandbox',
        '--disable-dev-shm-usage',
        '--disable-gpu',
        '--disable-gpu-sandbox',
        '--disable-software-rasterizer',
        '--disable-background-timer-throttling',
        '--disable-backgrounding-occluded-windows',
        '--disable-renderer-backgrounding',
        '--disable-features=TranslateUI,BlinkGenPropertyTrees,VizDisplayCompositor',
        '--disable-ipc-flooding-protection',
        '--disable-extensions',
        '--disable-default-apps',
        '--disable-sync',
        '--disable-background-networking',
        '--disable-background-mode',
        '--disable-client-side-phishing-detection',
        '--disable-component-extensions-with-background-pages',
        '--disable-hang-monitor',
        '--disable-prompt-on-repost',
        '--disable-domain-reliability',
        '--disable-component-update',
        '--disable-background-media-suspend',
        '--no-first-run',
        '--no-zygote',
        '--single-process',
        '--memory-pressure-off',
        '--max_old_space_size=128',
        '--aggressive-cache-discard',
        // CRITIQUES pour les erreurs GPU
        '--disable-vulkan',
        '--disable-vulkan-fallback-to-gl-for-testing',
        '--use-gl=swiftshader',
        '--disable-gl-extensions',
        '--disable-accelerated-2d-canvas',
        '--disable-accelerated-video-decode',
        '--disable-accelerated-video-encode',
        '--disable-accelerated-mjpeg-decode',
        '--disable-gpu-memory-buffer-compositor-resources',
        '--disable-gpu-memory-buffer-video-frames',
        '--disable-oop-rasterization',
        '--disable-zero-copy',
        '--ignore-gpu-blacklist',
        '--ignore-gpu-blocklist'
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
    
    // UN SEUL fallback ultra-simple
    console.log('🔄 Dernière tentative absolue...');
    try {
      browser = await puppeteer.launch({
        headless: true,
        executablePath: '/usr/bin/chromium-browser',
        args: [
          '--no-sandbox',
          '--disable-setuid-sandbox',
          '--disable-dev-shm-usage',
          '--single-process',
          '--disable-gpu'
        ]
      });
      console.log('✅ Browser lancé en mode de secours');
    } catch (finalError) {
      console.error('💥 Échec total:', finalError.message);
      process.exit(1);
    }
  }

  if (!browser) {
    console.error('❌ Impossible de lancer Chrome');
    process.exit(1);
  }

  try {
    const page = await browser.newPage();
    console.log('📄 Page créée');

    // Configuration page ultra-optimisée
    await page.setUserAgent('Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36');
    
    // Bloquer les ressources non essentielles pour économiser la mémoire (on NE bloque PAS les images)
    await page.setRequestInterception(true);
    page.on('request', (req) => {
      const resourceType = req.resourceType();
      const url = req.url();
      if (
        resourceType === 'font' ||
        resourceType === 'media' ||
        url.includes('google-analytics') ||
        url.includes('facebook.com') ||
        url.includes('doubleclick')
      ) {
        req.abort();
      } else {
        req.continue();
      }
    });
    
    console.log('🚀 Navigation vers:', targetUrl);
    await page.goto(targetUrl, { 
      waitUntil: 'domcontentloaded',
      timeout: 40000 // Augmenté car on bloque des ressources
    });
    console.log('✅ Page chargée');

    // Attendre que le contenu se charge
    await new Promise(resolve => setTimeout(resolve, 5000));

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

    // Scroll réduit pour économiser mémoire et temps
    console.log('📜 Scroll pour charger le contenu...');
    for (let i = 0; i < 6; i++) {
      const deckCount = await page.$$eval('.deck-card, .card-image, .deck-root', els => els.length);
      console.log(`➡️ Scroll ${i + 1} → ${deckCount} decks visibles`);
      if (deckCount >= 25) break;
      await page.evaluate(() => { window.scrollTo(0, document.body.scrollHeight); });
      await new Promise(resolve => setTimeout(resolve, 2500));
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
    const maxDecks = Math.min(deckElements.length, 40); // Limiter à 40

    for (let i = 0; i < maxDecks; i++) {
      const deckEl = deckElements[i];
      
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

        // --- NOUVEAU : fiabiliser le rendu des vignettes + pause 1s avant screenshot ---

        // 1) Amener l'élément au centre du viewport pour déclencher le lazy-loading
        await deckEl.evaluate(el => el.scrollIntoView({ block: 'center', inline: 'nearest' }));
        await page.waitForTimeout(100);

        // 2) Forcer/attendre le chargement des images internes (lazy, data-src/srcset, decode)
        try {
          await page.evaluate(async el => {
            const imgs = Array.from(el.querySelectorAll('img'));
            for (const img of imgs) {
              if (img.loading === 'lazy') img.loading = 'eager';
              if (img.dataset && img.dataset.src && !img.src) img.src = img.dataset.src;
              if (img.dataset && img.dataset.srcset && !img.srcset) img.srcset = img.dataset.srcset;
              try { await img.decode(); } catch (_) {}
            }
            // Cas fréquents: vignettes en background-image via data-*
            const bgNodes = Array.from(el.querySelectorAll('[data-bg],[data-background-image]'));
            for (const node of bgNodes) {
              const url = node.dataset.bg || node.dataset.backgroundImage;
              if (url && !getComputedStyle(node).backgroundImage.includes('url(')) {
                node.style.backgroundImage = `url("${url}")`;
              }
            }
          }, deckEl);
        } catch (e) {
          // l'élément a pu être détaché/transient: on ignore
        }

        // 3) Attendre que toutes les <img> soient complètes OU timeout doux
        await Promise.race([
          page.waitForFunction(el => {
            const imgs = Array.from(el.querySelectorAll('img'));
            return imgs.length === 0 || imgs.every(i => i.complete && i.naturalWidth > 0);
          }, {}, deckEl),
          page.waitForTimeout(3000)
        ]);

        // 4) Attente demandée : 1 seconde avant chaque screenshot
        await page.waitForTimeout(1000);

        // Screenshot optimisé
        await deckEl.screenshot({ 
          path: imagePath
          // Note: quality ne fonctionne que pour JPG, pas PNG
        });
        console.log(`📸 Screenshot: ${filename}`);

        decks.push({
          ...deckData,
          image: filename
        });

        count++;
        
        // Petit nettoyage mémoire tous les 10 screenshots
        if (count % 10 === 0) {
          global.gc && global.gc();
        }
        
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
