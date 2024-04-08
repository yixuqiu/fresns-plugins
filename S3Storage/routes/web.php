<?php

/*
 * Fresns (https://fresns.org)
 * Copyright (C) 2021-Present Jevan Tang
 * Released under the Apache-2.0 License.
 */

use Illuminate\Support\Facades\Route;
use Plugins\S3Storage\Http\Controllers\ApiController;
use Plugins\S3Storage\Http\Controllers\WebController;
use Plugins\S3Storage\Http\Middleware\CheckAccess;
use Plugins\S3Storage\Http\Middleware\CheckAuth;

Route::prefix('s3-storage')->name('s3-storage.')->group(function () {
    Route::get('upload', [WebController::class, 'upload'])->middleware(CheckAccess::class)->name('upload');

    Route::prefix('api')->name('api.')->middleware(CheckAuth::class)->group(function () {
        Route::post('upload-token', [ApiController::class, 'uploadToken'])->name('upload-token');
        Route::patch('uploaded', [ApiController::class, 'updateUploaded'])->name('uploaded');
    });
});
