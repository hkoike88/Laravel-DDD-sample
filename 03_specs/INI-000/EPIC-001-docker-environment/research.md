# Research: Docker 環境構築

**Date**: 2025-12-23
**Feature**: 002-docker-environment

## 1. Docker Compose ヘルスチェック設定

### Decision
`healthcheck` ディレクティブと `depends_on: condition: service_healthy` を組み合わせて使用する。

### Rationale
- MySQL が接続受付可能になるまでバックエンドの起動を待機できる
- Docker Compose v2 で標準サポートされている
- アプリケーション側のリトライロジック不要でシンプル

### Alternatives Considered
- **depends_on のみ**: 起動順序のみ保証、準備完了は待機しない → 接続エラーのリスク
- **wait-for-it スクリプト**: 追加依存が必要、メンテナンスコスト増加
- **アプリ側リトライ**: 各アプリで実装必要、開発環境には過剰

### Implementation Pattern
```yaml
services:
  db:
    healthcheck:
      test: ["CMD", "mysqladmin", "ping", "-h", "localhost"]
      interval: 10s
      timeout: 5s
      retries: 5
      start_period: 30s

  backend:
    depends_on:
      db:
        condition: service_healthy
```

---

## 2. 環境変数管理

### Decision
`.env.example` にデフォルト値を記載し、`.env` は `.gitignore` で除外する。

### Rationale
- 認証情報がリポジトリにコミットされるリスクを回避
- 開発者ごとにポート設定をカスタマイズ可能
- Docker Compose の標準的なプラクティス

### Alternatives Considered
- **docker-compose.yml に直接記載**: シンプルだが認証情報が露出
- **Docker secrets**: 開発環境には過剰な複雑さ
- **外部シークレット管理**: 本番向け、開発環境には不適切

### Implementation Pattern
```bash
# .env.example
FRONTEND_PORT=5173
BACKEND_PORT=8000
DB_PORT=3306
PHPMYADMIN_PORT=8080
NGINX_PORT=80

DB_DATABASE=library
DB_USERNAME=library
DB_PASSWORD=secret
DB_ROOT_PASSWORD=secret
```

---

## 3. ボリュームマウント戦略

### Decision
- データベース: Named Volume (`db_data`) で永続化
- ソースコード: Bind Mount でホストとコンテナを同期

### Rationale
- Named Volume は Docker が管理し、パフォーマンスと永続性が高い
- Bind Mount はホットリロードに必要
- node_modules は匿名ボリュームで分離（パフォーマンス向上）

### Alternatives Considered
- **全て Bind Mount**: DB データのパフォーマンス低下
- **全て Named Volume**: ホットリロード不可
- **tmpfs**: 永続化不可、開発には不適切

### Implementation Pattern
```yaml
services:
  frontend:
    volumes:
      - ./frontend:/app           # ソースコード同期
      - /app/node_modules         # 匿名ボリューム（ホストと分離）

  db:
    volumes:
      - db_data:/var/lib/mysql    # Named Volume

volumes:
  db_data:
```

---

## 4. Nginx リバースプロキシ設定

### Decision
- `/api/*` → backend:8000
- `/*` → frontend:5173
- WebSocket 対応（Vite HMR）

### Rationale
- 単一エントリーポイントで本番環境に近い構成
- CORS 問題を回避
- HMR が正常に動作

### Alternatives Considered
- **直接ポートアクセス**: CORS 設定が複雑化
- **Traefik**: 開発環境には過剰
- **Nginx なし**: 本番との乖離が大きい

### Implementation Pattern
```nginx
upstream frontend {
    server frontend:5173;
}

upstream backend {
    server backend:8000;
}

server {
    listen 80;

    location /api {
        proxy_pass http://backend;
    }

    location / {
        proxy_pass http://frontend;
        # WebSocket 対応
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
    }
}
```

---

## 5. コンテナユーザー権限

### Decision
コンテナ内プロセスは root ユーザーで実行する。

### Rationale
- 開発環境のシンプルさを優先
- ファイル権限問題を回避
- 本番環境では別途非 root ユーザーを設定

### Alternatives Considered
- **ホストと同じ UID/GID**: 設定が複雑、OS 依存
- **固定 UID (1000)**: 一部ホストで権限問題
- **Docker Desktop の自動調整**: Windows/Mac のみ有効

---

## 6. サービス構成

### Decision
5つのサービスを定義:
1. **frontend**: React + Vite 開発サーバー (port 5173)
2. **backend**: Laravel + PHP-FPM (port 8000)
3. **db**: MySQL 8.0 (port 3306)
4. **phpmyadmin**: データベース管理 (port 8080)
5. **nginx**: リバースプロキシ (port 80)

### Rationale
- フロントエンドとバックエンドの分離で本番に近い構成
- phpMyAdmin で開発中のデータ確認が容易
- Nginx で統一的なアクセスポイントを提供

### Dependencies
```
nginx → frontend, backend
backend → db
phpmyadmin → db
frontend → (none, but typically calls backend)
```

---

## Summary

すべての技術的な決定事項が確定しました。以下の方針で実装を進めます:

| 項目 | 決定 |
|------|------|
| ヘルスチェック | healthcheck + service_healthy |
| 環境変数 | .env.example + .gitignore |
| ボリューム | DB: Named, ソース: Bind |
| プロキシ | Nginx + WebSocket 対応 |
| ユーザー権限 | root（開発環境） |
| サービス数 | 5 (frontend, backend, db, phpmyadmin, nginx) |
