<?php

namespace App\Application\UseCase;

use App\Application\DTO\RateResponseDto;
use App\Application\DTO\RatesRequestDto;
use App\Domain\Repository\CryptoRateRepositoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class GetRatesByDay
{
    public function __construct(
        private CryptoRateRepositoryInterface $cryptoRateRepository,
        private TagAwareCacheInterface $cache,
        private LoggerInterface $logger,
    ) {}

    public function execute(RatesRequestDto $dto): array
    {
        try {
            $date = new \DateTimeImmutable($dto->date);
            $isToday = $date->format('Y-m-d') === new \DateTimeImmutable()->format('Y-m-d');

            $cacheKey = $this->createSafeCacheKey('rates_day', $dto->pair, $date->format('Y-m-d'));

            return $this->cache->get($cacheKey, function (ItemInterface $item) use ($dto, $date, $isToday) {
                $ttl = $isToday ? 310 : 86400;
                $item->expiresAfter($ttl);

                $rates = $this->cryptoRateRepository->findByPairAndDate(
                    $dto->pair,
                    $date
                );

                return $this->formatRatesResponse($rates);
            });

        } catch (\Throwable $e) {
            $this->logger->error('Failed to fetch daily rates', ['exception' => $e]);
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
