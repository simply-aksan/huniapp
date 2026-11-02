<?php

declare(strict_types=1);

namespace App\Enums;

enum ComplaintStatus: string
{
    case Baru = 'baru';
    case Diproses = 'diproses';
    case MenungguTanggapan = 'menunggu_tanggapan';
    case Selesai = 'selesai';
    case Ditutup = 'ditutup';

    public function label(): string
    {
        return match ($this) {
            self::Baru => 'Baru',
            self::Diproses => 'Sedang Ditangani',
            self::MenungguTanggapan => 'Menunggu Tanggapan',
            self::Selesai => 'Selesai',
            self::Ditutup => 'Ditutup',
        };
    }

    public function badgeVariant(): string
    {
        return match ($this) {
            self::Baru => 'warning',
            self::Diproses => 'diproses',
            self::MenungguTanggapan => 'warning',
            self::Selesai => 'aktif',
            self::Ditutup => 'secondary',
        };
    }

    /**
     * Determine if this complaint ticket is still actively open.
     */
    public function isOpen(): bool
    {
        return in_array($this, [self::Baru, self::Diproses, self::MenungguTanggapan], true);
    }

    /**
     * Determine if this complaint ticket has been marked resolved.
     */
    public function isResolved(): bool
    {
        return $this === self::Selesai;
    }

    /**
     * Determine if this complaint ticket has been permanently closed.
     */
    public function isClosed(): bool
    {
        return $this === self::Ditutup;
    }

    /**
     * Determine if this ticket can still receive new replies.
     */
    public function canBeReplied(): bool
    {
        return $this !== self::Ditutup;
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
