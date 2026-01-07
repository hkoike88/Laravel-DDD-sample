# Quickstart: 開発環境動作確認

**Feature**: [spec.md](./spec.md)
**Date**: 2025-12-24

## 前提条件

- Docker Desktop または Docker Engine がインストールされていること
- ポート 80, 3306, 5173, 8080 が使用可能であること
- EPIC-001, EPIC-002, EPIC-003 が完了していること

## 1. 環境起動

### 1.1 全サービスの起動

```bash
# プロジェクトルートで実行
docker compose up -d
```

### 1.2 起動状態の確認

```bash
docker compose ps
```

**期待される出力**:
```
NAME                    STATUS
sample-001-db           Running (healthy)
sample-001-frontend     Running
sample-001-backend      Running
sample-001-nginx        Running
sample-001-phpmyadmin   Running
```

## 2. エンドポイント確認

### 2.1 フロントエンド

ブラウザで http://localhost:5173 にアクセスし、React アプリケーションが表示されることを確認。

または:
```bash
curl -I http://localhost:5173
# HTTP/1.1 200 OK が返ること
```

### 2.2 バックエンド API

```bash
# ヘルスチェック
curl http://localhost:80/api/health

# 期待される出力:
# {"status":"ok","timestamp":"...","laravel_version":"12.0.0"}
```

### 2.3 データベース接続

```bash
# DB ヘルスチェック
curl http://localhost:80/api/health/db

# 期待される出力:
# {"status":"ok","connection":"mysql","database":"library"}
```

### 2.4 phpMyAdmin

ブラウザで http://localhost:8080 にアクセスし、phpMyAdmin ログイン画面が表示されることを確認。

- ユーザー名: `library`
- パスワード: `secret`

## 3. マイグレーション確認

```bash
# マイグレーション状態確認
docker compose exec backend php artisan migrate:status

# マイグレーション実行（必要な場合）
docker compose exec backend php artisan migrate
```

## 4. テスト実行

### 4.1 バックエンドテスト

```bash
# Pest テスト
docker compose exec backend php artisan test

# PHPStan 静的解析
docker compose exec backend ./vendor/bin/phpstan analyse

# Pint フォーマットチェック
docker compose exec backend ./vendor/bin/pint --test
```

### 4.2 フロントエンドテスト

```bash
# TypeScript 型チェック
docker compose exec frontend npm run typecheck

# ESLint
docker compose exec frontend npm run lint

# Prettier チェック
docker compose exec frontend npm run format:check

# プロダクションビルド
docker compose exec frontend npm run build
```

## 5. CORS 確認

```bash
# プリフライトリクエスト確認
curl -X OPTIONS http://localhost:80/api/health \
  -H "Origin: http://localhost:5173" \
  -H "Access-Control-Request-Method: GET" \
  -v
```

## 6. トラブルシューティング

### コンテナが起動しない

```bash
# ログ確認
docker compose logs [サービス名]

# 例: バックエンドのログ
docker compose logs backend
```

### ポート競合

```bash
# 使用中のポートを確認
lsof -i :80
lsof -i :5173
lsof -i :8080
lsof -i :3306

# プロセスを終了
kill -9 [PID]
```

### データベース接続エラー

```bash
# DB コンテナの状態確認
docker compose logs db

# DB コンテナに直接接続
docker compose exec db mysql -ulibrary -psecret library
```

### 権限エラー（Laravel）

```bash
docker compose exec backend chmod -R 777 storage bootstrap/cache
```

## 7. 環境停止

```bash
# 停止のみ（データ保持）
docker compose stop

# 停止とコンテナ削除（データ保持）
docker compose down

# 停止とボリューム削除（データ削除）
docker compose down -v
```

## 成功基準チェックリスト

- [ ] `docker compose up -d` で全サービスが起動する
- [ ] `docker compose ps` で全サービスが Running 状態
- [ ] http://localhost:5173 でフロントエンドが表示される
- [ ] http://localhost:80/api/health で JSON レスポンスが返る
- [ ] http://localhost:80/api/health/db で DB 接続が確認できる
- [ ] http://localhost:8080 で phpMyAdmin が表示される
- [ ] マイグレーションが正常に完了する
- [ ] バックエンドテストがすべて成功する
- [ ] フロントエンドの型チェック・リント・ビルドが成功する
