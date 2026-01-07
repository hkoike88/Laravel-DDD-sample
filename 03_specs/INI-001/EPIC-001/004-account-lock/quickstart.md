# Quickstart: アカウントロック機能

**Branch**: `006-account-lock` | **Date**: 2025-12-26

## 概要

本ドキュメントは、アカウントロック機能のテスト実装を開始するためのクイックスタートガイドである。

## 前提条件

- PHP 8.2+
- Node.js 20.x
- Docker Compose（バックエンド起動用）
- 既存の認証機能（002-staff-auth-api, 003-login-ui）が実装済み

## 環境セットアップ

### 1. 依存関係のインストール

```bash
# バックエンド
cd backend
composer install

# フロントエンド
cd frontend
npm install
```

### 2. 開発サーバーの起動

```bash
# バックエンド（Docker）
docker compose up -d

# フロントエンド
npm run dev
```

## 既存実装の確認

アカウントロック機能の基本実装は既に完了している。以下のファイルを確認：

### バックエンド

| ファイル | 説明 |
|---------|------|
| `packages/Domain/Staff/Domain/Model/Staff.php` | Staff エンティティ（lock メソッド実装済み） |
| `packages/Domain/Staff/Domain/Exceptions/AccountLockedException.php` | ロック例外 |
| `packages/Domain/Staff/Application/UseCases/Auth/LoginUseCase.php` | ログインユースケース（5回失敗でロック） |
| `database/migrations/2025_01_01_000000_create_staffs_table.php` | staffs テーブル（ロック関連カラム含む） |

### フロントエンド

| ファイル | 説明 |
|---------|------|
| `src/features/auth/api/authApi.ts` | 認証 API（423 エラー対応済み） |
| `src/features/auth/types/auth.ts` | 型定義（locked タイプ定義済み） |

## 追加実装タスク

### 1. バックエンド単体テスト

**ファイル**: `tests/Unit/Domain/Staff/StaffAccountLockTest.php`

```php
<?php

use Packages\Domain\Staff\Domain\Model\Staff;
use Packages\Domain\Staff\Domain\ValueObjects\Email;
use Packages\Domain\Staff\Domain\ValueObjects\Password;
use Packages\Domain\Staff\Domain\ValueObjects\StaffId;
use Packages\Domain\Staff\Domain\ValueObjects\StaffName;

test('5回失敗でアカウントがロックされる', function () {
    $staff = Staff::create(
        StaffId::generate(),
        Email::create('test@example.com'),
        Password::create('password123'),
        StaffName::create('テスト職員')
    );

    for ($i = 0; $i < 5; $i++) {
        $staff->incrementFailedLoginAttempts();
    }

    expect($staff->failedLoginAttempts())->toBe(5);

    $staff->lock();
    expect($staff->isLocked())->toBeTrue();
    expect($staff->lockedAt())->not->toBeNull();
});

test('ログイン成功時に失敗回数がリセットされる', function () {
    $staff = Staff::create(
        StaffId::generate(),
        Email::create('test@example.com'),
        Password::create('password123'),
        StaffName::create('テスト職員')
    );

    $staff->incrementFailedLoginAttempts();
    $staff->incrementFailedLoginAttempts();
    $staff->incrementFailedLoginAttempts();

    expect($staff->failedLoginAttempts())->toBe(3);

    $staff->resetFailedLoginAttempts();

    expect($staff->failedLoginAttempts())->toBe(0);
});
```

### 2. E2E テスト

**ファイル**: `frontend/tests/e2e/account-lock.spec.ts`

```typescript
import { test, expect, type Page, type Route } from '@playwright/test'

test.describe('アカウントロック機能', () => {
  test('5回連続失敗後にロックメッセージが表示される', async ({ page }) => {
    // モックを設定（5回失敗後に 423 を返す）
    let failCount = 0
    await page.route('**/api/auth/login', async (route: Route) => {
      failCount++
      if (failCount <= 5) {
        await route.fulfill({
          status: 401,
          contentType: 'application/json',
          body: JSON.stringify({ message: 'メールアドレスまたはパスワードが正しくありません' }),
        })
      } else {
        await route.fulfill({
          status: 423,
          contentType: 'application/json',
          body: JSON.stringify({ message: 'アカウントがロックされています。管理者にお問い合わせください' }),
        })
      }
    })

    await page.goto('/login')

    // 6回ログイン試行
    for (let i = 0; i < 6; i++) {
      await page.fill('input[name="email"]', 'test@example.com')
      await page.fill('input[name="password"]', 'wrongpassword')
      await page.click('button[type="submit"]')
      await page.waitForTimeout(500)
    }

    // ロックメッセージを確認
    await expect(page.getByText('アカウントがロックされています')).toBeVisible()
  })
})
```

## テスト実行

### バックエンドテスト

```bash
cd backend

# 全テスト実行
php artisan test

# アカウントロック関連のみ
php artisan test --filter=AccountLock
```

### フロントエンドテスト

```bash
cd frontend

# 単体テスト
npm run test:run

# E2E テスト
npm run test:e2e

# アカウントロック関連のみ
npm run test:e2e -- account-lock.spec.ts
```

## 成功基準の検証

| 基準 | 検証方法 |
|-----|---------|
| SC-001: 5回連続失敗後に100%拒否 | E2E テストで5回失敗→ロックを確認 |
| SC-002: 1秒以内にエラーメッセージ表示 | E2E テストでタイムアウト設定 |
| SC-003: ログイン成功後の失敗回数リセット | 単体テストで確認 |
| SC-004: 再起動後もロック状態維持 | Feature テストで確認 |

## トラブルシューティング

### テストが失敗する場合

1. データベースがリセットされているか確認: `php artisan migrate:fresh`
2. キャッシュをクリア: `php artisan config:clear`
3. モック設定を確認（特に API ルートのパターン）

### ロックが解除されない場合

本フィーチャーではロック解除機能は対象外（Phase 2）。
テスト時は直接データベースを更新するか、マイグレーションを再実行する。

```bash
# テスト用のリセット
php artisan migrate:fresh --seed
```

## 次のステップ

1. `/speckit.tasks` を実行してタスクリストを生成
2. 各タスクを順番に実装
3. テストを実行して成功基準を満たすことを確認
