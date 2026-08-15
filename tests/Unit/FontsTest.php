<?php

test('subtitle fonts are bundled in public/fonts', function () {
    $directory = public_path(config('bayan.fonts.directory'));

    expect($directory)->toBeDirectory();

    foreach (config('bayan.fonts.families') as $family => $file) {
        expect($family)->not->toBeEmpty()
            ->and($directory.DIRECTORY_SEPARATOR.$file)->toBeFile();
    }
});
