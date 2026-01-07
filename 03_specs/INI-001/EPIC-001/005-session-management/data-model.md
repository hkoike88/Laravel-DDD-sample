# Data Model: セッション管理実装

**Feature**: 001-session-management
**Date**: 2025-12-26

## Entities

### 1. Session（セッション）

職員の認証状態を表すエンティティ。Laravel 標準の sessions テーブルを使用。

#### Attributes

| フィールド | 型 | 制約 | 説明 |
|-----------|-----|------|------|
| id | VARCHAR(255) | PRIMARY KEY | セッションID |
| user_id | CHAR(26) | NULLABLE, INDEX | 職員ID（ULID） |
| ip_address | VARCHAR(45) | NULLABLE | クライアントIPアドレス |
| user_agent | TEXT | NULLABLE | ブラウザ情報 |
| payload | LONGTEXT | NOT NULL | セッションデータ（暗号化） |
| last_activity | INT | NOT NULL, INDEX | 最終アクティビティ（UNIX タイムスタンプ） |

#### Session Payload（セッション内データ）

| キー | 型 | 説明 |
|-----|-----|------|
| session_created_at | int | セッション作成日時（UNIX タイムスタンプ） |
| _token | string | CSRF トークン |

#### State Transitions

```
[未認証] ---(ログイン成功)---> [アクティブ]
[アクティブ] ---(操作)---> [アクティブ] (last_activity 更新)
[アクティブ] ---(30分無操作)---> [アイドルタイムアウト] ---> [削除]
[アクティブ] ---(8時間経過)---> [絶対タイムアウト] ---> [削除]
[アクティブ] ---(同時ログイン超過)---> [強制終了] ---> [削除]
[アクティブ] ---(ログアウト)---> [削除]
```

---

### 2. Staff（職員）【更新】

既存の職員エンティティに管理者フラグを追加。

#### Attributes（追加分）

| フィールド | 型 | 制約 | 説明 |
|-----------|-----|------|------|
| is_admin | BOOLEAN | DEFAULT FALSE | 管理者フラグ |

#### Updated Schema

```sql
-- 既存の staffs テーブルに追加
ALTER TABLE staffs
ADD COLUMN is_admin BOOLEAN DEFAULT FALSE
COMMENT '管理者フラグ'
AFTER name;
```

#### Business Rules

| ルールID | ルール |
|----------|-------|
| BR-STAFF-01 | is_admin = true の職員は同時ログイン数が 1 台に制限される |
| BR-STAFF-02 | is_admin = false の職員は同時ログイン数が 3 台に制限される |

---

## Relationships

```
+-------------+          +------------+
|   Staff     |  1    *  |  Session   |
|-------------|----------|------------|
| id (PK)     |<-------->| user_id    |
| is_admin    |          | id (PK)    |
| ...         |          | ...        |
+-------------+          +------------+
```

- Staff : Session = 1 : N（一人の職員が複数セッションを持てる、ただし上限あり）

---

## Validation Rules

### Session

| フィールド | バリデーション |
|-----------|---------------|
| user_id | ULID 形式（26文字英数字） |
| ip_address | 有効な IPv4 または IPv6 アドレス |
| last_activity | 現在時刻以前の UNIX タイムスタンプ |
| session_created_at | 現在時刻以前の UNIX タイムスタンプ |

### Staff（is_admin）

| フィールド | バリデーション |
|-----------|---------------|
| is_admin | boolean（true/false） |

---

## Indexes

### sessions テーブル

| インデックス名 | カラム | 目的 |
|---------------|--------|------|
| PRIMARY | id | 主キー |
| sessions_user_id_index | user_id | 同時ログイン制御クエリ高速化 |
| sessions_last_activity_index | last_activity | セッションクリーンアップ高速化 |

### staffs テーブル（追加なし）

is_admin カラムはインデックス不要（セッションチェック時に既に user_id で取得済み）。

---

## Migration Scripts

### 1. staffs テーブル更新

```php
// database/migrations/xxxx_add_is_admin_to_staffs_table.php

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('staffs', function (Blueprint $table) {
            $table->boolean('is_admin')
                ->default(false)
                ->after('name')
                ->comment('管理者フラグ');
        });
    }

    public function down(): void
    {
        Schema::table('staffs', function (Blueprint $table) {
            $table->dropColumn('is_admin');
        });
    }
};
```

### 2. sessions テーブル（既存）

sessions テーブルは既に Laravel 標準マイグレーションで作成済み。
user_id カラムは ULID 対応済み（`2025_01_01_000001_modify_sessions_user_id_for_ulid.php`）。

---

## Domain Model Updates

### Staff.php

```php
// packages/Domain/Staff/Domain/Model/Staff.php

final class Staff
{
    // 既存のプロパティに追加
    private bool $isAdmin;

    // コンストラクタに追加
    private function __construct(
        // ... 既存パラメータ
        private bool $isAdmin,
    ) {}

    // ファクトリメソッド更新
    public static function create(
        // ... 既存パラメータ
    ): self {
        return new self(
            // ... 既存値
            isAdmin: false, // 新規職員はデフォルト false
        );
    }

    public static function reconstruct(
        // ... 既存パラメータ
        bool $isAdmin,
    ): self {
        return new self(
            // ... 既存値
            isAdmin: $isAdmin,
        );
    }

    /**
     * 管理者かどうかを判定
     *
     * @return bool 管理者の場合 true
     */
    public function isAdmin(): bool
    {
        return $this->isAdmin;
    }
}
```

### StaffRecord.php

```php
// packages/Domain/Staff/Infrastructure/EloquentModels/StaffRecord.php

class StaffRecord extends Model implements Authenticatable
{
    protected $fillable = [
        // ... 既存フィールド
        'is_admin',
    ];

    protected $casts = [
        // ... 既存キャスト
        'is_admin' => 'boolean',
    ];
}
```
