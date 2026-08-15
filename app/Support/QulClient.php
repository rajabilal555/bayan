<?php

namespace App\Support;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class QulClient
{
    public function qul(): PendingRequest
    {
        return Http::baseUrl(rtrim((string) config('bayan.qul.base'), '/'))
            ->acceptJson()
            ->timeout(120);
    }

    public function quranCom(): PendingRequest
    {
        return Http::baseUrl(rtrim((string) config('bayan.qul.quran_com'), '/'))
            ->acceptJson()
            ->timeout(120);
    }
}
