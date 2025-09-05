<?php

namespace App\Application\UseCase;

use App\Application\DTO\RateResponseDto;
use App\Application\DTO\RatesRequestDto;
use App\Domain\Repository\CryptoRateRepositoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class GetRatesLast24h
{
    public function __construct(
        private CryptoRateRepositoryInterface $cryptoRateRepository,
        private TagAwareCacheInterface $cache,
        private LoggerInterface $logger,
    ) {}

    public function execute(RatesRequestDto $dto): array
    {
        try {
            $endDate = new \DateTimeImmutable();
            $startDate = $endDate->sub(new \DateInterval('P1D'));

            $cacheKey = $this->createSafeCacheKey('rates_24h', $dto->pair, $endDate->format('Y-m-d_H:i'));

            return $this->cache->get($cacheKey, function (ItemInterface $item) use ($dto, $startDate, $endDate) {
                $item->expiresAfter(310);

                $rates = $this->cryptoRateRepository->findByPairAndTimeRange(
                    $dto->pair,
                    $startDate,
                    $endDate
                );

                return $this->formatRatesResponse($rates);
            });

        } catch (\Throwable $e) {
            $this->logger->error('Failed to fetch last-24h rates', ['exception' => $e]);
            throw $e;
        }
    }

    private function formatRatesResponse(array $rates): array
    {
        return array_map(fn($rate) => new RateResponseDto(
            pair: $rate->getPair(),
            price: (float) $rate->getPrice(),
            timestamp: $rate->getCreatedAt()->format(\DateTimeInterface::ATOM)
        ), $rates);
    }

    private function createSafeCacheKey(string $prefix, string $pair, string $suffix): string
    {
        return 'cache_' . md5($prefix . $pair . $suffix);
    }
}
