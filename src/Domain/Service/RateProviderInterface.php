<?php

namespace App\Domain\Service;

interface RateProviderInterface
{
    public function getCurrentRate(string $pair): float;
}
