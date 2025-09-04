<?php

namespace App\Tests\Unit\Infrastucture\ApiClient;

use App\Infrastructure\ApiClient\BinanceApiClient;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class BinanceApiClientTest extends TestCase
{
    public function testGetCurrentRateReturnsFloat()
    {
        $httpClient = $this->createMock(HttpClientInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $logger = $this->createMock(LoggerInterface::class);

        $response->method('getStatusCode')->willReturn(200);
        $response->method('toArray')->willReturn(['price' => '1234.56']);

        $httpClient->method('request')->willReturn($response);

        $client = new BinanceApiClient($httpClient, $logger, 'https://api.binance.com/api/v3');

        $rate = $client->getCurrentRate('BTCEUR');

        $this->assertSame(1234.56, $rate);
    }
}
