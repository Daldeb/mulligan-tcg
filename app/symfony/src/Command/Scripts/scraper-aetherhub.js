const puppeteer = require('puppeteer');
const fs = require('fs');
const path = require('path');

const [,, targetUrl, outputDir, metadataPath, formatLabel] = process.argv;

if (!targetUrl || !outputDir || !metadataPath || !formatLabel) {
    console.error('❌ Usage: node scraper-aetherhub.js <url> <outputDir> <metadataPath> <formatLabel>');
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
        args: [
            '--no-sandbox', 
            '--disable-setuid-sandbox',
            '--disable-dev-shm-usage',
            '--disable-extensions',
            '--disable-plugins',
            '--disable-images',
            '--no-first-run',
            '--disable-background-timer-throttling',
            '--disable-backgrounding-occluded-windows',
            '--disable-renderer-backgrounding'
        ]
    });

    const page = await browser.newPage();
    await page.setViewport({ width: 1280, height: 720, deviceScaleFactor: 1.5 });
    
    await page.goto(targetUrl, { waitUntil: 'networkidle2' });
    console.log('✅ Page chargée:', targetUrl);

    await page.waitForSelector('.ae-deck-row');

    const deckUrls = await page.$$eval('td.ae-decktitle > a', links =>
        links.map(a => {
            const rawHref = a.getAttribute('href');
            const cleanHref = rawHref.replace(/\?.*$/, '');
            const parts = cleanHref.split('/');
            const slug = parts[parts.length - 1];
            return {
                title: a.textContent.trim(),
                galleryUrl: `https://aetherhub.com/Deck/${slug}/Gallery/`,
                deckSlug: slug
            };
        })
    );

    console.log(`🔗 ${deckUrls.length} decks trouvés`);

    const decks = [];
    let count = 0;

    for (const deck of deckUrls) {
        const { title, galleryUrl, deckSlug } = deck;
        const safeTitle = title.toLowerCase().replace(/[^a-z0-9]+/gi, '_').slice(0, 30);
        const filename = `deck__${safeTitle}_${count}.png`;
        const imagePath = path.join(outputDir, filename);

        try {
            console.log(`📥 Accès à ${galleryUrl}`);
            await page.goto(galleryUrl, { waitUntil: 'networkidle2' });

            // 🌐 Fermer popup cookies (optimisé)
            try {
                await new Promise(resolve => setTimeout(resolve, 1500));
                const popupVisible = await page.evaluate(() => {
                    const btns = Array.from(document.querySelectorAll('button'));
                    const acceptBtn = btns.find(btn => btn.textContent.trim().toLowerCase() === 'accept');
                    if (acceptBtn) {
                        acceptBtn.click();
                        return true;
                    }
                    return false;
                });
                if (popupVisible) {
                    console.log('🍪 Popup cookies acceptée');
                    await new Promise(resolve => setTimeout(resolve, 1000));
                }
            } catch (err) {
                console.log('⚠️ Erreur popup cookies :', err.message);
            }

            // Supprimer le bouton de retour + zoom (optimisé)
            await page.evaluate(() => {
                const backBtn = document.querySelector('a.text-white[href^="/Deck/"]');
                if (backBtn) backBtn.style.display = 'none';
                document.body.style.zoom = '1.2';
            });

            await new Promise(resolve => setTimeout(resolve, 500));
            await page.screenshot({ path: imagePath, fullPage: true });
            console.log(`📸 Screenshot: ${filename}`);

            decks.push({
                title,
                url: galleryUrl,
                image: filename,
                format: formatLabel
            });

            count++;

            // Sauvegarde progressive tous les 10 decks
            if (count % 10 === 0 && count > 0) {
                const tempData = { 
                    progress: count,
                    total: deckUrls.length,
                    decks: decks 
                };
                fs.writeFileSync(metadataPath.replace('.json', '_temp.json'), JSON.stringify(tempData, null, 2));
                console.log(`💾 Sauvegarde temporaire : ${count}/${deckUrls.length} decks`);
            }

            // Nettoyage mémoire périodique
            if (count % 20 === 0 && count > 0) {
                await page.goto('about:blank');
                console.log(`🧹 Nettoyage mémoire après ${count} decks`);
            }

        } catch (err) {
            console.warn(`⚠️ Erreur pour ${galleryUrl}:`, err.message);
        }
    }

    fs.writeFileSync(metadataPath, JSON.stringify(decks, null, 2));
    console.log(`📝 Métadonnées enregistrées dans: ${metadataPath}`);
    console.log(`✅ Scraping terminé : ${decks.length}/${deckUrls.length} decks traités`);

    await browser.close();
})();