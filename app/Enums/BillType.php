<?php

declare(strict_types=1);

namespace App\Enums;

enum BillType: string
{
    case Sewa = 'sewa';
    case Deposit = 'deposit';
    case Tambahan = 'tambahan';

    public function label(): string
    {
        return match ($this) {
            self::Sewa => 'Sewa Kamar',
            self::Deposit => 'Deposit Jaminan',
            self::Tambahan => 'Biaya Tambahan',
        };
    }

    public function badgeVariant(): string
    {
        return match ($this) {
            self::Sewa => 'aktif',
            self::Deposit => 'warning',
            self::Tambahan => 'secondary',
        };
    }

    /**
     * Get all possible raw values.
     *
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
