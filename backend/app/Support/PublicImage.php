<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

class PublicImage
{
    /**
     * Resolve a stored image path to a public URL.
     *
     * Paths beginning with "images/" are shipped in public/; everything else
     * was uploaded to the public storage disk.
     */
    public static function url(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        return str_starts_with($path, 'images/')
            ? asset($path)
            : Storage::disk('public')->url($path);
    }
}
