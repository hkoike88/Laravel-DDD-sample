# Quickstart: 認証 API 実装

**Date**: 2025-12-25
**Feature**: 002-staff-auth-api

## Prerequisites

- ST-001 職員エンティティ設計が完了していること
- Docker 環境が稼働していること
- Laravel Sanctum がインストール済みであること（composer.json で確認済み）

## Setup Steps

### 1. Sessions テーブルの作成

```bash
cd backend
docker compose exec backend php artisan make:session-table
docker compose exec backend php artisan migrate
```

### 2. Sanctum 設定

```bash
# config/sanctum.php が存在しない場合
docker compose exec backend php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
```

`.env` に以下を追加:
```env
SESSION_DRIVER=database
SESSION_LIFETIME=120
SANCTUM_STATEFUL_DOMAINS=localhost,localhost:3000,127.0.0.1,127.0.0.1:8000
```

### 3. Auth 設定の更新

`config/auth.php` を更新:
```php
'providers' => [
    'staffs' => [
        'driver' => 'eloquent',
        'model' => \Packages\Domain\Staff\Infrastructure\EloquentModels\StaffRecord::class,
    ],
],
```

### 4. API ルートの確認

`routes/api.php` に認証ルートを追加:
```php
use App\Http\Controllers\Auth\AuthController;

Route::post('/auth/login', [AuthController::class, 'login'])
    ->middleware('throttle:5,1');

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/auth/user', [AuthController::class, 'user']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
});
```

## Quick Test

### 1. CSRF トークン取得
```bash
curl -c cookies.txt -b cookies.txt \
  http://localhost:8000/sanctum/csrf-cookie
```

### 2. ログイン
```bash
# XSRF-TOKEN をヘッダーに付与
XSRF_TOKEN=$(grep XSRF-TOKEN cookies.txt | awk '{print $7}')

curl -c cookies.txt -b cookies.txt \
  -H "X-XSRF-TOKEN: $XSRF_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"email":"staff@example.com","password":"password123"}' \
  http://localhost:8000/api/auth/login
```

### 3. 認証ユーザー取得
```bash
curl -b cookies.txt \
  -H "Accept: application/json" \
  http://localhost:8000/api/auth/user
```

### 4. ログアウト
```bash
curl -c cookies.txt -b cookies.txt \
  -H "X-XSRF-TOKEN: $XSRF_TOKEN" \
  -H "Accept: application/json" \
  -X POST \
  http://localhost:8000/api/auth/logout
```

## Running Tests

```bash
# すべての認証テストを実行
docker compose exec backend php artisan test --filter=Auth

# 特定のテストファイルを実行
docker compose exec backend php artisan test tests/Feature/Auth/LoginTest.php
```

## Frontend Integration (React)

```typescript
import axios from 'axios';

// Axios 設定
axios.defaults.withCredentials = true;
axios.defaults.baseURL = 'http://localhost:8000';

// ログイン処理
async function login(email: string, password: string) {
  // 1. CSRF トークン取得
  await axios.get('/sanctum/csrf-cookie');

  // 2. ログイン
  const response = await axios.post('/api/auth/login', { email, password });
  return response.data;
}

// 認証ユーザー取得
async function getCurrentUser() {
  const response = await axios.get('/api/auth/user');
  return response.data;
}

// ログアウト
async function logout() {
  await axios.post('/api/auth/logout');
}
```

## Troubleshooting

### CSRF トークンエラー (419)
- `SANCTUM_STATEFUL_DOMAINS` にフロントエンドのドメインが含まれているか確認
- Cookie が正しく送信されているか確認 (`withCredentials: true`)

### 401 Unauthenticated
- セッションが有効か確認（2時間で期限切れ）
- Cookie が正しく保存されているか確認

### 429 Too Many Requests
- レート制限（5回/分）に達した場合、1分待ってから再試行
