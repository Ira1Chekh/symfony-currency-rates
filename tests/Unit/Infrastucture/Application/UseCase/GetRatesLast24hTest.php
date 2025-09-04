<?php

namespace App\Tests\Unit\Infrastucture\Application\UseCase;

use App\Application\DTO\RatesRequestDto;
use App\Application\UseCase\GetRatesLast24h;
use App\Domain\Entity\CryptoRate;
use App\Domain\Repository\CryptoRateRepositoryInterface;
use App\Domain\ValueObject\CryptoPair;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use stdClass;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class GetRatesLast24hTest extends TestCase
{
    public function testExecuteReturnsFormattedRates()
    {
        $pair = CryptoPair::BTC_EUR->value;
        $price = 30000.0;
        $dto = new RatesRequestDto();
        $dto->pair = $pair;

        $rate = new CryptoRate(CryptoPair::BTC_EUR, $price);

        $repository = $this->createMock(CryptoRateRepositoryInterface::class);
        $repository->method('findByPairAndTimeRange')
            ->willReturn([$rate]);

        $cache = $this->createMock(TagAwareCacheInterface::class);
        $cache->method('get')->willReturnCallback(function ($key, $callback) {
            $item = $this->createMock(\Symfony\Contracts\Cache\ItemInterface::class);
            $item->method('expiresAfter')->willReturnSelf();
            return $callback($item);
        });

        $logger = $this->createMock(LoggerInterface::class);

        $useCase = new GetRatesLast24h($repository, $cache, $logger);

        $result = $useCase->execute($dto);

        $this->assertEquals([
            [
                'pair' => $pair,
                'price' => $price,
                'timestamp' => $rate->getCreatedAt()->format(\DateTimeInterface::ATOM)
            ]
        ], $result);
    }
}
