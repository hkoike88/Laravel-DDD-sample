# Quickstart: 蔵書検索API

**Feature**: 003-book-search-api
**Date**: 2025-12-24

## 概要

蔵書検索APIの実装クイックスタートガイド。このドキュメントでは、開発環境のセットアップから基本的なAPIの使用方法までを説明する。

---

## 前提条件

- Docker & Docker Compose がインストール済み
- Git リポジトリがクローン済み
- `003-book-search-api` ブランチにチェックアウト済み

---

## 1. 開発環境のセットアップ

### コンテナの起動

```bash
cd infrastructure
docker-compose up -d
```

### 依存関係のインストール

```bash
docker-compose exec app composer install
```

### データベースのマイグレーション

```bash
docker-compose exec app php artisan migrate
```

### テストデータの投入（オプション）

```bash
docker-compose exec app php artisan db:seed --class=BookSeeder
```

---

## 2. API エンドポイント

### 蔵書検索

```
GET /api/books
```

### リクエストパラメータ

| パラメータ | 型 | 必須 | 説明 |
|-----------|-----|------|------|
| title | string | No | タイトル（部分一致） |
| author | string | No | 著者名（部分一致） |
| isbn | string | No | ISBN（完全一致） |
| page | int | No | ページ番号（デフォルト: 1） |
| per_page | int | No | ページサイズ（デフォルト: 20、最大: 100） |

---

## 3. 使用例

### 全件取得（ページネーション付き）

```bash
curl -X GET "http://localhost:8000/api/books"
```

レスポンス:
```json
{
  "data": [
    {
      "id": "01HQXYZ123456789ABCDEFG",
      "title": "吾輩は猫である",
      "author": "夏目漱石",
      "isbn": "9784003101018",
      "publisher": "岩波書店",
      "published_year": 1905,
      "genre": "小説",
      "status": "available"
    }
  ],
  "meta": {
    "total": 100,
    "page": 1,
    "per_page": 20,
    "last_page": 5
  }
}
```

### タイトルで検索

```bash
curl -X GET "http://localhost:8000/api/books?title=猫"
```

### 著者名で検索

```bash
curl -X GET "http://localhost:8000/api/books?author=夏目"
```

### ISBNで検索

```bash
curl -X GET "http://localhost:8000/api/books?isbn=9784003101018"
```

### 複合条件検索

```bash
curl -X GET "http://localhost:8000/api/books?title=猫&author=夏目"
```

### ページネーション

```bash
curl -X GET "http://localhost:8000/api/books?page=2&per_page=50"
```

---

## 4. テストの実行

### 全テスト実行

```bash
docker-compose exec app php artisan test
```

### 蔵書検索APIのテストのみ

```bash
docker-compose exec app php artisan test --filter=SearchBooksTest
```

### カバレッジレポート付き

```bash
docker-compose exec app php artisan test --coverage
```

---

## 5. ディレクトリ構成

```
backend/packages/Domain/Book/
├── Domain/                         # ドメイン層（既存）
│   ├── Model/Book.php
│   ├── Repositories/BookRepositoryInterface.php
│   └── ValueObjects/
├── Application/                    # アプリケーション層
│   ├── DTO/
│   │   ├── BookSearchCriteria.php  # 既存（ISBN追加）
│   │   └── BookCollection.php
│   ├── Repositories/
│   │   └── EloquentBookRepository.php
│   ├── Providers/BookServiceProvider.php
│   └── UseCases/
│       └── Queries/
│           └── SearchBooks/        # 新規
│               ├── SearchBooksQuery.php
│               └── SearchBooksHandler.php
├── Presentation/                   # プレゼンテーション層（新規）
│   ├── routes.php
│   └── HTTP/
│       ├── Controllers/
│       │   └── BookController.php
│       ├── Requests/
│       │   └── SearchBooksRequest.php
│       └── Resources/
│           ├── BookResource.php
│           └── BookCollectionResource.php
└── Infrastructure/                 # インフラ層（既存）
    └── EloquentModels/BookRecord.php
```

---

## 6. トラブルシューティング

### 404 Not Found

- ルーティングが正しく登録されているか確認
- `php artisan route:list` でルート一覧を確認

### 500 Internal Server Error

- ログを確認: `storage/logs/laravel.log`
- データベース接続を確認

### バリデーションエラー（422）

- リクエストパラメータの形式を確認
- ISBN は10桁または13桁の数字のみ

---

## 7. 関連ドキュメント

- [仕様書](./spec.md)
- [実装計画](./plan.md)
- [データモデル](./data-model.md)
- [API仕様（OpenAPI）](./contracts/openapi.yaml)
