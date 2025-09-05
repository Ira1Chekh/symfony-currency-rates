<?php

namespace App\Application\DTO;

use Symfony\Component\Serializer\Annotation\Groups;

class RateResponseDto
{
    public function __construct(
        #[Groups(['rates'])]
        public string $pair,

        #[Groups(['rates'])]
        public float $price,

        #[Groups(['rates'])]
        public string $timestamp
    ) {}
}
