# Research: 認証 API 実装

**Date**: 2025-12-25
**Feature**: 002-staff-auth-api

## 1. Laravel Sanctum SPA 認証

### Decision
Laravel Sanctum の SPA 認証モード（Cookie ベース）を使用する。

### Rationale
- 本プロジェクトは React SPA + Laravel API の構成
- Sanctum は Laravel 公式の認証パッケージで、SPA 向けセッションベース認証を提供
- トークンベース認証より安全（XSS でトークン漏洩のリスクがない）
- CSRF 保護が標準で組み込まれている

### Alternatives Considered
| 方式 | メリット | デメリット | 選定理由 |
|------|----------|------------|----------|
| Sanctum SPA (Cookie) | セキュア、CSRF保護標準 | 同一ドメイン制約 | ✅ 採用 - SPA との相性良好 |
| Sanctum Token (Bearer) | モバイル対応、ドメイン制約なし | XSS でトークン漏洩リスク | 今回は SPA のみのため不要 |
| Laravel Passport | OAuth2 完全対応 | 複雑、過剰 | シンプルな認証には過剰 |
| JWT (tymon/jwt-auth) | ステートレス | セッション管理不可 | セッション管理が必要 |

### Implementation Pattern
```php
// config/sanctum.php
return [
    'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS',
        'localhost,localhost:3000,127.0.0.1,127.0.0.1:8000'
    )),
    'guard' => ['web'],
    'expiration' => null,
];

// routes/api.php - ミドルウェア適用
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/auth/user', [AuthController::class, 'user']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
});
```

## 2. セッション管理（Database Driver）

### Decision
セッションドライバとして `database` を使用し、sessions テーブルで管理する。

### Rationale
- セッションデータの永続化が可能
- 複数サーバー構成でもセッション共有可能
- セッションの監査・デバッグが容易

### Implementation Pattern
```bash
# sessions テーブル作成
php artisan make:session-table
php artisan migrate
```

```php
// config/session.php
return [
    'driver' => env('SESSION_DRIVER', 'database'),
    'lifetime' => env('SESSION_LIFETIME', 120), // 2時間
    'expire_on_close' => false,
    'encrypt' => false,
    'table' => 'sessions',
    'cookie' => env('SESSION_COOKIE', 'laravel_session'),
    'domain' => env('SESSION_DOMAIN'),
    'secure' => env('SESSION_SECURE_COOKIE', true),
    'http_only' => true,
    'same_site' => 'lax',
];
```

## 3. CSRF 保護

### Decision
Laravel Sanctum 標準の CSRF 保護を使用する。

### Rationale
- SPA 認証では CSRF 攻撃対策が必須
- Sanctum は `/sanctum/csrf-cookie` エンドポイントを標準提供
- フロントエンドは Axios で自動的に XSRF-TOKEN を送信可能

### Implementation Pattern
```javascript
// Frontend (Axios)
axios.defaults.withCredentials = true;

// 1. CSRF トークン取得
await axios.get('/sanctum/csrf-cookie');

// 2. ログイン（自動的に X-XSRF-TOKEN ヘッダーが付与される）
await axios.post('/api/auth/login', { email, password });
```

## 4. レート制限

### Decision
Laravel 標準の `throttle` ミドルウェアを使用し、5回/分/IP に制限する。

### Rationale
- ブルートフォース攻撃対策として必須
- Laravel 標準機能で簡単に実装可能
- IP ベースの制限で正当なユーザーへの影響を最小化

### Implementation Pattern
```php
// routes/api.php
Route::post('/auth/login', [AuthController::class, 'login'])
    ->middleware('throttle:5,1'); // 5回/分

// app/Providers/RouteServiceProvider.php（カスタム設定の場合）
RateLimiter::for('login', function (Request $request) {
    return Limit::perMinute(5)->by($request->ip());
});
```

## 5. カスタム User Provider

### Decision
Laravel の標準 User Provider を拡張し、Staff エンティティと統合する。

### Rationale
- Laravel Sanctum は Eloquent User モデルを期待
- ST-001 で作成した Staff エンティティ・リポジトリを活用
- StaffRecord (Eloquent Model) を認証対象として使用

### Implementation Pattern
```php
// config/auth.php
return [
    'defaults' => [
        'guard' => 'web',
        'passwords' => 'staffs',
    ],
    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'staffs',
        ],
        'sanctum' => [
            'driver' => 'sanctum',
            'provider' => 'staffs',
        ],
    ],
    'providers' => [
        'staffs' => [
            'driver' => 'eloquent',
            'model' => \Packages\Domain\Staff\Infrastructure\EloquentModels\StaffRecord::class,
        ],
    ],
];
```

## 6. テスト戦略

### Decision
Pest + Laravel Sanctum の `actingAs` ヘルパーを使用する。

### Rationale
- Sanctum 標準のテストヘルパーで認証状態を簡単にシミュレート
- Feature テストで実際の HTTP リクエストをテスト
- Unit テストで UseCase ロジックを個別テスト

### Implementation Pattern
```php
use Laravel\Sanctum\Sanctum;

// Feature Test
test('authenticated user can get current user info', function () {
    $staff = StaffRecord::factory()->create();

    Sanctum::actingAs($staff);

    $response = $this->getJson('/api/auth/user');

    $response->assertStatus(200)
             ->assertJsonStructure(['data' => ['id', 'name', 'email']]);
});

// Unauthenticated Test
test('unauthenticated request returns 401', function () {
    $response = $this->getJson('/api/auth/user');

    $response->assertStatus(401);
});
```

## 7. エラーハンドリング

### Decision
認証エラーは情報漏洩を防ぐため、統一的なエラーメッセージを返す。

### Rationale
- 存在しないメールアドレスと間違ったパスワードを区別しない（セキュリティ）
- アカウントロック時のみ 403 で明示的に通知
- レート制限超過時は 429 を返す

### Error Response Mapping
| 状況 | HTTPステータス | メッセージ |
|------|---------------|-----------|
| メール/パスワード不正 | 401 | 認証情報が正しくありません |
| 存在しないメール | 401 | 認証情報が正しくありません |
| アカウントロック | 403 | アカウントがロックされています |
| 未認証 | 401 | Unauthenticated. |
| レート制限超過 | 429 | Too Many Requests |

## Summary

すべての技術的な疑問点が解決されました。NEEDS CLARIFICATION はありません。

| 項目 | 決定事項 |
|------|---------|
| 認証方式 | Laravel Sanctum SPA (Cookie ベース) |
| セッション管理 | Database Driver (sessions テーブル) |
| セッション有効期限 | 2時間 (SESSION_LIFETIME=120) |
| CSRF 保護 | Sanctum 標準 (/sanctum/csrf-cookie) |
| レート制限 | 5回/分/IP (throttle ミドルウェア) |
| User Provider | Eloquent (StaffRecord モデル) |
| テスト | Pest + Sanctum::actingAs ヘルパー |
