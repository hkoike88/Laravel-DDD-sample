# ST-002: 貸出 API の実装

最終更新: 2025-12-24

---

## ストーリー

**図書館職員として**、貸出処理を行う API を使いたい。
**なぜなら**、画面から貸出処理を実行したいからだ。

---

## 関連情報

| 項目 | 値 |
|------|-----|
| Epic | [EPIC-003: 貸出処理](../epic.md) |
| ポイント | 5 |
| 優先度 | Must |
| ステータス | Draft |

---

## 受け入れ条件

1. [ ] POST /api/loans で貸出処理ができること
2. [ ] 利用者ID と 図書ID を指定して貸出できること
3. [ ] 貸出成功時に 201 Created が返ること
4. [ ] 貸出上限エラー時に 422 が返ること
5. [ ] 延滞中エラー時に 422 が返ること
6. [ ] 図書貸出不可エラー時に 422 が返ること

---

## タスク

### Design Tasks（外部設計）

- [ ] API 仕様の確定

### Spec Tasks（詳細設計）

- [ ] CreateLoanUseCase の実装
- [ ] LoanController の実装
- [ ] CreateLoanRequest の実装
- [ ] LoanResource の実装
- [ ] Feature テストの作成

---

## API 仕様

### リクエスト

```http
POST /api/loans
Content-Type: application/json

{
  "patron_id": "uuid",
  "book_id": "uuid"
}
```

### レスポンス（成功時）

```http
HTTP/1.1 201 Created
Content-Type: application/json

{
  "data": {
    "id": "uuid",
    "patron_id": "uuid",
    "book_id": "uuid",
    "borrowed_at": "2025-01-15T10:30:00+09:00",
    "due_at": "2025-01-29T23:59:59+09:00",
    "status": "borrowed"
  }
}
```

### レスポンス（エラー時）

```http
HTTP/1.1 422 Unprocessable Entity
Content-Type: application/json

{
  "message": "貸出上限（5冊）に達しています",
  "errors": {
    "patron_id": ["現在の貸出数: 5冊"]
  }
}
```

---

## 変更履歴

| 日付 | 変更内容 |
|------|---------|
| 2025-12-24 | 初版作成 |
