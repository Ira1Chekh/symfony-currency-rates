<?php

namespace App\Infrastructure\Factory;

use App\Application\DTO\RatesRequestDto;
use Symfony\Component\HttpFoundation\Request;

class RatesRequestFactory
{
    public function fromRequest(Request $request, array $trackedPairs): RatesRequestDto
    {
        RatesRequestDto::$trackedPairs = $trackedPairs;

        $dto = new RatesRequestDto();
        $dto->pair = $request->query->get('pair');
        $dto->date = $request->query->get('date');

        return $dto;
    }

}
