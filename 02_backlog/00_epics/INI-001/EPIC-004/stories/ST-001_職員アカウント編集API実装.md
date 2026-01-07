# ST-001: 職員アカウント編集 API の実装

最終更新: 2025-12-26

---

## ストーリー

**フロントエンド開発者として**、職員アカウント編集 API を利用したい。
**なぜなら**、フロントエンドから職員情報を更新したいからだ。

---

## 関連情報

| 項目 | 値 |
|------|-----|
| Epic | [EPIC-004: 職員アカウント編集機能](../epic.md) |
| ポイント | 3 |
| 優先度 | Must |
| ステータス | Planned |

---

## 受け入れ条件

1. [ ] `GET /api/staff/accounts/{id}` で職員詳細を取得できること
2. [ ] `PUT /api/staff/accounts/{id}` で職員情報を更新できること
3. [ ] メールアドレスの一意性が検証されること（自身を除く）
4. [ ] 管理者が自分自身の権限を変更しようとすると 422 が返ること
5. [ ] 最後の管理者の権限を変更しようとすると 422 が返ること
6. [ ] 楽観的ロックで競合が検出されると 409 が返ること
7. [ ] 管理者以外がアクセスすると 403 が返ること
8. [ ] 更新操作が監査ログに記録されること

---

## 技術仕様

### API エンドポイント

| メソッド | エンドポイント | 説明 | 認証 | 権限 |
|----------|---------------|------|------|------|
| GET | `/api/staff/accounts/{id}` | 職員詳細取得 | 必須 | 管理者 |
| PUT | `/api/staff/accounts/{id}` | 職員更新 | 必須 | 管理者 |

### リクエスト/レスポンス

#### GET /api/staff/accounts/{id}

**成功レスポンス (200):**
```json
{
  "staff": {
    "id": "01HV...",
    "name": "田中 花子",
    "email": "tanaka@example.com",
    "role": "staff",
    "isActive": true,
    "createdAt": "2025-12-26T10:00:00+09:00",
    "updatedAt": "2025-12-26T10:00:00+09:00"
  }
}
```

#### PUT /api/staff/accounts/{id}

**リクエスト:**
```json
{
  "name": "田中 花子",
  "email": "tanaka.hanako@example.com",
  "role": "admin",
  "updatedAt": "2025-12-26T10:00:00+09:00"
}
```

**成功レスポンス (200):**
```json
{
  "message": "職員情報を更新しました",
  "staff": {
    "id": "01HV...",
    "name": "田中 花子",
    "email": "tanaka.hanako@example.com",
    "role": "admin",
    "updatedAt": "2025-12-26T10:30:00+09:00"
  }
}
```

### バリデーション

```php
// UpdateStaffRequest
public function rules(): array
{
    $staffId = $this->route('id');

    return [
        'name' => ['required', 'string', 'max:50'],
        'email' => ['required', 'email', 'max:255', Rule::unique('staffs')->ignore($staffId)],
        'role' => ['required', 'in:staff,admin'],
        'updatedAt' => ['required', 'date'],
    ];
}
```

### UseCase 設計

```php
// UpdateStaffHandler
class UpdateStaffHandler
{
    public function handle(UpdateStaffCommand $command): Staff
    {
        $staff = $this->staffRepository->findById(new StaffId($command->id));

        // 楽観的ロックチェック
        if ($staff->updatedAt()->format('c') !== $command->updatedAt) {
            throw new OptimisticLockException(
                '他のユーザーによって更新されています'
            );
        }

        // 自己権限変更チェック
        if ($command->currentUserId === $command->id &&
            $staff->role() !== Role::from($command->role)) {
            throw new SelfRoleChangeException(
                '自分自身の権限は変更できません'
            );
        }

        // 最後の管理者チェック
        if ($staff->role() === Role::ADMIN &&
            $command->role === 'staff' &&
            $this->staffRepository->countAdmins() === 1) {
            throw new LastAdminException(
                '最後の管理者アカウントの権限は変更できません'
            );
        }

        $staff->update(
            name: new StaffName($command->name),
            email: new Email($command->email),
            role: Role::from($command->role),
        );

        $this->staffRepository->save($staff);

        $this->logger->channel('security')->info('職員情報を更新しました', [
            'staff_id' => $staff->id()->value(),
            'updated_by' => $command->currentUserId,
        ]);

        return $staff;
    }
}
```

---

## 成果物

| 成果物 | 配置場所 |
|--------|---------|
| UpdateStaffCommand | backend/packages/Domain/Staff/Application/UseCases/Commands/UpdateStaff/UpdateStaffCommand.php |
| UpdateStaffHandler | backend/packages/Domain/Staff/Application/UseCases/Commands/UpdateStaff/UpdateStaffHandler.php |
| UpdateStaffRequest | backend/app/Http/Requests/Staff/UpdateStaffRequest.php |
| OptimisticLockException | backend/packages/Domain/Shared/Exceptions/OptimisticLockException.php |
| SelfRoleChangeException | backend/packages/Domain/Staff/Exceptions/SelfRoleChangeException.php |
| LastAdminException | backend/packages/Domain/Staff/Exceptions/LastAdminException.php |
| Feature テスト | backend/tests/Feature/Staff/UpdateStaffTest.php |

---

## タスク

### Design Tasks（外部設計）

- [ ] API レスポンス形式の確定
- [ ] エラーメッセージの確定
- [ ] 楽観的ロック仕様の確定

### Spec Tasks（詳細設計）

- [ ] UpdateStaffCommand 実装
- [ ] UpdateStaffHandler 実装
- [ ] 例外クラス実装
- [ ] UpdateStaffRequest 実装
- [ ] StaffAccountController に show/update メソッド追加
- [ ] StaffRepository に countAdmins メソッド追加
- [ ] 監査ログ出力実装
- [ ] Feature テスト作成

---

## 変更履歴

| 日付 | 変更内容 |
|------|---------|
| 2025-12-26 | 初版作成 |
