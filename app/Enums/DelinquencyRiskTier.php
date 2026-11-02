<?php

declare(strict_types=1);

namespace App\Enums;

enum DelinquencyRiskTier: string
{
    case Rendah = 'rendah';
    case Sedang = 'sedang';
    case Tinggi = 'tinggi';

    public function label(): string
    {
        return match ($this) {
            self::Rendah => 'Risiko Rendah',
            self::Sedang => 'Risiko Sedang',
            self::Tinggi => 'Risiko Tinggi',
        };
    }

    public function badgeVariant(): string
    {
        return match ($this) {
            self::Rendah => 'aktif',
            self::Sedang => 'warning',
            self::Tinggi => 'danger',
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
