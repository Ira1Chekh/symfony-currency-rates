<?php

namespace App\Application\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class RatesRequestDto
{
    #[Assert\NotBlank]
    #[Assert\Choice(callback: [self::class, 'getTrackedPairs'])]
    public ?string $pair = null;

    #[Assert\NotBlank(allowNull: true)]
    #[Assert\DateTime(format: 'Y-m-d')]
    public ?string $date = null;

    public static array $trackedPairs = [];

    public static function getTrackedPairs(): array
    {
        return self::$trackedPairs;
    }
}
