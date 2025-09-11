<?php

namespace App\Presentation\Controller\Api;

use App\Application\DTO\RatesRequestDto;
use App\Application\Exception\ValidationException;
use App\Application\UseCase\GetRatesByDay;
use App\Application\UseCase\GetRatesLast24h;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/rates')]
class RatesController extends AbstractController
{
    public function __construct(
        private ValidatorInterface $validator,
        private GetRatesLast24h $getRatesLast24h,
        private GetRatesByDay $getRatesByDay,
        private SerializerInterface $serializer
    ) {}

    #[Route('/last-24h', name: 'api_rates_last_24h', methods: ['GET'])]
    public function getLast24Hours(RatesRequestDto $dto): JsonResponse
    {
        $errors = $this->validateDto($dto);

        if ($errors) {
            throw new ValidationException('Invalid request', $errors);
        }

        $data = $this->getRatesLast24h->execute($dto);
        $json = $this->serializer->serialize(
            ['data' => $data],
            'json',
            ['groups' => ['rates']]
        );

        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }

    #[Route('/day', name: 'api_rates_day', methods: ['GET'])]
    public function getDayData(RatesRequestDto $dto): JsonResponse
    {
        $errors = $this->validateDto($dto);

        if ($errors) {
            throw new ValidationException('Invalid request', $errors);
        }

        $data = $this->getRatesByDay->execute($dto);
        $json = $this->serializer->serialize(
            ['data' => $data],
            'json',
            ['groups' => ['rates']]
        );

        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }

    private function validateDto(object $dto): array
    {
        $violations = $this->validator->validate($dto);
        $errors = [];

        foreach ($violations as $violation) {
            $propertyPath = $violation->getPropertyPath();
            if (!isset($errors[$propertyPath])) {
                $errors[$propertyPath] = [];
            }
            $errors[$propertyPath][] = $violation->getMessage();
        }

        return $errors;
    }
}
