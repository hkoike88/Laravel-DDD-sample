# Research: 開発環境動作確認

**Feature**: [spec.md](./spec.md)
**Date**: 2025-12-24
**Status**: Complete

## Overview

このドキュメントは、Docker Compose で構築した開発環境の動作確認に必要な技術的調査結果をまとめたものです。

## 1. 環境構成

### 1.1 Docker Compose サービス構成

| サービス | イメージ | ポート | 依存関係 |
|----------|----------|--------|----------|
| db | mysql:8.0 | 3306 | なし |
| frontend | カスタム (Vite+React) | 5173 | なし |
| backend | カスタム (PHP-FPM+Laravel) | 9000 (内部) | db |
| phpmyadmin | phpmyadmin:latest | 8080 | db |
| nginx | nginx:alpine | 80 | frontend, backend |

### 1.2 ネットワーク構成

- **ネットワーク名**: app-network (bridge)
- **サービス間通信**: Docker ネットワーク内で直接通信
- **外部公開ポート**:
  - http://localhost:80 - Nginx（リバースプロキシ）
  - http://localhost:5173 - フロントエンド直接アクセス
  - http://localhost:8080 - phpMyAdmin
  - localhost:3306 - MySQL 直接接続

## 2. アクセスエンドポイント

### 2.1 Nginx 経由のルーティング

```
http://localhost:80/
├── /api/*        → backend:9000 (FastCGI/PHP-FPM)
├── /*.php        → backend:9000 (FastCGI/PHP-FPM)
└── /*            → frontend:5173 (HTTP プロキシ)
```

### 2.2 直接アクセス

| URL | 説明 | 期待される応答 |
|-----|------|----------------|
| http://localhost:5173 | フロントエンド開発サーバー | React アプリ |
| http://localhost:80/api/health | バックエンドヘルスチェック | JSON レスポンス |
| http://localhost:80/api/health/db | DB 接続確認 | JSON レスポンス |
| http://localhost:8080 | phpMyAdmin | ログイン画面 |

## 3. ヘルスチェック API

### 3.1 基本ヘルスチェック

**エンドポイント**: `GET /api/health`

**レスポンス例**:
```json
{
  "status": "ok",
  "timestamp": "2025-12-24T12:00:00+09:00",
  "laravel_version": "12.0.0"
}
```

### 3.2 データベースヘルスチェック

**エンドポイント**: `GET /api/health/db`

**正常レスポンス例**:
```json
{
  "status": "ok",
  "connection": "mysql",
  "database": "library"
}
```

**エラーレスポンス例**:
```json
{
  "status": "error",
  "message": "Database connection failed"
}
```

## 4. CORS 設定

### 4.1 現在の設定

Laravel 12 では CORS ミドルウェアがフレームワークに統合されています。デフォルトでは `config/cors.php` は生成されず、すべてのオリジンからのリクエストを許可する寛容な設定が適用されます。

### 4.2 Nginx プロキシ経由の通信

Nginx がリバースプロキシとして動作するため、フロントエンド（localhost:80）からバックエンド API（localhost:80/api）への通信は同一オリジンとなり、CORS の問題は発生しません。

### 4.3 直接通信（開発時）

フロントエンド開発サーバー（localhost:5173）から直接バックエンド（localhost:80/api）にアクセスする場合、CORS が適用されます。Laravel のデフォルト設定で許可されています。

## 5. データベース設定

### 5.1 接続情報

| パラメータ | 値 |
|-----------|-----|
| ホスト | db（Docker 内） / localhost（外部） |
| ポート | 3306 |
| データベース名 | library |
| ユーザー名 | library |
| パスワード | secret |
| Root パスワード | secret |

### 5.2 ヘルスチェック

MySQL コンテナは以下のヘルスチェックを実行：
- コマンド: `mysqladmin ping -h localhost`
- 間隔: 10 秒
- タイムアウト: 5 秒
- リトライ: 5 回
- 開始待機: 30 秒

## 6. テストコマンド

### 6.1 バックエンド

| コマンド | 説明 |
|----------|------|
| `docker compose exec backend php artisan test` | Pest テスト実行 |
| `docker compose exec backend ./vendor/bin/phpstan analyse` | PHPStan 静的解析 |
| `docker compose exec backend ./vendor/bin/pint --test` | Pint フォーマットチェック |

### 6.2 フロントエンド

| コマンド | 説明 |
|----------|------|
| `docker compose exec frontend npm run lint` | ESLint 実行 |
| `docker compose exec frontend npm run typecheck` | TypeScript 型チェック |
| `docker compose exec frontend npm run build` | プロダクションビルド |
| `docker compose exec frontend npm run format:check` | Prettier チェック |

## 7. 既知の問題と解決策

### 7.1 ポート競合

**問題**: ポートが既に使用されている場合、コンテナ起動が失敗する。

**解決策**:
1. `lsof -i :PORT` でプロセスを確認
2. `kill -9 PID` でプロセスを終了
3. または `.env` でポート番号を変更

### 7.2 データベース起動遅延

**問題**: バックエンドがデータベースより先に起動しようとして接続エラーが発生する。

**解決策**: docker-compose.yml で `depends_on` と `healthcheck` を設定済み。バックエンドは DB の healthcheck が通過するまで待機する。

### 7.3 権限エラー

**問題**: ボリュームマウント時に権限の問題が発生することがある。

**解決策**:
- `chmod -R 777 storage bootstrap/cache`（開発環境のみ）
- Dockerfile 内で適切なユーザー設定

## 8. 検証手順

### 8.1 全サービス起動確認

```bash
# サービス起動
docker compose up -d

# ステータス確認
docker compose ps

# 全サービスが Running であることを確認
```

### 8.2 エンドポイント確認

```bash
# フロントエンド
curl -I http://localhost:5173

# バックエンド API
curl http://localhost:80/api/health

# DB 接続
curl http://localhost:80/api/health/db

# phpMyAdmin
curl -I http://localhost:8080
```

### 8.3 マイグレーション

```bash
docker compose exec backend php artisan migrate:status
docker compose exec backend php artisan migrate
```

## 9. 結論

既存の環境構成は以下の検証項目をサポートしています：

- ✅ 全サービスの起動と状態確認
- ✅ ヘルスチェック API（/api/health, /api/health/db）
- ✅ フロントエンド・バックエンド連携（Nginx プロキシ経由）
- ✅ データベース接続とマイグレーション
- ✅ テストコマンド（Pest, PHPStan, ESLint, TypeScript）
- ✅ CORS 設定（同一オリジン + クロスオリジン対応）

追加の実装は不要です。
