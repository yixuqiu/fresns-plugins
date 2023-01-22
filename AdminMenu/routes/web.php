<?php

/*
 * Fresns (https://fresns.org)
 * Copyright (C) 2021-Present Jarvis Tang
 * Released under the Apache-2.0 License.
 */

use Illuminate\Support\Facades\Route;
use Plugins\AdminMenu\Controllers\WebController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::prefix('admin-menu')->name('admin-menu.')->group(function () {
    Route::get('/', [WebController::class, 'index'])->name('index');
    Route::get('/delete-post', [WebController::class, 'deletePost'])->name('delete.post');
    Route::get('/edit-post-group', [WebController::class, 'editPostGroup'])->name('edit.post.group');
    Route::get('/delete-comment', [WebController::class, 'deleteComment'])->name('delete.comment');
    Route::get('/edit-user', [WebController::class, 'editUser'])->name('edit.user');
});
