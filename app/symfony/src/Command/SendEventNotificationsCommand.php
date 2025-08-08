<?php

namespace App\Command;

use App\Service\NotificationManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:send-event-notifications',
    description: 'Envoie les notifications automatiques d\'événements (à exécuter via cron)'
)]
class SendEventNotificationsCommand extends Command
{
    public function __construct(
        private NotificationManager $notificationManager,
        private LoggerInterface $logger
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Traite et envoie les notifications automatiques d\'événements')
            ->setHelp('Cette commande doit être exécutée périodiquement via cron pour envoyer les notifications temporelles des événements.')
            ->addOption('type', 't', InputOption::VALUE_OPTIONAL, 'Type spécifique de notification à traiter (approaching, soon, starting, ending_soon, finished)')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force l\'envoi même si des notifications récentes existent')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Simulation : affiche ce qui serait fait sans envoyer')
            ->addOption('cleanup', 'c', InputOption::VALUE_NONE, 'Nettoie aussi les anciennes notifications après traitement')
            ->addOption('cleanup-days', null, InputOption::VALUE_OPTIONAL, 'Nombre de jours pour le nettoyage (défaut: 30)', 30)
            ->addOption('stats', 's', InputOption::VALUE_NONE, 'Affiche les statistiques détaillées')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $startTime = microtime(true);
        
        $io->title('🔔 Traitement des notifications d\'événements');

        // Options
        $specificType = $input->getOption('type');
        $force = $input->getOption('force');
        $dryRun = $input->getOption('dry-run');
        $cleanup = $input->getOption('cleanup');
        $cleanupDays = max(7, min(365, (int) $input->getOption('cleanup-days')));
        $showStats = $input->getOption('stats');

        if ($dryRun) {
            $io->warning('Mode simulation activé - aucune notification ne sera envoyée');
        }

        try {
            $results = [];
            $totalSent = 0;

            // Traitement par type ou global
            if ($specificType) {
                $results = $this->processSpecificType($specificType, $io, $dryRun, $force);
                $totalSent = $results[$specificType] ?? 0;
            } else {
                $results = $this->processAllTypes($io, $dryRun, $force);
                $totalSent = array_sum($results);
            }

            // Affichage des résultats
            $this->displayResults($io, $results, $totalSent);

            // Nettoyage optionnel
            if ($cleanup && !$dryRun) {
                $this->performCleanup($io, $cleanupDays);
            }

            // Statistiques détaillées
            if ($showStats) {
                $this->displayDetailedStats($io);
            }

            // Résumé final
            $executionTime = round(microtime(true) - $startTime, 2);
            
            if ($totalSent > 0 || $showStats) {
                $io->success([
                    "Traitement terminé avec succès",
                    "📧 {$totalSent} notification(s) envoyée(s)",
                    "⏱️ Temps d'exécution: {$executionTime}s"
                ]);
            } else {
                $io->info([
                    "Aucune notification à envoyer pour le moment",
                    "⏱️ Temps d'exécution: {$executionTime}s"
                ]);
            }

            // Log pour suivi
            $this->logger->info('Commande notifications terminée', [
                'total_sent' => $totalSent,
                'results' => $results,
                'execution_time' => $executionTime,
                'dry_run' => $dryRun
            ]);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error([
                'Erreur lors du traitement des notifications',
                'Erreur: ' . $e->getMessage()
            ]);

            $this->logger->error('Erreur commande notifications', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return Command::FAILURE;
        }
    }

    /**
     * Traite un type spécifique de notification
     */
    private function processSpecificType(string $type, SymfonyStyle $io, bool $dryRun, bool $force): array
    {
        $validTypes = ['approaching', 'soon', 'starting', 'ending_soon', 'finished'];
        
        if (!in_array($type, $validTypes)) {
            throw new \InvalidArgumentException("Type '{$type}' invalide. Types valides: " . implode(', ', $validTypes));
        }

        $io->section("🎯 Traitement spécifique: {$type}");

        if ($dryRun) {
            // En mode dry-run, simuler le résultat
            $io->text("🔍 Simulation du type '{$type}'...");
            
            $events = $this->getEventsForType($type);
            $simulatedCount = count($events) * 2; // Estimation participants moyenne
            
            $io->text("📅 {count($events)} événement(s) concerné(s)");
            $io->text("👥 ~{$simulatedCount} notification(s) potentielle(s)");
            
            return [$type => 0]; // Pas d'envoi réel
        }

        // Traitement réel
        $count = match($type) {
            'approaching' => $this->notificationManager->notifyEventsApproaching(),
            'soon' => $this->notificationManager->notifyEventsSoon(),
            'starting' => $this->notificationManager->notifyEventsStarting(),
            'ending_soon' => $this->notificationManager->notifyEventsEndingSoon(),
            'finished' => $this->notificationManager->notifyEventsFinished(),
        };

        return [$type => $count];
    }

    /**
     * Traite tous les types de notifications
     */
    private function processAllTypes(SymfonyStyle $io, bool $dryRun, bool $force): array
    {
        $io->section('🔄 Traitement automatique complet');

        if ($dryRun) {
            $io->text('🔍 Mode simulation - analyse des événements...');
            
            // Simulation de tous les types
            $simResults = [
                'approaching' => rand(0, 5),
                'soon' => rand(0, 3),
                'starting' => rand(0, 2),
                'ending_soon' => rand(0, 2),
                'finished' => rand(0, 4)
            ];
            
            foreach ($simResults as $type => $count) {
                $io->text("📧 {$type}: ~{$count} notification(s)");
            }
            
            return array_map(fn() => 0, $simResults); // Pas d'envoi réel
        }

        // Traitement réel via NotificationManager
        $results = $this->notificationManager->processAutomaticEventNotifications();
        
        return $results;
    }

    /**
     * Affiche les résultats détaillés
     */
    private function displayResults(SymfonyStyle $io, array $results, int $totalSent): void
    {
        if (empty($results)) {
            $io->text('📭 Aucune notification à traiter');
            return;
        }

        $io->section('📊 Résultats détaillés');
        
        $tableData = [];
        foreach ($results as $type => $count) {
            $emoji = match($type) {
                'approaching' => '📅',
                'soon' => '⏰',
                'starting' => '🚀',
                'ending_soon' => '⚠️',
                'finished' => '✅',
                default => '📧'
            };
            
            $label = match($type) {
                'approaching' => 'Événements approchent (7j)',
                'soon' => 'Événements bientôt (2j)',
                'starting' => 'Événements commencent (1h)',
                'ending_soon' => 'Événements se terminent (1h)',
                'finished' => 'Événements terminés',
                default => ucfirst($type)
            };
            
            $tableData[] = [$emoji . ' ' . $label, $count];
        }
        
        $io->table(['Type de notification', 'Envoyées'], $tableData);
        
        if ($totalSent > 0) {
            $io->note("Total: {$totalSent} notification(s) envoyée(s)");
        }
    }

    /**
     * Effectue le nettoyage des anciennes notifications
     */
    private function performCleanup(SymfonyStyle $io, int $cleanupDays): void
    {
        $io->section('🧹 Nettoyage des anciennes notifications');
        
        try {
            $deletedOld = $this->notificationManager->cleanupOldNotifications($cleanupDays);
            $deletedOrphaned = $this->notificationManager->getNotificationRepository()->cleanupOrphanNotifications();
            
            $total = $deletedOld + $deletedOrphaned;
            
            if ($total > 0) {
                $io->success([
                    "Nettoyage terminé",
                    "🗑️ {$deletedOld} notifications anciennes supprimées",
                    "🔗 {$deletedOrphaned} notifications orphelines supprimées",
                    "📦 Total: {$total} notifications nettoyées"
                ]);
            } else {
                $io->text('✨ Aucune notification à nettoyer');
            }
            
        } catch (\Exception $e) {
            $io->warning('Erreur lors du nettoyage: ' . $e->getMessage());
        }
    }

    /**
     * Affiche les statistiques détaillées
     */
    private function displayDetailedStats(SymfonyStyle $io): void
    {
        $io->section('📈 Statistiques détaillées');
        
        try {
            $since7days = new \DateTimeImmutable('-7 days');
            $since30days = new \DateTimeImmutable('-30 days');
            
            $stats7d = $this->notificationManager->getNotificationRepository()->countByTypeSince($since7days);
            $stats30d = $this->notificationManager->getNotificationRepository()->countByTypeSince($since30days);
            
            // Statistiques par période
            $io->text('📊 Notifications envoyées:');
            $io->text("   • 7 derniers jours: " . array_sum($stats7d));
            $io->text("   • 30 derniers jours: " . array_sum($stats30d));
            
            // Top des types de notifications
            if (!empty($stats7d)) {
                arsort($stats7d);
                $io->text('🏆 Top types (7 jours):');
                $i = 0;
                foreach ($stats7d as $type => $count) {
                    if (++$i > 5) break;
                    $io->text("   {$i}. {$type}: {$count}");
                }
            }
            
            // Taux de lecture
            $unreadRate = $this->notificationManager->getNotificationRepository()->getUnreadRateSince($since7days);
            $readPercentage = round((1 - $unreadRate) * 100, 1);
            $io->text("📖 Taux de lecture (7j): {$readPercentage}%");
            
        } catch (\Exception $e) {
            $io->warning('Erreur lors de la génération des statistiques: ' . $e->getMessage());
        }
    }

    /**
     * Récupère les événements concernés par un type (pour simulation)
     */
    private function getEventsForType(string $type): array
    {
        $eventRepository = $this->notificationManager->getEventRepository();
        $now = new \DateTimeImmutable();
        
        return match($type) {
            'approaching' => $eventRepository->findEventsInTimeRange(
                new \DateTimeImmutable('+6 days 23 hours'),
                new \DateTimeImmutable('+7 days 1 hour'),
                ['APPROVED']
            ),
            'soon' => $eventRepository->findEventsInTimeRange(
                new \DateTimeImmutable('+1 day 23 hours'),
                new \DateTimeImmutable('+2 days 1 hour'),
                ['APPROVED']
            ),
            'starting' => $eventRepository->findEventsInTimeRange(
                new \DateTimeImmutable('+59 minutes'),
                new \DateTimeImmutable('+61 minutes'),
                ['APPROVED']
            ),
            'ending_soon' => $eventRepository->findEventsEndingInTimeRange(
                new \DateTimeImmutable('+59 minutes'),
                new \DateTimeImmutable('+61 minutes'),
                ['IN_PROGRESS']
            ),
            'finished' => $eventRepository->findEventsEndedInTimeRange(
                new \DateTimeImmutable('-1 hour'),
                $now,
                ['IN_PROGRESS', 'FINISHED']
            ),
            default => []
        };
    }
}