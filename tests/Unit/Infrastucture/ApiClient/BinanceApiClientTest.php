<?php

namespace App\Tests\Unit\Infrastucture\ApiClient;

use App\Infrastructure\ApiClient\BinanceApiClient;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class BinanceApiClientTest extends TestCase
{
    private $logger;
    private $client;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->client = new BinanceApiClient(
            $this->createMock(HttpClientInterface::class),
            $this->logger,
            'https://api.binance.com/api/v3'
        );
    }

    public function testExtractPriceFromResponseWithValidData(): void
    {
        $reflection = new \ReflectionClass($this->client);
        $method = $reflection->getMethod('extractPriceFromResponse');
        $method->setAccessible(true);

        $testData = ['price' => '50000.50'];
        $result = $method->invokeArgs($this->client, [$testData, 'BTC/EUR']);

        $this->assertSame(50000.50, $result);
    }

    public function testExtractPriceFromResponseWithInvalidPrice(): void
    {
        $this->logger->expects($this->once())
            ->method('error')
            ->with('Invalid price value received');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid price value received');

        $reflection = new \ReflectionClass($this->client);
        $method = $reflection->getMethod('extractPriceFromResponse');
        $method->setAccessible(true);

        $method->invokeArgs($this->client, [['price' => '0'], 'BTC/EUR']);
    }

    public function testExtractPriceFromResponseWithNegativePrice(): void
    {
        $this->logger->expects($this->once())
            ->method('error')
            ->with('Invalid price value received');

        $this->expectException(\RuntimeException::class);

        $reflection = new \ReflectionClass($this->client);
        $method = $reflection->getMethod('extractPriceFromResponse');
        $method->setAccessible(true);

        $method->invokeArgs($this->client, [['price' => '-100.50'], 'BTC/EUR']);
    }

}
