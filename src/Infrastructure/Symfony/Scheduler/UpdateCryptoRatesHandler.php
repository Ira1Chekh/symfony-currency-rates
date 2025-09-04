<?php

namespace App\Infrastructure\Symfony\Scheduler;

use App\Application\Service\CryptoConfig;
use App\Application\Service\RateUpdateService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Scheduler\Attribute\AsPeriodicTask;

#[AsPeriodicTask(frequency: '5 minutes')]
class UpdateCryptoRatesHandler
{
    public function __construct(
        private RateUpdateService $rateUpdateService,
        private LoggerInterface $logger,
        private CryptoConfig $cryptoConfig,
    ) {}

    public function __invoke(): void
    {
        try {
            $this->rateUpdateService->updateAllRates($this->cryptoConfig->getTrackedPairs());
        } catch (\Exception $e) {
            $this->logger->error('Failed to update crypto rates', ['exception' => $e]);
        }
    }
}
