# ST-001: 職員アカウント作成 API の実装

最終更新: 2025-12-26

---

## ストーリー

**フロントエンド開発者として**、職員アカウント作成 API を利用したい。
**なぜなら**、フロントエンドから新しい職員アカウントを作成したいからだ。

---

## 関連情報

| 項目 | 値 |
|------|-----|
| Epic | [EPIC-003: 職員アカウント作成機能](../epic.md) |
| ポイント | 3 |
| 優先度 | Must |
| ステータス | Planned |

---

## 受け入れ条件

1. [ ] `POST /api/staff/accounts` で職員アカウントを作成できること
2. [ ] 氏名、メールアドレス、権限が必須であること
3. [ ] メールアドレスの一意性が検証されること
4. [ ] 初期パスワードが自動生成されること
5. [ ] 作成した職員情報と初期パスワードがレスポンスに含まれること
6. [ ] 管理者以外がアクセスすると 403 が返ること
7. [ ] アカウント作成が監査ログに記録されること

---

## 技術仕様

### API エンドポイント

| メソッド | エンドポイント | 説明 | 認証 | 権限 |
|----------|---------------|------|------|------|
| POST | `/api/staff/accounts` | 職員作成 | 必須 | 管理者 |

### リクエスト/レスポンス

#### POST /api/staff/accounts

**リクエスト:**
```json
{
  "name": "田中 花子",
  "email": "tanaka@example.com",
  "role": "staff"
}
```

**成功レスポンス (201):**
```json
{
  "message": "職員アカウントを作成しました",
  "staff": {
    "id": "01HV...",
    "name": "田中 花子",
    "email": "tanaka@example.com",
    "role": "staff",
    "createdAt": "2025-12-26T10:00:00+09:00"
  },
  "temporaryPassword": "Abc123!@#xyz"
}
```

**バリデーションエラーレスポンス (422):**
```json
{
  "message": "入力内容に誤りがあります",
  "errors": {
    "name": ["氏名を入力してください"],
    "email": ["このメールアドレスは既に登録されています"],
    "role": ["権限を選択してください"]
  }
}
```

### バリデーション

```php
// CreateStaffRequest
public function rules(): array
{
    return [
        'name' => ['required', 'string', 'max:50'],
        'email' => ['required', 'email', 'max:255', 'unique:staffs,email'],
        'role' => ['required', 'in:staff,admin'],
    ];
}

public function messages(): array
{
    return [
        'name.required' => '氏名を入力してください',
        'email.required' => 'メールアドレスを入力してください',
        'email.unique' => 'このメールアドレスは既に登録されています',
        'role.required' => '権限を選択してください',
    ];
}
```

### UseCase 設計

```php
// CreateStaffHandler
class CreateStaffHandler
{
    public function __construct(
        private StaffRepositoryInterface $staffRepository,
        private PasswordGenerator $passwordGenerator,
        private LoggerInterface $logger,
    ) {}

    public function handle(CreateStaffCommand $command): CreateStaffResult
    {
        $temporaryPassword = $this->passwordGenerator->generate(16);

        $staff = Staff::create(
            name: new StaffName($command->name),
            email: new Email($command->email),
            role: Role::from($command->role),
            password: Hash::make($temporaryPassword),
        );

        $this->staffRepository->save($staff);

        $this->logger->channel('security')->info('職員アカウントを作成しました', [
            'staff_id' => $staff->id()->value(),
            'email' => $staff->email()->value(),
            'created_by' => auth()->id(),
        ]);

        return new CreateStaffResult($staff, $temporaryPassword);
    }
}
```

### Controller 実装

```php
// StaffAccountController
public function store(CreateStaffRequest $request): JsonResponse
{
    $result = $this->createStaffHandler->handle(
        new CreateStaffCommand(
            name: $request->name,
            email: $request->email,
            role: $request->role,
        )
    );

    return response()->json([
        'message' => '職員アカウントを作成しました',
        'staff' => new StaffResource($result->staff),
        'temporaryPassword' => $result->temporaryPassword,
    ], 201);
}
```

---

## 成果物

| 成果物 | 配置場所 |
|--------|---------|
| CreateStaffCommand | backend/packages/Domain/Staff/Application/UseCases/Commands/CreateStaff/CreateStaffCommand.php |
| CreateStaffHandler | backend/packages/Domain/Staff/Application/UseCases/Commands/CreateStaff/CreateStaffHandler.php |
| CreateStaffResult | backend/packages/Domain/Staff/Application/UseCases/Commands/CreateStaff/CreateStaffResult.php |
| PasswordGenerator | backend/packages/Domain/Staff/Services/PasswordGenerator.php |
| CreateStaffRequest | backend/app/Http/Requests/Staff/CreateStaffRequest.php |
| StaffAccountController | backend/app/Http/Controllers/Staff/StaffAccountController.php |
| Feature テスト | backend/tests/Feature/Staff/CreateStaffTest.php |

---

## タスク

### Design Tasks（外部設計）

- [ ] API レスポンス形式の確定
- [ ] エラーメッセージの確定
- [ ] 監査ログ形式の確定

### Spec Tasks（詳細設計）

- [ ] CreateStaffCommand 実装
- [ ] CreateStaffHandler 実装
- [ ] PasswordGenerator 実装
- [ ] CreateStaffRequest 実装
- [ ] StaffAccountController 実装
- [ ] ルーティング設定
- [ ] 監査ログ出力実装
- [ ] Feature テスト作成

---

## 変更履歴

| 日付 | 変更内容 |
|------|---------|
| 2025-12-26 | 初版作成 |
