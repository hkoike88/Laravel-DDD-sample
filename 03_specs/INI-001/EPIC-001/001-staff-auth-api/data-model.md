# Data Model: 認証 API 実装

**Date**: 2025-12-25
**Feature**: 002-staff-auth-api

## Entities

### Staff（既存 - ST-001 で実装済み）

認証対象の職員エンティティ。認証 API はこのエンティティを参照する。

| Field | Type | Nullable | Description |
|-------|------|----------|-------------|
| id | ULID (string, 26) | No | 職員ID（主キー） |
| name | string (100) | No | 職員名 |
| email | string (255) | No | メールアドレス（ユニーク、ログイン識別子） |
| password | string (255) | No | パスワード（bcrypt ハッシュ） |
| is_locked | boolean | No | アカウントロック状態（default: false） |
| created_at | timestamp | No | 作成日時 |
| updated_at | timestamp | No | 更新日時 |

**Validation Rules**:
- email: 有効なメールアドレス形式、255文字以下、システム内でユニーク
- password: 8文字以上、bcrypt ハッシュ化して保存
- name: 1〜100文字

**State Transitions**:
- `is_locked: false → true`: 管理者によるアカウントロック
- `is_locked: true → false`: 管理者によるアカウントロック解除

### Session（新規 - Laravel 標準）

Laravel セッション管理テーブル。Sanctum の SPA 認証で使用。

| Field | Type | Nullable | Description |
|-------|------|----------|-------------|
| id | string (255) | No | セッションID（主キー） |
| user_id | ULID (string, 26) | Yes | 認証済みユーザーID（FK: staffs.id） |
| ip_address | string (45) | Yes | クライアント IP アドレス |
| user_agent | text | Yes | クライアント User-Agent |
| payload | longtext | No | セッションデータ（シリアライズ済み） |
| last_activity | integer | No | 最終アクティビティ（UNIX タイムスタンプ） |

**Lifecycle**:
- ログイン成功時に作成
- ログアウト時に削除
- 2時間（SESSION_LIFETIME=120分）経過後に無効化

## Relationships

```
Staff (1) ←──── (0..n) Session
  │                    │
  └── id ◄──────────── user_id (nullable)
```

- Staff は複数の Session を持つことができる（同時ログイン許可）
- Session の user_id は Staff の id を参照（認証済みの場合）
- 未認証セッションは user_id が null

## Database Migrations

### sessions テーブル（新規）

```php
// database/migrations/xxxx_create_sessions_table.php
Schema::create('sessions', function (Blueprint $table) {
    $table->string('id')->primary();
    $table->string('user_id', 26)->nullable()->index();
    $table->string('ip_address', 45)->nullable();
    $table->text('user_agent')->nullable();
    $table->longText('payload');
    $table->integer('last_activity')->index();

    // 外部キー制約（オプション - Laravel 標準では設定しない）
    // $table->foreign('user_id')->references('id')->on('staffs')->onDelete('cascade');
});
```

**Note**: Laravel の `php artisan make:session-table` コマンドで生成可能。

## DTO

### LoginRequest

ログインリクエストのデータ転送オブジェクト。

```php
final readonly class LoginRequest
{
    public function __construct(
        public string $email,
        public string $password,
    ) {}
}
```

### StaffResponse

認証済み職員情報のレスポンス DTO。

```php
final readonly class StaffResponse
{
    public function __construct(
        public string $id,
        public string $name,
        public string $email,
    ) {}

    public static function fromEntity(Staff $staff): self
    {
        return new self(
            id: $staff->id()->value(),
            name: $staff->name()->value(),
            email: $staff->email()->value(),
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
        ];
    }
}
```

## Index Strategy

| Table | Index | Columns | Purpose |
|-------|-------|---------|---------|
| staffs | PRIMARY | id | 主キー検索 |
| staffs | UNIQUE | email | ログイン時のメール検索 |
| sessions | PRIMARY | id | セッション検索 |
| sessions | INDEX | user_id | ユーザー別セッション検索 |
| sessions | INDEX | last_activity | 期限切れセッション削除 |

## Security Considerations

1. **パスワード保存**: bcrypt ハッシュ化（cost factor: 12）
2. **セッションID**: ランダム生成、予測不可能
3. **CSRF**: XSRF-TOKEN クッキーで保護
4. **セッション固定攻撃対策**: ログイン成功時にセッション再生成
5. **同時ログイン**: 許可（各セッション独立管理）
