# ST-001: 職員認証 API の実装

最終更新: 2025-12-26

---

## ストーリー

**フロントエンド開発者として**、職員認証 API を利用したい。
**なぜなら**、フロントエンドから職員のログイン処理を実行したいからだ。

---

## 関連情報

| 項目 | 値 |
|------|-----|
| Epic | [EPIC-001: 職員ログイン機能](../epic.md) |
| ポイント | 3 |
| 優先度 | Must |
| ステータス | Planned |

---

## 受け入れ条件

1. [ ] `GET /api/auth/csrf-cookie` で CSRF Cookie を取得できること
2. [ ] `POST /api/auth/login` でログインできること
3. [ ] `GET /api/auth/me` で認証ユーザー情報を取得できること
4. [ ] 認証失敗時に適切なエラーメッセージが返ること
5. [ ] パスワードが bcrypt (cost=12) でハッシュ化されていること
6. [ ] ログイン成功時にセッションが開始されること

---

## 技術仕様

### API エンドポイント

| メソッド | エンドポイント | 説明 | 認証 |
|----------|---------------|------|------|
| GET | `/api/auth/csrf-cookie` | CSRF Cookie 取得 | 不要 |
| POST | `/api/auth/login` | ログイン | 不要 |
| GET | `/api/auth/me` | 認証ユーザー情報取得 | 必須 |

### リクエスト/レスポンス

#### GET /api/auth/csrf-cookie

**レスポンス:**
```
204 No Content
Set-Cookie: XSRF-TOKEN=xxx; Path=/
```

#### POST /api/auth/login

**リクエスト:**
```json
{
  "email": "staff@example.com",
  "password": "password123"
}
```

**成功レスポンス (200):**
```json
{
  "message": "ログインしました",
  "user": {
    "id": "01HV...",
    "name": "山田太郎",
    "email": "staff@example.com",
    "role": "staff"
  }
}
```

**認証失敗レスポンス (401):**
```json
{
  "message": "メールアドレスまたはパスワードが正しくありません"
}
```

**バリデーションエラーレスポンス (422):**
```json
{
  "message": "入力内容に誤りがあります",
  "errors": {
    "email": ["メールアドレスを入力してください"],
    "password": ["パスワードを入力してください"]
  }
}
```

#### GET /api/auth/me

**成功レスポンス (200):**
```json
{
  "user": {
    "id": "01HV...",
    "name": "山田太郎",
    "email": "staff@example.com",
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

### UseCase 設計

```php
// LoginHandler
class LoginHandler
{
    public function __construct(
        private StaffRepositoryInterface $staffRepository,
    ) {}

    public function handle(LoginCommand $command): Staff
    {
        $staff = $this->staffRepository->findByEmail(
            new Email($command->email)
        );

        if (!$staff || !$staff->verifyPassword($command->password)) {
            throw new AuthenticationException('認証に失敗しました');
        }

        if ($staff->isLocked()) {
            throw new AccountLockedException('アカウントがロックされています');
        }

        return $staff;
    }
}
```

### Controller 実装

```php
// AuthController
public function login(LoginRequest $request): JsonResponse
{
    $staff = $this->loginHandler->handle(
        new LoginCommand(
            email: $request->email,
            password: $request->password,
        )
    );

    Auth::login($staff);
    $request->session()->regenerate();

    return response()->json([
        'message' => 'ログインしました',
        'user' => new StaffResource($staff),
    ]);
}
```

### バリデーション

```php
// LoginRequest
public function rules(): array
{
    return [
        'email' => ['required', 'email'],
        'password' => ['required', 'string'],
    ];
}
```

---

## 成果物

| 成果物 | 配置場所 |
|--------|---------|
| LoginHandler | backend/packages/Domain/Staff/Application/UseCases/Commands/Login/LoginHandler.php |
| LoginCommand | backend/packages/Domain/Staff/Application/UseCases/Commands/Login/LoginCommand.php |
| AuthController | backend/app/Http/Controllers/Auth/AuthController.php |
| LoginRequest | backend/app/Http/Requests/Auth/LoginRequest.php |
| StaffResource | backend/app/Http/Resources/StaffResource.php |
| Feature テスト | backend/tests/Feature/Auth/LoginTest.php |

---

## タスク

### Design Tasks（外部設計）

- [ ] API レスポンス形式の確定
- [ ] エラーメッセージの確定
- [ ] バリデーションルールの確定

### Spec Tasks（詳細設計）

- [ ] LoginCommand 実装
- [ ] LoginHandler 実装
- [ ] LoginRequest 実装
- [ ] AuthController 実装
- [ ] StaffResource 実装
- [ ] ルーティング設定
- [ ] Feature テスト作成

---

## 変更履歴

| 日付 | 変更内容 |
|------|---------|
| 2025-12-26 | 初版作成 |
