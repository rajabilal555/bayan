<?php

namespace App\Support;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

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
     * @return list<array<string, mixed>>
     */
    public function chapters(): array
    {
        try {
            $chapters = $this->qul()->get('/api/v1/chapters')->throw()->json('chapters');
        } catch (ConnectionException|RequestException $exception) {
            throw new RuntimeException('QUL chapters could not be loaded.', previous: $exception);
        }

        if (! is_array($chapters) || $chapters === []) {
            throw new RuntimeException('QUL chapters response was empty.');
        }

        return array_values($chapters);
    }
}
