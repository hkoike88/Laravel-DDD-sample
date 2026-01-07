# Quickstart: 職員エンティティの設計

**Branch**: `001-staff-entity-design` | **Date**: 2025-12-25

## 前提条件

- Docker / Docker Compose が起動していること
- PHP 8.3 環境
- MySQL 8.0 コンテナが稼働していること

## セットアップ手順

### 1. マイグレーション実行

```bash
# Docker コンテナ内で実行
docker compose exec backend php artisan migrate
```

### 2. ServiceProvider の登録

`backend/config/app.php` の `providers` 配列に追加:

```php
Packages\Domain\Staff\Application\Providers\StaffServiceProvider::class,
```

または、Package Auto-Discovery を使用する場合は `composer.json` の `extra.laravel.providers` に追加。

## 使用例

### 職員の新規作成

```php
use Packages\Domain\Staff\Domain\Model\Staff;
use Packages\Domain\Staff\Domain\ValueObjects\StaffId;
use Packages\Domain\Staff\Domain\ValueObjects\Email;
use Packages\Domain\Staff\Domain\ValueObjects\Password;
use Packages\Domain\Staff\Domain\ValueObjects\StaffName;
use Packages\Domain\Staff\Domain\Repositories\StaffRepositoryInterface;

// DI でリポジトリを取得
$repository = app(StaffRepositoryInterface::class);

// 職員を作成
$staff = Staff::create(
    id: StaffId::generate(),
    email: Email::create('tanaka@example.com'),
    password: Password::fromPlainText('SecureP@ssw0rd'),
    name: StaffName::create('田中太郎')
);

// 保存
$repository->save($staff);
```

### 職員の検索

```php
use Packages\Domain\Staff\Domain\ValueObjects\StaffId;
use Packages\Domain\Staff\Domain\ValueObjects\Email;

// ID で検索
$staff = $repository->find(StaffId::fromString('01HXYZ...'));

// メールアドレスで検索
$staff = $repository->findByEmail(Email::create('tanaka@example.com'));
```

### パスワード検証

```php
// 認証時のパスワード検証
$staff = $repository->findByEmail(Email::create('tanaka@example.com'));

if ($staff !== null && $staff->verifyPassword('入力されたパスワード')) {
    // 認証成功
    $staff->resetFailedLoginAttempts();
    $repository->save($staff);
} else {
    // 認証失敗
    if ($staff !== null) {
        $staff->incrementFailedLoginAttempts();
        $repository->save($staff);
    }
}
```

### アカウントロック

```php
// ロック
$staff->lock();
$repository->save($staff);

// ロック状態の確認
if ($staff->isLocked()) {
    throw new AccountLockedException();
}

// アンロック
$staff->unlock();
$repository->save($staff);
```

## テスト実行

```bash
# 全テスト実行
docker compose exec backend ./vendor/bin/pest

# Staff ドメインのみ
docker compose exec backend ./vendor/bin/pest tests/Unit/Packages/Domain/Staff

# 特定のテストファイル
docker compose exec backend ./vendor/bin/pest tests/Unit/Packages/Domain/Staff/Domain/Model/StaffTest.php
```

## ディレクトリ構成

```
backend/packages/Domain/Staff/
├── Domain/
│   ├── Model/
│   │   └── Staff.php
│   ├── ValueObjects/
│   │   ├── StaffId.php
│   │   ├── Email.php
│   │   ├── Password.php
│   │   └── StaffName.php
│   ├── Repositories/
│   │   └── StaffRepositoryInterface.php
│   └── Exceptions/
│       ├── InvalidEmailException.php
│       ├── InvalidPasswordException.php
│       ├── StaffNotFoundException.php
│       └── DuplicateEmailException.php
├── Application/
│   ├── Repositories/
│   │   └── EloquentStaffRepository.php
│   └── Providers/
│       └── StaffServiceProvider.php
└── Infrastructure/
    └── EloquentModels/
        └── StaffRecord.php
```

## トラブルシューティング

### マイグレーションエラー

```bash
# マイグレーション状態を確認
docker compose exec backend php artisan migrate:status

# ロールバック
docker compose exec backend php artisan migrate:rollback
```

### クラスが見つからない

```bash
# autoload を再生成
docker compose exec backend composer dump-autoload
```

## 関連ドキュメント

- [spec.md](./spec.md) - 機能仕様
- [data-model.md](./data-model.md) - データモデル設計
- [research.md](./research.md) - 技術調査結果
- [contracts/staff-repository.md](./contracts/staff-repository.md) - リポジトリコントラクト
