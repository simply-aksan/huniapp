<?php

declare(strict_types=1);

namespace App\Enums;

enum ComplaintPriority: string
{
    case Rendah = 'rendah';
    case Sedang = 'sedang';
    case Tinggi = 'tinggi';
    case Darurat = 'darurat';

    public function label(): string
    {
        return match ($this) {
            self::Rendah => 'Rendah',
            self::Sedang => 'Sedang',
            self::Tinggi => 'Tinggi',
            self::Darurat => 'Darurat',
        };
    }

    public function badgeVariant(): string
    {
        return match ($this) {
            self::Rendah => 'secondary',
            self::Sedang => 'info',
            self::Tinggi => 'warning',
            self::Darurat => 'danger',
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
