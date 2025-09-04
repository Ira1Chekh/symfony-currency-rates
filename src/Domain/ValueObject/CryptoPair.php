<?php

namespace App\Domain\ValueObject;

enum CryptoPair: string
{
    case BTC_EUR = 'BTC/EUR';
    case ETH_EUR = 'ETH/EUR';
    case LTC_EUR = 'LTC/EUR';

    public static function values(): array
    {
        return array_map(fn(CryptoPair $pair) => $pair->value, self::cases());
    }
}
