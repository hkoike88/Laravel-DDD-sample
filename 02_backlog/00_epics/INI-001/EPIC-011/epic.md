# EPIC-011: 利用者検索機能

最終更新: 2025-12-26

---

## 概要

図書館職員が利用者を検索し、一覧表示する機能を実装する。利用者番号、氏名、電話番号などの条件で検索し、貸出・返却処理や情報編集のために利用者を特定する。

---

## 関連情報

| 項目 | 値 |
|------|-----|
| イニシアチブ | [INI-001: 認証・利用者管理基盤](../../../../01_vision/initiatives/INI-001/charter.md) |
| Use Case | [UC-001-011: 利用者検索](../../../../01_vision/initiatives/INI-001/usecases/UC-001-011_利用者検索.md) |
| 優先度 | Must |
| ステータス | Planned |

---

## ビジネス価値

利用者を素早く特定することで、貸出・返却処理や問い合わせ対応を効率化する。複数の検索条件による柔軟な検索で、職員の業務効率を向上させる。

---

## 受け入れ条件

1. 利用者番号、氏名、ふりがな、電話番号で検索できること
2. 部分一致検索が可能であること
3. 検索結果が一覧表示されること
4. 検索結果からページネーションが動作すること
5. 検索結果から利用者詳細画面に遷移できること
6. 無効化された利用者もフィルタで表示できること
7. 検索結果が2秒以内に表示されること

---

## 画面一覧

| 画面ID | 画面名 | パス | 説明 |
|--------|--------|------|------|
| SCR-001-010 | 利用者管理 | `/staff/patrons` | 検索フォームと一覧 |
| SCR-001-012 | 利用者詳細 | `/staff/patrons/{id}` | 検索結果から遷移 |

---

## User Story 一覧

| ID | Story 名 | ポイント | 優先度 | ステータス |
|----|----------|---------|--------|----------|
| [ST-001](./stories/ST-001_利用者検索API実装.md) | 利用者検索 API の実装 | 3 | Must | Planned |
| [ST-002](./stories/ST-002_利用者検索UI実装.md) | 利用者検索 UI の実装 | 3 | Must | Planned |
| [ST-003](./stories/ST-003_利用者一覧表示機能実装.md) | 利用者一覧表示機能の実装 | 2 | Must | Planned |

---

## 成果物

| 成果物 | 配置場所 | 説明 |
|--------|---------|------|
| SearchPatronsHandler | backend/packages/Domain/Patron/Application/UseCases/Queries/SearchPatrons/ | 利用者検索処理 |
| PatronController（更新） | backend/app/Http/Controllers/Patron/ | 検索エンドポイント |
| PatronSearchForm | frontend/src/features/patron/components/ | 検索フォーム |
| PatronList | frontend/src/features/patron/components/ | 利用者一覧 |

---

## 技術仕様

### API エンドポイント

| メソッド | エンドポイント | 説明 | 認証 | 権限 |
|----------|---------------|------|------|------|
| GET | `/api/patrons` | 利用者検索・一覧取得 | 必須 | 職員 |

### リクエスト/レスポンス

#### GET /api/patrons

**クエリパラメータ:**
| パラメータ | 型 | 必須 | 説明 |
|-----------|-----|:----:|------|
| patron_number | string | - | 利用者番号（完全一致） |
| name | string | - | 氏名（部分一致） |
| name_kana | string | - | ふりがな（部分一致） |
| phone_number | string | - | 電話番号（部分一致） |
| status | string | - | ステータス（active/inactive/all）デフォルト: active |
| sort | string | - | ソート項目（name_kana/patron_number/created_at）デフォルト: name_kana |
| order | string | - | ソート順（asc/desc）デフォルト: asc |
| page | int | - | ページ番号 デフォルト: 1 |
| per_page | int | - | 1ページあたり件数 デフォルト: 20、最大: 100 |

**リクエスト例:**
```
GET /api/patrons?name=山田&status=active&page=1&per_page=20
```

**成功レスポンス (200):**
```json
{
  "data": [
    {
      "id": "01HV...",
      "patronNumber": "P2025000001",
      "name": "山田 太郎",
      "nameKana": "やまだ たろう",
      "phoneNumber": "090-****-5678",
      "patronType": "general",
      "isActive": true,
      "expiresAt": "2026-12-26"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 20,
    "total": 45,
    "last_page": 3
  }
}
```

**検索結果0件レスポンス (200):**
```json
{
  "data": [],
  "meta": {
    "current_page": 1,
    "per_page": 20,
    "total": 0,
    "last_page": 1
  },
  "message": "該当する利用者が見つかりませんでした"
}
```

### 検索条件

| 項目 | 検索方法 | インデックス |
|------|---------|-------------|
| 利用者番号 | 完全一致 | PRIMARY |
| 氏名 | 部分一致（LIKE） | あり |
| ふりがな | 部分一致（LIKE） | あり |
| 電話番号 | 部分一致（LIKE） | あり |

### セキュリティ

- 電話番号は一覧表示時にマスキング（中4桁を`****`）
- 住所は一覧には表示しない（詳細画面のみ）

---

## 依存関係

### 前提条件

| Epic ID | Epic 名 | 関係 |
|---------|---------|------|
| EPIC-001 | 職員ログイン機能 | 認証機能が完了していること |
| EPIC-008 | 利用者アカウント登録機能 | 検索対象の利用者が存在すること |

### 後続タスク

なし（本 Epic で利用者管理機能は完了）

---

## 非機能要件

| 項目 | 要件 |
|------|------|
| パフォーマンス | 検索結果は2秒以内に表示する |
| 可用性 | 検索機能は常時利用可能 |
| セキュリティ | 検索結果の個人情報は必要最小限を表示 |
| キャッシュ | 検索結果は5分間キャッシュ（オプション） |

---

## ビジネスルール

| ルールID | ルール内容 |
|----------|-----------|
| BR-UC001-11-01 | 検索結果は最大100件まで表示 |
| BR-UC001-11-02 | 無効化された利用者も検索対象に含める（フィルタ可能） |
| BR-UC001-11-03 | 検索結果は氏名の五十音順でソート（デフォルト） |
| BR-UC001-11-04 | 部分一致検索は前方一致・後方一致・中間一致を選択可能 |

---

## 関連ドキュメント

- [UC-001-011: 利用者検索](../../../../01_vision/initiatives/INI-001/usecases/UC-001-011_利用者検索.md)
- [利用者登録・管理業務フロー（AS-IS）](../../../../00_docs/10_business/asis/swimlane/user-registration-process.md)
- [バックエンド標準 - API設計](../../../../00_docs/20_tech/99_standard/backend/05_ApiDesign.md)

---

## 変更履歴

| 日付 | 変更内容 |
|------|---------|
| 2025-12-26 | 初版作成 |
