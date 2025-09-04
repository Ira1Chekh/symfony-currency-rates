<?php

namespace App\Presentation\Controller\Api;

use App\Application\UseCase\GetRatesByDay;
use App\Application\UseCase\GetRatesLast24h;
use App\Domain\ValueObject\CryptoPair;
use App\Infrastructure\Factory\RatesRequestFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[Route('/api/rates')]
class RatesController extends AbstractController
{
    public function __construct(
        private RatesRequestFactory $requestFactory,
        private ValidatorInterface $validator,
        private GetRatesLast24h $getRatesLast24h,
        private GetRatesByDay $getRatesByDay,
    ) {}

    #[Route('/last-24h', name: 'api_rates_last_24h', methods: ['GET'])]
    public function getLast24Hours(Request $request): JsonResponse
    {
        $dto = $this->requestFactory->fromRequest($request, CryptoPair::values());
        $errors = $this->validateDto($dto);

        if ($errors) {
            return $this->json(['error' => $errors], Response::HTTP_BAD_REQUEST);
        }

        try {
            $data = $this->getRatesLast24h->execute($dto);
            return $this->json(['data' => $data]);

        } catch (\Throwable) {
            return $this->json(['error' => 'Failed to fetch rates'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/day', name: 'api_rates_day', methods: ['GET'])]
    public function getDayData(Request $request): JsonResponse
    {
        $dto = $this->requestFactory->fromRequest($request, CryptoPair::values());
        $errors = $this->validateDto($dto);

        if ($errors) {
            return $this->json(['error' => $errors], Response::HTTP_BAD_REQUEST);
        }

        try {
            $data = $this->getRatesByDay->execute($dto);
            return $this->json(['data' => $data]);

        } catch (\Throwable) {
            return $this->json(['error' => 'Invalid date format or failed to fetch data'], Response::HTTP_BAD_REQUEST);
        }
    }

    private function validateDto(object $dto): array
    {
        $violations = $this->validator->validate($dto);
        $errors = [];

        foreach ($violations as $violation) {
            $errors[$violation->getPropertyPath()] = $violation->getMessage();
        }

        return $errors;
    }
}
