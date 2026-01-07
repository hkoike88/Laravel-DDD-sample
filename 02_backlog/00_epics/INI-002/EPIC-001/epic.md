# EPIC-001: 蔵書検索

最終更新: 2025-12-24

---

## 概要

職員または利用者が、タイトル・著者名・ISBN等の条件で蔵書を検索し、所蔵情報を確認できる機能を構築する。
現状の Excel 検索（10分/件）をシステム検索（30秒以内）に置き換え、業務効率を大幅に向上させる。

---

## 関連情報

| 項目 | 値 |
|------|-----|
| イニシアチブ | [LIB-001: 図書館業務デジタル化 MVP](../../../../01_vision/initiatives/INI-001/charter.md) |
| Use Case | [UC-001-001: 蔵書検索](../../../../01_vision/initiatives/INI-001/usecases/UC-001-001_蔵書検索.md) |
| 優先度 | Must |
| ステータス | Draft |
| 対応課題 | ISS-001（蔵書検索に時間がかかる） |

---

## ビジネス価値

- 蔵書検索時間を 10分 → 30秒 に短縮（97%削減）
- 職員のストレス軽減
- 利用者の待ち時間短縮
- 既存 Excel データの仕様検証（アジャイル的アプローチ）

---

## 受け入れ条件

1. タイトル、著者名、ISBN で蔵書を検索できること
2. 検索結果が30秒以内に返ること
3. 検索結果に所蔵状態（貸出可/貸出中/予約あり）が表示されること
4. 部分一致検索ができること
5. 検索結果が最大100件まで表示されること
6. 検索結果が0件の場合、適切なメッセージが表示されること

---

## User Story 一覧

| ID | Story 名 | ポイント | 優先度 | ステータス |
|----|----------|---------|--------|----------|
| [ST-001](./stories/ST-001_蔵書エンティティ設計.md) | 蔵書エンティティ・Value Object の設計 | 3 | Must | Draft |
| [ST-002](./stories/ST-002_蔵書リポジトリ実装.md) | 蔵書リポジトリの実装 | 3 | Must | Draft |
| [ST-003](./stories/ST-003_蔵書検索API実装.md) | 蔵書検索 API の実装 | 5 | Must | Draft |
| [ST-004](./stories/ST-004_蔵書検索画面実装.md) | 蔵書検索画面の実装 | 5 | Must | Draft |
| [ST-005](./stories/ST-005_シードデータ投入.md) | シードデータ（Excel インポート）の投入 | 3 | Must | Draft |

---

## 成果物

| 成果物 | 配置場所 | 説明 |
|--------|---------|------|
| Book エンティティ | backend/packages/Domain/Book/ | 蔵書ドメインモデル |
| BookRepository | backend/packages/Infrastructure/ | 蔵書永続化 |
| SearchBooksUseCase | backend/packages/UseCase/ | 検索ユースケース |
| GET /api/books | backend/routes/api.php | 検索 API エンドポイント |
| BookSearchPage | frontend/src/features/books/ | 検索画面コンポーネント |
| books テーブル | database/migrations/ | 蔵書テーブル |

---

## 技術仕様

### API 設計

```
GET /api/books?title=xxx&author=xxx&isbn=xxx&page=1&per_page=20

Response:
{
  "data": [
    {
      "id": "uuid",
      "title": "書籍タイトル",
      "author": "著者名",
      "isbn": "978-xxx",
      "publisher": "出版社",
      "status": "available|borrowed|reserved"
    }
  ],
  "meta": {
    "total": 100,
    "page": 1,
    "per_page": 20
  }
}
```

### データベース設計

```sql
CREATE TABLE books (
    id CHAR(36) PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    author VARCHAR(255),
    isbn VARCHAR(13),
    publisher VARCHAR(255),
    published_year INT,
    genre VARCHAR(100),
    status ENUM('available', 'borrowed', 'reserved') DEFAULT 'available',
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    INDEX idx_title (title),
    INDEX idx_author (author),
    INDEX idx_isbn (isbn)
);
```

---

## 依存関係

### 前提条件

- EPIC-000（開発環境構築）が完了していること
- データベースが起動していること

### 後続タスク

| Epic ID | Epic 名 | 関係 |
|---------|---------|------|
| EPIC-002 | 蔵書登録 | 本 Epic でデータモデルを検証後に実施 |
| EPIC-003 | 貸出処理 | 蔵書データを使用 |

---

## リスクと対策

| リスク | 影響 | 対策 |
|--------|------|------|
| 検索パフォーマンス | UX 低下 | インデックス最適化、ページネーション |
| データ量増加 | 検索速度低下 | 全文検索エンジン（将来検討） |
| 仕様変更 | 手戻り | 既存データで検証してから登録機能を作成 |

---

## 変更履歴

| 日付 | 変更内容 |
|------|---------|
| 2025-12-24 | 初版作成 |
