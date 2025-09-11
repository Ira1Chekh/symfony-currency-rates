<?php

namespace App\Domain\Repository;

use App\Domain\Entity\CryptoRate;

interface CryptoRateRepositoryInterface
{
    public function add(CryptoRate $rate): void;
    public function save(CryptoRate $rate): void;
    public function flush(): void;
    public function beginTransaction(): void;
    public function commit(): void;
    public function rollback(): void;
    public function bulkUpsert(array $rates): void;

    public function findByPairAndTimeRange(
        string $pair,
        \DateTimeInterface $startDate,
        \DateTimeInterface $endDate
    ): array;

    public function findByPairAndDate(string $pair, \DateTimeInterface $date): array;
}
