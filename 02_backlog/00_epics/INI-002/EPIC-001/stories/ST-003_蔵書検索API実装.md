# ST-003: 蔵書検索 API の実装

最終更新: 2025-12-24

---

## ストーリー

**フロントエンド開発者として**、蔵書を検索する API を呼び出したい。
**なぜなら**、検索条件を送信して蔵書一覧を取得し、画面に表示したいからだ。

---

## 関連情報

| 項目 | 値 |
|------|-----|
| Epic | [EPIC-001: 蔵書検索](../epic.md) |
| ポイント | 5 |
| 優先度 | Must |
| ステータス | Draft |

---

## 受け入れ条件

1. [ ] GET /api/books エンドポイントが実装されていること
2. [ ] title, author, isbn でフィルタリングできること
3. [ ] ページネーションが実装されていること（page, per_page）
4. [ ] レスポンスが30秒以内に返ること
5. [ ] 検索結果が0件の場合、空配列が返ること
6. [ ] Feature テストが作成されていること

---

## 技術仕様

### エンドポイント

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
| per_page | int | No | 1ページあたり件数（デフォルト: 20、最大: 100） |

### レスポンス（200 OK）

```json
{
  "data": [
    {
      "id": "550e8400-e29b-41d4-a716-446655440000",
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

---

## 成果物

| 成果物 | 配置場所 |
|--------|---------|
| SearchBooksUseCase.php | backend/packages/UseCase/Book/ |
| BookSearchCriteria.php | backend/packages/UseCase/Book/ |
| BookController.php | backend/app/Http/Controllers/ |
| BookResource.php | backend/app/Http/Resources/ |
| api.php（ルート追加） | backend/routes/ |
| BookSearchTest.php | backend/tests/Feature/ |

---

## タスク

### Design Tasks（外部設計）

- [ ] API 仕様の確定
- [ ] リクエスト・レスポンス形式の確定

### Spec Tasks（詳細設計）

- [ ] UseCase の実装
- [ ] Controller の実装
- [ ] Resource の実装
- [ ] ルーティング設定
- [ ] Feature テスト作成

---

## 変更履歴

| 日付 | 変更内容 |
|------|---------|
| 2025-12-24 | 初版作成 |
