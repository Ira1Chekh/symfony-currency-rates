<?php

namespace App\Infrastructure\ApiClient;

use App\Domain\Service\RateProviderInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\Retry\GenericRetryStrategy;
use Symfony\Component\HttpClient\RetryableHttpClient;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class BinanceApiClient implements RateProviderInterface
{
    private RetryableHttpClient $retryableClient;
    private const int TIMEOUT = 10;
    private const string USER_AGENT = 'CryptoRateAPI/1.0';
    private const array ALLOWED_API_HOSTS = [
        'api.binance.com',
        'api-gcp.binance.com',
        'api1.binance.com',
        'api2.binance.com',
        'api3.binance.com',
        'api4.binance.com',
    ];

    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger,
        private string $apiUrl
    ) {
        $this->apiUrl = rtrim($this->apiUrl, '/');

        $host = parse_url($this->apiUrl, PHP_URL_HOST);

        if ($host === false || !in_array($host, self::ALLOWED_API_HOSTS, true)) {
            throw new \InvalidArgumentException(sprintf(
                'Invalid Binance API URL configured: %s',
                $this->apiUrl
            ));
        }

        $this->retryableClient = new RetryableHttpClient(
            $this->httpClient,
            new GenericRetryStrategy(
                [429, 500, 502, 503, 504],
                1000,
                2.0,
                3000
            ),
            2
        );
    }

    public function getCurrentRate(string $pair): float
    {
        $url = $this->buildApiUrl($pair);

        try {
            $data = $this->fetchJsonFromApi($url, $pair);
            return $this->extractAndValidatePrice($data, $pair);

        } catch (TransportExceptionInterface $e) {
            $this->handleError($e, $pair, 'Network error');
        } catch (\Throwable $e) {
            $this->handleError($e, $pair, 'Unexpected error');
        }
    }

    private function buildApiUrl(string $pair): string
    {
        $normalizedPair = strtoupper(str_replace('/', '', $pair));

        return sprintf('%s/ticker/price?symbol=%s', $this->apiUrl, $normalizedPair);
    }

    /**
     * @throws TransportExceptionInterface
     */
    private function fetchJsonFromApi(string $url, string $pair): array
    {
        $response = $this->retryableClient->request('GET', $url, [
            'timeout' => self::TIMEOUT,
            'headers' => [
                'Accept' => 'application/json',
                'User-Agent' => self::USER_AGENT
            ],
        ]);

        $status = $response->getStatusCode();
        if ($status !== 200) {
            throw new \RuntimeException(sprintf(
                'Binance API returned %d status code for pair %s',
                $status,
                $pair
            ));
        }

        return $response->toArray();
    }

    private function extractAndValidatePrice(array $data, string $pair): float
    {
        if (!isset($data['price'])) {
            $this->logger->error('Binance API response missing price key', [
                'response' => $data,
                'pair' => $pair
            ]);
            throw new \RuntimeException('Invalid response format from Binance API');
        }

        $price = (float) $data['price'];

        if ($price <= 0) {
            $this->logger->error('Invalid price value received', [
                'price' => $price,
                'pair' => $pair
            ]);
            throw new \RuntimeException('Invalid price value received from Binance API');
        }

        return $price;
    }

    private function handleError(\Throwable $e, string $pair, string $context): void
    {
        $this->logger->error("$context while fetching rate from Binance", [
            'error' => $e->getMessage(),
            'pair' => $pair,
            'exception' => $e
        ]);

        throw new \RuntimeException(sprintf(
            '%s for %s: %s',
            $context,
            $pair,
            $e->getMessage()
        ), 0, $e);
    }
}
