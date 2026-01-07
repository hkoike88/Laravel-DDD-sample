# テストケース仕様書: 蔵書登録API

## 概要

POST `/api/books` エンドポイントのテストケース仕様書。

## ファイル構成

| ファイル | 内容 | テストケース数 |
|---------|------|---------------|
| [normal-cases.md](./normal-cases.md) | 正常系テストケース | 9件 |
| [error-cases.md](./error-cases.md) | 異常系テストケース | 9件 |
| [edge-cases.md](./edge-cases.md) | エッジケーステストケース | 15件 |

**合計: 33件**

---

## テストケースカテゴリ

### 正常系 (Normal Cases)

基本的な成功パターンのテスト。

- TC-N001: タイトルのみで蔵書登録
- TC-N002: 全項目入力で蔵書登録
- TC-N003: ISBN-10形式で蔵書登録
- TC-N004: ISBN-13形式（ハイフン付き）で蔵書登録
- TC-N005: レスポンスにIDが含まれる
- TC-N006: レスポンスに全項目が含まれる
- TC-N007: ステータスが「available」で返る
- TC-N008: 登録後に検索APIで発見可能
- TC-N009: リポジトリのsaveメソッドが呼ばれる

### 異常系 (Error Cases)

バリデーションエラーのテスト。

- TC-E001: タイトル未入力
- TC-E002: 空白のみのタイトル
- TC-E003: 不正なISBN形式
- TC-E004: 出版年に非数値
- TC-E005: タイトル文字数超過（501文字）
- TC-E006: 出版年が0以下
- TC-E007: 出版年が未来すぎる（現在年+6以上）
- TC-E008: 著者名文字数超過（201文字）
- TC-E009: ジャンル文字数超過（101文字）

### エッジケース (Edge Cases)

境界値・特殊条件のテスト。

**境界値テスト:**
- TC-B001〜B006: 各フィールドの最大文字数・最小/最大値

**ISBN形式テスト:**
- TC-I001〜I005: 各種ISBN形式の受け入れ

**複本対応テスト:**
- TC-D001〜D002: 同一ISBN/タイトルの複数登録

**ID生成テスト:**
- TC-U001: ULID形式のID生成

**特殊文字テスト:**
- TC-S001〜S004: 日本語・英語・記号・絵文字

---

## テスト実装ファイル

| ファイル | 種別 | テスト数 |
|---------|------|---------|
| `backend/tests/Feature/Book/CreateBookTest.php` | Feature | 20件 |
| `backend/tests/Unit/Domain/Book/UseCases/CreateBookHandlerTest.php` | Unit | 6件 |

**実装済み: 26件**

---

## テスト実行コマンド

```bash
# 全テスト実行
docker compose exec backend php artisan test

# 蔵書登録API関連のみ
docker compose exec backend php artisan test --filter=CreateBook

# Featureテストのみ
docker compose exec backend php artisan test --filter=CreateBookTest

# Unitテストのみ
docker compose exec backend php artisan test --filter=CreateBookHandlerTest
```

---

## バリデーションルールサマリー

| フィールド | 必須 | 型 | 最小 | 最大 | 備考 |
|-----------|-----|-----|-----|-----|------|
| title | ○ | string | 1文字 | 500文字 | 空白トリム |
| author | - | string | - | 200文字 | |
| isbn | - | string | - | - | ISBN-10/13形式 |
| publisher | - | string | - | 200文字 | |
| published_year | - | integer | 1 | 現在年+5 | |
| genre | - | string | - | 100文字 | |

---

## HTTPレスポンスコード

| コード | 状況 |
|-------|------|
| 201 Created | 登録成功 |
| 422 Unprocessable Entity | バリデーションエラー |
| 500 Internal Server Error | サーバーエラー |
