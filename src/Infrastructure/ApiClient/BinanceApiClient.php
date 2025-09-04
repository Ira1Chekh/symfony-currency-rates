<?php

namespace App\Infrastructure\ApiClient;

use App\Domain\Service\RateProviderInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class BinanceApiClient implements RateProviderInterface
{
    private const int TIMEOUT = 10;
    private const string USER_AGENT = 'CryptoRateAPI/1.0';

    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger,
        private string $apiUrl
    ) {
    }

    public function getCurrentRate(string $pair): float
    {
        $url = $this->buildApiUrl($pair);

        try {
            $response = $this->makeHttpRequest($url);
            $this->validateResponseStatusCode($response, $pair);
            $data = $response->toArray();
            $this->validateResponseData($data, $pair);

            return $this->extractPriceFromResponse($data, $pair);

        } catch (TransportExceptionInterface $e) {
            $this->handleNetworkError($e, $pair);
        } catch (\Exception $e) {
            $this->handleUnexpectedError($e, $pair);
        }
    }

    private function buildApiUrl(string $pair): string
    {
        $normalizedPair = strtoupper(str_replace('/', '', $pair));

        return sprintf('%s/ticker/price?symbol=%s', $this->apiUrl, $normalizedPair);
    }

    private function makeHttpRequest(string $url): ResponseInterface
    {
        return $this->httpClient->request('GET', $url, [
            'timeout' => self::TIMEOUT,
            'headers' => [
                'Accept' => 'application/json',
                'User-Agent' => self::USER_AGENT
            ],
        ]);
    }

    /**
     * @throws TransportExceptionInterface
     */
    private function validateResponseStatusCode(ResponseInterface $response, string $pair): void
    {
        $statusCode = $response->getStatusCode();

        if ($statusCode !== 200) {
            throw new \RuntimeException(sprintf(
                'Binance API returned %d status code for pair %s',
                $statusCode,
                $pair
            ));
        }
    }

    private function validateResponseData(array $data, string $pair): void
    {
        if (!isset($data['price'])) {
            $this->logger->error('Binance API response missing price key', [
                'response' => $data,
                'pair' => $pair
            ]);
            throw new \RuntimeException('Invalid response format from Binance API');
        }
    }

    private function extractPriceFromResponse(array $data, string $pair): float
    {
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

    private function handleNetworkError(TransportExceptionInterface $e, string $pair): void
    {
        $this->logger->error('Network error while fetching rate from Binance', [
            'error' => $e->getMessage(),
            'pair' => $pair
        ]);
        throw new \RuntimeException(sprintf(
            'Network error while fetching rate for %s: %s',
            $pair,
            $e->getMessage()
        ), 0, $e);
    }

    private function handleUnexpectedError(\Exception $e, string $pair): void
    {
        $this->logger->error('Unexpected error while fetching rate from Binance', [
            'error' => $e->getMessage(),
            'pair' => $pair,
            'exception' => $e
        ]);
        throw new \RuntimeException(sprintf(
            'Unexpected error while fetching rate for %s: %s',
            $pair,
            $e->getMessage()
        ), 0, $e);
    }
}
