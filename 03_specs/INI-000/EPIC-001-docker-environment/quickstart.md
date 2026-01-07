# Quickstart: Docker 環境構築

**Date**: 2025-12-23
**Feature**: 002-docker-environment

## 前提条件

- Docker Engine 24.0+ または Docker Desktop
- Docker Compose v2.20+
- Git
- 最低 5GB のディスク空き容量

### Docker のインストール確認

```bash
docker --version
# Docker version 24.0.x または以降

docker compose version
# Docker Compose version v2.20.x または以降
```

---

## クイックスタート

### 1. リポジトリのクローン

```bash
git clone <repository-url>
cd sample-001
```

### 2. 環境変数の設定

```bash
cp .env.example .env
```

必要に応じて `.env` ファイルを編集してポート番号を変更できます。

### 3. サービスの起動

```bash
docker compose up -d
```

初回起動時はイメージのビルドが行われるため、数分かかります。

### 4. 起動確認

```bash
docker compose ps
```

すべてのサービスが「Running」状態であることを確認します。

### 5. アクセス

| サービス | URL | 用途 |
|----------|-----|------|
| メインアプリ | http://localhost | フロントエンド（Nginx 経由） |
| API | http://localhost/api | バックエンド API（Nginx 経由） |
| フロントエンド（直接） | http://localhost:5173 | Vite 開発サーバー |
| phpMyAdmin | http://localhost:8080 | データベース管理 |

---

## 基本コマンド

### サービス操作

```bash
# 全サービス起動
docker compose up -d

# 全サービス停止
docker compose down

# 再起動
docker compose restart

# ログ確認
docker compose logs -f

# 特定サービスのログ
docker compose logs -f backend
```

### 開発作業

```bash
# フロントエンドコンテナに入る
docker compose exec frontend sh

# バックエンドコンテナに入る
docker compose exec backend bash

# データベースに接続
docker compose exec db mysql -u library -psecret library
```

### リセット操作

```bash
# コンテナのみ削除（データは保持）
docker compose down

# データも含めて完全リセット
docker compose down -v

# イメージも含めて完全リセット
docker compose down -v --rmi all
```

---

## ポート設定のカスタマイズ

ポートが競合する場合は `.env` ファイルで変更できます。

```bash
# .env
FRONTEND_PORT=3000
BACKEND_PORT=9000
DB_PORT=13306
PHPMYADMIN_PORT=18080
NGINX_PORT=8080
```

変更後は再起動が必要です：

```bash
docker compose down
docker compose up -d
```

---

## トラブルシューティング

### ポートが使用中

```bash
# 使用中のポートを確認
lsof -i :80

# .env でポートを変更
NGINX_PORT=8080
```

### データベース接続エラー

```bash
# データベースのヘルスチェック状態を確認
docker compose ps db

# ログを確認
docker compose logs db
```

### コンテナが起動しない

```bash
# 詳細ログを確認
docker compose logs --tail=50

# コンテナを再ビルド
docker compose build --no-cache
docker compose up -d
```

### 権限エラー

```bash
# ファイル権限をリセット
sudo chown -R $USER:$USER ./frontend ./backend
```

---

## 次のステップ

1. バックエンド初期設定（EPIC-002）
2. フロントエンド初期設定（EPIC-003）
3. 開発環境動作確認（EPIC-004）
