# セッション管理ポリシー

## 概要

本ドキュメントは、システムにおけるセッション管理のセキュリティポリシーを定義する。
適切なセッション管理により、認証状態の安全な維持とセッションハイジャック等の攻撃を防止する。

**Last updated:** 2025-12-26

---

## 目次

- [セッション管理の基本方針](#セッション管理の基本方針)
- [セッションライフサイクル](#セッションライフサイクル)
- [セッションID管理](#セッションid管理)
- [タイムアウト設定](#タイムアウト設定)
- [同時ログイン制御](#同時ログイン制御)
- [セッション固定攻撃対策](#セッション固定攻撃対策)
- [Remember Me機能](#remember-me機能)
- [セッション無効化](#セッション無効化)
- [実装ガイドライン](#実装ガイドライン)
- [監視・ログ](#監視ログ)

---

## セッション管理の基本方針

### 原則

1. **機密性**: セッション情報は暗号化して保存・送信
2. **一意性**: セッションIDは予測不可能で一意
3. **有効期限**: 適切なタイムアウトを設定
4. **無効化**: ログアウト時は確実にセッションを破棄
5. **監視**: 異常なセッション活動を検知

### セッション方式

| 方式 | 用途 | 実装 |
|------|------|------|
| Cookie ベースセッション | SPA認証 | Laravel Sanctum（ステートフル） |
| データベースセッション | セッション管理 | Laravel Session（DB driver） |
| トークンベース | API認証 | Laravel Sanctum（トークン） |

---

## セッションライフサイクル

### ライフサイクルフロー

```
┌─────────────────────────────────────────────────────────────────┐
│                        セッションライフサイクル                    │
└─────────────────────────────────────────────────────────────────┘

  [未認証]
      │
      ▼ ログイン成功
┌─────────────┐
│ セッション作成 │ ← 新しいセッションIDを発行
│             │   ← 古いセッションIDを無効化（固定攻撃対策）
└─────┬───────┘
      │
      ▼
┌─────────────┐
│  アクティブ  │ ← 最終アクセス時刻を更新
│             │ ← 定期的に整合性チェック
└─────┬───────┘
      │
      ├────────────────────┬─────────────────────┐
      ▼                    ▼                     ▼
┌───────────┐      ┌───────────────┐     ┌───────────────┐
│ ログアウト │      │ アイドルタイムアウト│     │ 絶対タイムアウト │
└─────┬─────┘      └───────┬───────┘     └───────┬───────┘
      │                    │                     │
      └────────────────────┴─────────────────────┘
                           │
                           ▼
                   ┌───────────────┐
                   │ セッション破棄  │ ← 全データを削除
                   │               │ ← 関連Cookieを削除
                   └───────────────┘
```

### 状態遷移

| 現在の状態 | イベント | 次の状態 | アクション |
|-----------|---------|---------|-----------|
| 未認証 | ログイン成功 | アクティブ | セッション作成、ID再生成 |
| アクティブ | リクエスト | アクティブ | 最終アクセス時刻更新 |
| アクティブ | アイドルタイムアウト | 期限切れ | 再認証要求 |
| アクティブ | 絶対タイムアウト | 期限切れ | 強制ログアウト |
| アクティブ | ログアウト | 無効 | セッション破棄 |
| アクティブ | 不正検知 | 無効 | セッション破棄、アラート |

---

## セッションID管理

### セッションIDの要件

| 要件 | 設定値 | 根拠 |
|------|--------|------|
| 長さ | 128ビット以上 | 総当たり攻撃への耐性 |
| エントロピー | 暗号学的に安全な乱数 | 予測不可能性 |
| 文字種 | 英数字（Base64等） | Cookie互換性 |

### Laravel設定

```php
// config/session.php
return [
    'driver' => 'database',  // DBにセッションを保存

    'lifetime' => 120,       // セッション有効期限（分）

    'expire_on_close' => false,  // ブラウザ閉じても維持

    'encrypt' => true,       // セッションデータを暗号化

    'cookie' => 'app_session',

    'path' => '/',

    'domain' => env('SESSION_DOMAIN'),

    'secure' => true,        // HTTPS のみ

    'http_only' => true,     // JavaScript からアクセス不可

    'same_site' => 'lax',    // CSRF 対策
];
```

### Cookie属性

| 属性 | 設定 | 目的 |
|------|------|------|
| `Secure` | `true` | HTTPS接続でのみ送信 |
| `HttpOnly` | `true` | XSSによる窃取を防止 |
| `SameSite` | `Lax` or `Strict` | CSRF攻撃を防止 |
| `Path` | `/` | アプリケーション全体で有効 |
| `Domain` | 明示的に設定 | サブドメイン共有の制御 |

---

## タイムアウト設定

### タイムアウト種別

| 種別 | 設定値 | 説明 |
|------|--------|------|
| **アイドルタイムアウト** | 30分 | 最後の操作から無操作が続いた場合 |
| **絶対タイムアウト** | 8時間 | セッション開始からの最大有効期間 |
| **Remember Me** | 14日 | 永続ログイン時の最大期間 |

### アカウント種別による調整

| アカウント種別 | アイドル | 絶対 | 備考 |
|---------------|:--------:|:----:|------|
| 一般スタッフ | 30分 | 8時間 | 標準設定 |
| 管理者 | 15分 | 4時間 | より厳格 |
| API トークン | - | 24時間 | トークン有効期限 |

### 実装

```php
// app/Http/Middleware/SessionTimeout.php
class SessionTimeout
{
    private const IDLE_TIMEOUT = 30 * 60;      // 30分（秒）
    private const ABSOLUTE_TIMEOUT = 8 * 60 * 60; // 8時間（秒）

    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $lastActivity = session('last_activity');
            $sessionStart = session('session_start');
            $now = time();

            // アイドルタイムアウトチェック
            if ($lastActivity && ($now - $lastActivity) > self::IDLE_TIMEOUT) {
                return $this->terminateSession($request, 'idle_timeout');
            }

            // 絶対タイムアウトチェック
            if ($sessionStart && ($now - $sessionStart) > self::ABSOLUTE_TIMEOUT) {
                return $this->terminateSession($request, 'absolute_timeout');
            }

            // 最終アクティビティを更新
            session(['last_activity' => $now]);
        }

        return $next($request);
    }

    private function terminateSession(Request $request, string $reason): Response
    {
        Log::channel('security')->info('Session terminated', [
            'user_id' => Auth::id(),
            'reason' => $reason,
            'ip' => $request->ip(),
        ]);

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'message' => 'セッションがタイムアウトしました。再度ログインしてください。',
            'code' => 'SESSION_TIMEOUT',
        ], 401);
    }
}
```

---

## 同時ログイン制御

### ポリシー

| ポリシー | 説明 | 推奨用途 |
|---------|------|---------|
| 許可（無制限） | 複数デバイスから同時ログイン可 | 一般ユーザー向けサービス |
| 許可（制限付き） | 最大N台まで許可 | 標準的なビジネスアプリ |
| 禁止（後勝ち） | 新しいログインで古いセッションを無効化 | セキュリティ重視 |
| 禁止（先勝ち） | 既存セッションがある場合は拒否 | 金融系システム |

### 本システムの設定

```
スタッフアカウント: 最大3台まで許可（後勝ちで古いセッションから無効化）
管理者アカウント: 最大1台のみ（後勝ち）
```

### 実装

```php
// app/Services/SessionManagerService.php
class SessionManagerService
{
    private const MAX_SESSIONS_STAFF = 3;
    private const MAX_SESSIONS_ADMIN = 1;

    public function enforceSessionLimit(Staff $staff): void
    {
        $maxSessions = $staff->isAdmin()
            ? self::MAX_SESSIONS_ADMIN
            : self::MAX_SESSIONS_STAFF;

        $activeSessions = DB::table('sessions')
            ->where('user_id', $staff->id)
            ->orderBy('last_activity', 'desc')
            ->get();

        if ($activeSessions->count() >= $maxSessions) {
            // 古いセッションから削除
            $sessionsToDelete = $activeSessions->slice($maxSessions - 1);

            foreach ($sessionsToDelete as $session) {
                DB::table('sessions')->where('id', $session->id)->delete();

                Log::channel('security')->info('Session terminated due to limit', [
                    'user_id' => $staff->id,
                    'session_id' => $session->id,
                    'reason' => 'concurrent_session_limit',
                ]);
            }
        }
    }

    public function getActiveSessions(Staff $staff): Collection
    {
        return DB::table('sessions')
            ->where('user_id', $staff->id)
            ->orderBy('last_activity', 'desc')
            ->get()
            ->map(fn ($session) => [
                'id' => $session->id,
                'ip_address' => $session->ip_address,
                'user_agent' => $session->user_agent,
                'last_activity' => Carbon::createFromTimestamp($session->last_activity),
                'is_current' => $session->id === session()->getId(),
            ]);
    }

    public function terminateSession(Staff $staff, string $sessionId): bool
    {
        $deleted = DB::table('sessions')
            ->where('id', $sessionId)
            ->where('user_id', $staff->id)
            ->delete();

        if ($deleted) {
            Log::channel('security')->info('Session manually terminated', [
                'user_id' => $staff->id,
                'terminated_session_id' => $sessionId,
            ]);
        }

        return $deleted > 0;
    }
}
```

### セッション管理UI

ユーザーが自身のアクティブセッションを確認・管理できる機能を提供：

```typescript
// フロントエンド: セッション管理画面
interface ActiveSession {
  id: string;
  ipAddress: string;
  userAgent: string;
  lastActivity: string;
  isCurrent: boolean;
}

// GET /api/v1/auth/sessions - アクティブセッション一覧
// DELETE /api/v1/auth/sessions/{id} - 特定セッションを無効化
// DELETE /api/v1/auth/sessions - 現在以外の全セッションを無効化
```

---

## セッション固定攻撃対策

### 対策概要

セッション固定攻撃（Session Fixation）を防止するため、認証状態の変更時にセッションIDを再生成する。

### 再生成が必要なタイミング

| イベント | 対応 |
|---------|------|
| ログイン成功 | セッションID再生成（必須） |
| 権限昇格 | セッションID再生成 |
| パスワード変更 | セッションID再生成 |
| 重要な設定変更 | セッションID再生成 |

### 実装

```php
// app/Http/Controllers/Auth/LoginController.php
public function login(LoginFormRequest $request): JsonResponse
{
    $credentials = $request->validated();

    if (Auth::attempt($credentials)) {
        // セッション固定攻撃対策: セッションIDを再生成
        $request->session()->regenerate();

        // セッション開始時刻を記録（絶対タイムアウト用）
        session(['session_start' => time()]);
        session(['last_activity' => time()]);

        Log::channel('security')->info('Login successful', [
            'user_id' => Auth::id(),
            'ip' => $request->ip(),
        ]);

        return response()->json([
            'message' => 'ログインしました',
            'user' => Auth::user(),
        ]);
    }

    return response()->json([
        'message' => '認証に失敗しました',
    ], 401);
}

// パスワード変更時
public function changePassword(ChangePasswordRequest $request): JsonResponse
{
    // パスワード変更処理...

    // セッションIDを再生成
    $request->session()->regenerate();

    // 他のセッションを無効化（オプション）
    Auth::logoutOtherDevices($request->input('current_password'));

    return response()->json(['message' => 'パスワードを変更しました']);
}
```

---

## Remember Me機能

### 設計方針

| 項目 | 設定 |
|------|------|
| 有効期限 | 14日間 |
| トークン形式 | ランダムトークン（ハッシュ保存） |
| 保存先 | remember_tokens テーブル |
| セキュリティ | デバイス情報と紐付け |

### セキュリティ要件

1. **トークンはハッシュ化して保存**（平文禁止）
2. **使用時に新しいトークンを発行**（トークンローテーション）
3. **デバイス情報と紐付け**（異なるデバイスからの使用を検知）
4. **パスワード変更時は全トークンを無効化**

### 実装

```php
// database/migrations/xxxx_create_remember_tokens_table.php
Schema::create('remember_tokens', function (Blueprint $table) {
    $table->id();
    $table->foreignId('staff_id')->constrained()->onDelete('cascade');
    $table->string('token_hash', 64);  // SHA-256
    $table->string('device_fingerprint')->nullable();
    $table->string('ip_address', 45);
    $table->string('user_agent');
    $table->timestamp('expires_at');
    $table->timestamp('last_used_at')->nullable();
    $table->timestamps();

    $table->index(['staff_id', 'token_hash']);
    $table->index('expires_at');
});

// app/Services/RememberTokenService.php
class RememberTokenService
{
    private const TOKEN_LENGTH = 64;
    private const EXPIRY_DAYS = 14;

    public function createToken(Staff $staff, Request $request): string
    {
        $token = Str::random(self::TOKEN_LENGTH);

        RememberToken::create([
            'staff_id' => $staff->id,
            'token_hash' => hash('sha256', $token),
            'device_fingerprint' => $this->generateDeviceFingerprint($request),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'expires_at' => now()->addDays(self::EXPIRY_DAYS),
        ]);

        return $token;
    }

    public function validateAndRotate(string $token, Request $request): ?Staff
    {
        $tokenHash = hash('sha256', $token);

        $rememberToken = RememberToken::where('token_hash', $tokenHash)
            ->where('expires_at', '>', now())
            ->first();

        if (!$rememberToken) {
            return null;
        }

        $staff = $rememberToken->staff;

        // トークンローテーション: 古いトークンを削除し新しいものを発行
        $rememberToken->delete();
        $newToken = $this->createToken($staff, $request);

        // 新しいトークンをCookieにセット
        Cookie::queue('remember_token', $newToken, self::EXPIRY_DAYS * 24 * 60);

        return $staff;
    }

    public function revokeAllTokens(Staff $staff): void
    {
        RememberToken::where('staff_id', $staff->id)->delete();
    }

    private function generateDeviceFingerprint(Request $request): string
    {
        return hash('sha256', $request->userAgent() . $request->header('Accept-Language'));
    }
}
```

---

## セッション無効化

### 無効化が必要なケース

| ケース | 対象 | 実装 |
|-------|------|------|
| 通常ログアウト | 現在のセッションのみ | `Auth::logout()` |
| 全デバイスからログアウト | 全セッション | `Auth::logoutOtherDevices()` + 現在のセッション |
| パスワード変更 | 現在以外の全セッション | `Auth::logoutOtherDevices()` |
| アカウントロック | 全セッション | 全セッション強制削除 |
| 不正アクセス検知 | 全セッション | 全セッション強制削除 + Remember Token無効化 |

### 実装

```php
// app/Http/Controllers/Auth/LogoutController.php
class LogoutController extends Controller
{
    public function logout(Request $request): JsonResponse
    {
        Log::channel('security')->info('Logout', [
            'user_id' => Auth::id(),
            'ip' => $request->ip(),
        ]);

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Remember Token も削除
        Cookie::queue(Cookie::forget('remember_token'));

        return response()->json(['message' => 'ログアウトしました']);
    }

    public function logoutAll(Request $request): JsonResponse
    {
        $staff = Auth::user();

        // 全Remember Tokenを無効化
        app(RememberTokenService::class)->revokeAllTokens($staff);

        // 他デバイスのセッションを無効化
        Auth::logoutOtherDevices($request->input('password'));

        // 現在のセッションも無効化
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        Log::channel('security')->info('Logout from all devices', [
            'user_id' => $staff->id,
            'ip' => $request->ip(),
        ]);

        return response()->json(['message' => '全デバイスからログアウトしました']);
    }
}
```

---

## 実装ガイドライン

### セッションテーブル

```php
// database/migrations/xxxx_create_sessions_table.php
Schema::create('sessions', function (Blueprint $table) {
    $table->string('id')->primary();
    $table->foreignId('user_id')->nullable()->index();
    $table->string('ip_address', 45)->nullable();
    $table->text('user_agent')->nullable();
    $table->longText('payload');
    $table->integer('last_activity')->index();
});
```

### Kernel設定

```php
// app/Http/Kernel.php
protected $middlewareGroups = [
    'web' => [
        \App\Http\Middleware\EncryptCookies::class,
        \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
        \Illuminate\Session\Middleware\StartSession::class,
        \App\Http\Middleware\SessionTimeout::class,  // カスタムタイムアウト
        \Illuminate\View\Middleware\ShareErrorsFromSession::class,
        \App\Http\Middleware\VerifyCsrfToken::class,
        \Illuminate\Routing\Middleware\SubstituteBindings::class,
    ],
];
```

### セキュリティチェックリスト

- [ ] セッションIDは認証時に再生成される
- [ ] Cookie に Secure, HttpOnly, SameSite が設定されている
- [ ] アイドルタイムアウトが実装されている
- [ ] 絶対タイムアウトが実装されている
- [ ] セッションデータは暗号化されている
- [ ] ログアウト時にセッションが完全に破棄される
- [ ] 同時ログイン数が制限されている
- [ ] セッション関連イベントがログに記録される

---

## 監視・ログ

### ログ記録対象

| イベント | ログレベル | 記録項目 |
|---------|-----------|---------|
| セッション作成 | INFO | user_id, ip, user_agent |
| セッション終了 | INFO | user_id, 終了理由 |
| タイムアウト | INFO | user_id, タイムアウト種別 |
| 同時ログイン制限 | WARNING | user_id, 削除されたセッション数 |
| 不審なセッション活動 | WARNING | 詳細情報 |

### 異常検知

| 検知項目 | 条件 | アクション |
|---------|------|-----------|
| IP アドレス変更 | セッション中にIPが変わった | 警告ログ、再認証要求（オプション） |
| User-Agent 変更 | セッション中にUAが変わった | セッション無効化 |
| 短時間での大量リクエスト | 閾値超過 | レート制限、アラート |
| 地理的に不可能な移動 | 短時間で遠距離のIP変更 | セッション無効化、通知 |

### ログ出力例

```php
// セキュリティログ
Log::channel('security')->info('Session created', [
    'user_id' => $staff->id,
    'session_id' => session()->getId(),
    'ip_address' => request()->ip(),
    'user_agent' => request()->userAgent(),
    'timestamp' => now()->toIso8601String(),
]);

Log::channel('security')->warning('Suspicious session activity', [
    'user_id' => $staff->id,
    'session_id' => session()->getId(),
    'reason' => 'ip_address_changed',
    'old_ip' => $oldIp,
    'new_ip' => $newIp,
    'timestamp' => now()->toIso8601String(),
]);
```

---

## 関連ドキュメント

- [01_PasswordPolicy.md](./01_PasswordPolicy.md) - パスワードポリシー
- [02_SecurityDesign.md](../backend/02_SecurityDesign.md) - セキュリティ設計標準
- [04_LoggingDesign.md](../backend/04_LoggingDesign.md) - ログ設計標準

---

## 改訂履歴

| バージョン | 日付 | 変更内容 | 担当者 |
|-----------|------|---------|-------|
| 1.0.0 | 2025-12-26 | 初版作成 | - |
