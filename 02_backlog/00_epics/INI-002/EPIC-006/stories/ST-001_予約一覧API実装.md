# ST-001: 予約一覧 API の実装

最終更新: 2025-12-24

---

## ストーリー

**図書館職員として**、予約一覧を取得する API を使いたい。
**なぜなら**、予約状況を画面で確認したいからだ。

---

## 関連情報

| 項目 | 値 |
|------|-----|
| Epic | [EPIC-006: 予約管理](../epic.md) |
| ポイント | 3 |
| 優先度 | Must |
| ステータス | Draft |

---

## 受け入れ条件

1. [ ] GET /api/reservations で予約一覧が取得できること
2. [ ] ステータスでフィルタリングできること
3. [ ] 図書・利用者情報が含まれること
4. [ ] 取り置き期限が含まれること
5. [ ] ページネーションが機能すること

---

## タスク

### Design Tasks（外部設計）

- [ ] API 仕様の確定

### Spec Tasks（詳細設計）

- [ ] GetReservationsUseCase の実装
- [ ] ReservationController の index アクション実装
- [ ] ReservationResource の実装
- [ ] Feature テストの作成

---

## API 仕様

### リクエスト

```http
GET /api/reservations?status=ready&page=1&per_page=20
```

### クエリパラメータ

| パラメータ | 型 | 説明 |
|-----------|-----|------|
| status | string | フィルタ: waiting, ready, completed, cancelled, expired |
| book_id | uuid | 図書でフィルタ |
| patron_id | uuid | 利用者でフィルタ |
| page | int | ページ番号 |
| per_page | int | 1ページあたりの件数 |

### レスポンス

```http
HTTP/1.1 200 OK
Content-Type: application/json

{
  "data": [
    {
      "id": "uuid",
      "patron": {
        "id": "uuid",
        "name": "山田太郎",
        "phone": "090-1234-5678"
      },
      "book": {
        "id": "uuid",
        "title": "プログラミング入門"
      },
      "order": 1,
      "status": "ready",
      "reserved_at": "2025-01-15T10:30:00+09:00",
      "ready_at": "2025-01-20T14:00:00+09:00",
      "expires_at": "2025-01-27T23:59:59+09:00",
      "contacted": true,
      "contacted_at": "2025-01-20T15:00:00+09:00"
    }
  ],
  "meta": {
    "total": 25,
    "page": 1,
    "per_page": 20,
    "last_page": 2
  }
}
```

---

## 変更履歴

| 日付 | 変更内容 |
|------|---------|
| 2025-12-24 | 初版作成 |
