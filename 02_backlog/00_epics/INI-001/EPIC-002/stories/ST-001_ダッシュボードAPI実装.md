# ST-001: ダッシュボード API の実装

最終更新: 2025-12-26

---

## ストーリー

**フロントエンド開発者として**、ダッシュボード API を利用したい。
**なぜなら**、フロントエンドでダッシュボード情報（業務サマリー、ユーザー情報）を表示したいからだ。

---

## 関連情報

| 項目 | 値 |
|------|-----|
| Epic | [EPIC-002: 職員ダッシュボード機能](../epic.md) |
| ポイント | 2 |
| 優先度 | Must |
| ステータス | Planned |

---

## 受け入れ条件

1. [ ] `GET /api/staff/dashboard` でダッシュボード情報を取得できること
2. [ ] 本日の貸出件数が取得できること
3. [ ] 本日の返却件数が取得できること
4. [ ] 延滞中の図書数が取得できること
5. [ ] 予約待ち件数が取得できること
6. [ ] ログインユーザー情報（名前、権限）が取得できること
7. [ ] 未認証の場合 401 が返ること

---

## 技術仕様

### API エンドポイント

| メソッド | エンドポイント | 説明 | 認証 |
|----------|---------------|------|------|
| GET | `/api/staff/dashboard` | ダッシュボード情報取得 | 必須 |

### リクエスト/レスポンス

#### GET /api/staff/dashboard

**リクエストヘッダー:**
```
GET /api/staff/dashboard
Cookie: laravel_session=xxx
```

**成功レスポンス (200):**
```json
{
  "summary": {
    "todayLoans": 15,
    "todayReturns": 12,
    "overdueBooks": 3,
    "pendingReservations": 8
  },
  "user": {
    "id": "01HV...",
    "name": "山田太郎",
    "email": "yamada@example.com",
    "role": "staff"
  }
}
```

**未認証レスポンス (401):**
```json
{
  "message": "Unauthenticated."
}
```

### Controller 実装

```php
// DashboardController
class DashboardController extends Controller
{
    public function __construct(
        private GetDashboardSummaryHandler $getSummaryHandler,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $summary = $this->getSummaryHandler->handle();

        return response()->json([
            'summary' => new DashboardSummaryResource($summary),
            'user' => new StaffResource($request->user()),
        ]);
    }
}
```

### UseCase 設計

```php
// GetDashboardSummaryHandler
class GetDashboardSummaryHandler
{
    public function __construct(
        private LoanRepositoryInterface $loanRepository,
        private ReservationRepositoryInterface $reservationRepository,
    ) {}

    public function handle(): DashboardSummary
    {
        $today = Carbon::today();

        return new DashboardSummary(
            todayLoans: $this->loanRepository->countByDate($today),
            todayReturns: $this->loanRepository->countReturnsByDate($today),
            overdueBooks: $this->loanRepository->countOverdue(),
            pendingReservations: $this->reservationRepository->countPending(),
        );
    }
}
```

### ルーティング

```php
// routes/api.php
Route::middleware('auth:sanctum')->prefix('staff')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);
});
```

---

## 成果物

| 成果物 | 配置場所 |
|--------|---------|
| DashboardController | backend/app/Http/Controllers/Staff/DashboardController.php |
| GetDashboardSummaryHandler | backend/packages/Domain/Dashboard/Application/UseCases/Queries/GetDashboardSummaryHandler.php |
| DashboardSummary | backend/packages/Domain/Dashboard/ValueObjects/DashboardSummary.php |
| DashboardSummaryResource | backend/app/Http/Resources/DashboardSummaryResource.php |
| Feature テスト | backend/tests/Feature/Staff/DashboardTest.php |

---

## タスク

### Design Tasks（外部設計）

- [ ] API レスポンス形式の確定
- [ ] サマリー項目の確定

### Spec Tasks（詳細設計）

- [ ] DashboardSummary ValueObject 実装
- [ ] GetDashboardSummaryHandler 実装
- [ ] DashboardController 実装
- [ ] DashboardSummaryResource 実装
- [ ] ルーティング設定
- [ ] Feature テスト作成

---

## 備考

- 貸出・返却・予約機能は INI-002 で実装予定のため、本フェーズではモックデータを返す
- 実装完了後、INI-002 完了時に実データに切り替える

---

## 変更履歴

| 日付 | 変更内容 |
|------|---------|
| 2025-12-26 | 初版作成 |
