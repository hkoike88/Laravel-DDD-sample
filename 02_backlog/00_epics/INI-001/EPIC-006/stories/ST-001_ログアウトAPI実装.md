# ST-001: ログアウト API の実装

最終更新: 2025-12-24

---

## ストーリー

**フロントエンド開発者として**、ログアウト API を利用したい。
**なぜなら**、フロントエンドから職員のセッションを安全に終了させたいからだ。

---

## 関連情報

| 項目 | 値 |
|------|-----|
| Epic | [EPIC-006: 職員ログアウト機能](../epic.md) |
| ポイント | 1 |
| 優先度 | Must |
| ステータス | Planned |

---

## 受け入れ条件

1. [ ] `POST /api/auth/logout` でログアウトできること
2. [ ] ログアウト後、セッションが破棄されること
3. [ ] ログアウト後、認証が必要な API にアクセスできなくなること
4. [ ] 未認証状態でログアウト API を呼ぶと 401 が返ること
5. [ ] CSRF トークンが再生成されること

---

## 技術仕様

### API エンドポイント

| メソッド | エンドポイント | 説明 | 認証 |
|----------|---------------|------|------|
| POST | `/api/auth/logout` | ログアウト | 必須 |

### リクエスト/レスポンス

#### POST /api/auth/logout

**リクエストヘッダー:**
```
POST /api/auth/logout
Cookie: laravel_session=xxx
X-XSRF-TOKEN: xxx
```

**成功レスポンス (200):**
```json
{
  "message": "ログアウトしました"
}
```

**未認証レスポンス (401):**
```json
{
  "message": "Unauthenticated."
}
```

### UseCase 設計

```php
// LogoutHandler
class LogoutHandler
{
    public function handle(): void
    {
        // 1. 現在のセッションを無効化
        Auth::guard('web')->logout();

        // 2. セッションを再生成（セッション固定攻撃対策）
        request()->session()->invalidate();

        // 3. CSRF トークンを再生成
        request()->session()->regenerateToken();
    }
}
```

### Controller 実装

```php
// AuthController
public function logout(Request $request): JsonResponse
{
    $this->logoutHandler->handle();

    return response()->json([
        'message' => 'ログアウトしました'
    ]);
}
```

### ルーティング

```php
// routes/api.php
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
});
```

---

## 成果物

| 成果物 | 配置場所 |
|--------|---------|
| LogoutHandler | backend/packages/Domain/Staff/Application/UseCases/Commands/Logout/LogoutHandler.php |
| AuthController（更新） | backend/app/Http/Controllers/Auth/AuthController.php |
| Feature テスト | backend/tests/Feature/Auth/LogoutTest.php |

---

## タスク

### Design Tasks（外部設計）

- [ ] API レスポンス形式の確定
- [ ] エラーハンドリング方針の確定

### Spec Tasks（詳細設計）

- [ ] LogoutHandler 実装
- [ ] AuthController に logout メソッド追加
- [ ] ルーティング設定
- [ ] Feature テスト作成

---

## 変更履歴

| 日付 | 変更内容 |
|------|---------|
| 2025-12-24 | 初版作成 |
