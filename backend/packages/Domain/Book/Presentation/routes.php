<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Packages\Domain\Book\Presentation\HTTP\Controllers\BookController;

/*
|--------------------------------------------------------------------------
| Book Domain API Routes
|--------------------------------------------------------------------------
|
| 蔵書ドメインのAPIルート定義。
| すべてのルートは /api/books プレフィックスで登録される。
|
| 注意: api ミドルウェアグループを使用して、
| EnsureFrontendRequestsAreStateful ミドルウェアを適用する。
| これにより Sanctum SPA 認証が正しく機能する。
|
*/

Route::middleware('api')->prefix('api/books')->group(function () {
    // 蔵書一覧取得（検索）- 認証不要
    Route::get('/', [BookController::class, 'index'])->name('books.index');

    // 認証が必要なルート
    Route::middleware('auth:sanctum')->group(function () {
        // ISBN重複チェック（check-isbn は store より先に定義）
        Route::get('/check-isbn', [BookController::class, 'checkIsbn'])->name('books.checkIsbn');

        // 蔵書登録
        Route::post('/', [BookController::class, 'store'])->name('books.store');
    });

    // 蔵書詳細取得 - 認証不要（{id} は他のルートより後に定義）
    Route::get('/{id}', [BookController::class, 'show'])->name('books.show');
});
