# ST-002: バックエンド→DB 接続確認

最終更新: 2025-12-23

---

## ストーリー

**開発者として**、バックエンドからデータベースに正常に接続できることを確認したい。
**なぜなら**、データの永続化が正しく行えることを保証したいからだ。

---

## 関連情報

| 項目 | 値 |
|------|-----|
| Epic | [EPIC-004: 開発環境動作確認](../epic.md) |
| ポイント | 1 |
| 優先度 | Must |
| ステータス | Draft |

---

## 受け入れ条件

1. [ ] `php artisan db:show` でデータベース情報が表示されること
2. [ ] `php artisan migrate` が成功すること
3. [ ] `php artisan migrate:status` でマイグレーション状態が確認できること
4. [ ] ヘルスチェック API でデータベース接続が成功すること
5. [ ] phpMyAdmin でテーブルが確認できること

---

## 確認手順

### 1. コンテナに入る

```bash
# バックエンドコンテナに入る
docker compose exec backend bash
```

### 2. データベース接続確認

```bash
# データベース情報表示
php artisan db:show

# 期待される出力
# Database: library
# Host: db
# Port: 3306
# Username: library
# Connection: mysql
```

### 3. マイグレーション実行

```bash
# マイグレーション実行
php artisan migrate

# 期待される出力
# Migration table created successfully.
# Running migrations...
# 2024_xx_xx_000000_create_users_table ... done
# 2024_xx_xx_000001_create_cache_table ... done
# ...

# マイグレーション状態確認
php artisan migrate:status
```

### 4. ヘルスチェック API 確認

```bash
# コンテナ外から実行
curl http://localhost:8000/api/health

# 期待される出力
{
    "status": "ok",
    "database": "connected",
    "timestamp": "2025-12-23T10:00:00+09:00"
}
```

### 5. phpMyAdmin で確認

1. http://localhost:8080 にアクセス
2. 左メニューから `library` データベースを選択
3. テーブル一覧に `users`, `migrations` などが表示されることを確認

---

## 確認チェックリスト

| 確認項目 | コマンド/方法 | 確認 |
|----------|--------------|------|
| DB 接続 | `php artisan db:show` | [ ] |
| マイグレーション | `php artisan migrate` | [ ] |
| ヘルスチェック | `curl /api/health` | [ ] |
| phpMyAdmin | ブラウザでテーブル確認 | [ ] |

---

## トラブルシューティング

| 問題 | 原因 | 解決策 |
|------|------|--------|
| SQLSTATE[HY000] [2002] Connection refused | DB コンテナ未起動 | `docker compose up -d db` |
| SQLSTATE[HY000] [2002] No such file | ホスト名誤り | DB_HOST=db（コンテナ名）に修正 |
| Access denied for user | 認証情報誤り | .env の DB_USERNAME/PASSWORD 確認 |
| Unknown database 'library' | DB 未作成 | DB コンテナを再作成 |

### DB コンテナの再作成

```bash
# ボリュームを含めて削除
docker compose down -v

# 再起動（DB が自動作成される）
docker compose up -d
```

---

## タスク

### Spec Tasks（詳細設計）

- [ ] php artisan db:show の実行
- [ ] php artisan migrate の実行
- [ ] ヘルスチェック API の確認
- [ ] phpMyAdmin でのテーブル確認

---

## 変更履歴

| 日付 | 変更内容 |
|------|---------|
| 2025-12-23 | 初版作成 |
