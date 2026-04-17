<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $indexFile = public_path('index.html');

    if (file_exists($indexFile)) {
        return response()->file($indexFile);
    }

    return view('welcome');
});

Route::get('/{any}', function () {
    $indexFile = public_path('index.html');

    abort_unless(file_exists($indexFile), 404);

    return response()->file($indexFile);
})->where('any', '^(?!api(?:/|$)).*');
