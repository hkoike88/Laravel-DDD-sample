# ST-002: 予約登録 API の実装

最終更新: 2025-12-24

---

## ストーリー

**図書館職員として**、予約を登録する API を使いたい。
**なぜなら**、画面から予約登録を実行したいからだ。

---

## 関連情報

| 項目 | 値 |
|------|-----|
| Epic | [EPIC-005: 予約登録](../epic.md) |
| ポイント | 3 |
| 優先度 | Must |
| ステータス | Draft |

---

## 受け入れ条件

1. [ ] POST /api/reservations で予約を登録できること
2. [ ] 利用者ID と 図書ID を指定して予約できること
3. [ ] 予約成功時に 201 Created が返ること
4. [ ] 重複予約時に 422 が返ること
5. [ ] 貸出可能な図書への予約時に 422 が返ること
6. [ ] 予約順番が自動付番されること

---

## タスク

### Design Tasks（外部設計）

- [ ] API 仕様の確定

### Spec Tasks（詳細設計）

- [ ] CreateReservationUseCase の実装
- [ ] ReservationController の実装
- [ ] CreateReservationRequest の実装
- [ ] ReservationResource の実装
- [ ] Feature テストの作成

---

## API 仕様

### リクエスト

```http
POST /api/reservations
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
    "order": 2,
    "status": "waiting",
    "reserved_at": "2025-01-20T10:30:00+09:00",
    "message": "予約順: 2番目です"
  }
}
```

### レスポンス（エラー時 - 重複予約）

```http
HTTP/1.1 422 Unprocessable Entity
Content-Type: application/json

{
  "message": "既にこの図書を予約しています",
  "errors": {
    "book_id": ["重複予約はできません"]
  }
}
```

### レスポンス（エラー時 - 貸出可能）

```http
HTTP/1.1 422 Unprocessable Entity
Content-Type: application/json

{
  "message": "この図書は現在貸出可能です",
  "errors": {
    "book_id": ["貸出可能な図書は直接貸出してください"]
  }
}
```

---

## 変更履歴

| 日付 | 変更内容 |
|------|---------|
| 2025-12-24 | 初版作成 |
