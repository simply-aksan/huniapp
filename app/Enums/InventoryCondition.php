<?php

declare(strict_types=1);

namespace App\Enums;

enum InventoryCondition: string
{
    case Baik = 'baik';
    case PerluPerawatan = 'perlu_perawatan';
    case Rusak = 'rusak';
    case Hilang = 'hilang';

    public function label(): string
    {
        return match ($this) {
            self::Baik => 'Baik',
            self::PerluPerawatan => 'Perlu Perawatan',
            self::Rusak => 'Rusak',
            self::Hilang => 'Hilang',
        };
    }

    public function badgeVariant(): string
    {
        return match ($this) {
            self::Baik => 'aktif',
            self::PerluPerawatan => 'warning',
            self::Rusak => 'danger',
            self::Hilang => 'secondary',
        };
    }

    /**
     * Map legacy or alternative values to a valid enum instance.
     */
    public static function fromInput(?string $value): self
    {
        if (! $value) {
            return self::Baik;
        }

        $normalized = strtolower(trim($value));

        return match ($normalized) {
            'baik', 'bagus', 'good' => self::Baik,
            'perlu_perawatan', 'rusak_ringan', 'warning' => self::PerluPerawatan,
            'rusak', 'rusak_berat', 'broken' => self::Rusak,
            'hilang', 'missing' => self::Hilang,
            default => self::tryFrom($normalized) ?? self::Baik,
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
