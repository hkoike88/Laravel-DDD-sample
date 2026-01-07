# ST-001: 返却 API の実装

最終更新: 2025-12-24

---

## ストーリー

**図書館職員として**、返却処理を行う API を使いたい。
**なぜなら**、画面から返却処理を実行したいからだ。

---

## 関連情報

| 項目 | 値 |
|------|-----|
| Epic | [EPIC-004: 返却処理](../epic.md) |
| ポイント | 3 |
| 優先度 | Must |
| ステータス | Draft |

---

## 受け入れ条件

1. [ ] PUT /api/loans/{id}/return で返却処理ができること
2. [ ] 返却成功時に 200 OK が返ること
3. [ ] 延滞していた場合、延滞情報が返ること
4. [ ] 予約がある場合、予約情報が返ること
5. [ ] 既に返却済みの場合、422 が返ること

---

## タスク

### Design Tasks（外部設計）

- [ ] API 仕様の確定

### Spec Tasks（詳細設計）

- [ ] ReturnBookUseCase の実装
- [ ] LoanController への return アクション追加
- [ ] ReturnBookResource の実装
- [ ] Feature テストの作成

---

## API 仕様

### リクエスト

```http
PUT /api/loans/{loan_id}/return
Content-Type: application/json
```

### レスポンス（成功時）

```http
HTTP/1.1 200 OK
Content-Type: application/json

{
  "data": {
    "id": "uuid",
    "patron_id": "uuid",
    "book_id": "uuid",
    "borrowed_at": "2025-01-15T10:30:00+09:00",
    "due_at": "2025-01-29T23:59:59+09:00",
    "returned_at": "2025-01-20T14:00:00+09:00",
    "status": "returned",
    "was_overdue": false,
    "overdue_days": 0,
    "has_reservation": true,
    "next_patron": {
      "id": "uuid",
      "name": "鈴木花子"
    }
  }
}
```

### レスポンス（延滞返却時）

```http
HTTP/1.1 200 OK
Content-Type: application/json

{
  "data": {
    "id": "uuid",
    "status": "returned",
    "was_overdue": true,
    "overdue_days": 5
  },
  "warning": "5日間の延滞がありました"
}
```

---

## 変更履歴

| 日付 | 変更内容 |
|------|---------|
| 2025-12-24 | 初版作成 |
