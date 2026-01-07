# Research: セッション管理実装

**Feature**: 001-session-management
**Date**: 2025-12-26

## 1. Laravel セッション設定（データベースドライバー）

### Decision
Laravel 標準のデータベースセッションドライバーを使用する。

### Rationale
- Laravel 11.x では `database` ドライバーがデフォルトで推奨されている
- 既存の `sessions` テーブルが存在し、ULID 対応済み
- マルチサーバー環境でのセッション共有が容易
- セッション情報をデータベースクエリで操作可能（同時ログイン制御に必須）

### Alternatives Considered
| 選択肢 | 却下理由 |
|--------|---------|
| Redis | 追加インフラが必要、現時点では過剰 |
| File | マルチサーバー環境で共有不可 |
| Cookie | データサイズ制限、セキュリティリスク |

### Configuration
```php
// config/session.php
return [
    'driver' => 'database',
    'lifetime' => 30,           // アイドルタイムアウト: 30分
    'expire_on_close' => false,
    'encrypt' => true,          // セッションデータ暗号化
    'cookie' => 'library_session',
    'path' => '/',
    'domain' => null,
    'secure' => true,           // HTTPS 必須
    'http_only' => true,        // JavaScript アクセス禁止
    'same_site' => 'lax',       // CSRF 対策
];
```

---

## 2. 絶対タイムアウト実装パターン

### Decision
カスタムミドルウェア `AbsoluteSessionTimeout` でセッション作成日時を追跡し、8時間経過後に強制ログアウトする。

### Rationale
- Laravel 標準のセッション lifetime はアイドルタイムアウトのみ対応
- 絶対タイムアウトはセキュリティベストプラクティス（長時間セッションハイジャック対策）
- セッション内に作成日時を保存することで実装が簡潔

### Implementation Pattern
```php
class AbsoluteSessionTimeout
{
    private const ABSOLUTE_TIMEOUT_SECONDS = 8 * 60 * 60; // 8時間

    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $createdAt = session('session_created_at');

            if (!$createdAt) {
                // 初回アクセス時に作成日時を記録
                session(['session_created_at' => now()->timestamp]);
            } elseif (now()->timestamp - $createdAt > self::ABSOLUTE_TIMEOUT_SECONDS) {
                // 絶対タイムアウト
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return response()->json([
                    'message' => 'セッションがタイムアウトしました。再度ログインしてください',
                ], 401);
            }
        }

        return $next($request);
    }
}
```

### Alternatives Considered
| 選択肢 | 却下理由 |
|--------|---------|
| sessions テーブルに created_at 追加 | 既存テーブル構造変更が必要、マイグレーション複雑 |
| Redis TTL 利用 | Redis 未導入 |
| JWT トークン有効期限 | Sanctum セッション認証と整合しない |

---

## 3. 同時ログイン制限実装パターン

### Decision
カスタムミドルウェア `ConcurrentSessionLimit` でログイン時に sessions テーブルをクエリし、上限超過時は最古セッションを削除する。

### Rationale
- sessions テーブルに user_id が既に存在
- ログイン時のみチェックすることでパフォーマンス影響を最小化
- 最古セッション削除により、新しいデバイスを優先

### Implementation Pattern
```php
class ConcurrentSessionLimit
{
    private const MAX_SESSIONS_STAFF = 3;  // 一般職員
    private const MAX_SESSIONS_ADMIN = 1;  // 管理者

    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();
            $maxSessions = $user->isAdmin()
                ? self::MAX_SESSIONS_ADMIN
                : self::MAX_SESSIONS_STAFF;

            $activeSessions = DB::table('sessions')
                ->where('user_id', $user->id)
                ->orderByDesc('last_activity')
                ->get();

            if ($activeSessions->count() > $maxSessions) {
                // 古いセッションを削除
                $toDelete = $activeSessions->skip($maxSessions);
                DB::table('sessions')
                    ->whereIn('id', $toDelete->pluck('id'))
                    ->delete();
            }
        }

        return $next($request);
    }
}
```

### Alternatives Considered
| 選択肢 | 却下理由 |
|--------|---------|
| 新規ログイン拒否 | UX が悪い（正規ユーザーがロックアウト） |
| 確認ダイアログ表示 | 実装複雑、要件外 |
| 全セッション無効化 | 過度に破壊的 |

---

## 4. 管理者フラグの追加

### Decision
staffs テーブルに `is_admin` カラム（BOOLEAN）を追加し、Staff エンティティに `isAdmin()` メソッドを追加する。

### Rationale
- 仕様で管理者と一般職員の同時ログイン数が異なる
- 既存の Staff エンティティは管理者判定機能を持たない
- Boolean フラグは最もシンプルで拡張性がある

### Migration
```php
Schema::table('staffs', function (Blueprint $table) {
    $table->boolean('is_admin')->default(false)->after('name')
        ->comment('管理者フラグ');
});
```

### Domain Model Update
```php
// Staff.php
public function isAdmin(): bool
{
    return $this->isAdmin;
}
```

### Alternatives Considered
| 選択肢 | 却下理由 |
|--------|---------|
| Role テーブル分離 | 現時点では2種類のみ、過剰設計 |
| Enum カラム | PHP 8.1 Enum 対応が必要、複雑 |
| 別テーブル (admins) | 認証フローが複雑化 |

---

## 5. フロントエンド セッションタイムアウト処理

### Decision
Axios インターセプターで 401 レスポンスを検知し、セッション切れメッセージを表示後、ログイン画面へリダイレクトする。

### Rationale
- 既存の Axios 設定に追加するだけで実装可能
- 一元的なエラーハンドリング
- UX 一貫性（すべての API 呼び出しで同じ処理）

### Implementation Pattern
```typescript
// lib/axios.ts
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      const message = error.response?.data?.message;

      if (message?.includes('セッション')) {
        toast.error('セッションがタイムアウトしました。再度ログインしてください');
      }

      useAuthStore.getState().clearUser();
      window.location.href = '/staff/login';
    }
    return Promise.reject(error);
  }
);
```

---

## 6. CSRF 保護

### Decision
Laravel 標準の CSRF 保護機能を使用する（VerifyCsrfToken ミドルウェア）。

### Rationale
- Laravel Sanctum がデフォルトで CSRF 保護を提供
- SPA 向けに `/sanctum/csrf-cookie` エンドポイントが利用可能
- 追加実装不要

### Configuration
- `config/cors.php` で `supports_credentials: true` を設定済み
- フロントエンドで `withCredentials: true` を設定済み

---

## Summary of Decisions

| 項目 | 決定 |
|------|------|
| セッションドライバー | database |
| アイドルタイムアウト | Laravel 標準 lifetime = 30 |
| 絶対タイムアウト | カスタムミドルウェア（8時間） |
| 同時ログイン制限 | カスタムミドルウェア + sessions テーブルクエリ |
| 管理者判定 | staffs.is_admin カラム追加 |
| セッション暗号化 | Laravel 標準 encrypt = true |
| Cookie 設定 | HttpOnly, Secure, SameSite=Lax |
| CSRF 保護 | Laravel Sanctum 標準 |
| フロント処理 | Axios インターセプター |
