# 異常系テストケース: 蔵書登録API

## 概要

POST `/api/books` エンドポイントの異常系テストケース一覧。
バリデーションエラー時は HTTP 422 Unprocessable Entity が返却されることを期待する。

---

## TC-E001: タイトル未入力

**目的**: 必須項目（タイトル）が未入力の場合にエラーとなること

**前提条件**: なし

**テストデータ**:
```json
{}
```

**期待結果**:
- ステータスコード: 422 Unprocessable Entity
- レスポンス:
  ```json
  {
    "message": "タイトルは必須です",
    "errors": {
      "title": ["タイトルは必須です"]
    }
  }
  ```

**実装**: `CreateBookTest::test_create_book_without_title_returns_validation_error`

---

## TC-E002: 空白のみのタイトル

**目的**: 空白文字のみのタイトルが拒否されること

**前提条件**: なし

**テストデータ**:
```json
{
  "title": "   "
}
```

**期待結果**:
- ステータスコード: 422 Unprocessable Entity
- `errors.title` にエラーメッセージが含まれる
- 空白はトリムされ、空文字として扱われる

**実装**: `CreateBookTest::test_create_book_with_whitespace_only_title_returns_validation_error`

---

## TC-E003: 不正なISBN形式

**目的**: 無効なISBN形式が拒否されること

**前提条件**: なし

**テストデータ**:
```json
{
  "title": "吾輩は猫である",
  "isbn": "invalid-isbn"
}
```

**期待結果**:
- ステータスコード: 422 Unprocessable Entity
- レスポンス:
  ```json
  {
    "message": "ISBNの形式が正しくありません",
    "errors": {
      "isbn": ["ISBNの形式が正しくありません"]
    }
  }
  ```

**実装**: `CreateBookTest::test_create_book_with_invalid_isbn_returns_validation_error`

---

## TC-E004: 出版年に非数値

**目的**: 出版年に数値以外が入力された場合にエラーとなること

**前提条件**: なし

**テストデータ**:
```json
{
  "title": "吾輩は猫である",
  "published_year": "not-a-number"
}
```

**期待結果**:
- ステータスコード: 422 Unprocessable Entity
- `errors.published_year` にエラーメッセージが含まれる

**実装**: `CreateBookTest::test_create_book_with_non_numeric_published_year_returns_validation_error`

---

## TC-E005: タイトル文字数超過（501文字）

**目的**: タイトルが最大文字数（500文字）を超える場合にエラーとなること

**前提条件**: なし

**テストデータ**:
```json
{
  "title": "<501文字の文字列>"
}
```

**期待結果**:
- ステータスコード: 422 Unprocessable Entity
- レスポンス:
  ```json
  {
    "errors": {
      "title": ["タイトルは500文字以内で入力してください"]
    }
  }
  ```

**実装**: `CreateBookTest::test_create_book_with_title_exceeding_max_length_returns_validation_error`

---

## TC-E006: 出版年が0以下

**目的**: 出版年に0が入力された場合にエラーとなること

**前提条件**: なし

**テストデータ**:
```json
{
  "title": "吾輩は猫である",
  "published_year": 0
}
```

**期待結果**:
- ステータスコード: 422 Unprocessable Entity
- `errors.published_year` にエラーメッセージが含まれる

**実装**: `CreateBookTest::test_create_book_with_published_year_out_of_range_returns_validation_error`

---

## TC-E007: 出版年が未来すぎる（現在年+6以上）

**目的**: 出版年が許容範囲（現在年+5）を超える場合にエラーとなること

**前提条件**: なし

**テストデータ**:
```json
{
  "title": "未来の本",
  "published_year": 2031
}
```
※ 現在年が2025年の場合

**期待結果**:
- ステータスコード: 422 Unprocessable Entity
- `errors.published_year` にエラーメッセージが含まれる

**実装**: `CreateBookTest::test_create_book_with_published_year_out_of_range_returns_validation_error`

---

## TC-E008: 著者名文字数超過（201文字）

**目的**: 著者名が最大文字数（200文字）を超える場合にエラーとなること

**前提条件**: なし

**テストデータ**:
```json
{
  "title": "テスト書籍",
  "author": "<201文字の文字列>"
}
```

**期待結果**:
- ステータスコード: 422 Unprocessable Entity
- レスポンス:
  ```json
  {
    "errors": {
      "author": ["著者名は200文字以内で入力してください"]
    }
  }
  ```

**実装**: `CreateBookTest::test_create_book_with_author_exceeding_max_length_returns_validation_error`

---

## TC-E009: ジャンル文字数超過（101文字）

**目的**: ジャンルが最大文字数（100文字）を超える場合にエラーとなること

**前提条件**: なし

**テストデータ**:
```json
{
  "title": "テスト書籍",
  "genre": "<101文字の文字列>"
}
```

**期待結果**:
- ステータスコード: 422 Unprocessable Entity
- レスポンス:
  ```json
  {
    "errors": {
      "genre": ["ジャンルは100文字以内で入力してください"]
    }
  }
  ```

**実装**: `CreateBookTest::test_create_book_with_genre_exceeding_max_length_returns_validation_error`

---

## バリデーションルール一覧

| フィールド | ルール | エラーメッセージ |
|-----------|--------|-----------------|
| title | required | タイトルは必須です |
| title | string | タイトルは文字列で入力してください |
| title | min:1 | タイトルは1文字以上で入力してください |
| title | max:500 | タイトルは500文字以内で入力してください |
| author | nullable, string | 著者名は文字列で入力してください |
| author | max:200 | 著者名は200文字以内で入力してください |
| isbn | nullable, regex | ISBNの形式が正しくありません |
| publisher | nullable, string | 出版社名は文字列で入力してください |
| publisher | max:200 | 出版社名は200文字以内で入力してください |
| published_year | nullable, integer | 出版年は整数で入力してください |
| published_year | min:1 | 出版年は1年以上で入力してください |
| published_year | max:現在年+5 | 出版年は{max}年以内で入力してください |
| genre | nullable, string | ジャンルは文字列で入力してください |
| genre | max:100 | ジャンルは100文字以内で入力してください |

---

## 関連ファイル

- テスト実装: `backend/tests/Feature/Book/CreateBookTest.php`
- バリデーション: `backend/packages/Domain/Book/Presentation/HTTP/Requests/CreateBookRequest.php`
- 対象API: `POST /api/books`
