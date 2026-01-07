# Data Model: 職員アカウント作成機能

**Branch**: `007-staff-account-create` | **Date**: 2026-01-06

## 概要

職員アカウント作成機能で使用するエンティティ、値オブジェクト、および関連するデータ構造を定義する。

---

## エンティティ

### Staff（職員）- 既存エンティティ

**説明**: システムを利用する職員を表すエンティティ（集約ルート）。認証情報とアカウント状態を管理する。

**テーブル**: `staffs`

| フィールド | 型 | 制約 | 説明 |
|------------|-----|------|------|
| id | CHAR(26) | PK | 職員ID（ULID） |
| email | VARCHAR(255) | UNIQUE, NOT NULL | メールアドレス（小文字正規化済み） |
| password | VARCHAR(255) | NOT NULL | ハッシュ化済みパスワード |
| name | VARCHAR(100) | NOT NULL | 職員名 |
| is_admin | BOOLEAN | DEFAULT false | 管理者フラグ |
| is_locked | BOOLEAN | DEFAULT false | ロック状態 |
| failed_login_attempts | INT UNSIGNED | DEFAULT 0 | ログイン失敗回数 |
| locked_at | TIMESTAMP | NULLABLE | ロック日時 |
| created_at | TIMESTAMP | NOT NULL | 作成日時 |
| updated_at | TIMESTAMP | NOT NULL | 更新日時 |

**ドメインモデル**: `Packages\Domain\Staff\Domain\Model\Staff`

```php
final class Staff
{
    private function __construct(
        private readonly StaffId $id,
        private readonly Email $email,
        private Password $password,
        private readonly StaffName $name,
        private readonly bool $isAdmin,
        private bool $isLocked,
        private int $failedLoginAttempts,
        private ?DateTimeImmutable $lockedAt,
    ) {}

    public static function create(
        StaffId $id,
        Email $email,
        Password $password,
        StaffName $name,
        bool $isAdmin = false,
    ): self;

    public static function reconstruct(...): self;

    // Getters...
}
```

**状態遷移**:

```
[新規作成] --> [アクティブ]
                  |
                  v (ロック操作)
              [ロック済み]
                  |
                  v (アンロック操作)
              [アクティブ]
```

---

## 値オブジェクト

### StaffId（職員ID）- 既存

**説明**: ULID 形式の職員識別子

```php
final class StaffId
{
    private function __construct(private readonly string $value) {}

    public static function generate(): self;
    public static function fromString(string $value): self;
    public function value(): string;
}
```

**制約**:
- 26文字の ULID 形式
- 不変

### Email（メールアドレス）- 既存

**説明**: メールアドレスを表す値オブジェクト

```php
final class Email
{
    private function __construct(private readonly string $value) {}

    public static function create(string $value): self;
    public function value(): string;
}
```

**制約**:
- 有効なメールアドレス形式
- 255文字以内
- 小文字に正規化
- システム内で一意

### Password（パスワード）- 既存

**説明**: ハッシュ化されたパスワードを表す値オブジェクト

```php
final class Password
{
    private function __construct(private readonly string $hashedValue) {}

    public static function fromPlainText(string $plainText): self;
    public static function fromHash(string $hash): self;
    public function verify(string $plainText): bool;
    public function value(): string;
}
```

**制約**:
- bcrypt (cost=12) でハッシュ化
- 平文は保持しない

### StaffName（職員名）- 既存

**説明**: 職員の氏名を表す値オブジェクト

```php
final class StaffName
{
    private function __construct(private readonly string $value) {}

    public static function create(string $value): self;
    public function value(): string;
}
```

**制約**:
- 必須
- 50文字以内（注: DB は 100 文字だが、仕様では 50 文字制限）
- 空文字列不可

---

## DTO（Data Transfer Objects）

### CreateStaffInput

**説明**: 職員作成リクエストの入力データ

**配置**: `backend/packages/Domain/Staff/Application/DTO/StaffAccount/CreateStaffInput.php`

```php
<?php

declare(strict_types=1);

namespace Packages\Domain\Staff\Application\DTO\StaffAccount;

/**
 * 職員作成リクエストの入力データ
 */
final readonly class CreateStaffInput
{
    /**
     * @param string $name 職員名（50文字以内）
     * @param string $email メールアドレス（255文字以内、一意）
     * @param string $role 権限（'staff' | 'admin'）
     */
    public function __construct(
        public string $name,
        public string $email,
        public string $role,
    ) {}
}
```

### CreateStaffOutput

**説明**: 職員作成レスポンスの出力データ

**配置**: `backend/packages/Domain/Staff/Application/DTO/StaffAccount/CreateStaffOutput.php`

```php
<?php

declare(strict_types=1);

namespace Packages\Domain\Staff\Application\DTO\StaffAccount;

/**
 * 職員作成レスポンスの出力データ
 */
final readonly class CreateStaffOutput
{
    /**
     * @param string $id 職員ID（ULID）
     * @param string $name 職員名
     * @param string $email メールアドレス
     * @param string $role 権限
     * @param string $temporaryPassword 初期パスワード（平文、この場面でのみ使用）
     * @param string $createdAt 作成日時（ISO 8601）
     */
    public function __construct(
        public string $id,
        public string $name,
        public string $email,
        public string $role,
        public string $temporaryPassword,
        public string $createdAt,
    ) {}
}
```

### StaffListOutput

**説明**: 職員一覧の1件分の出力データ

**配置**: `backend/packages/Domain/Staff/Application/DTO/StaffAccount/StaffListOutput.php`

```php
<?php

declare(strict_types=1);

namespace Packages\Domain\Staff\Application\DTO\StaffAccount;

/**
 * 職員一覧の1件分の出力データ
 */
final readonly class StaffListOutput
{
    /**
     * @param string $id 職員ID（ULID）
     * @param string $name 職員名
     * @param string $email メールアドレス
     * @param string $role 権限
     * @param string $createdAt 作成日時（ISO 8601）
     */
    public function __construct(
        public string $id,
        public string $name,
        public string $email,
        public string $role,
        public string $createdAt,
    ) {}
}
```

### StaffListPaginatedOutput

**説明**: ページネーション付き職員一覧レスポンス

**配置**: `backend/packages/Domain/Staff/Application/DTO/StaffAccount/StaffListPaginatedOutput.php`

```php
<?php

declare(strict_types=1);

namespace Packages\Domain\Staff\Application\DTO\StaffAccount;

/**
 * ページネーション付き職員一覧レスポンス
 */
final readonly class StaffListPaginatedOutput
{
    /**
     * @param StaffListOutput[] $data 職員一覧
     * @param int $currentPage 現在のページ番号
     * @param int $lastPage 最終ページ番号
     * @param int $perPage 1ページあたりの件数
     * @param int $total 総件数
     * @param int|null $from 現在のページの開始レコード番号
     * @param int|null $to 現在のページの終了レコード番号
     * @param PaginationLinks $links ページネーションリンク
     */
    public function __construct(
        public array $data,
        public int $currentPage,
        public int $lastPage,
        public int $perPage,
        public int $total,
        public ?int $from,
        public ?int $to,
        public PaginationLinks $links,
    ) {}
}
```

### PaginationLinks

**説明**: ページネーションリンク

**配置**: `backend/packages/Domain/Staff/Application/DTO/StaffAccount/PaginationLinks.php`

```php
<?php

declare(strict_types=1);

namespace Packages\Domain\Staff\Application\DTO\StaffAccount;

/**
 * ページネーションリンク
 */
final readonly class PaginationLinks
{
    /**
     * @param string|null $first 最初のページへのURL
     * @param string|null $last 最後のページへのURL
     * @param string|null $prev 前のページへのURL
     * @param string|null $next 次のページへのURL
     */
    public function __construct(
        public ?string $first,
        public ?string $last,
        public ?string $prev,
        public ?string $next,
    ) {}
}
```

---

## リポジトリインターフェース拡張

### StaffRepositoryInterface - 拡張メソッド

既存のインターフェースに以下のメソッドを追加:

```php
interface StaffRepositoryInterface
{
    // 既存メソッド...
    public function find(StaffId $id): Staff;
    public function findOrNull(StaffId $id): ?Staff;
    public function findByEmail(Email $email): ?Staff;
    public function existsByEmail(Email $email): bool;
    public function save(Staff $staff): void;
    public function delete(StaffId $id): void;

    // 新規メソッド
    /**
     * ページネーション付きで全職員を取得
     *
     * @param int $page ページ番号（1始まり）
     * @param int $perPage 1ページあたりの件数
     * @return array{data: Staff[], currentPage: int, lastPage: int, perPage: int, total: int}
     */
    public function findAllPaginated(int $page = 1, int $perPage = 20): array;
}
```

---

## バリデーションルール

### 職員作成リクエスト

| フィールド | ルール | エラーメッセージ |
|------------|--------|------------------|
| name | required, string, max:50 | 氏名は必須です / 氏名は50文字以内で入力してください |
| email | required, email, max:255, unique:staffs | メールアドレスは必須です / 有効なメールアドレスを入力してください / このメールアドレスは既に登録されています |
| role | required, in:staff,admin | 権限を選択してください |

---

## 監査ログ構造

### StaffCreatedLog

**説明**: 職員作成時の監査ログ

```json
{
    "operator_id": "01HV...",
    "target_staff_id": "01HV...",
    "operation": "staff_created",
    "timestamp": "2026-01-06T10:00:00+09:00"
}
```

| フィールド | 型 | 説明 |
|------------|-----|------|
| operator_id | string | 操作を行った管理者のID |
| target_staff_id | string | 作成された職員のID |
| operation | string | 操作種別（固定: "staff_created"） |
| timestamp | string | ISO 8601 形式のタイムスタンプ |

---

## 関連図

```
┌─────────────────────────────────────────────────────────────┐
│                        Staff（集約ルート）                    │
├─────────────────────────────────────────────────────────────┤
│ - id: StaffId                                               │
│ - email: Email                                              │
│ - password: Password                                        │
│ - name: StaffName                                           │
│ - isAdmin: boolean                                          │
│ - isLocked: boolean                                         │
│ - failedLoginAttempts: int                                  │
│ - lockedAt: DateTimeImmutable?                              │
├─────────────────────────────────────────────────────────────┤
│ + create(): Staff                                           │
│ + reconstruct(): Staff                                      │
│ + verifyPassword(): bool                                    │
│ + lock(): void                                              │
│ + unlock(): void                                            │
└─────────────────────────────────────────────────────────────┘
                              │
                              │ uses
                              ▼
┌───────────────────────────────────────────────────────────────────────────┐
│                        StaffRepositoryInterface                           │
├───────────────────────────────────────────────────────────────────────────┤
│ + find(id): Staff                                                         │
│ + findOrNull(id): Staff?                                                  │
│ + findByEmail(email): Staff?                                              │
│ + existsByEmail(email): bool                                              │
│ + save(staff): void                                                       │
│ + delete(id): void                                                        │
│ + findAllPaginated(page, perPage): array   ← 新規追加                     │
└───────────────────────────────────────────────────────────────────────────┘
```
