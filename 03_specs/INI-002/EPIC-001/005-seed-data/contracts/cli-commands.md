# CLI Commands Contract: シードデータ投入

**Date**: 2025-12-24
**Feature**: 005-seed-data

## コマンド一覧

| コマンド | 説明 | 優先度 |
|---------|------|--------|
| `php artisan db:seed --class=BookSeeder` | サンプルデータ投入 | P1 |
| `php artisan import:books` | CSVインポート | P2 |
| `php artisan book:generate` | ランダムデータ生成 | P3 |

---

## 1. BookSeeder（サンプルデータ投入）

### 実行方法

```bash
# BookSeederのみ実行
php artisan db:seed --class=BookSeeder

# DatabaseSeeder経由で実行（他のSeederと一緒に）
php artisan db:seed
```

### 動作仕様

| 項目 | 仕様 |
|------|------|
| 投入件数 | 100件以上 |
| 重複時動作 | ISBNが重複する場合はスキップ |
| 状態分布 | available: 40%, borrowed: 35%, reserved: 25% |
| 日本語対応 | タイトル・著者名は日本語 |

### 出力例

```text
Seeding: Database\Seeders\BookSeeder
  蔵書データを投入中...
  100件の蔵書データを投入しました。
Seeded:  Database\Seeders\BookSeeder (1.23s)
```

---

## 2. import:books（CSVインポート）

### シグネチャ

```text
import:books {file : CSVファイルパス}
              {--dry-run : バリデーションのみ実行}
              {--skip-duplicates : 重複ISBNをスキップ（デフォルト有効）}
              {--no-skip-duplicates : 重複ISBNでエラー}
```

### 実行例

```bash
# 基本的な使用方法
php artisan import:books storage/app/books.csv

# ドライラン（実際に投入せずバリデーションのみ）
php artisan import:books storage/app/books.csv --dry-run

# 重複時にエラーを出す
php artisan import:books storage/app/books.csv --no-skip-duplicates
```

### 動作仕様

| 項目 | 仕様 |
|------|------|
| ファイル形式 | UTF-8エンコードのCSV |
| ヘッダー行 | 必須（1行目） |
| 不正行処理 | スキップして継続 |
| 重複ISBN処理 | デフォルトでスキップ |
| バッチサイズ | 100件単位 |

### 入力バリデーション

| フィールド | ルール | エラーメッセージ例 |
|-----------|--------|------------------|
| title | 必須、255文字以内 | `タイトル未入力` |
| author | 255文字以内 | `著者名が長すぎます` |
| isbn | 13桁数字、チェックディジット | `ISBN形式エラー` |
| published_year | 1000〜現在年 | `無効な出版年` |
| status | available/borrowed/reserved | `無効な状態値` |

### 出力例（成功）

```text
=== 蔵書インポート結果 ===
処理ファイル: storage/app/books.csv
総行数: 100
成功: 100件
スキップ: 0件
```

### 出力例（一部エラー）

```text
=== 蔵書インポート結果 ===
処理ファイル: storage/app/books.csv
総行数: 105
成功: 100件
スキップ: 5件

--- スキップ詳細 ---
行 15: ISBN形式エラー (1234567890123)
行 23: タイトル未入力
行 45: 重複ISBN (9784003101018)
行 67: 無効な出版年 (3000)
行 89: 無効な状態値 (unknown)
```

### 終了コード

| コード | 意味 |
|--------|------|
| 0 | 成功（全件投入） |
| 0 | 成功（一部スキップあり） |
| 1 | エラー（ファイルが存在しない） |
| 1 | エラー（ファイル形式不正） |

---

## 3. book:generate（ランダムデータ生成）

### シグネチャ

```text
book:generate {count=100 : 生成件数}
              {--status= : 状態を指定（available/borrowed/reserved）}
```

### 実行例

```bash
# 100件生成（デフォルト）
php artisan book:generate

# 500件生成
php artisan book:generate 500

# 貸出可のデータのみ200件生成
php artisan book:generate 200 --status=available
```

### 動作仕様

| 項目 | 仕様 |
|------|------|
| デフォルト件数 | 100件 |
| 最大件数 | 10,000件 |
| ISBN生成 | Faker日本語ローカライズ使用 |
| 状態分布 | --status未指定時はランダム |

### 出力例

```text
蔵書データを生成中...
500件の蔵書データを生成しました。
  - available: 167件
  - borrowed: 166件
  - reserved: 167件
```

### 終了コード

| コード | 意味 |
|--------|------|
| 0 | 成功 |
| 1 | エラー（件数が範囲外） |
