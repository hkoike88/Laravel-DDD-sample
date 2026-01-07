# 正常系テストケース: 蔵書登録API

## 概要

POST `/api/books` エンドポイントの正常系テストケース一覧。
すべてのテストケースで HTTP 201 Created が返却されることを期待する。

---

## TC-N001: タイトルのみで蔵書登録

**目的**: 必須項目（タイトル）のみで蔵書が登録できること

**前提条件**: なし

**テストデータ**:
```json
{
  "title": "吾輩は猫である"
}
```

**期待結果**:
- ステータスコード: 201 Created
- レスポンス:
  ```json
  {
    "data": {
      "id": "<ULID形式26文字>",
      "title": "吾輩は猫である",
      "author": null,
      "isbn": null,
      "publisher": null,
      "published_year": null,
      "genre": null,
      "status": "available"
    }
  }
  ```
- データベースに該当レコードが存在する

**実装**: `CreateBookTest::test_can_create_book_with_title_only`

---

## TC-N002: 全項目入力で蔵書登録

**目的**: 全項目を入力して蔵書が登録できること

**前提条件**: なし

**テストデータ**:
```json
{
  "title": "吾輩は猫である",
  "author": "夏目漱石",
  "isbn": "978-4-00-310101-8",
  "publisher": "岩波書店",
  "published_year": 1905,
  "genre": "文学"
}
```

**期待結果**:
- ステータスコード: 201 Created
- レスポンス:
  ```json
  {
    "data": {
      "id": "<ULID形式26文字>",
      "title": "吾輩は猫である",
      "author": "夏目漱石",
      "isbn": "9784003101018",
      "publisher": "岩波書店",
      "published_year": 1905,
      "genre": "文学",
      "status": "available"
    }
  }
  ```
- ISBNはハイフンなしの正規化形式で保存される

**実装**: `CreateBookTest::test_can_create_book_with_all_fields`

---

## TC-N003: ISBN-10形式で蔵書登録

**目的**: ISBN-10形式で蔵書が登録できること

**前提条件**: なし

**テストデータ**:
```json
{
  "title": "坊っちゃん",
  "isbn": "4003101014"
}
```

**期待結果**:
- ステータスコード: 201 Created
- ISBNがそのまま保存される（ISBN-10は変換しない）

**実装**: `CreateBookTest::test_can_create_book_with_isbn`

---

## TC-N004: ISBN-13形式（ハイフン付き）で蔵書登録

**目的**: ハイフン付きISBN-13が正規化されて登録されること

**前提条件**: なし

**テストデータ**:
```json
{
  "title": "吾輩は猫である",
  "isbn": "978-4-00-310101-8"
}
```

**期待結果**:
- ステータスコード: 201 Created
- ISBNが `9784003101018` に正規化される

**実装**: `CreateBookHandlerTest::test_can_create_book_with_hyphenated_isbn13`

---

## TC-N005: レスポンスにIDが含まれる

**目的**: 登録完了レスポンスにULID形式のIDが含まれること

**前提条件**: なし

**テストデータ**:
```json
{
  "title": "吾輩は猫である"
}
```

**期待結果**:
- レスポンスの `data.id` が存在する
- IDは26文字のULID形式である

**実装**: `CreateBookTest::test_create_book_response_contains_id`

---

## TC-N006: レスポンスに全項目が含まれる

**目的**: 登録完了レスポンスに全項目が含まれること

**前提条件**: なし

**テストデータ**:
```json
{
  "title": "吾輩は猫である",
  "author": "夏目漱石",
  "isbn": "9784003101018",
  "publisher": "岩波書店",
  "published_year": 1905,
  "genre": "文学"
}
```

**期待結果**:
- レスポンス構造:
  ```json
  {
    "data": {
      "id": "...",
      "title": "...",
      "author": "...",
      "isbn": "...",
      "publisher": "...",
      "published_year": "...",
      "genre": "...",
      "status": "..."
    }
  }
  ```

**実装**: `CreateBookTest::test_create_book_response_contains_all_input_fields`

---

## TC-N007: ステータスが「available」で返る

**目的**: 新規登録時のステータスが「available」（貸出可能）であること

**前提条件**: なし

**テストデータ**:
```json
{
  "title": "吾輩は猫である"
}
```

**期待結果**:
- `data.status` が `"available"` である

**実装**: `CreateBookTest::test_create_book_response_status_is_available`

---

## TC-N008: 登録後に検索APIで発見可能

**目的**: 登録した蔵書が検索APIで発見できること

**前提条件**: なし

**テストデータ**:
```json
{
  "title": "吾輩は猫である",
  "author": "夏目漱石"
}
```

**手順**:
1. 上記データでPOST /api/books を実行
2. GET /api/books?title=猫 を実行

**期待結果**:
- 検索結果に登録した蔵書が含まれる
- IDが一致する

**実装**: `CreateBookTest::test_created_book_is_searchable`

---

## TC-N009: リポジトリのsaveメソッドが呼ばれる

**目的**: ユースケース実行時にリポジトリが正しく呼び出されること

**前提条件**: BookRepositoryInterfaceがモック化されている

**テストデータ**:
```php
new CreateBookCommand(title: '吾輩は猫である')
```

**期待結果**:
- `BookRepositoryInterface::save()` が1回呼び出される
- 引数に `Book` インスタンスが渡される

**実装**: `CreateBookHandlerTest::test_repository_save_is_called`

---

## 関連ファイル

- テスト実装: `backend/tests/Feature/Book/CreateBookTest.php`
- テスト実装: `backend/tests/Unit/Domain/Book/UseCases/CreateBookHandlerTest.php`
- 対象API: `POST /api/books`
