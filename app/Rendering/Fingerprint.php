<?php

namespace App\Rendering;

class Fingerprint
{
    /**
     * @param  array<string, mixed>  $params
     */
    public static function make(array $params): string
    {
        $canonical = [
            'reciter_id' => (int) $params['reciter_id'],
            'background_id' => (int) $params['background_id'],
            'surah' => (int) $params['surah'],
            'ayah_start' => (int) $params['ayah_start'],
            'ayah_end' => (int) $params['ayah_end'],
            'orientation' => (string) $params['orientation'],
            'show_arabic' => (bool) $params['show_arabic'],
            'show_translation' => (bool) $params['show_translation'],
            'translation_source_id' => ($params['show_translation'] ?? false) && $params['translation_source_id'] !== null
                ? (int) $params['translation_source_id']
                : null,
            'include_bismillah' => (bool) $params['include_bismillah'],
            'loop_background' => (bool) $params['loop_background'],
            'arabic_style' => self::normalizeStyle($params['arabic_style'] ?? []),
            'translation_style' => self::normalizeStyle($params['translation_style'] ?? []),
        ];

        return hash('sha256', json_encode($canonical, JSON_THROW_ON_ERROR));
    }

    /**
     * @param  array<string, mixed>  $style
     * @return array<string, mixed>
     */
    public static function normalizeStyle(array $style): array
    {
        $normalized = [
            'font' => (string) ($style['font'] ?? ''),
            'color' => strtoupper((string) ($style['color'] ?? '#FFFFFF')),
            'outline' => strtoupper((string) ($style['outline'] ?? '#000000')),
            'position' => (string) ($style['position'] ?? 'bottom'),
            'size' => (int) ($style['size'] ?? 48),
        ];

        ksort($normalized);

        return $normalized;
    }
}
