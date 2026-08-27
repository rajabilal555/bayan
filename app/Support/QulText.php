<?php

namespace App\Support;

class QulText
{
    public static function plain(mixed $value): string
    {
        if (is_array($value)) {
            if (isset($value['t'])) {
                return self::plain($value['t']);
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
}
