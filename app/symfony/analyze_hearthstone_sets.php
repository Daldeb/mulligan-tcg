<?php
// Script temporaire pour analyser les sets Hearthstone
// À exécuter une fois pour découvrir tous les sets disponibles

echo "📡 Téléchargement des données Hearthstone...\n";
$url = 'https://api.hearthstonejson.com/v1/latest/frFR/cards.collectible.json';
$jsonData = file_get_contents($url);

if (!$jsonData) {
    die("❌ Erreur lors du téléchargement depuis : $url\n");
}

echo "✅ Téléchargement terminé, analyse en cours...\n\n";
$cardsData = json_decode($jsonData, true);

if (!$cardsData) {
    die("Erreur lors du chargement du JSON\n");
}

// Extraire tous les sets uniques
$allSets = [];
foreach ($cardsData as $card) {
    if (isset($card['set'])) {
        $setId = $card['set'];
        
        if (!isset($allSets[$setId])) {
            $allSets[$setId] = [
                'id' => $setId,
                'sample_card_name' => $card['name']['frFR'] ?? $card['name']['enUS'] ?? 'Unknown',
                'count' => 0
            ];
        }
        
        $allSets[$setId]['count']++;
    }
}

// Trier par nombre de cartes (descendant)
uasort($allSets, function($a, $b) {
    return $b['count'] <=> $a['count'];
});

echo "=== SETS HEARTHSTONE DÉCOUVERTS ===\n";
echo sprintf("%-20s %-40s %s\n", "CODE SET", "EXEMPLE DE CARTE", "NB CARTES");
echo str_repeat("-", 80) . "\n";

foreach ($allSets as $set) {
    echo sprintf("%-20s %-40s %d\n", 
        $set['id'], 
        mb_substr($set['sample_card_name'], 0, 40), 
        $set['count']
    );
}

echo "\n=== SETS PROBABLES POUR STANDARD ===\n";
echo "Basé sur tes données, ces sets devraient être Standard :\n";

$probableStandardSets = [];
foreach ($allSets as $set) {
    // On cherche les sets avec beaucoup de cartes (extensions récentes)
    if ($set['count'] > 50) {
        $probableStandardSets[] = $set['id'];
    }
}

// Affichage pour config YAML
echo "\n=== CONFIGURATION YAML SUGGÉRÉE ===\n";
echo "hearthstone:\n";
echo "  standard_sets:\n";
echo "    - CORE  # Core Set (permanent)\n";

foreach (array_slice($probableStandardSets, 0, 10) as $setId) {
    if ($setId !== 'CORE') {
        echo "    - {$setId}  # " . $allSets[$setId]['sample_card_name'] . "\n";
    }
}

echo "  \n  rotation_date: '2024-04-01'\n";

echo "\n=== SETS ANCIENS (PROBABLEMENT WILD) ===\n";
foreach ($allSets as $set) {
    if ($set['count'] < 50) {
        echo "- {$set['id']} ({$set['count']} cartes)\n";
    }
}
?>