<?php

/*
 * Fresns (https://fresns.org)
 * Copyright (C) 2021-Present Jevan Tang
 * Released under the Apache-2.0 License.
 */

use Illuminate\Support\Facades\Route;
use Plugins\Cloudinary\Http\Controllers\ApiController;
use Plugins\Cloudinary\Http\Controllers\WebController;
use Plugins\Cloudinary\Http\Middleware\CheckAccess;
use Plugins\Cloudinary\Http\Middleware\CheckAuth;

Route::prefix('cloudinary')->name('cloudinary.')->group(function () {
    Route::get('upload', [WebController::class, 'upload'])->middleware(CheckAccess::class)->name('upload');

    Route::prefix('api')->name('api.')->middleware(CheckAuth::class)->group(function () {
        Route::post('upload-token', [ApiController::class, 'uploadToken'])->name('upload-token');
        Route::patch('uploaded', [ApiController::class, 'updateUploaded'])->name('uploaded');
    });
});
