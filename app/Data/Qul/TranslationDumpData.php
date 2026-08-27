<?php

namespace App\Data\Qul;

use App\Support\QulText;
use App\Support\VerseKey;
use InvalidArgumentException;
use JsonException;
use Spatie\LaravelData\Data;

class TranslationDumpData extends Data
{
    /**
     * @param  list<TranslationVerseData>  $verses
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
            throw new InvalidArgumentException('Translation dump is not valid JSON.', previous: $exception);
        }

        if (! is_array($payload)) {
            throw new InvalidArgumentException('Translation dump must be a JSON object.');
        }

        return self::fromPayload($payload);
    }

    /**
     * @param  array<int|string, mixed>  $payload
     */
    public static function fromPayload(array $payload): self
    {
        if (isset($payload['translations']) && is_array($payload['translations'])) {
            $payload = $payload['translations'];
        }

        $verses = [];

        foreach ($payload as $key => $value) {
            $verse = VerseKey::tryFrom(is_array($value) ? $value : [], is_string($key) ? $key : null);

            if ($verse === null) {
                continue;
            }

            $text = QulText::plain(is_array($value) ? ($value['t'] ?? $value['text'] ?? $value) : $value);

            if ($text === '') {
                continue;
            }

            $verses[] = new TranslationVerseData($verse->surah, $verse->ayah, $text);
        }

        if ($verses === []) {
            throw new InvalidArgumentException('Translation dump has no ayah text.');
        }

        return new self($verses);
    }
}
