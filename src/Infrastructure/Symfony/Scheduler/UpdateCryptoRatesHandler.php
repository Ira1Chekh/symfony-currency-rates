<?php

namespace App\Infrastructure\Symfony\Scheduler;

use App\Application\Service\RateUpdateService;
use App\Domain\ValueObject\CryptoPair;
use Psr\Log\LoggerInterface;
use Symfony\Component\Scheduler\Attribute\AsPeriodicTask;

#[AsPeriodicTask(frequency: '5 minutes')]
class UpdateCryptoRatesHandler
{
    public function __construct(
        private RateUpdateService $rateUpdateService,
        private LoggerInterface $logger
    ) {}

    public function __invoke(): void
    {
        try {
            $this->rateUpdateService->updateAllRates(CryptoPair::values());
        } catch (\Exception $e) {
            $this->logger->error('Failed to update crypto rates', ['exception' => $e->getMessage()]);
        }
    }
}
