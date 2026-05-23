<?php

namespace App\Services\DCB;

use InvalidArgumentException;

class DCBFactory
{
    // Phone prefix → operator name mapping
    private const PREFIX_MAP = [
        '013' => 'Grameenphone',
        '017' => 'Grameenphone',
        '014' => 'Banglalink',
        '019' => 'Banglalink',
        '018' => 'Robi',       // Robi
        '016' => 'Robi',       // Airtel → Robi
        '015' => 'Teletalk',
    ];

    public static function detectOperator(string $phone): ?string
    {
        $clean = preg_replace('/\D/', '', $phone);

        // Normalise to 11-digit (01XXXXXXXXX)
        if (strlen($clean) === 10 && $clean[0] === '1') {
            $clean = '0' . $clean;
        }

        if (strlen($clean) < 3) return null;

        $prefix = substr($clean, 0, 3);
        return self::PREFIX_MAP[$prefix] ?? null;
    }

    public static function make(string $operator): DCBInterface
    {
        return match ($operator) {
            'Robi'       => new RobiService(),
            'Banglalink' => new BanglalinkService(),
            default      => throw new InvalidArgumentException("No DCB service for operator: {$operator}"),
        };
    }
}
