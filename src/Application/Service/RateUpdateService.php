<?php

namespace App\Application\Service;

use App\Domain\Entity\CryptoRate;
use App\Domain\Repository\CryptoRateRepositoryInterface;
use App\Domain\Service\RateProviderInterface;
use App\Domain\ValueObject\CryptoPair;
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
            $this->saveRate($pair, $price);
        } catch (\Throwable $e) {
            $this->logAndThrow($pair, $e, 'Failed to update rate');
        }
    }

    public function updateAllRates(array $pairs): void
    {
        foreach ($pairs as $pair) {
            try {
                $this->updateRateForPair($pair);
            } catch (\Throwable $e) {
                $this->logger->error('Error updating pair, continuing with others', [
                    'pair' => $pair,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    private function saveRate(string $pair, float $price): void
    {
        $rate = new CryptoRate(CryptoPair::from($pair), $price);
        $this->cryptoRateRepository->save($rate);
    }

    private function logAndThrow(string $pair, \Throwable $e, string $context): void
    {
        $this->logger->error($context, [
            'pair' => $pair,
            'error' => $e->getMessage(),
            'exception' => $e
        ]);

        throw new \RuntimeException(sprintf('%s for %s: %s', $context, $pair, $e->getMessage()), 0, $e);
    }
}
