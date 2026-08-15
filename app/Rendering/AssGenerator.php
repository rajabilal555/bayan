<?php

namespace App\Rendering;

use App\Enums\Orientation;
use App\Enums\SubtitlePosition;

class AssGenerator
{
    /**
     * @param  list<array{start_ms: int, end_ms: int, arabic?: string|null, translation?: string|null}>  $cues
     * @param  array<string, mixed>  $arabicStyle
     * @param  array<string, mixed>  $translationStyle
     */
    public function generate(
        array $cues,
        Orientation $orientation,
        array $arabicStyle,
        array $translationStyle,
        bool $showArabic,
        bool $showTranslation,
    ): string {
        $width = $orientation->width();
        $height = $orientation->height();
        $arabic = Fingerprint::normalizeStyle($arabicStyle);
        $translation = Fingerprint::normalizeStyle($translationStyle);

        $lines = [
            '[Script Info]',
            'ScriptType: v4.00+',
            "PlayResX: {$width}",
            "PlayResY: {$height}",
            'WrapStyle: 0',
            'ScaledBorderAndShadow: yes',
            '',
            '[V4+ Styles]',
            'Format: Name, Fontname, Fontsize, PrimaryColour, SecondaryColour, OutlineColour, BackColour, Bold, Italic, Underline, StrikeOut, ScaleX, ScaleY, Spacing, Angle, BorderStyle, Outline, Shadow, Alignment, MarginL, MarginR, MarginV, Encoding',
            $this->styleLine('Arabic', $arabic),
            $this->styleLine('Translation', $translation),
            '',
            '[Events]',
            'Format: Layer, Start, End, Style, Name, MarginL, MarginR, MarginV, Effect, Text',
        ];

        foreach ($cues as $cue) {
            $start = $this->timestamp($cue['start_ms']);
            $end = $this->timestamp($cue['end_ms']);

            if ($showArabic && filled($cue['arabic'] ?? null)) {
                $lines[] = $this->dialogue(0, $start, $end, 'Arabic', $cue['arabic']);
            }

            if ($showTranslation && filled($cue['translation'] ?? null)) {
                $lines[] = $this->dialogue(1, $start, $end, 'Translation', $cue['translation']);
            }
        }

        return implode("\n", $lines)."\n";
    }

    /**
     * @param  array<string, mixed>  $style
     */
    private function styleLine(string $name, array $style): string
    {
        $position = SubtitlePosition::tryFrom((string) $style['position']) ?? SubtitlePosition::Bottom;
        $marginV = match ($position) {
            SubtitlePosition::Top => 120,
            SubtitlePosition::Center => 0,
            SubtitlePosition::Bottom => 160,
        };

        return sprintf(
            'Style: %s,%s,%d,%s,&H000000FF,%s,&H80000000,0,0,0,0,100,100,0,0,1,3,0,%d,80,80,%d,1',
            $name,
            $style['font'],
            $style['size'],
            $this->assColor((string) $style['color']),
            $this->assColor((string) $style['outline']),
            $position->assAlignment(),
            $marginV,
        );
    }

    private function dialogue(int $layer, string $start, string $end, string $style, string $text): string
    {
        $escaped = str_replace(['\\', '{', '}', "\n"], ['\\\\', '\\{', '\\}', '\\N'], $text);

        return "Dialogue: {$layer},{$start},{$end},{$style},,0,0,0,,{\\q2}{$escaped}";
    }

    public function timestamp(int $ms): string
    {
        $cs = intdiv($ms, 10);
        $hours = intdiv($cs, 360000);
        $cs %= 360000;
        $minutes = intdiv($cs, 6000);
        $cs %= 6000;
        $seconds = intdiv($cs, 100);
        $cs %= 100;

        return sprintf('%d:%02d:%02d.%02d', $hours, $minutes, $seconds, $cs);
    }

    private function assColor(string $hex): string
    {
        $hex = ltrim($hex, '#');

        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }

        $r = substr($hex, 0, 2);
        $g = substr($hex, 2, 2);
        $b = substr($hex, 4, 2);

        return "&H00{$b}{$g}{$r}";
    }
}
