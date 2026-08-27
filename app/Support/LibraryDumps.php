<?php

namespace App\Support;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

class LibraryDumps
{
    public static function disk(): Filesystem
    {
        return Storage::disk((string) config('bayan.disks.library'));
    }

    public static function scriptPath(): string
    {
        return 'imports/script.json';
    }

    public static function translationPath(string $slug): string
    {
        return 'imports/translations/'.$slug.'.json';
    }

    public static function recitationPath(string $id): string
    {
        return 'imports/recitations/'.$id.'.json';
    }

    public static function uploadedRelative(mixed $file): string
    {
        if (is_array($file)) {
            $file = $file[0] ?? null;
        }

        if (! is_string($file) || $file === '') {
            throw new InvalidArgumentException('Upload a QUL JSON dump.');
        }

        return $file;
    }

    public static function replace(string $relative, string $contents): void
    {
        self::disk()->put($relative, $contents);
    }

    public static function contents(string $relative): string
    {
        $disk = self::disk();

        if (! $disk->exists($relative)) {
            throw new InvalidArgumentException("QUL dump not found: {$relative}");
        }

        return (string) $disk->get($relative);
    }

    public static function absolute(string $relative): string
    {
        if (! self::disk()->exists($relative)) {
            throw new InvalidArgumentException("QUL dump not found: {$relative}");
        }

        return self::disk()->path($relative);
    }
}
