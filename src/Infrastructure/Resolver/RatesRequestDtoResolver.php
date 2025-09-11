<?php

namespace App\Infrastructure\Resolver;

use App\Application\DTO\RatesRequestDto;
use App\Domain\ValueObject\CryptoPair;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

class RatesRequestDtoResolver implements ValueResolverInterface
{

    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        if ($argument->getType() !== RatesRequestDto::class) {
            return [];
        }

        RatesRequestDto::$trackedPairs = CryptoPair::values();

        $dto = new RatesRequestDto();
        $dto->pair = $request->query->get('pair');
        $dto->date = $request->query->get('date');

        return [$dto];
    }
}
