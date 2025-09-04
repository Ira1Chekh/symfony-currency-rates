<?php

namespace App\Application\Service;

use App\Domain\Entity\CryptoRate;
use App\Domain\Repository\CryptoRateRepositoryInterface;
use App\Domain\Service\RateProviderInterface;
use Psr\Log\LoggerInterface;

class RateUpdateService
{
    public function __construct(
        private RateProviderInterface $rateProvider,
        private CryptoRateRepositoryInterface $cryptoRateRepository,
        private LoggerInterface $logger
    ) {
    }

    public function updateRateForPair(string $pair): void
    {
        try {
            $price = $this->rateProvider->getCurrentRate($pair);
            $cryptoRate = new CryptoRate($pair, $price);
            $this->cryptoRateRepository->save($cryptoRate);
        } catch (\Exception $e) {
            $this->logger->error('Failed to update rate', ['pair' => $pair, 'error' => $e->getMessage()]);

            throw new \RuntimeException(sprintf('Failed to update rate for %s: %s', $pair, $e->getMessage()), 0, $e);
        }
    }

    public function updateAllRates(array $pairs): void
    {
        foreach ($pairs as $pair) {
            try {
                $this->updateRateForPair($pair);
            } catch (\Exception $e) {
                $this->logger->error('Error updating pair, continuing with others', ['pair' => $pair, 'error' => $e->getMessage()]);
            }
        }
    }
}
