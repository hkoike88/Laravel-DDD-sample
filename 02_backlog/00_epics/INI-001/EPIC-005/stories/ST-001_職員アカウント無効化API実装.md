# ST-001: 職員アカウント無効化 API の実装

最終更新: 2025-12-26

---

## ストーリー

**フロントエンド開発者として**、職員アカウント無効化 API を利用したい。
**なぜなら**、フロントエンドから職員アカウントを無効化したいからだ。

---

## 関連情報

| 項目 | 値 |
|------|-----|
| Epic | [EPIC-005: 職員アカウント無効化機能](../epic.md) |
| ポイント | 3 |
| 優先度 | Must |
| ステータス | Planned |

---

## 受け入れ条件

1. [ ] `DELETE /api/staff/accounts/{id}` で職員アカウントを無効化できること
2. [ ] 無効化理由が必須であること
3. [ ] 無効化された職員の全セッションが削除されること
4. [ ] 管理者が自分自身を無効化しようとすると 422 が返ること
5. [ ] 最後の管理者を無効化しようとすると 422 が返ること
6. [ ] 既に無効化済みのアカウントを無効化しようとすると 422 が返ること
7. [ ] 管理者以外がアクセスすると 403 が返ること
8. [ ] 無効化操作が監査ログに記録されること（理由含む）

---

## 技術仕様

### API エンドポイント

| メソッド | エンドポイント | 説明 | 認証 | 権限 |
|----------|---------------|------|------|------|
| DELETE | `/api/staff/accounts/{id}` | 職員無効化 | 必須 | 管理者 |

### リクエスト/レスポンス

#### DELETE /api/staff/accounts/{id}

**リクエスト:**
```json
{
  "reason": "退職のため"
}
```

**成功レスポンス (200):**
```json
{
  "message": "職員アカウントを無効化しました"
}
```

**バリデーションエラーレスポンス (422):**
```json
{
  "message": "入力内容に誤りがあります",
  "errors": {
    "reason": ["無効化理由を入力してください"]
  }
}
```

**自己無効化エラーレスポンス (422):**
```json
{
  "message": "自分自身のアカウントは無効化できません"
}
```

**最後の管理者エラーレスポンス (422):**
```json
{
  "message": "最後の管理者アカウントは無効化できません"
}
```

**既に無効化済みレスポンス (422):**
```json
{
  "message": "このアカウントは既に無効化されています"
}
```

### バリデーション

```php
// DeactivateStaffRequest
public function rules(): array
{
    return [
        'reason' => ['required', 'string', 'max:200'],
    ];
}

public function messages(): array
{
    return [
        'reason.required' => '無効化理由を入力してください',
        'reason.max' => '無効化理由は200文字以内で入力してください',
    ];
}
```

### UseCase 設計

```php
// DeactivateStaffHandler
class DeactivateStaffHandler
{
    public function handle(DeactivateStaffCommand $command): void
    {
        $staff = $this->staffRepository->findById(new StaffId($command->staffId));

        // 自己無効化チェック
        if ($command->currentUserId === $command->staffId) {
            throw new SelfDeactivationException(
                '自分自身のアカウントは無効化できません'
            );
        }

        // 既に無効化済みチェック
        if (!$staff->isActive()) {
            throw new AlreadyDeactivatedException(
                'このアカウントは既に無効化されています'
            );
        }

        // 最後の管理者チェック
        if ($staff->role() === Role::ADMIN &&
            $this->staffRepository->countActiveAdmins() === 1) {
            throw new LastAdminException(
                '最後の管理者アカウントは無効化できません'
            );
        }

        // 無効化
        $staff->deactivate($command->reason);
        $this->staffRepository->save($staff);

        // 全セッション削除
        DB::table('sessions')->where('user_id', $command->staffId)->delete();

        // 監査ログ
        $this->logger->channel('security')->info('職員アカウントを無効化しました', [
            'staff_id' => $command->staffId,
            'reason' => $command->reason,
            'deactivated_by' => $command->currentUserId,
        ]);
    }
}
```

### Controller 実装

```php
// StaffAccountController
public function destroy(DeactivateStaffRequest $request, string $id): JsonResponse
{
    $this->deactivateStaffHandler->handle(
        new DeactivateStaffCommand(
            staffId: $id,
            reason: $request->reason,
            currentUserId: auth()->id(),
        )
    );

    return response()->json([
        'message' => '職員アカウントを無効化しました',
    ]);
}
```

---

## 成果物

| 成果物 | 配置場所 |
|--------|---------|
| DeactivateStaffCommand | backend/packages/Domain/Staff/Application/UseCases/Commands/DeactivateStaff/DeactivateStaffCommand.php |
| DeactivateStaffHandler | backend/packages/Domain/Staff/Application/UseCases/Commands/DeactivateStaff/DeactivateStaffHandler.php |
| DeactivateStaffRequest | backend/app/Http/Requests/Staff/DeactivateStaffRequest.php |
| SelfDeactivationException | backend/packages/Domain/Staff/Exceptions/SelfDeactivationException.php |
| AlreadyDeactivatedException | backend/packages/Domain/Staff/Exceptions/AlreadyDeactivatedException.php |
| Feature テスト | backend/tests/Feature/Staff/DeactivateStaffTest.php |

---

## タスク

### Design Tasks（外部設計）

- [ ] API レスポンス形式の確定
- [ ] エラーメッセージの確定

### Spec Tasks（詳細設計）

- [ ] DeactivateStaffCommand 実装
- [ ] DeactivateStaffHandler 実装
- [ ] 例外クラス実装
- [ ] Staff エンティティに deactivate メソッド追加
- [ ] DeactivateStaffRequest 実装
- [ ] StaffAccountController に destroy メソッド追加
- [ ] StaffRepository に countActiveAdmins メソッド追加
- [ ] セッション削除処理実装
- [ ] 監査ログ出力実装
- [ ] Feature テスト作成

---

## 変更履歴

| 日付 | 変更内容 |
|------|---------|
| 2025-12-26 | 初版作成 |
