# ST-003: 職員一覧 API の実装

最終更新: 2025-12-26

---

## ストーリー

**フロントエンド開発者として**、職員一覧 API を利用したい。
**なぜなら**、フロントエンドで職員一覧を表示したいからだ。

---

## 関連情報

| 項目 | 値 |
|------|-----|
| Epic | [EPIC-003: 職員アカウント作成機能](../epic.md) |
| ポイント | 2 |
| 優先度 | Must |
| ステータス | Planned |

---

## 受け入れ条件

1. [ ] `GET /api/staff/accounts` で職員一覧を取得できること
2. [ ] ページネーションに対応していること（20件/ページ）
3. [ ] 氏名での検索ができること
4. [ ] メールアドレスでの検索ができること
5. [ ] 管理者以外がアクセスすると 403 が返ること
6. [ ] 無効化された職員も含めて取得できること（フィルタ対応）

---

## 技術仕様

### API エンドポイント

| メソッド | エンドポイント | 説明 | 認証 | 権限 |
|----------|---------------|------|------|------|
| GET | `/api/staff/accounts` | 職員一覧取得 | 必須 | 管理者 |

### クエリパラメータ

| パラメータ | 型 | 必須 | 説明 |
|-----------|-----|------|------|
| page | int | - | ページ番号（デフォルト: 1） |
| per_page | int | - | 1ページあたり件数（デフォルト: 20、最大: 100） |
| search | string | - | 氏名またはメールアドレスで検索 |
| include_inactive | bool | - | 無効化された職員を含む（デフォルト: false） |

### リクエスト/レスポンス

#### GET /api/staff/accounts

**リクエスト:**
```
GET /api/staff/accounts?page=1&per_page=20&search=田中
```

**成功レスポンス (200):**
```json
{
  "data": [
    {
      "id": "01HV...",
      "name": "田中 花子",
      "email": "tanaka@example.com",
      "role": "staff",
      "isActive": true,
      "createdAt": "2025-12-26T10:00:00+09:00"
    },
    {
      "id": "01HW...",
      "name": "田中 太郎",
      "email": "tanaka.t@example.com",
      "role": "admin",
      "isActive": true,
      "createdAt": "2025-12-25T09:00:00+09:00"
    }
  ],
  "meta": {
    "currentPage": 1,
    "lastPage": 3,
    "perPage": 20,
    "total": 45
  }
}
```

### UseCase 設計

```php
// GetStaffListHandler
class GetStaffListHandler
{
    public function __construct(
        private StaffRepositoryInterface $staffRepository,
    ) {}

    public function handle(GetStaffListQuery $query): LengthAwarePaginator
    {
        return $this->staffRepository->paginate(
            search: $query->search,
            includeInactive: $query->includeInactive,
            perPage: $query->perPage,
            page: $query->page,
        );
    }
}
```

### Controller 実装

```php
// StaffAccountController
public function index(Request $request): JsonResponse
{
    $staffList = $this->getStaffListHandler->handle(
        new GetStaffListQuery(
            search: $request->input('search'),
            includeInactive: $request->boolean('include_inactive'),
            perPage: $request->integer('per_page', 20),
            page: $request->integer('page', 1),
        )
    );

    return StaffResource::collection($staffList)->response();
}
```

### ルーティング

```php
// routes/api.php
Route::middleware(['auth:sanctum', 'admin'])->prefix('staff')->group(function () {
    Route::get('/accounts', [StaffAccountController::class, 'index']);
    Route::post('/accounts', [StaffAccountController::class, 'store']);
});
```

---

## 成果物

| 成果物 | 配置場所 |
|--------|---------|
| GetStaffListQuery | backend/packages/Domain/Staff/Application/UseCases/Queries/GetStaffListQuery.php |
| GetStaffListHandler | backend/packages/Domain/Staff/Application/UseCases/Queries/GetStaffListHandler.php |
| StaffAccountController（更新） | backend/app/Http/Controllers/Staff/StaffAccountController.php |
| StaffResource | backend/app/Http/Resources/StaffResource.php |
| Feature テスト | backend/tests/Feature/Staff/GetStaffListTest.php |

---

## タスク

### Design Tasks（外部設計）

- [ ] API レスポンス形式の確定
- [ ] ページネーション仕様の確定
- [ ] 検索仕様の確定

### Spec Tasks（詳細設計）

- [ ] GetStaffListQuery 実装
- [ ] GetStaffListHandler 実装
- [ ] StaffRepository に paginate メソッド追加
- [ ] StaffAccountController に index メソッド追加
- [ ] StaffResource 実装
- [ ] ルーティング設定
- [ ] Feature テスト作成

---

## 変更履歴

| 日付 | 変更内容 |
|------|---------|
| 2025-12-26 | 初版作成 |
