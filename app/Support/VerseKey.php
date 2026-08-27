<?php

namespace App\Support;

use InvalidArgumentException;

final readonly class VerseKey
{
    public function __construct(public int $surah, public int $ayah)
    {
        if ($this->surah < 1 || $this->ayah < 1) {
            throw new InvalidArgumentException("Invalid verse {$this->surah}:{$this->ayah}.");
        }
    }

    public static function parse(string $key): self
    {
        if (! str_contains($key, ':')) {
            throw new InvalidArgumentException("Invalid verse key {$key}.");
        }

        [$surah, $ayah] = array_map('intval', explode(':', $key, 2));

        return new self($surah, $ayah);
    }

    public static function tryFrom(mixed $value, int|string|null $key = null): ?self
    {
        try {
            if (is_string($key) && str_contains($key, ':')) {
                return self::parse($key);
            }

            if (! is_array($value)) {
                return null;
            }

            $verseKey = $value['verse_key'] ?? $value['ayah_key'] ?? null;

            if (is_string($verseKey) && str_contains($verseKey, ':')) {
                return self::parse($verseKey);
            }

            $surah = (int) ($value['surah_id'] ?? $value['surah_number'] ?? $value['surah'] ?? $value['chapter_id'] ?? $value['chapter'] ?? 0);
            $ayah = (int) ($value['ayah_number'] ?? $value['ayah'] ?? $value['verse_number'] ?? $value['verse_id'] ?? 0);

            return new self($surah, $ayah);
        } catch (InvalidArgumentException) {
            return null;
        }
    }
}
