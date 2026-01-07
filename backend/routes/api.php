<?php

use App\Http\Controllers\Auth\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Packages\Domain\Staff\Presentation\HTTP\Controllers\StaffAccountController;

/*
|--------------------------------------------------------------------------
| 認証 API
|--------------------------------------------------------------------------
|
| 職員認証に関するルート。
| ログインにはレート制限（5回/分/IP）を適用。
|
*/

Route::prefix('auth')->group(function () {
    // ログイン（レート制限: 5回/分/IP）
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:5,1')
        ->name('auth.login');

    // ログアウト（認証必須 + 絶対タイムアウト）
    Route::post('/logout', [AuthController::class, 'logout'])
        ->middleware(['auth:sanctum', 'absolute.timeout'])
        ->name('auth.logout');

    // 認証済み職員情報取得（認証必須 + 絶対タイムアウト）
    Route::get('/user', [AuthController::class, 'user'])
        ->middleware(['auth:sanctum', 'absolute.timeout'])
        ->name('auth.user');
});

// 後方互換性のための既存ルート（将来的に /api/auth/user に移行）
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

/**
 * ヘルスチェック API
 * バックエンドサービスの稼働状態を確認
 */
Route::get('/health', [\App\Http\Controllers\HealthController::class, 'index']);

/**
 * データベースヘルスチェック API
 * MySQL への接続状態を確認
 */
Route::get('/health/db', [\App\Http\Controllers\HealthController::class, 'database']);

/*
|--------------------------------------------------------------------------
| 管理者専用 API
|--------------------------------------------------------------------------
|
| 管理者権限を持つ職員のみがアクセス可能なルート。
| 認証 + 絶対タイムアウト + 管理者権限チェックを適用。
|
| @feature 003-role-based-menu
|
*/

Route::prefix('staff')
    ->middleware(['auth:sanctum', 'absolute.timeout', 'require.admin'])
    ->group(function () {
        // 職員アカウント一覧
        Route::get('/accounts', [StaffAccountController::class, 'index'])
            ->name('staff.accounts.index');

        // 職員アカウント作成
        Route::post('/accounts', [StaffAccountController::class, 'store'])
            ->name('staff.accounts.store');

        // 職員アカウント詳細取得
        // @feature EPIC-004-staff-account-edit
        Route::get('/accounts/{id}', [StaffAccountController::class, 'show'])
            ->name('staff.accounts.show');

        // 職員アカウント更新
        // @feature EPIC-004-staff-account-edit
        Route::put('/accounts/{id}', [StaffAccountController::class, 'update'])
            ->name('staff.accounts.update');

        // パスワードリセット
        // @feature EPIC-004-staff-account-edit
        Route::post('/accounts/{id}/reset-password', [StaffAccountController::class, 'resetPassword'])
            ->name('staff.accounts.resetPassword');
    });

/*
|--------------------------------------------------------------------------
| 認証済み職員 API
|--------------------------------------------------------------------------
|
| 認証済み職員が自身の情報を管理するためのルート。
| 認証 + 絶対タイムアウトを適用。
|
| @feature 001-security-preparation
|
*/

Route::middleware(['auth:sanctum', 'absolute.timeout'])
    ->group(function () {
        // パスワード変更
        // @feature 001-security-preparation
        Route::put('/staff/password', [\App\Http\Controllers\Staff\ChangePasswordController::class, '__invoke'])
            ->name('staff.password.update');

        // セッション管理
        // @feature 001-security-preparation
        Route::prefix('staff/sessions')->group(function () {
            // セッション一覧取得
            Route::get('/', [\App\Http\Controllers\Staff\SessionController::class, 'index'])
                ->name('staff.sessions.index');

            // 他セッション一括終了
            Route::delete('/others', [\App\Http\Controllers\Staff\SessionController::class, 'destroyOthers'])
                ->name('staff.sessions.destroyOthers');

            // 個別セッション終了
            Route::delete('/{id}', [\App\Http\Controllers\Staff\SessionController::class, 'destroy'])
                ->name('staff.sessions.destroy');
        });
    });
