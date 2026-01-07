# EPIC-001: Docker 環境構築

最終更新: 2025-12-23

---

## 概要

Docker Compose を使用して、開発環境に必要な全サービス（フロントエンド、バックエンド、データベース、リバースプロキシ）をコンテナ化し、一括で起動・停止できる環境を構築する。

---

## 関連情報

| 項目 | 値 |
|------|-----|
| イニシアチブ | [LIB-000: 開発環境構築](../../../01_vision/initiatives/LIB-000/charter.md) |
| Use Case | [UC-000-001: Docker 環境構築](../../../01_vision/initiatives/LIB-000/usecases/UC-000-001_Docker環境構築.md) |
| 優先度 | Must |
| ステータス | Draft |

---

## ビジネス価値

開発チームが統一された環境で開発を開始できるようにする。
環境差異によるトラブルを排除し、オンボーディング時間を短縮する。

---

## 受け入れ条件

1. `docker compose up -d` で全サービスが起動すること
2. フロントエンド（http://localhost:5173）にアクセスできること
3. バックエンドAPI（http://localhost:8000）にアクセスできること
4. phpMyAdmin（http://localhost:8080）でDBに接続できること
5. `docker compose down` で全サービスが停止すること
6. ボリュームによりDBデータが永続化されること

---

## 構築対象サービス

| サービス | イメージ/ビルド | ポート | 用途 |
|----------|----------------|--------|------|
| frontend | カスタムビルド | 5173 | React 開発サーバー |
| backend | カスタムビルド | 8000 | Laravel API サーバー |
| db | mysql:8.0 | 3306 | データベース |
| phpmyadmin | phpmyadmin:latest | 8080 | DB 管理ツール |
| nginx | nginx:alpine | 80 | リバースプロキシ |

---

## User Story 一覧

| ID | Story 名 | ポイント | 優先度 | ステータス |
|----|----------|---------|--------|----------|
| [ST-001](./stories/ST-001_docker-compose作成.md) | docker-compose.yml の作成 | 3 | Must | Draft |
| [ST-002](./stories/ST-002_backend-Dockerfile作成.md) | バックエンド用 Dockerfile の作成 | 2 | Must | Draft |
| [ST-003](./stories/ST-003_frontend-Dockerfile作成.md) | フロントエンド用 Dockerfile の作成 | 2 | Must | Draft |
| [ST-004](./stories/ST-004_nginx設定作成.md) | Nginx 設定の作成 | 2 | Must | Draft |
| [ST-005](./stories/ST-005_環境変数設定作成.md) | 環境変数設定（.env.example）の作成 | 1 | Must | Draft |

---

## 成果物

| 成果物 | 配置場所 | 説明 |
|--------|---------|------|
| docker-compose.yml | プロジェクトルート | サービス定義 |
| .env.example | プロジェクトルート | 環境変数テンプレート |
| Dockerfile | backend/ | バックエンドコンテナ定義 |
| Dockerfile | frontend/ | フロントエンドコンテナ定義 |
| nginx.conf | infrastructure/nginx/ | リバースプロキシ設定 |
| default.conf | infrastructure/nginx/ | Nginx サイト設定 |

---

## 技術仕様

### Docker イメージ

| サービス | ベースイメージ | 理由 |
|----------|--------------|------|
| backend | php:8.3-fpm-alpine | Laravel 11.x の要件、軽量 |
| frontend | node:22-alpine | Vite の要件、軽量 |
| db | mysql:8.0 | 本番環境との整合性 |
| nginx | nginx:alpine | 軽量、リバースプロキシに最適 |

### ネットワーク構成

```
┌─────────────────────────────────────────────────────────────┐
│                     Docker Network                          │
│                                                             │
│  ┌──────────┐    ┌──────────┐    ┌──────────┐             │
│  │ frontend │    │ backend  │    │    db    │             │
│  │  :5173   │    │  :8000   │    │  :3306   │             │
│  └────┬─────┘    └────┬─────┘    └────┬─────┘             │
│       │               │               │                    │
│       └───────────────┼───────────────┘                    │
│                       │                                    │
│                 ┌─────┴─────┐                              │
│                 │   nginx   │                              │
│                 │    :80    │                              │
│                 └─────┬─────┘                              │
│                       │                                    │
└───────────────────────┼────────────────────────────────────┘
                        │
                   Host :80
```

---

## 依存関係

### 前提条件

- Docker Desktop または Docker Engine がインストール済み
- Docker Compose v2 が利用可能
- Git でリポジトリをクローン済み

### 後続タスク

| Epic ID | Epic 名 | 関係 |
|---------|---------|------|
| EPIC-002 | バックエンド初期設定 | 本 Epic の後に実施 |
| EPIC-003 | フロントエンド初期設定 | 本 Epic の後に実施 |

---

## リスクと対策

| リスク | 影響 | 対策 |
|--------|------|------|
| ポート競合 | 起動失敗 | .env でポート変更可能に |
| イメージビルド失敗 | 開発開始遅延 | マルチステージビルドで依存を明確化 |
| ボリューム権限問題 | ファイル編集不可 | 適切なUID/GID設定 |

---

## 変更履歴

| 日付 | 変更内容 |
|------|---------|
| 2025-12-23 | 初版作成 |
