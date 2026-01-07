# ST-001: 利用者検索 API の実装

最終更新: 2025-12-26

---

## ストーリー

**フロントエンド開発者として**、利用者検索 API を利用したい。
**なぜなら**、フロントエンドから利用者を検索・一覧取得したいからだ。

---

## 関連情報

| 項目 | 値 |
|------|-----|
| Epic | [EPIC-011: 利用者検索機能](../epic.md) |
| ポイント | 3 |
| 優先度 | Must |
| ステータス | Planned |

---

## 受け入れ条件

1. [ ] `GET /api/patrons` で利用者を検索・一覧取得できること
2. [ ] 利用者番号での完全一致検索ができること
3. [ ] 氏名、ふりがな、電話番号での部分一致検索ができること
4. [ ] ステータス（有効/無効/すべて）でフィルタできること
5. [ ] ページネーションが動作すること
6. [ ] ソート順を指定できること
7. [ ] 職員以外がアクセスすると 403 が返ること
8. [ ] 電話番号がマスキングされて返却されること

---

## 技術仕様

### API エンドポイント

| メソッド | エンドポイント | 説明 | 認証 | 権限 |
|----------|---------------|------|------|------|
| GET | `/api/patrons` | 利用者検索・一覧取得 | 必須 | 職員 |

### クエリパラメータ

| パラメータ | 型 | 必須 | 説明 |
|-----------|-----|:----:|------|
| patron_number | string | - | 利用者番号（完全一致） |
| name | string | - | 氏名（部分一致） |
| name_kana | string | - | ふりがな（部分一致） |
| phone_number | string | - | 電話番号（部分一致） |
| status | string | - | active/inactive/all（デフォルト: active） |
| sort | string | - | name_kana/patron_number/created_at（デフォルト: name_kana） |
| order | string | - | asc/desc（デフォルト: asc） |
| page | int | - | ページ番号（デフォルト: 1） |
| per_page | int | - | 件数（デフォルト: 20、最大: 100） |

### リクエスト/レスポンス

**リクエスト例:**
```
GET /api/patrons?name=山田&status=active&sort=name_kana&order=asc&page=1&per_page=20
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
    },
    {
      "id": "01HV...",
      "patronNumber": "P2025000015",
      "name": "山田 花子",
      "nameKana": "やまだ はなこ",
      "phoneNumber": "080-****-1234",
      "patronType": "student",
      "isActive": true,
      "expiresAt": "2026-06-15"
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

### UseCase 設計

```php
// SearchPatronsHandler
class SearchPatronsHandler
{
    public function handle(SearchPatronsQuery $query): PaginatedResult
    {
        $criteria = new PatronSearchCriteria(
            patronNumber: $query->patronNumber,
            name: $query->name,
            nameKana: $query->nameKana,
            phoneNumber: $query->phoneNumber,
            status: $query->status,
        );

        $sortOrder = new SortOrder(
            field: $query->sort,
            direction: $query->order,
        );

        $patrons = $this->patronRepository->search(
            criteria: $criteria,
            sortOrder: $sortOrder,
            page: $query->page,
            perPage: $query->perPage,
        );

        // 電話番号のマスキング
        return $patrons->map(function ($patron) {
            return $patron->withMaskedPhoneNumber();
        });
    }
}
```

### PatronSearchCriteria 値オブジェクト

```php
// PatronSearchCriteria.php
final class PatronSearchCriteria
{
    public function __construct(
        public readonly ?string $patronNumber = null,
        public readonly ?string $name = null,
        public readonly ?string $nameKana = null,
        public readonly ?string $phoneNumber = null,
        public readonly PatronStatus $status = PatronStatus::ACTIVE,
    ) {}

    public function isEmpty(): bool
    {
        return $this->patronNumber === null
            && $this->name === null
            && $this->nameKana === null
            && $this->phoneNumber === null;
    }
}
```

### Controller 実装

```php
// PatronController
public function index(SearchPatronsRequest $request): JsonResponse
{
    $result = $this->searchPatronsHandler->handle(
        new SearchPatronsQuery(
            patronNumber: $request->patron_number,
            name: $request->name,
            nameKana: $request->name_kana,
            phoneNumber: $request->phone_number,
            status: $request->status ?? 'active',
            sort: $request->sort ?? 'name_kana',
            order: $request->order ?? 'asc',
            page: $request->page ?? 1,
            perPage: min($request->per_page ?? 20, 100),
        )
    );

    return PatronResource::collection($result)
        ->additional([
            'meta' => [
                'current_page' => $result->currentPage(),
                'per_page' => $result->perPage(),
                'total' => $result->total(),
                'last_page' => $result->lastPage(),
            ],
        ]);
}
```

### 電話番号マスキング

```php
// Patron.php
public function maskedPhoneNumber(): string
{
    $phone = $this->encryptor->decrypt($this->phoneNumber);
    // 090-1234-5678 → 090-****-5678
    return preg_replace('/(\d{3}-)(\d{4})(-.+)/', '$1****$3', $phone);
}
```

---

## 成果物

| 成果物 | 配置場所 |
|--------|---------|
| SearchPatronsQuery | backend/packages/Domain/Patron/Application/UseCases/Queries/SearchPatrons/SearchPatronsQuery.php |
| SearchPatronsHandler | backend/packages/Domain/Patron/Application/UseCases/Queries/SearchPatrons/SearchPatronsHandler.php |
| PatronSearchCriteria | backend/packages/Domain/Patron/PatronSearchCriteria.php |
| SearchPatronsRequest | backend/app/Http/Requests/Patron/SearchPatronsRequest.php |
| PatronController（更新） | backend/app/Http/Controllers/Patron/PatronController.php |
| PatronResource（更新） | backend/app/Http/Resources/PatronResource.php |
| Feature テスト | backend/tests/Feature/Patron/SearchPatronsTest.php |

---

## タスク

### Design Tasks（外部設計）

- [ ] API レスポンス形式の確定
- [ ] 検索パラメータの確定
- [ ] マスキングルールの確定

### Spec Tasks（詳細設計）

- [ ] SearchPatronsQuery 実装
- [ ] SearchPatronsHandler 実装
- [ ] PatronSearchCriteria 値オブジェクト実装
- [ ] SearchPatronsRequest 実装
- [ ] PatronRepository に search メソッド追加
- [ ] PatronController に index メソッド追加
- [ ] 電話番号マスキング処理実装
- [ ] ルーティング設定
- [ ] 検索インデックス作成（マイグレーション）
- [ ] Feature テスト作成

---

## 変更履歴

| 日付 | 変更内容 |
|------|---------|
| 2025-12-26 | 初版作成 |
