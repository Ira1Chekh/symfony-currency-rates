<?php

namespace App\Presentation\Controller\Api;

use App\Application\Service\CryptoConfig;
use App\Domain\Repository\CryptoRateRepositoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

#[Route('/api/rates')]
class RatesController extends AbstractController
{
    public function __construct(
        private CryptoRateRepositoryInterface $cryptoRateRepository,
        private ValidatorInterface $validator,
        private CryptoConfig $cryptoConfig,
        private TagAwareCacheInterface $cache,
        private LoggerInterface $logger
    ) {
    }

    #[Route('/last-24h', name: 'api_rates_last_24h', methods: ['GET'])]
    public function getLast24Hours(Request $request): JsonResponse
    {
        $pair = $request->query->get('pair');
        $errors = $this->validateRequestParams(['pair' => $pair]);

        if (count($errors) > 0) {
            return $this->json(['errors' => $errors], Response::HTTP_BAD_REQUEST);
        }

        try {
            $endDate = new \DateTimeImmutable();
            $startDate = $endDate->sub(new \DateInterval('P1D'));
            $cacheKey = $this->createSafeCacheKey(
                'rates_24h',
                $pair,
                (new \DateTime())->format('Y-m-d_H:i')
            );

            $responseData = $this->cache->get($cacheKey, function (ItemInterface $item) use ($pair, $startDate, $endDate) {
                $item->expiresAfter(310);

                $rates = $this->cryptoRateRepository->findByPairAndTimeRange(
                    $pair,
                    $startDate,
                    $endDate
                );

                return $this->formatRatesResponse($rates);
            });

            return $this->json($responseData);

        } catch (\Exception $e) {
            return $this->json(
                ['error' => 'Failed to fetch rates data'.$e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    #[Route('/day', name: 'api_rates_day', methods: ['GET'])]
    public function getDayData(Request $request): JsonResponse
    {
        $pair = $request->query->get('pair');
        $dateString = $request->query->get('date');

        $errors = $this->validateRequestParams([
            'pair' => $pair,
            'date' => $dateString
        ]);

        if (count($errors) > 0) {
            return $this->json(['errors' => $errors], Response::HTTP_BAD_REQUEST);
        }

        try {
            $date = new \DateTimeImmutable($dateString);
            $cacheKey = $this->createSafeCacheKey(
                'rates_day',
                $pair,
                (new \DateTime())->format('Y-m-d')
            );

            $responseData = $this->cache->get($cacheKey, function (ItemInterface $item) use ($pair, $date) {
                $isToday = $date->format('Y-m-d') === new \DateTime()->format('Y-m-d');
                $ttl = $isToday ? 310 : 86400;
                $item->expiresAfter($ttl);

                $rates = $this->cryptoRateRepository->findByPairAndDate(
                    $pair,
                    $date
                );

                return $this->formatRatesResponse($rates);
            });

            return $this->json($responseData);

        } catch (\Exception $e) {
            return $this->json(
                ['error' => 'Invalid date format or failed to fetch data'],
                Response::HTTP_BAD_REQUEST
            );
        }
    }

    private function validateRequestParams(array $params): array
    {
        $constraints = new Assert\Collection([
            'pair' => [
                new Assert\NotBlank(),
                new Assert\Choice(choices: $this->cryptoConfig->getTrackedPairs())
            ],
            'date' => [
                new Assert\Optional([
                    new Assert\NotBlank(),
                    new Assert\DateTime(format: 'Y-m-d')
                ])
            ],
        ]);

        $violations = $this->validator->validate($params, $constraints);
        $errors = [];

        foreach ($violations as $violation) {
            $field = preg_replace('/[\[\]]/', '', $violation->getPropertyPath());
            $errors[$field] = $violation->getMessage();
        }

        return $errors;
    }

    private function formatRatesResponse(array $rates): array
    {
        return array_map(function ($rate) {
            return [
                'pair' => $rate->getPair(),
                'price' => (float) $rate->getPrice(),
                'timestamp' => $rate->getCreatedAt()->format('c')
            ];
        }, $rates);
    }

    private function createSafeCacheKey(string $prefix, string $pair, string $suffix): string
    {
        $keyString = $prefix . $pair . $suffix;

        return 'cache_' . md5($keyString);
    }
}
