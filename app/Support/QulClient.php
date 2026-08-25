<?php

namespace App\Support;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class QulClient
{
    public function qul(): PendingRequest
    {
        return Http::baseUrl(rtrim((string) config('bayan.qul.base'), '/'))
            ->acceptJson()
            ->connectTimeout(10)
            ->timeout(120)
            ->retry([100, 500, 1000]);
    }

    /**
     * @return array<string, string>
     */
    public function translationOptions(): array
    {
        $options = [];

        foreach ($this->translationCatalog() as $row) {
            $id = (string) $row['id'];
            $name = $row['name'];
            $language = $row['language'] ?? null;
            $options[$id] = $language !== null && $language !== ''
                ? "{$name} ({$language}, {$id})"
                : "{$name} ({$id})";
        }

        return $options;
    }

    /**
     * @return array<string, string>
     */
    public function ayahRecitationOptions(): array
    {
        $options = [];

        foreach ($this->ayahRecitationCatalog() as $row) {
            $id = (string) $row['id'];
            $name = trim($row['name']);
            $style = $row['style'] ?? null;
            $options[$id] = filled($style) && ! str_contains(Str::lower($name), Str::lower((string) $style))
                ? "{$name} ({$style}, {$id})"
                : "{$name} ({$id})";
        }

        return $options;
    }

    /**
     * @return array{id: string, name: string, slug: string}|null
     */
    public function translationResource(string $source): ?array
    {
        $source = Str::lower($source);
        $aliases = [
            'saheeh' => '20',
            'en-sahih-international' => '20',
            'clearquran' => '131',
            'pickthall' => '19',
            'yusufali' => '22',
        ];
        $source = $aliases[$source] ?? $source;

        foreach ($this->translationCatalog() as $row) {
            if ($row['id'] === $source || Str::lower($row['slug']) === $source) {
                return $row;
            }
        }

        if (ctype_digit($source)) {
            return [
                'id' => $source,
                'name' => 'QUL translation '.$source,
                'slug' => 'qul-'.$source,
            ];
        }

        return null;
    }

    /**
     * @return array{id: string, name: string, style: ?string}|null
     */
    public function ayahRecitation(string $id): ?array
    {
        foreach ($this->ayahRecitationCatalog() as $row) {
            if ($row['id'] === $id) {
                return $row;
            }
        }

        return null;
    }

    /**
     * @return list<array{id: string, name: string, slug: string, language: ?string}>
     */
    public function translationCatalog(): array
    {
        try {
            $rows = $this->qul()->get('/api/v1/resources/translations.json')->throw()->json('translations');
        } catch (ConnectionException|RequestException) {
            return [];
        }

        if (! is_array($rows)) {
            return [];
        }

        $catalog = [];

        foreach ($rows as $row) {
            if (! is_array($row) || ! isset($row['id'], $row['name'])) {
                continue;
            }

            $catalog[] = [
                'id' => (string) $row['id'],
                'name' => (string) $row['name'],
                'slug' => (string) ($row['slug'] ?? 'qul-'.$row['id']),
                'language' => isset($row['language']) ? (string) $row['language'] : null,
            ];
        }

        return $catalog;
    }

    /**
     * @return list<array{id: string, name: string, style: ?string}>
     */
    public function ayahRecitationCatalog(): array
    {
        try {
            $rows = $this->qul()->get('/api/v1/audio/ayah_recitations')->throw()->json('recitations');
        } catch (ConnectionException|RequestException) {
            return [];
        }

        if (! is_array($rows)) {
            return [];
        }

        $catalog = [];

        foreach ($rows as $row) {
            if (! is_array($row) || ! isset($row['id'], $row['name'])) {
                continue;
            }

            $catalog[] = [
                'id' => (string) $row['id'],
                'name' => trim((string) $row['name']),
                'style' => isset($row['style']) ? (string) $row['style'] : null,
            ];
        }

        return $catalog;
    }
}
