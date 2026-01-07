# Data Model: Docker 環境構築

**Date**: 2025-12-23
**Feature**: 002-docker-environment
**Spec**: [spec.md](./spec.md)

## Entity Definitions

### Service（サービス）

開発環境を構成する個別のコンテナ。

| Property | Type | Description |
|----------|------|-------------|
| name | string | サービス識別子（frontend, backend, db, phpmyadmin, nginx） |
| image | string | ベースイメージまたは Dockerfile パス |
| ports | string[] | ホスト:コンテナ のポートマッピング |
| volumes | string[] | ボリュームマウント設定 |
| environment | object | 環境変数 |
| depends_on | object | 依存サービスと起動条件 |
| healthcheck | object | ヘルスチェック設定（オプション） |
| networks | string[] | 接続するネットワーク |

### Volume（ボリューム）

データの永続化領域。

| Property | Type | Description |
|----------|------|-------------|
| name | string | ボリューム識別子 |
| driver | string | ストレージドライバ（local） |
| type | enum | named / bind / anonymous |

### Network（ネットワーク）

サービス間通信を可能にする仮想ネットワーク。

| Property | Type | Description |
|----------|------|-------------|
| name | string | ネットワーク識別子 |
| driver | string | ネットワークドライバ（bridge） |

### Environment Variable（環境変数）

ポート番号やデータベース接続情報などの設定値。

| Property | Type | Description |
|----------|------|-------------|
| key | string | 変数名 |
| value | string | デフォルト値 |
| scope | enum | port / database / application |

---

## Entity Relationships

```
┌─────────────────────────────────────────────────────────────┐
│                        Network                               │
│                      (app-network)                           │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  ┌──────────┐      ┌──────────┐      ┌──────────┐          │
│  │  nginx   │──────│ frontend │      │phpmyadmin│          │
│  │  :80     │      │  :5173   │      │  :8080   │          │
│  └────┬─────┘      └──────────┘      └────┬─────┘          │
│       │                                    │                 │
│       │ /api/*                             │                 │
│       ▼                                    ▼                 │
│  ┌──────────┐                        ┌──────────┐          │
│  │ backend  │───────────────────────▶│    db    │          │
│  │  :8000   │     depends_on         │  :3306   │          │
│  └──────────┘     (service_healthy)  └────┬─────┘          │
│                                           │                 │
│                                      ┌────▼─────┐          │
│                                      │ db_data  │          │
│                                      │ (Volume) │          │
│                                      └──────────┘          │
└─────────────────────────────────────────────────────────────┘
```

---

## Service Instances

### 1. frontend

| Property | Value |
|----------|-------|
| name | frontend |
| build | ./frontend |
| ports | ${FRONTEND_PORT:-5173}:5173 |
| volumes | ./frontend:/app, /app/node_modules |
| networks | app-network |

### 2. backend

| Property | Value |
|----------|-------|
| name | backend |
| build | ./backend |
| ports | ${BACKEND_PORT:-8000}:8000 |
| volumes | ./backend:/var/www/html |
| depends_on | db (service_healthy) |
| networks | app-network |

### 3. db

| Property | Value |
|----------|-------|
| name | db |
| image | mysql:8.0 |
| ports | ${DB_PORT:-3306}:3306 |
| volumes | db_data:/var/lib/mysql |
| environment | MYSQL_DATABASE, MYSQL_USER, MYSQL_PASSWORD, MYSQL_ROOT_PASSWORD |
| healthcheck | mysqladmin ping -h localhost |
| networks | app-network |

### 4. phpmyadmin

| Property | Value |
|----------|-------|
| name | phpmyadmin |
| image | phpmyadmin:latest |
| ports | ${PHPMYADMIN_PORT:-8080}:80 |
| depends_on | db (service_started) |
| environment | PMA_HOST=db |
| networks | app-network |

### 5. nginx

| Property | Value |
|----------|-------|
| name | nginx |
| image | nginx:alpine |
| ports | ${NGINX_PORT:-80}:80 |
| volumes | ./infrastructure/nginx/default.conf:/etc/nginx/conf.d/default.conf |
| depends_on | frontend, backend |
| networks | app-network |

---

## Volume Instances

| Name | Type | Mount Point | Purpose |
|------|------|-------------|---------|
| db_data | named | /var/lib/mysql | MySQL データ永続化 |
| ./frontend | bind | /app | フロントエンドソースコード |
| ./backend | bind | /var/www/html | バックエンドソースコード |
| (anonymous) | anonymous | /app/node_modules | Node.js 依存パッケージ分離 |

---

## Environment Variable Instances

### Port Configuration

| Key | Default | Description |
|-----|---------|-------------|
| FRONTEND_PORT | 5173 | フロントエンド公開ポート |
| BACKEND_PORT | 8000 | バックエンド公開ポート |
| DB_PORT | 3306 | MySQL 公開ポート |
| PHPMYADMIN_PORT | 8080 | phpMyAdmin 公開ポート |
| NGINX_PORT | 80 | Nginx 公開ポート |

### Database Configuration

| Key | Default | Description |
|-----|---------|-------------|
| DB_DATABASE | library | データベース名 |
| DB_USERNAME | library | データベースユーザー |
| DB_PASSWORD | secret | データベースパスワード |
| DB_ROOT_PASSWORD | secret | MySQL root パスワード |
