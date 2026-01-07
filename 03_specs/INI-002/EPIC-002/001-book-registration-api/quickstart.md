# Quickstart: 蔵書登録API実装

**Feature**: 001-book-registration-api
**Date**: 2025-12-24

## 概要

図書館職員が新規図書をシステムに登録するためのREST APIエンドポイント（POST /api/books）を実装する。

## 前提条件

- Docker環境が起動していること
- `docker compose up -d` でコンテナが稼働中
- 既存のBookエンティティ・リポジトリが実装済み（EPIC-001完了）

## クイックテスト

### 1. 開発サーバー起動確認

```bash
# バックエンドコンテナに接続
docker compose exec backend bash

# マイグレーション確認
php artisan migrate:status
```

### 2. API動作確認（実装後）

```bash
# タイトルのみで登録
curl -X POST http://localhost:8080/api/books \
  -H "Content-Type: application/json" \
  -d '{"title": "吾輩は猫である"}'

# 期待レスポンス: 201 Created
# {
#   "data": {
#     "id": "01JFXXX...",
#     "title": "吾輩は猫である",
#     "status": "available"
#   }
# }

# 全項目入力で登録
curl -X POST http://localhost:8080/api/books \
  -H "Content-Type: application/json" \
  -d '{
    "title": "吾輩は猫である",
    "author": "夏目漱石",
    "isbn": "978-4-00-310101-8",
    "publisher": "岩波書店",
    "published_year": 1905,
    "genre": "文学"
  }'

# バリデーションエラー確認（タイトル未入力）
curl -X POST http://localhost:8080/api/books \
  -H "Content-Type: application/json" \
  -d '{}'

# 期待レスポンス: 422 Unprocessable Entity
# {
#   "message": "The given data was invalid.",
#   "errors": {
#     "title": ["タイトルは必須です"]
#   }
# }
```

### 3. テスト実行

```bash
# Featureテスト実行
docker compose exec backend php artisan test --filter=CreateBookTest

# 全テスト実行
docker compose exec backend php artisan test
```

## 実装ファイル一覧

### 新規作成

| ファイル | 説明 |
|---------|------|
| `Application/UseCases/Commands/CreateBook/CreateBookCommand.php` | 入力DTO |
| `Application/UseCases/Commands/CreateBook/CreateBookHandler.php` | ユースケースハンドラ |
| `Presentation/HTTP/Requests/CreateBookRequest.php` | FormRequest |
| `tests/Feature/Book/CreateBookTest.php` | Featureテスト |
| `tests/Unit/Domain/Book/UseCases/CreateBookHandlerTest.php` | Unitテスト |

### 既存ファイル修正

| ファイル | 変更内容 |
|---------|---------|
| `Presentation/HTTP/Controllers/BookController.php` | `store()`メソッド追加 |
| `Presentation/routes.php` | `POST /books`ルート追加 |
| `Application/Providers/BookServiceProvider.php` | CreateBookHandler DI登録 |

## 主要なAPIエンドポイント

| メソッド | パス | 説明 | ステータス |
|---------|------|------|-----------|
| POST | /api/books | 蔵書登録 | 201/422/500 |

## バリデーションルール

| フィールド | ルール |
|-----------|--------|
| title | 必須、1-500文字 |
| author | 任意、最大200文字 |
| isbn | 任意、ISBN-10/13形式 |
| publisher | 任意、最大200文字 |
| published_year | 任意、1〜現在年+5 |
| genre | 任意、最大100文字 |

## トラブルシューティング

### 422エラーが返らない

```bash
# バリデーションルールを確認
docker compose exec backend php artisan route:list --path=books
```

### ISBNバリデーションエラー

正しいISBN形式:
- ISBN-10: `4003101014`
- ISBN-13: `9784003101018`
- ハイフン付き: `978-4-00-310101-8`

### テストが失敗する

```bash
# テストデータベースをリフレッシュ
docker compose exec backend php artisan migrate:fresh --env=testing
```
