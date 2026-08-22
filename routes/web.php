<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| SPA entry point
|--------------------------------------------------------------------------
|
| Everything that is not an API call, a stored file or a build asset is handed
| to the Vue app so it can route client-side.
|
| The `where` used to be a bare `.*`, which swallowed those three prefixes too:
| a typo'd endpoint answered 200 + HTML instead of a 404 JSON envelope, a
| POST-only route answered 200 instead of 405, and a missing upload answered 200
| instead of 404 — so no client could tell success from failure by status code.
|
*/

Route::get('{any?}', fn () => view('application'))
    ->where('any', '^(?!api/|storage/|build/).*$');
