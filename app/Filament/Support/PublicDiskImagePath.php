<?php

namespace App\Filament\Support;

final class PublicDiskImagePath
{
    /**
     * Path relative to the Laravel "public" disk root (storage/app/public).
     * Filament FileUpload often stores "folder/file.ext"; seeders may store basename only.
     */
    public static function resolve(?string $stored, string $directory): ?string
    {
        if ($stored === null || $stored === '') {
            return null;
        }

        $stored = ltrim($stored, '/');

        if (str_contains($stored, '/')) {
            return $stored;
        }

        return rtrim($directory, '/') . '/' . $stored;
    }
}
