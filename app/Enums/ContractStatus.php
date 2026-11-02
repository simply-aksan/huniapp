<?php

declare(strict_types=1);

namespace App\Enums;

enum ContractStatus: string
{
    case Draft = 'draft';
    case Aktif = 'aktif';
    case Selesai = 'selesai';
    case Dibatalkan = 'dibatalkan';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Aktif => 'Aktif',
            self::Selesai => 'Selesai',
            self::Dibatalkan => 'Dibatalkan',
        };
    }

    public function badgeVariant(): string
    {
        return match ($this) {
            self::Draft => 'warning',
            self::Aktif => 'aktif',
            self::Selesai => 'secondary',
            self::Dibatalkan => 'danger',
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
