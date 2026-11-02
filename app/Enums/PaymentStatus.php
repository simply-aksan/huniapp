<?php

declare(strict_types=1);

namespace App\Enums;

enum PaymentStatus: string
{
    case MenungguVerifikasi = 'menunggu_verifikasi';
    case Berhasil = 'berhasil';
    case Ditolak = 'ditolak';

    public function label(): string
    {
        return match ($this) {
            self::MenungguVerifikasi => 'Menunggu Verifikasi',
            self::Berhasil => 'Berhasil',
            self::Ditolak => 'Ditolak',
        };
    }

    public function badgeVariant(): string
    {
        return match ($this) {
            self::MenungguVerifikasi => 'warning',
            self::Berhasil => 'aktif',
            self::Ditolak => 'danger',
        };
    }

    /**
     * Determine if payment is waiting for verification.
     */
    public function isPending(): bool
    {
        return $this === self::MenungguVerifikasi;
    }

    /**
     * Determine if payment was verified successfully.
     */
    public function isSuccessful(): bool
    {
        return $this === self::Berhasil;
    }

    /**
     * Determine if payment was rejected by owner.
     */
    public function isRejected(): bool
    {
        return $this === self::Ditolak;
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
