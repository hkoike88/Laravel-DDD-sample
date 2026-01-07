# Service Contracts: Docker 環境構築

**Date**: 2025-12-23
**Feature**: 002-docker-environment

## Overview

Docker Compose で定義する5つのサービスのコントラクト定義。

---

## 1. Frontend Service

### 責務
- React + Vite 開発サーバーの実行
- ホットモジュールリプレースメント（HMR）のサポート
- TypeScript のトランスパイル

### インターフェース

| Type | Endpoint | Description |
|------|----------|-------------|
| HTTP | :5173 | Vite 開発サーバー |
| WebSocket | :5173 | HMR 接続 |

### 依存関係
- なし（独立して起動可能）

### ヘルスチェック
- 不要（開発サーバーは即時応答）

### 環境変数

| Variable | Required | Default | Description |
|----------|----------|---------|-------------|
| VITE_API_URL | No | /api | バックエンド API ベース URL |

---

## 2. Backend Service

### 責務
- Laravel アプリケーションの実行
- REST API エンドポイントの提供
- データベースアクセス

### インターフェース

| Type | Endpoint | Description |
|------|----------|-------------|
| HTTP | :8000 | Laravel 開発サーバー |

### 依存関係
- db: service_healthy（MySQL 準備完了まで待機）

### ヘルスチェック
- 不要（依存先のヘルスチェックで制御）

### 環境変数

| Variable | Required | Default | Description |
|----------|----------|---------|-------------|
| DB_HOST | Yes | db | データベースホスト |
| DB_PORT | Yes | 3306 | データベースポート |
| DB_DATABASE | Yes | library | データベース名 |
| DB_USERNAME | Yes | library | データベースユーザー |
| DB_PASSWORD | Yes | secret | データベースパスワード |
| APP_ENV | No | local | アプリケーション環境 |
| APP_DEBUG | No | true | デバッグモード |

---

## 3. Database Service (db)

### 責務
- MySQL データベースサーバーの提供
- データの永続化
- 初回起動時のデータベース・ユーザー自動作成

### インターフェース

| Type | Endpoint | Description |
|------|----------|-------------|
| TCP | :3306 | MySQL プロトコル |

### 依存関係
- なし（独立して起動可能）

### ヘルスチェック

```yaml
healthcheck:
  test: ["CMD", "mysqladmin", "ping", "-h", "localhost"]
  interval: 10s
  timeout: 5s
  retries: 5
  start_period: 30s
```

### 環境変数

| Variable | Required | Default | Description |
|----------|----------|---------|-------------|
| MYSQL_DATABASE | Yes | library | 作成するデータベース名 |
| MYSQL_USER | Yes | library | 作成するユーザー名 |
| MYSQL_PASSWORD | Yes | secret | ユーザーパスワード |
| MYSQL_ROOT_PASSWORD | Yes | secret | root パスワード |

### ボリューム
- `db_data:/var/lib/mysql` - データ永続化

---

## 4. phpMyAdmin Service

### 責務
- Web ベースのデータベース管理インターフェース提供
- データベースの閲覧・編集機能

### インターフェース

| Type | Endpoint | Description |
|------|----------|-------------|
| HTTP | :8080 → :80 | phpMyAdmin Web UI |

### 依存関係
- db: service_started（起動順序のみ）

### ヘルスチェック
- 不要

### 環境変数

| Variable | Required | Default | Description |
|----------|----------|---------|-------------|
| PMA_HOST | Yes | db | 接続先 MySQL ホスト |
| PMA_PORT | No | 3306 | 接続先 MySQL ポート |

---

## 5. Nginx Service

### 責務
- リバースプロキシとしてのリクエスト振り分け
- 単一エントリーポイントの提供
- WebSocket 接続のプロキシ

### インターフェース

| Type | Endpoint | Description |
|------|----------|-------------|
| HTTP | :80 | メインエントリーポイント |

### ルーティング

| Path | Upstream | Description |
|------|----------|-------------|
| /api/* | backend:8000 | バックエンド API |
| /* | frontend:5173 | フロントエンド |

### 依存関係
- frontend: service_started
- backend: service_started

### ヘルスチェック
- 不要

### 設定ファイル
- `infrastructure/nginx/default.conf`

---

## Service Communication Matrix

| From \ To | frontend | backend | db | phpmyadmin | nginx |
|-----------|----------|---------|-----|------------|-------|
| frontend | - | via nginx | - | - | - |
| backend | - | - | TCP:3306 | - | - |
| db | - | - | - | - | - |
| phpmyadmin | - | - | TCP:3306 | - | - |
| nginx | HTTP:5173 | HTTP:8000 | - | - | - |
| Host | :5173 | :8000 | :3306 | :8080 | :80 |

---

## Startup Order

```
1. db        ──────────────────────────────────────┐
   (healthcheck: mysqladmin ping)                  │
                                                   │
2. frontend  ──────────────────┐                   │
                               │                   │
3. backend   ──────────────────┤ (waits for db)    │
   (depends_on: db healthy)    │                   │
                               │                   │
4. phpmyadmin ─────────────────┤ (waits for db)    │
   (depends_on: db started)    │                   │
                               │                   │
5. nginx     ──────────────────┘                   │
   (depends_on: frontend, backend)                 │
                                                   │
Timeline ──────────────────────────────────────────┘
```
