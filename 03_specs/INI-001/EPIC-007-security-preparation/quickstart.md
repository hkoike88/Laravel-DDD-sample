# Quickstart: セキュリティ対策準備

**Feature**: 001-security-preparation
**Date**: 2026-01-06

---

## 概要

このドキュメントは、セキュリティ対策準備機能の実装開始に必要な情報を提供する。

---

## 前提条件

### 既存機能

- 職員認証機能（EPIC-001）が完成していること
- Staff エンティティとアカウントロック機能が実装済み
- Laravel Sanctum による SPA 認証が動作していること

### 環境要件

- PHP 8.3+
- Laravel 11.x
- MySQL 8.0
- Node.js 20.x
- Docker / Docker Compose

---

## クイックスタート

### 1. 環境セットアップ

```bash
# リポジトリをクローン（既存プロジェクトの場合はスキップ）
cd /path/to/project

# ブランチを作成
git checkout -b 001-security-preparation

# 依存関係をインストール
docker compose exec backend composer install
docker compose exec frontend npm install
```

### 2. 設定ファイルの確認・更新

#### 2.1 ハッシュ設定（新規作成）

`backend/config/hashing.php`:

```php
<?php

return [
    'driver' => 'bcrypt',
    'bcrypt' => [
        'rounds' => 12,  // NIST SP 800-63B 準拠
    ],
];
```

#### 2.2 セッション設定（確認のみ）

`backend/config/session.php` は既に以下の要件を満たしている:
- `driver` = 'database'
- `lifetime` = 30（分）
- `encrypt` = true
- `secure` = true
- `http_only` = true
- `same_site` = 'lax'

#### 2.3 ロギング設定（security チャンネル追加）

`backend/config/logging.php` の `channels` 配列に追加:

```php
'security' => [
    'driver' => 'daily',
    'path' => storage_path('logs/security.log'),
    'level' => 'info',
    'days' => 90,
],
```

### 3. マイグレーション実行

```bash
# パスワード履歴テーブル作成
docker compose exec backend php artisan make:migration create_password_histories_table

# マイグレーション実行
docker compose exec backend php artisan migrate
```

### 4. 実装順序

以下の順序で実装することを推奨:

1. **パスワードポリシー**
   - パスワードバリデーションルールの作成
   - FormRequest への適用

2. **パスワード履歴**
   - PasswordHistory エンティティ
   - PasswordHistoryRepository
   - PasswordHistoryService（再利用チェック）

3. **絶対タイムアウト**
   - AbsoluteSessionTimeout ミドルウェア
   - セッション開始時刻の記録

4. **同時ログイン制御**
   - SessionManagerService
   - ログイン時の制御ロジック

5. **セキュリティログ**
   - SecurityLogger サービス
   - イベントリスナーの実装

6. **CI/CD セキュリティスキャン**
   - GitHub Actions ワークフロー追加

---

## キーファイル

### バックエンド

| ファイル | 説明 |
|---------|------|
| `packages/Domain/Staff/Domain/Model/PasswordHistory.php` | パスワード履歴エンティティ |
| `packages/Domain/Staff/Domain/Services/PasswordHistoryService.php` | パスワード履歴サービス |
| `packages/Domain/Staff/Domain/Services/SessionManagerService.php` | セッション管理サービス |
| `app/Http/Middleware/AbsoluteSessionTimeout.php` | 絶対タイムアウトミドルウェア |
| `config/hashing.php` | ハッシュ設定 |
| `config/logging.php` | ロギング設定（security チャンネル） |

### フロントエンド

| ファイル | 説明 |
|---------|------|
| `frontend/src/features/auth/components/SessionList.tsx` | セッション一覧コンポーネント |
| `frontend/src/features/auth/hooks/useSessions.ts` | セッション管理フック |
| `frontend/src/features/settings/components/PasswordChangeForm.tsx` | パスワード変更フォーム |

### CI/CD

| ファイル | 説明 |
|---------|------|
| `.github/workflows/security.yml` | セキュリティスキャンワークフロー |

---

## テスト実行

```bash
# バックエンドテスト
docker compose exec backend php artisan test --filter=Security
docker compose exec backend php artisan test --filter=Password
docker compose exec backend php artisan test --filter=Session

# フロントエンドテスト
docker compose exec frontend npm run test -- --grep="session"
docker compose exec frontend npm run test -- --grep="password"

# 静的解析
docker compose exec backend ./vendor/bin/phpstan analyse

# セキュリティ監査
docker compose exec backend composer audit
docker compose exec frontend npm audit
```

---

## 関連ドキュメント

- [spec.md](./spec.md) - 機能仕様書
- [research.md](./research.md) - 調査結果
- [data-model.md](./data-model.md) - データモデル
- [contracts/api.yaml](./contracts/api.yaml) - API コントラクト

### セキュリティ標準

- `00_docs/20_tech/99_standard/security/01_PasswordPolicy.md`
- `00_docs/20_tech/99_standard/security/02_SessionManagement.md`
- `00_docs/20_tech/99_standard/security/04_EncryptionPolicy.md`
- `00_docs/20_tech/99_standard/security/08_SecurityScanning.md`

---

## 注意事項

1. **Have I Been Pwned API**
   - パスワード漏洩チェックで使用
   - API 障害時はスキップして他の検証のみ実行
   - タイムアウト: 5秒

2. **パフォーマンス**
   - パスワード履歴チェックは5件のみ
   - bcrypt cost=12 はログイン時に約300ms

3. **後方互換性**
   - 既存のパスワードは変更時にポリシー適用
   - 既存セッションは新ルール適用後も有効
