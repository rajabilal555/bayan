<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use PDO;
use RuntimeException;

class QulDump
{
    /**
     * @return list<array{surah: int, ayah: int, text: string}>
     */
    public static function versesFrom(string $file): array
    {
        return self::verses(self::read($file));
    }

    /**
     * @return list<array{surah: int, ayah: int, audio_url: string, duration_ms: int}>
     */
    public static function audioFrom(string $file): array
    {
        return self::audio(self::read($file));
    }

    /**
     * @return list<array{surah: int, ayah: int, text: string}>
     */
    public static function verses(mixed $payload): array
    {
        $verses = [];

        foreach (self::entries($payload) as $entry) {
            $value = $entry['value'];
            $text = is_string($value)
                ? self::plainText($value)
                : self::plainText(is_array($value) ? ($value['text'] ?? $value['text_uthmani'] ?? $value['uthmani_text'] ?? $value['translation'] ?? $value['t'] ?? null) : null);

            if ($entry['surah'] < 1 || $entry['ayah'] < 1 || $text === '') {
                continue;
            }

            $verses[] = [
                'surah' => $entry['surah'],
                'ayah' => $entry['ayah'],
                'text' => $text,
            ];
        }

        return $verses;
    }

    /**
     * @return list<array{surah: int, ayah: int, audio_url: string, duration_ms: int}>
     */
    public static function audio(mixed $payload): array
    {
        $files = [];

        foreach (self::entries($payload) as $entry) {
            $value = $entry['value'];
            $url = '';
            $duration = 0;

            if (is_string($value)) {
                $url = $value;
            } elseif (is_array($value)) {
                $url = (string) ($value['audio_url'] ?? $value['url'] ?? $value['audio'] ?? '');
                $duration = (int) ($value['duration_ms'] ?? $value['duration'] ?? 0);
                if ($duration > 0 && $duration < 1000) {
                    $duration *= 1000;
                }
            }

            if ($entry['surah'] < 1 || $entry['ayah'] < 1 || $url === '') {
                continue;
            }

            $files[] = [
                'surah' => $entry['surah'],
                'ayah' => $entry['ayah'],
                'audio_url' => $url,
                'duration_ms' => $duration,
            ];
        }

        return $files;
    }

    public static function absolutePath(string $file): string
    {
        if ($file !== '' && is_file($file)) {
            return $file;
        }

        $disk = Storage::disk((string) config('bayan.disks.library'));

        if ($disk->exists($file)) {
            return $disk->path($file);
        }

        throw new InvalidArgumentException("QUL dump not found: {$file}");
    }

    public static function read(string $file): mixed
    {
        $path = self::absolutePath($file);
        $header = (string) file_get_contents($path, false, null, 0, 16);

        if (str_starts_with($header, 'SQLite format 3')) {
            return self::sqliteRows($path);
        }

        $decoded = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);

        return $decoded;
    }

    /**
     * @return list<array{surah: int, ayah: int, value: mixed}>
     */
    private static function entries(mixed $payload): array
    {
        $rows = self::unwrap($payload);
        $entries = [];

        foreach ($rows as $key => $value) {
            if (is_array($value)) {
                $value = self::normalizeKeys($value);
            }

            $ref = is_string($key) && str_contains($key, ':')
                ? self::refFromKey($key)
                : self::refFromValue($value, is_string($key) ? $key : null);

            if ($ref === null) {
                continue;
            }

            $entries[] = [
                'surah' => $ref['surah'],
                'ayah' => $ref['ayah'],
                'value' => $value,
            ];
        }

        return $entries;
    }

    /**
     * @return array<int|string, mixed>
     */
    private static function unwrap(mixed $payload): array
    {
        if (! is_array($payload)) {
            return [];
        }

        foreach (['verses', 'translations', 'audio_files', 'data'] as $key) {
            if (isset($payload[$key]) && is_array($payload[$key])) {
                return self::unwrap($payload[$key]);
            }
        }

        if (isset($payload['recitation']) && is_array($payload['recitation'])) {
            return self::unwrap($payload['recitation']);
        }

        return $payload;
    }

    /**
     * @param  array<int|string, mixed>  $row
     * @return array<string, mixed>
     */
    private static function normalizeKeys(array $row): array
    {
        $normalized = [];

        foreach ($row as $key => $value) {
            $normalized[Str::lower((string) $key)] = $value;
        }

        return $normalized;
    }

    /**
     * @return array{surah: int, ayah: int}|null
     */
    private static function refFromKey(string $key): ?array
    {
        [$surah, $ayah] = array_map('intval', explode(':', $key, 2));

        if ($surah < 1 || $ayah < 1) {
            return null;
        }

        return ['surah' => $surah, 'ayah' => $ayah];
    }

    /**
     * @return array{surah: int, ayah: int}|null
     */
    private static function refFromValue(mixed $value, ?string $key): ?array
    {
        if (is_string($key)) {
            $fromKey = self::refFromKey($key);
            if ($fromKey !== null) {
                return $fromKey;
            }
        }

        if (! is_array($value)) {
            return null;
        }

        $verseKey = $value['verse_key'] ?? $value['ayah_key'] ?? $value['ayahkey'] ?? null;

        if (is_string($verseKey) && str_contains($verseKey, ':')) {
            return self::refFromKey($verseKey);
        }

        $surah = (int) ($value['surah_id'] ?? $value['surah_number'] ?? $value['surah'] ?? $value['sura'] ?? $value['chapter_id'] ?? $value['chapter'] ?? 0);
        $ayah = (int) ($value['ayah_number'] ?? $value['ayah'] ?? $value['verse_number'] ?? $value['verse_id'] ?? 0);

        if ($surah < 1 || $ayah < 1) {
            return null;
        }

        return ['surah' => $surah, 'ayah' => $ayah];
    }

    private static function plainText(mixed $value): string
    {
        if (is_array($value)) {
            if (isset($value['t'])) {
                return self::plainText($value['t']);
            }

            $parts = [];

            foreach ($value as $chunk) {
                if (is_string($chunk)) {
                    $parts[] = $chunk;
                } elseif (is_array($chunk) && isset($chunk['text']) && is_string($chunk['text'])) {
                    $parts[] = $chunk['text'];
                }
            }

            $value = implode('', $parts);
        }

        if (! is_string($value)) {
            return '';
        }

        $value = preg_replace('/<(sup|a)\b[^>]*>.*?<\/\1>/is', '', $value) ?? $value;

        return trim(html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function sqliteRows(string $path): array
    {
        $pdo = new PDO('sqlite:'.$path, options: [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);

        $tableStatement = $pdo->query("SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%'");

        if ($tableStatement === false) {
            throw new RuntimeException('QUL SQLite dump could not be read.');
        }

        $tables = $tableStatement->fetchAll(PDO::FETCH_COLUMN);

        if ($tables === []) {
            throw new RuntimeException('QUL SQLite dump has no tables.');
        }

        $table = (string) $tables[0];
        $rowStatement = $pdo->query('SELECT * FROM "'.str_replace('"', '""', $table).'"');

        if ($rowStatement === false) {
            throw new RuntimeException('QUL SQLite dump table could not be read.');
        }

        $normalized = [];

        foreach ($rowStatement->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $normalized[] = self::normalizeKeys($row);
        }

        return $normalized;
    }
}
