<?php

namespace App\Application\Service;

use App\Application\Exception\ServiceException;
use App\Domain\Entity\CryptoRate;
use App\Domain\Repository\CryptoRateRepositoryInterface;
use App\Domain\Service\RateProviderInterface;
use App\Domain\ValueObject\CryptoPair;
use Psr\Log\LoggerInterface;

class RateUpdateService
{
    private const int BATCH_SIZE = 50;
    private const int UPDATE_DELAY = 100000;

    public function __construct(
        private RateProviderInterface $rateProvider,
        private CryptoRateRepositoryInterface $cryptoRateRepository,
        private LoggerInterface $logger
    ) {}

    public function updateRateForPair(string $pair): void
    {
        try {
            $price = $this->rateProvider->getCurrentRate($pair);
            $cryptoPair = CryptoPair::from($pair);
            $rate = new CryptoRate($cryptoPair, $price);

            $this->cryptoRateRepository->beginTransaction();
            $this->cryptoRateRepository->save($rate);
            $this->cryptoRateRepository->commit();

        } catch (\Throwable $e) {
            $this->cryptoRateRepository->rollback();
            $this->logger->error('Failed to update rate for pair', [
                'pair' => $pair,
                'exception' => $e->getMessage()
            ]);
            throw new ServiceException('Failed to update rate for ' . $pair, 0);
        }
    }

    public function updateAllRates(array $pairs): void
    {
        $rates = [];
        $processed = 0;

        foreach ($pairs as $pair) {
            try {
                $price = $this->rateProvider->getCurrentRate($pair);
                $cryptoPair = CryptoPair::from($pair);
                $rates[] = new CryptoRate($cryptoPair, $price);

                if (count($rates) >= self::BATCH_SIZE) {
                    $this->processBatch($rates);
                    $rates = [];
                    usleep(self::UPDATE_DELAY);
                }

                $processed++;

            } catch (\Throwable $e) {
                $this->logger->error('Error updating pair, skipping', [
                    'pair' => $pair,
                    'error' => $e->getMessage()
                ]);
            }
        }

        if (!empty($rates)) {
            $this->processBatch($rates);
        }

        $this->logger->info('Rate update completed', [
            'total_pairs' => count($pairs),
            'processed' => $processed,
            'failed' => count($pairs) - $processed
        ]);
    }

    private function processBatch(array $rates): void
    {
        try {
            $this->cryptoRateRepository->beginTransaction();

            foreach ($rates as $rate) {
                $this->cryptoRateRepository->add($rate);
            }

            $this->cryptoRateRepository->flush();
            $this->cryptoRateRepository->commit();

        } catch (\Throwable $e) {
            $this->cryptoRateRepository->rollback();
            $this->logger->error('Batch processing failed', [
                'batch_size' => count($rates),
                'error' => $e->getMessage()
            ]);
            throw new ServiceException('Batch processing failed');
        }
    }
}
