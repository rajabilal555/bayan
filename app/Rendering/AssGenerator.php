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
            'WrapStyle: 2',
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

        $arabicPosition = SubtitlePosition::tryFrom((string) $arabic['position']) ?? SubtitlePosition::Bottom;
        $translationPosition = SubtitlePosition::tryFrom((string) $translation['position']) ?? SubtitlePosition::Bottom;
        $stackAtCenter = $showArabic
            && $showTranslation
            && $arabicPosition === SubtitlePosition::Center
            && $translationPosition === SubtitlePosition::Center;

        foreach ($cues as $cue) {
            $start = $this->timestamp($cue['start_ms']);
            $end = $this->timestamp($cue['end_ms']);
            $arabicText = $cue['arabic'] ?? null;
            $translationText = $cue['translation'] ?? null;
            $hasArabic = $showArabic && filled($arabicText);
            $hasTranslation = $showTranslation && filled($translationText);

            if ($stackAtCenter && $hasArabic && $hasTranslation) {
                $center = $this->stackedCenterOverrides($width, $height, (int) $arabic['size']);
                $lines[] = $this->dialogue(0, $start, $end, 'Arabic', (string) $arabicText, $center['arabic']);
                $lines[] = $this->dialogue(1, $start, $end, 'Translation', (string) $translationText, $center['translation']);

                continue;
            }

            if ($hasArabic) {
                $lines[] = $this->dialogue(0, $start, $end, 'Arabic', (string) $arabicText);
            }

            if ($hasTranslation) {
                $lines[] = $this->dialogue(1, $start, $end, 'Translation', (string) $translationText);
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

        $outline = max(2, intdiv((int) $style['size'], 24));

        return sprintf(
            'Style: %s,%s,%d,%s,&H000000FF,%s,&H80000000,0,0,0,0,100,100,0,0,1,%d,1,%d,120,120,%d,1',
            $name,
            $style['font'],
            $style['size'],
            $this->assColor((string) $style['color']),
            $this->assColor((string) $style['outline']),
            $outline,
            $position->assAlignment(),
            $marginV,
        );
    }

    /**
     * @return array{arabic: string, translation: string}
     */
    private function stackedCenterOverrides(int $width, int $height, int $arabicSize): array
    {
        $x = intdiv($width, 2);
        $y = intdiv($height, 2);
        $gap = max(36, intdiv($arabicSize, 2));

        return [
            'arabic' => sprintf('{\\an2\\pos(%d,%d)\\q2}', $x, $y - $gap),
            'translation' => sprintf('{\\an8\\pos(%d,%d)\\q2}', $x, $y + $gap),
        ];
    }

    private function dialogue(int $layer, string $start, string $end, string $style, string $text, string $overrides = '{\\q2}'): string
    {
        return "Dialogue: {$layer},{$start},{$end},{$style},,0,0,0,,{$overrides}{$this->escapeAss($text)}";
    }

    private function escapeAss(string $text): string
    {
        return str_replace(['\\', '{', '}', "\n"], ['\\\\', '\\{', '\\}', '\\N'], $text);
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
