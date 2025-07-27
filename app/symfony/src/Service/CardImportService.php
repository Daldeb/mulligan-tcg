<?php

namespace App\Service;

use App\Entity\Card;
use App\Entity\Game;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Yaml\Yaml;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class CardImportService
{
    public function __construct(
        private EntityManagerInterface $em,
        private HttpClientInterface $http
    ) {}

    public function importFromConfig(string $slug): void
    {
        $config = Yaml::parseFile(__DIR__ . '/../../config/games/' . $slug . '.yaml');
        $url = $config['endpoint'];
        $pageSize = $config['pagination']['pageSize'] ?? 100;
        $maxPages = $config['pagination']['maxPages'] ?? 10;

        $authHeader = [
            $config['auth']['header'] => 'Bearer ' . getenv($config['auth']['token_env'])
        ];

        for ($page = 1; $page <= $maxPages; $page++) {
            $response = $this->http->request('GET', $url, [
                'headers' => $authHeader,
                'query' => [
                    'locale' => $config['locale'],
                    'gameMode' => $config['default_mode'],
                    'pageSize' => $pageSize,
                    'page' => $page
                ]
            ]);

            $data = $response->toArray();

            foreach ($data['cards'] ?? [] as $cardData) {
                $card = new Card();
                $card->setName($cardData['name'] ?? '');
                $card->setSlug($cardData['slug'] ?? '');
                $card->setGameMode($config['default_mode']);
                $card->setData($cardData);
                $card->setImage($cardData['image'] ?? null);
                $card->setUpdatedAt(new \DateTime());

                $this->em->persist($card);
            }

            $this->em->flush();
        }
    }
}
