<?php

namespace App\Tests\Unit\Infrastucture\ApiClient;

use App\Infrastructure\ApiClient\BinanceApiClient;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class BinanceApiClientTest extends TestCase
{
    private const string VALID_API_URL = 'https://api.binance.com';
    private const string VALID_PAIR = 'BTCEUR';
    private const float VALID_PRICE = 1234.56;

    public function testGetCurrentRateReturnsFloat(): void
    {
        $mockResponse = new MockResponse(
            json_encode(['price' => self::VALID_PRICE]),
            ['http_code' => 200]
        );

        $httpClient = new MockHttpClient($mockResponse);
        $logger = $this->createMock(LoggerInterface::class);

        $client = new BinanceApiClient($httpClient, $logger, self::VALID_API_URL);

        $rate = $client->getCurrentRate(self::VALID_PAIR);

        $this->assertSame(self::VALID_PRICE, $rate);
        $this->assertSame('GET', $mockResponse->getRequestMethod());
        $this->assertStringContainsString('/ticker/price?symbol=' . self::VALID_PAIR, $mockResponse->getRequestUrl());
    }

    public function testGetCurrentRateThrowsOnNon200Status(): void
    {
        $mockResponse = new MockResponse('', ['http_code' => 500]);
        $httpClient = new MockHttpClient($mockResponse);
        $logger = $this->createMock(LoggerInterface::class);

        $logger->expects($this->once())
            ->method('error')
            ->with(
                'Network error while fetching rate from Binance',
                $this->arrayHasKey('exception')
            );

        $client = new BinanceApiClient($httpClient, $logger, self::VALID_API_URL);

        $this->expectException(\RuntimeException::class);

        $client->getCurrentRate(self::VALID_PAIR);
    }
}
