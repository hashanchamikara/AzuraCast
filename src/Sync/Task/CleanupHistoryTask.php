<?php

namespace App\Sync\Task;

use App\Doctrine\ReloadableEntityManagerInterface;
use App\Entity;
use Psr\Log\LoggerInterface;

class CleanupHistoryTask extends AbstractTask
{
    public function __construct(
        protected Entity\Repository\SettingsRepository $settingsRepo,
        protected Entity\Repository\SongHistoryRepository $historyRepo,
        protected Entity\Repository\StationQueueRepository $queueRepo,
        protected Entity\Repository\ListenerRepository $listenerRepo,
        ReloadableEntityManagerInterface $em,
        LoggerInterface $logger
    ) {
        parent::__construct($em, $logger);
    }

    public function run(bool $force = false): void
    {
        $settings = $this->settingsRepo->readSettings();
        $daysToKeep = $settings->getHistoryKeepDays();

        if ($daysToKeep !== 0) {
            $this->historyRepo->cleanup($daysToKeep);
            $this->queueRepo->cleanup($daysToKeep);
            $this->listenerRepo->cleanup($daysToKeep);
        }
    }
}
