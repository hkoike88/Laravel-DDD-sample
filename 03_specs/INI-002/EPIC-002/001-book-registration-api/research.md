# Research: 蔵書登録API実装

**Date**: 2025-12-24
**Feature**: 001-book-registration-api

## 1. 既存アーキテクチャパターン

### Decision: CQRSパターンに従いCommandハンドラを実装

**Rationale**: 既存のSearchBooksクエリハンドラと同様の構造で、Commands/CreateBook配下にCreateBookCommand.phpとCreateBookHandler.phpを作成。一貫性を維持しつつ、コマンド（書き込み）とクエリ（読み取り）を分離。

**Alternatives considered**:
- 直接Controllerでリポジトリを呼び出す → DDDレイヤー違反、テスト困難
- Serviceクラスを使用 → 既存パターンと不一致

### 参考: 既存のQueryパターン
```
Application/UseCases/Queries/SearchBooks/
├── SearchBooksQuery.php      # 入力DTO（イミュータブル）
└── SearchBooksHandler.php    # ユースケース処理
```

### 適用: Commandパターン
```
Application/UseCases/Commands/CreateBook/
├── CreateBookCommand.php     # 入力DTO（タイトル必須、他オプション）
└── CreateBookHandler.php     # ユースケース処理（Book生成→保存→返却）
```

## 2. FormRequest バリデーション

### Decision: Laravel FormRequestでバリデーション実装

**Rationale**: 既存のSearchBooksRequestと同様に、CreateBookRequest.phpでバリデーションルールと日本語エラーメッセージを定義。Laravelの標準機能を活用し、422レスポンスを自動生成。

**Alternatives considered**:
- ドメイン層でバリデーション → 既にBook::create()で基本バリデーションあり、二重になる
- カスタムバリデータ → 過剰な複雑性

### バリデーションルール設計

| フィールド | ルール | エラーメッセージ |
|-----------|--------|----------------|
| title | required, string, max:500 | タイトルは必須です / 500文字以内 |
| author | nullable, string, max:200 | 200文字以内 |
| isbn | nullable, string, regex:ISBN形式 | ISBNの形式が正しくありません |
| publisher | nullable, string, max:200 | 200文字以内 |
| published_year | nullable, integer, min:1, max:現在年+5 | 出版年の範囲エラー |
| genre | nullable, string, max:100 | 100文字以内 |

## 3. ISBN バリデーション

### Decision: 既存のISBN ValueObjectを活用

**Rationale**: `Domain/ValueObjects/ISBN.php`が既にISBN-10/13の形式検証を実装済み。FormRequestでは正規表現で形式チェックし、ドメイン層で詳細検証（チェックディジット等）を行う二段階バリデーション。

**Alternatives considered**:
- FormRequestのみでチェックディジット検証 → ドメイン知識がプレゼンテーション層に漏れる
- 外部ライブラリ使用 → 既存実装あり、不要

### ISBN形式正規表現
```php
// ISBN-10: 10桁（最後はXまたは数字）
// ISBN-13: 13桁（978または979で始まる）
// ハイフン区切り対応
'regex:/^(?:ISBN[-]?)?(97[89][-]?\d{1,5}[-]?\d{1,7}[-]?\d{1,7}[-]?\d|(?:\d{9}[\dX]))$/i'
```

## 4. レスポンス形式

### Decision: 既存BookResourceを再利用、201 Createdで返却

**Rationale**: 検索APIと同じレスポンス形式を使用し、クライアント側の処理を統一。登録成功時は201 Createdステータスで、生成されたBookIdを含む完全なBookリソースを返却。

**レスポンス例**:
```json
{
  "data": {
    "id": "01JFXXX...",
    "title": "吾輩は猫である",
    "author": "夏目漱石",
    "isbn": "978-4-00-310101-8",
    "publisher": "岩波書店",
    "published_year": 1905,
    "genre": "文学",
    "status": "available"
  }
}
```

## 5. エラーハンドリング

### Decision: Laravelのデフォルト例外ハンドリング + ドメイン例外変換

**Rationale**:
- FormRequest違反 → 自動的に422レスポンス
- ドメイン例外（EmptyBookTitleException, InvalidISBNException）→ 422レスポンスに変換
- その他のエラー → 500レスポンス

**例外マッピング**:
| ドメイン例外 | HTTPステータス | メッセージ |
|-------------|---------------|-----------|
| EmptyBookTitleException | 422 | タイトルは必須です |
| InvalidISBNException | 422 | ISBNの形式が正しくありません |

## 6. テスト戦略

### Decision: Feature + Unitテストの二層構造

**Rationale**:
- **Featureテスト**: HTTPレベルのE2Eテスト（リクエスト→レスポンス）
- **Unitテスト**: CreateBookHandlerの単体テスト（モック使用）

**テストケース**:
1. 正常系: タイトルのみで登録成功（201）
2. 正常系: 全項目入力で登録成功（201）
3. 異常系: タイトル未入力（422）
4. 異常系: 不正なISBN形式（422）
5. 異常系: タイトル文字数超過（422）
6. 異常系: 出版年範囲外（422）

## 7. ルーティング

### Decision: 既存ルート定義に追加

**Rationale**: `Presentation/routes.php`に既存の`GET /api/books`があり、同ファイルに`POST /api/books`を追加。RESTful設計に準拠。

```php
Route::post('/books', [BookController::class, 'store']);
```

## Summary

全ての技術的決定が既存のアーキテクチャパターンに基づいており、NEEDS CLARIFICATIONはありません。Phase 1（Design & Contracts）に進む準備が整いました。
