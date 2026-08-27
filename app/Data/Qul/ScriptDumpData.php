<?php

namespace App\Data\Qul;

use App\Support\QulText;
use App\Support\VerseKey;
use InvalidArgumentException;
use JsonException;
use Spatie\LaravelData\Data;

class ScriptDumpData extends Data
{
    /**
     * @param  list<ScriptVerseData>  $verses
     */
    public function __construct(public array $verses) {}

    public static function fromFile(string $path): self
    {
        $contents = file_get_contents($path);

        if ($contents === false) {
            throw new InvalidArgumentException("QUL dump not found: {$path}");
        }

        try {
            $payload = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new InvalidArgumentException('Script dump is not valid JSON.', previous: $exception);
        }

        if (! is_array($payload)) {
            throw new InvalidArgumentException('Script dump must be JSON.');
        }

        return self::fromPayload($payload);
    }

    /**
     * @param  array<int|string, mixed>  $payload
     */
    public static function fromPayload(array $payload): self
    {
        foreach (['verses', 'data'] as $wrapper) {
            if (isset($payload[$wrapper]) && is_array($payload[$wrapper])) {
                $payload = $payload[$wrapper];
                break;
            }
        }

        $verses = [];

        foreach ($payload as $key => $value) {
            $verse = VerseKey::tryFrom(is_array($value) ? $value : [], is_string($key) ? $key : null);

            if ($verse === null) {
                continue;
            }

            $text = QulText::plain(is_array($value)
                ? ($value['text'] ?? $value['text_uthmani'] ?? $value['uthmani_text'] ?? $value)
                : $value);

            if ($text === '') {
                continue;
            }

            $verses[] = new ScriptVerseData($verse->surah, $verse->ayah, $text);
        }

        if ($verses === []) {
            throw new InvalidArgumentException('Script dump has no ayah text.');
        }

        return new self($verses);
    }
}
