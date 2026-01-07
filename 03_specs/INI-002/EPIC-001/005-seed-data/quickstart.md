# Quickstart: シードデータ投入

**Date**: 2025-12-24
**Feature**: 005-seed-data

## 前提条件

- Docker環境が起動していること
- マイグレーションが完了していること（`php artisan migrate`）

## クイックスタート

### 1. サンプルデータ投入（P1）

```bash
# Dockerコンテナに入る
docker compose exec app bash

# サンプルデータを投入
php artisan db:seed --class=BookSeeder
```

期待される結果:
- 100件以上の蔵書データがデータベースに投入される
- 蔵書検索機能で投入データが検索可能になる

### 2. CSVインポート（P2）

```bash
# CSVファイルを用意
# storage/app/books.csv に配置

# インポート実行
php artisan import:books storage/app/books.csv

# ドライランで事前確認
php artisan import:books storage/app/books.csv --dry-run
```

### 3. ランダムデータ生成（P3）

```bash
# 100件生成
php artisan book:generate

# 500件生成
php artisan book:generate 500
```

## 検証方法

### データ投入確認

```bash
# MySQL接続
docker compose exec db mysql -u library -plibrary library_db

# 件数確認
SELECT COUNT(*) FROM books;

# 状態別件数確認
SELECT status, COUNT(*) FROM books GROUP BY status;

# サンプルデータ確認
SELECT * FROM books LIMIT 10;
```

### 蔵書検索APIで確認

```bash
# 全件検索
curl http://localhost/api/books

# タイトル検索
curl "http://localhost/api/books?title=猫"

# 著者検索
curl "http://localhost/api/books?author=夏目"
```

## トラブルシューティング

### CSVインポートエラー

**問題**: `ファイルが見つかりません`
**解決**: ファイルパスが正しいか確認。`storage/app/` からの相対パスを使用。

**問題**: `エンコーディングエラー`
**解決**: CSVファイルをUTF-8で保存し直す。

### 重複エラー

**問題**: `重複ISBN`でスキップされる
**解決**: 既存データを削除するか、異なるISBNのデータを使用。

```bash
# 既存データを全削除（注意）
php artisan tinker --execute="DB::table('books')->truncate();"
```

## 関連ドキュメント

- [仕様書](./spec.md)
- [実装計画](./plan.md)
- [データモデル](./data-model.md)
- [CLIコマンド仕様](./contracts/cli-commands.md)
