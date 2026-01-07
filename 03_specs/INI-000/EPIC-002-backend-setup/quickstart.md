# Quickstart: バックエンド初期設定

**Date**: 2025-12-23
**Feature**: 003-backend-setup

## 前提条件

- EPIC-001（Docker 環境構築）が完了していること
- Docker コンテナが起動していること

```bash
# Docker 環境の起動確認
docker compose ps
```

すべてのサービスが「Running」状態であることを確認してください。

---

## セットアップ手順

### 1. Laravel プロジェクトの初期化（初回のみ）

```bash
# バックエンドコンテナに入る
docker compose exec backend bash

# Laravel プロジェクトの作成（既存ファイルがある場合はスキップ）
composer create-project laravel/laravel . --prefer-dist

# 環境設定ファイルの作成
cp .env.example .env

# アプリケーションキーの生成
php artisan key:generate
```

### 2. DDD ディレクトリ構成の作成

```bash
# コンテナ内で実行
mkdir -p app/src/Common/{Domain,Application,Infrastructure}
mkdir -p app/src/BookManagement/{Domain,Application,Infrastructure,Presentation}
mkdir -p app/src/LoanManagement/{Domain,Application,Infrastructure,Presentation}
mkdir -p app/src/UserManagement/{Domain,Application,Infrastructure,Presentation}
```

### 3. Composer オートロード設定

`composer.json` に以下を追加:

```json
{
    "autoload": {
        "psr-4": {
            "App\\": "app/",
            "App\\Src\\": "app/src/"
        }
    }
}
```

オートローダーを再生成:

```bash
composer dump-autoload
```

### 4. データベース接続確認

```bash
# マイグレーション実行
php artisan migrate

# 接続確認
php artisan db:show
```

### 5. PHPStan/Larastan の設定

```bash
# Larastan のインストール
composer require larastan/larastan --dev

# phpstan.neon の作成
cat > phpstan.neon << 'EOF'
includes:
    - vendor/larastan/larastan/extension.neon

parameters:
    level: 5
    paths:
        - app/
EOF

# 静的解析の実行
./vendor/bin/phpstan analyse
```

### 6. Pest テストフレームワークの設定

```bash
# Pest のインストール
composer require pestphp/pest --dev --with-all-dependencies
composer require pestphp/pest-plugin-laravel --dev

# Pest の初期化
./vendor/bin/pest --init

# テストの実行
./vendor/bin/pest
```

### 7. Laravel Sanctum の導入

```bash
# Sanctum のインストール
composer require laravel/sanctum

# 設定ファイルの公開
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"

# マイグレーション実行
php artisan migrate
```

---

## 動作確認

### Laravel バージョン確認

```bash
docker compose exec backend php artisan --version
# 期待: Laravel Framework 11.x.x
```

### API ヘルスチェック

```bash
curl http://localhost/api/health
# 期待: {"status":"ok","timestamp":"..."}
```

### データベース接続

```bash
docker compose exec backend php artisan migrate:status
# 期待: マイグレーション一覧が表示される
```

### 静的解析

```bash
docker compose exec backend ./vendor/bin/phpstan analyse
# 期待: エラー 0 件
```

### テスト実行

```bash
docker compose exec backend ./vendor/bin/pest
# 期待: テストがパスする
```

---

## トラブルシューティング

### Permission denied エラー

```bash
# ファイル権限を修正
sudo chown -R $USER:$USER ./backend
chmod -R 755 ./backend/storage ./backend/bootstrap/cache
```

### Composer メモリ不足

```bash
# メモリ制限を解除
COMPOSER_MEMORY_LIMIT=-1 composer install
```

### データベース接続エラー

```bash
# .env の設定確認
DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=library
DB_USERNAME=library
DB_PASSWORD=secret
```

---

## 次のステップ

1. ドメインモデルの実装（EPIC-003 以降）
2. API エンドポイントの実装
3. フロントエンドとの連携
