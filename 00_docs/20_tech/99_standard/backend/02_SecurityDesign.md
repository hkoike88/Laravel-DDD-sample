# バックエンド セキュリティ設計標準

## 概要

本プロジェクトのバックエンドにおけるセキュリティ設計標準を定める。
OWASP Top 10 を基準とし、Laravel のセキュリティ機能を活用する。

---

## 基本方針

- **多層防御**: 複数のセキュリティ層で保護
- **最小権限の原則**: 必要最小限の権限のみ付与
- **セキュアバイデフォルト**: デフォルトで安全な設定
- **入力は信頼しない**: すべての入力を検証・サニタイズ

---

## 認証（Authentication）

### 認証方式

| 用途 | 方式 | 実装 |
|------|------|------|
| SPA 認証 | Cookie ベース | Laravel Sanctum |
| API 認証 | Bearer Token | Laravel Sanctum API Token |
| 管理画面 | セッション認証 | Laravel 標準 |

### Sanctum 設定

```php
// config/sanctum.php
return [
    'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', 'localhost')),
    'expiration' => 60 * 24, // トークン有効期限: 24時間
    'guard' => ['web'],
];
```

### パスワード要件

詳細なパスワードポリシーは [password-policy.md](../security/password-policy.md) を参照。

```php
// app/Providers/AuthServiceProvider.php
use Illuminate\Validation\Rules\Password;

public function boot(): void
{
    Password::defaults(function () {
        return Password::min(12)
            ->letters()
            ->mixedCase()
            ->numbers()
            ->symbols()
            ->uncompromised(); // 漏洩パスワードチェック
    });
}
```

### ログイン試行制限

```php
// app/Http/Controllers/Auth/LoginController.php
use Illuminate\Foundation\Auth\ThrottlesLogins;

class LoginController extends Controller
{
    use ThrottlesLogins;

    protected $maxAttempts = 5;       // 最大試行回数
    protected $decayMinutes = 15;     // ロック時間（分）
}
```

### 多要素認証（MFA）

- 管理者アカウントは MFA 必須
- TOTP（Time-based One-Time Password）を使用
- リカバリーコードの安全な保管を案内

---

## 認可（Authorization）

### 認可の実装場所

```
┌─────────────────────────────────────────────────────┐
│ Presentation 層: Route Middleware / Policy          │
│ - ルートレベルのアクセス制御                          │
│ - リソースの所有者チェック                            │
└─────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────┐
│ Application 層: UseCase 内での権限チェック            │
│ - 業務操作の実行可否判定                              │
└─────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────┐
│ Domain 層: ビジネスルールによる制約                   │
│ - 状態遷移の可否（canPlace, canCancel など）         │
└─────────────────────────────────────────────────────┘
```

### Policy の実装

```php
// packages/Agenda/Order/Domain/Policies/OrderPolicy.php
final class OrderPolicy
{
    public function view(User $user, Order $order): bool
    {
        return $user->id === $order->customerId()->value()
            || $user->hasRole('admin');
    }

    public function cancel(User $user, Order $order): bool
    {
        return $user->id === $order->customerId()->value()
            && $order->status()->canCancel();
    }
}
```

### Middleware での認可

```php
// routes/api.php
Route::middleware(['auth:sanctum', 'verified'])->group(function () {
    Route::get('/orders/{order}', [OrderController::class, 'show'])
        ->can('view', 'order');

    Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel'])
        ->can('cancel', 'order');
});
```

### ロールベースアクセス制御（RBAC）

| ロール | 権限 |
|--------|------|
| user | 自身のリソースの CRUD |
| staff | 全ユーザーのリソース閲覧、一部操作 |
| admin | 全リソースの CRUD、システム設定 |

---

## 入力検証

### バリデーション層

```
[クライアント] → [FormRequest] → [Domain ValueObject]
                    ↑                    ↑
              形式チェック          ビジネスルール検証
```

### FormRequest でのバリデーション

```php
final class CreateOrderRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.min' => '注文には1つ以上の商品が必要です',
        ];
    }
}
```

### ValueObject での検証

```php
final class Email
{
    public function __construct(private string $value)
    {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('無効なメールアドレス形式です');
        }

        if (strlen($value) > 254) {
            throw new InvalidArgumentException('メールアドレスが長すぎます');
        }
    }
}
```

### 禁止入力パターン

```php
// 危険な入力のブロック
public function rules(): array
{
    return [
        'name' => [
            'required',
            'string',
            'max:255',
            'regex:/^[^<>{}]*$/',  // HTML/Script タグを禁止
        ],
        'url' => [
            'required',
            'url',
            'regex:/^https:\/\//',  // HTTPS のみ許可
            function ($attribute, $value, $fail) {
                if ($this->isInternalUrl($value)) {
                    $fail('内部URLは指定できません');
                }
            },
        ],
    ];
}
```

---

## SQLインジェクション対策

### Eloquent / Query Builder の使用

```php
// ✓ Good: プレースホルダを使用
$orders = OrderRecord::where('customer_id', $customerId)->get();

$orders = DB::table('orders')
    ->where('status', '=', $status)
    ->get();

// ✗ Bad: 文字列結合（絶対禁止）
$orders = DB::select("SELECT * FROM orders WHERE customer_id = " . $customerId);
```

### Raw クエリが必要な場合

```php
// ✓ Good: バインディングを使用
$results = DB::select(
    'SELECT * FROM orders WHERE status = ? AND created_at > ?',
    [$status, $date]
);

// 名前付きバインディング
$results = DB::select(
    'SELECT * FROM orders WHERE status = :status',
    ['status' => $status]
);
```

### LIKE 検索のエスケープ

```php
// ✓ Good: 特殊文字をエスケープ
$searchTerm = addcslashes($request->input('search'), '%_');
$products = ProductRecord::where('name', 'LIKE', "%{$searchTerm}%")->get();
```

---

## XSS（クロスサイトスクリプティング）対策

### Blade テンプレート

```php
// ✓ Good: 自動エスケープ
{{ $user->name }}

// ✗ Bad: エスケープなし（HTMLを出力する明確な理由がある場合のみ）
{!! $user->bio !!}
```

### API レスポンス

```php
// JSON レスポンスは自動的にエスケープされる
return response()->json([
    'name' => $user->name,  // 安全
]);
```

### Content Security Policy（CSP）

```php
// app/Http/Middleware/SecurityHeaders.php
final class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set(
            'Content-Security-Policy',
            "default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline';"
        );

        return $response;
    }
}
```

---

## CSRF 対策

### SPA での対応

```php
// Sanctum を使用した CSRF 対策
// 初回アクセス時に CSRF Cookie を取得
axios.get('/sanctum/csrf-cookie').then(() => {
    // 以降のリクエストで X-XSRF-TOKEN ヘッダーが自動付与される
});
```

### API での除外設定

```php
// app/Http/Middleware/VerifyCsrfToken.php
protected $except = [
    'api/*',        // API はトークン認証で保護
    'webhook/*',    // Webhook は署名検証で保護
];
```

---

## セキュリティヘッダー

### 必須ヘッダー

```php
// app/Http/Middleware/SecurityHeaders.php
final class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // クリックジャッキング対策
        $response->headers->set('X-Frame-Options', 'DENY');

        // MIME タイプスニッフィング対策
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // XSS フィルター（レガシーブラウザ向け）
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // HTTPS 強制
        $response->headers->set(
            'Strict-Transport-Security',
            'max-age=31536000; includeSubDomains'
        );

        // リファラー制御
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // 権限ポリシー
        $response->headers->set(
            'Permissions-Policy',
            'geolocation=(), microphone=(), camera=()'
        );

        return $response;
    }
}
```

### Kernel への登録

```php
// app/Http/Kernel.php
protected $middleware = [
    // ...
    \App\Http\Middleware\SecurityHeaders::class,
];
```

---

## 機密情報の保護

### 環境変数の管理

```bash
# .env（コミット禁止）
APP_KEY=base64:xxxxxxxxxxxx
DB_PASSWORD=secret
API_SECRET_KEY=xxxxxxxxxxxx

# .env.example（コミット可、値は空またはダミー）
APP_KEY=
DB_PASSWORD=
API_SECRET_KEY=
```

### 機密情報のログ出力禁止

```php
// config/logging.php
'channels' => [
    'stack' => [
        'driver' => 'stack',
        'channels' => ['daily'],
    ],
    'daily' => [
        'driver' => 'daily',
        'path' => storage_path('logs/laravel.log'),
        'level' => 'debug',
        'days' => 14,
        'replace_placeholders' => true,
    ],
],

// ログに出力しない情報
// app/Http/Middleware/LogRequests.php
protected $except = [
    'password',
    'password_confirmation',
    'credit_card',
    'cvv',
    'token',
];
```

### シークレットのマスキング

```php
// 例外レポートでのマスキング
// app/Exceptions/Handler.php
protected $dontFlash = [
    'current_password',
    'password',
    'password_confirmation',
];
```

---

## ファイルアップロード

### 検証ルール

```php
public function rules(): array
{
    return [
        'file' => [
            'required',
            'file',
            'max:10240',  // 10MB
            'mimes:pdf,doc,docx,jpg,png',  // 許可する MIME タイプ
        ],
        'image' => [
            'required',
            'image',
            'max:5120',   // 5MB
            'dimensions:min_width=100,min_height=100,max_width=4000,max_height=4000',
        ],
    ];
}
```

### 安全なファイル保存

```php
final class FileUploadService
{
    public function store(UploadedFile $file, string $directory): string
    {
        // ファイル名をランダム化（推測不可能に）
        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();

        // 公開ディレクトリ外に保存
        $path = $file->storeAs(
            $directory,
            $filename,
            'private'  // storage/app/private/
        );

        return $path;
    }
}
```

### ファイルタイプの検証

```php
// MIME タイプを実際のファイル内容から検証
private function validateFileType(UploadedFile $file): bool
{
    $finfo = new \finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file->getRealPath());

    $allowedTypes = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'application/pdf',
    ];

    return in_array($mimeType, $allowedTypes, true);
}
```

---

## API セキュリティ

### レート制限

```php
// routes/api.php
Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
    // API ルート
});

// app/Providers/RouteServiceProvider.php
protected function configureRateLimiting(): void
{
    // 一般 API: 60リクエスト/分
    RateLimiter::for('api', function (Request $request) {
        return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
    });

    // 認証 API: 5リクエスト/分
    RateLimiter::for('auth', function (Request $request) {
        return Limit::perMinute(5)->by($request->ip());
    });

    // 重要操作: 10リクエスト/時
    RateLimiter::for('sensitive', function (Request $request) {
        return Limit::perHour(10)->by($request->user()->id);
    });
}
```

### API バージョニング

```php
// routes/api.php
Route::prefix('v1')->group(function () {
    // v1 API
});

Route::prefix('v2')->group(function () {
    // v2 API
});
```

### レスポンスに含めない情報

```php
// ✗ Bad: 内部情報を露出
return response()->json([
    'error' => $exception->getMessage(),
    'trace' => $exception->getTraceAsString(),  // 禁止
    'sql' => $query,  // 禁止
]);

// ✓ Good: ユーザー向けメッセージのみ
return response()->json([
    'error' => 'リクエストを処理できませんでした',
    'code' => 'ORDER_CREATION_FAILED',
]);
```

---

## ログ・監査

### セキュリティイベントのログ

```php
// 記録すべきイベント
final class SecurityLogger
{
    public function logAuthentication(User $user, string $action, bool $success): void
    {
        Log::channel('security')->info('Authentication event', [
            'user_id' => $user->id,
            'action' => $action,  // login, logout, password_change
            'success' => $success,
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    public function logAuthorization(User $user, string $resource, string $action, bool $allowed): void
    {
        Log::channel('security')->info('Authorization event', [
            'user_id' => $user->id,
            'resource' => $resource,
            'action' => $action,
            'allowed' => $allowed,
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
```

### ログ設定

```php
// config/logging.php
'channels' => [
    'security' => [
        'driver' => 'daily',
        'path' => storage_path('logs/security.log'),
        'level' => 'info',
        'days' => 90,  // 90日間保持
    ],
],
```

---

## 依存関係のセキュリティ

### 脆弱性チェック

```bash
# Composer の脆弱性チェック
composer audit

# npm の脆弱性チェック
npm audit
```

### CI での自動チェック

```yaml
# .github/workflows/security.yml
name: Security Check

on:
  push:
  schedule:
    - cron: '0 0 * * *'  # 毎日実行

jobs:
  security:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4

      - name: Composer Audit
        run: composer audit

      - name: Check for known vulnerabilities
        uses: symfonycorp/security-checker-action@v5
```

### 依存関係の更新方針

| 種類 | 更新頻度 | 対応 |
|------|----------|------|
| セキュリティパッチ | 即時 | 発見次第すぐに適用 |
| マイナーアップデート | 週次 | CI でテスト後に適用 |
| メジャーアップデート | 月次 | 影響調査後に計画的に適用 |

---

## チェックリスト

### 開発時

- [ ] すべての入力を検証しているか
- [ ] SQL はプレースホルダを使用しているか
- [ ] 機密情報をログに出力していないか
- [ ] 適切な認可チェックを行っているか
- [ ] エラーメッセージに内部情報を含めていないか

### デプロイ前

- [ ] `APP_DEBUG=false` になっているか
- [ ] `APP_ENV=production` になっているか
- [ ] HTTPS が強制されているか
- [ ] セキュリティヘッダーが設定されているか
- [ ] 不要なルート・エンドポイントが無効化されているか

### 定期確認

- [ ] 依存関係の脆弱性チェックを実施したか
- [ ] ログを確認し、不審なアクセスがないか
- [ ] アクセス権限の棚卸しを行ったか

---

## インシデント対応

### 連絡先

| 担当 | 連絡先 |
|------|--------|
| セキュリティ責任者 | security@example.com |
| インフラ担当 | infra@example.com |

### 対応手順

1. **検知**: 監視アラート、ログ、ユーザー報告
2. **初動対応**: 影響範囲の特定、必要に応じてサービス停止
3. **調査**: ログ分析、原因特定
4. **復旧**: 脆弱性修正、サービス再開
5. **報告**: 関係者への報告、再発防止策の策定

---

## 関連ドキュメント

- [01_ArchitectureDesign.md](./01_ArchitectureDesign.md) - アーキテクチャ設計標準
- [02_CodingStandards.md](./02_CodingStandards.md) - コーディング規約
- [04_Non-FunctionalRequirements.md](./04_Non-FunctionalRequirements.md) - 非機能要件
- [password-policy.md](../security/password-policy.md) - パスワードポリシー
