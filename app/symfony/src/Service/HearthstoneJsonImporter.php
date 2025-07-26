<?php

namespace App\Service;

use App\Entity\HearthstoneCard;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Console\Style\SymfonyStyle;

class HearthstoneJsonImporter
{
    private const URL = 'https://api.hearthstonejson.com/v1/latest/enUS/cards.collectible.json';
    private const IMG_BASE = 'https://art.hearthstonejson.com/v1/render/latest/enUS/256x/';
    private const TARGET_SETS = ['CORE', 'FESTIVAL', 'TITANS', 'BADLANDS', 'WORLDSBEYOND'];

    public function __construct(private EntityManagerInterface $em)
    {
    }

    public function import(SymfonyStyle $io): void
    {
        $client = HttpClient::create();
        $response = $client->request('GET', self::URL);
        $cards = $response->toArray();
        $fs = new Filesystem();

        $count = 0;

        foreach ($cards as $data) {
            if (!in_array($data['set'], self::TARGET_SETS)) continue;

            $id = $data['id'];
            $existing = $this->em->getRepository(HearthstoneCard::class)->find($id);
            $entity = $existing ?? new HearthstoneCard();

            $imageUrl = self::IMG_BASE . $id . '.png';
            $imagePath = '/uploads/cards/hearthstone/' . $id . '.png';
            $target = __DIR__ . '/../../public' . $imagePath;

            $io->writeln("📥 (Re)téchargement image: $imageUrl");
            @file_put_contents($target, @file_get_contents($imageUrl));

            $entity->setId($id);
            $entity->setDbfId($data['dbfId'] ?? 0);
            $entity->setName($data['name'] ?? '');
            $entity->setCardSet($data['set'] ?? null);
            $entity->setRarity($data['rarity'] ?? null);
            $entity->setType($data['type'] ?? null);
            $entity->setCardClass($data['cardClass'] ?? null);
            $entity->setImagePath($imagePath);
            $entity->setData($data);
            $entity->setImportedAt(new \DateTime());

            $this->em->persist($entity);

            $io->writeln("✅ Carte insérée/maj : {$data['name']} ({$id})");
            $count++;
        }

        $io->note("🧠 Flush appelé, $count cartes persistées.");

        try {
            $this->em->flush();
            $io->success("✅ Import terminé avec $count cartes mises à jour.");
        } catch (\Throwable $e) {
            $io->error("❌ Erreur lors du flush : " . $e->getMessage());
            throw $e;
        }
    }
}
