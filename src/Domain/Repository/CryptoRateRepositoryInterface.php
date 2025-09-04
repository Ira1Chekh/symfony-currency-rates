<?php

namespace App\Domain\Repository;

use App\Domain\Entity\CryptoRate;

interface CryptoRateRepositoryInterface
{
    public function save(CryptoRate $rate): void;

    public function findByPairAndTimeRange(
        string $pair,
        \DateTimeInterface $startDate,
        \DateTimeInterface $endDate
    ): array;

    public function findByPairAndDate(string $pair, \DateTimeInterface $date): array;
}
