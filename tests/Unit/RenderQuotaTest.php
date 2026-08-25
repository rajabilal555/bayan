<?php

use App\Support\RenderQuota;

test('one render covers the configured ayah threshold', function () {
    expect(RenderQuota::ayahsPerRender())->toBe(7)
        ->and(RenderQuota::creditsFor(1))->toBe(1)
        ->and(RenderQuota::creditsFor(7))->toBe(1)
        ->and(RenderQuota::creditsFor(8))->toBe(2)
        ->and(RenderQuota::creditsFor(14))->toBe(2)
        ->and(RenderQuota::creditsFor(15))->toBe(3);
});

test('max ayahs scale with remaining render credits', function () {
    expect(RenderQuota::maxAyahs(0))->toBe(0)
        ->and(RenderQuota::maxAyahs(1))->toBe(7)
        ->and(RenderQuota::maxAyahs(5))->toBe(35);
});

test('bismillah adds one ayah outside al-fatihah', function () {
    expect(RenderQuota::ayahCount(1, 7))->toBe(7)
        ->and(RenderQuota::ayahCount(1, 7, true, 1))->toBe(7)
        ->and(RenderQuota::ayahCount(1, 7, true, 2))->toBe(8)
        ->and(RenderQuota::creditsFor(RenderQuota::ayahCount(1, 7, true, 2)))->toBe(2);
});
