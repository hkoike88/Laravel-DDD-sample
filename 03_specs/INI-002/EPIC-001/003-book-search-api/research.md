# Research: 蔵書検索API

**Feature**: 003-book-search-api
**Date**: 2025-12-24

## 調査サマリー

本フィーチャーの実装に必要な技術調査結果をまとめる。既存のBookドメインモデルとリポジトリが実装済みのため、追加で必要な実装と設計判断を整理。

---

## 1. 既存実装の確認

### Decision: 既存のドメインモデルとリポジトリを活用

**Rationale**:
- `Book` エンティティ、`BookRepositoryInterface`、`EloquentBookRepository` が既に実装済み
- `BookSearchCriteria` DTOと `BookCollection` DTOも実装済み
- `search()` メソッドが既にリポジトリインターフェースに定義されている

**Alternatives considered**:
- 新規に検索専用リポジトリを作成 → 不要（既存で十分）
- CQRSで読み取り専用モデルを作成 → オーバーエンジニアリング（小規模システム）

---

## 2. ISBN検索条件の追加

### Decision: `BookSearchCriteria` にISBN検索条件を追加

**Rationale**:
- 現在の `BookSearchCriteria` にはISBN検索条件がない
- ISBNは完全一致検索（仕様書FR-003）
- ISBN-10/ISBN-13両対応（仕様書Clarifications）

**Implementation**:
```php
// BookSearchCriteria.php への追加
public ?string $isbn = null,  // ISBN（完全一致）
```

**Alternatives considered**:
- リポジトリの `findByIsbn()` を直接使用 → 統一的な検索インターフェースを維持するため不採用

---

## 3. Presentation層の設計

### Decision: Laravel API Resource パターンを採用

**Rationale**:
- Laravel標準のAPI Resource（`JsonResource`）を使用
- レスポンス形式の一貫性を保証
- ページネーション情報の構造化が容易

**Response Structure**:
```json
{
  "data": [...],
  "meta": {
    "total": 100,
    "page": 1,
    "per_page": 20,
    "last_page": 5
  }
}
```

**Alternatives considered**:
- カスタムレスポンスクラス → Laravel標準で十分
- Fractal Transformer → 依存関係追加不要

---

## 4. UseCase層の設計

### Decision: Query/Handler パターンを採用

**Rationale**:
- プロジェクトのDDDアーキテクチャ標準に準拠（03_ディレクトリ構成例.md）
- 検索は読み取り操作のためQueryカテゴリ
- テスタビリティの向上

**Structure**:
```
UseCases/
└── Queries/
    └── SearchBooks/
        ├── SearchBooksQuery.php    # 入力DTO
        └── SearchBooksHandler.php  # ビジネスロジック
```

**Alternatives considered**:
- コントローラに直接ロジック実装 → DDDアーキテクチャ違反
- Serviceクラス → Query/Handlerパターンがより明確

---

## 5. バリデーション戦略

### Decision: FormRequest + DTO変換

**Rationale**:
- LaravelのFormRequestでHTTPレベルのバリデーション
- `BookSearchCriteria` DTOへの変換でドメインレベルの制約を適用

**Validation Rules**:
| パラメータ | ルール |
|-----------|--------|
| title | nullable, string, max:255 |
| author | nullable, string, max:255 |
| isbn | nullable, string, regex(ISBN-10/13形式) |
| page | nullable, integer, min:1 |
| per_page | nullable, integer, min:1, max:100 |

**Alternatives considered**:
- コントローラ内バリデーション → 再利用性低下
- DTO内のみでバリデーション → HTTPエラーレスポンスの制御が困難

---

## 6. エラーハンドリング

### Decision: Laravel標準のバリデーションエラー + カスタム例外

**Rationale**:
- バリデーションエラー: 422 Unprocessable Entity（Laravel標準）
- ドメイン例外: 適切なHTTPステータスコードにマッピング

**Error Response Format**:
```json
{
  "message": "バリデーションエラー",
  "errors": {
    "isbn": ["ISBNの形式が不正です"]
  }
}
```

**Alternatives considered**:
- Problem Details (RFC 7807) → シンプルなAPIには過剰

---

## 7. パフォーマンス考慮

### Decision: データベースインデックスの活用

**Rationale**:
- title, author: LIKE検索用のインデックス（prefix index推奨）
- isbn: 完全一致用の通常インデックス
- 1000件程度であれば特別な最適化は不要

**Query Optimization**:
- COUNT クエリとデータ取得クエリを分離（既存実装で対応済み）
- N+1問題なし（単一テーブルクエリ）

**Alternatives considered**:
- 全文検索（Elasticsearch）→ 小規模システムには過剰
- クエリキャッシュ → スコープ外（仕様書Scope Exclusions）

---

## 8. テスト戦略

### Decision: Feature Test（HTTP統合テスト）を中心に

**Rationale**:
- API エンドポイントの動作検証が主目的
- 既存のドメインモデルは別途テスト済み
- Pest を使用（プロジェクト標準）

**Test Cases**:
1. タイトル検索（部分一致）
2. 著者検索（部分一致）
3. ISBN検索（完全一致、ISBN-10/13）
4. 複合条件検索（AND条件）
5. ページネーション
6. 検索結果0件
7. バリデーションエラー
8. パラメータなし（全件取得）

**Alternatives considered**:
- Unit Test のみ → API レベルの検証が不十分
- E2E Test → 本フィーチャーではオーバーキル

---

## 未解決事項

なし。すべての技術的判断が完了。
