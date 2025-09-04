<?php

namespace App\Application\Service;

class CryptoConfig
{
    private const array TRACKED_PAIRS = [
        'BTC/EUR',
        'ETH/EUR',
        'LTC/EUR',
    ];

    public function getTrackedPairs(): array
    {
        return self::TRACKED_PAIRS;
    }

    public function isValidPair(string $pair): bool
    {
        return in_array($pair, self::TRACKED_PAIRS, true);
    }
}
