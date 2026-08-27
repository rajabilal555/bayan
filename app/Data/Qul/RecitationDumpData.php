<?php

namespace App\Data\Qul;

use App\Support\VerseKey;
use InvalidArgumentException;
use JsonException;
use Spatie\LaravelData\Data;

class RecitationDumpData extends Data
{
    /**
     * @param  array<string, RecitationAudioFileData>  $files
     */
    public function __construct(public array $files) {}

    public static function fromFile(string $path): self
    {
        $contents = file_get_contents($path);

        if ($contents === false) {
            throw new InvalidArgumentException("QUL dump not found: {$path}");
        }

        try {
            $payload = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new InvalidArgumentException('Recitation dump is not valid JSON.', previous: $exception);
        }

        if (! is_array($payload)) {
            throw new InvalidArgumentException('Recitation dump must be a JSON object.');
        }

        return self::fromPayload($payload);
    }

    /**
     * @param  array<int|string, mixed>  $payload
     */
    public static function fromPayload(array $payload): self
    {
        if (isset($payload['recitation']) && is_array($payload['recitation'])) {
            $payload = is_array($payload['recitation']['audio_files'] ?? null)
                ? $payload['recitation']['audio_files']
                : $payload['recitation'];
        }

        $files = [];

        foreach ($payload as $key => $file) {
            if (! is_array($file)) {
                continue;
            }

            $verse = VerseKey::tryFrom($file, is_string($key) ? $key : null);

            if ($verse === null) {
                continue;
            }

            $audioUrl = (string) ($file['audio_url'] ?? '');

            if ($audioUrl === '') {
                continue;
            }

            $files["{$verse->surah}:{$verse->ayah}"] = new RecitationAudioFileData($audioUrl);
        }

        if ($files === []) {
            throw new InvalidArgumentException('Recitation dump has no ayah audio URLs.');
        }

        return new self($files);
    }

    /**
     * @return array<int, list<array{ayah: int, audio_url: string}>>
     */
    public function groupedBySurah(): array
    {
        $groups = [];

        foreach ($this->files as $key => $file) {
            $verse = VerseKey::parse($key);
            $groups[$verse->surah][] = [
                'ayah' => $verse->ayah,
                'audio_url' => $file->audio_url,
            ];
        }

        ksort($groups);

        return $groups;
    }
}
