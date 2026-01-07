# EPIC-004: 職員アカウント編集機能

最終更新: 2025-12-26

---

## 概要

管理者が既存の職員アカウント情報を編集する機能を実装する。氏名、メールアドレス、権限の変更、およびパスワードのリセットが可能。楽観的ロックによる同時編集の競合検出を行う。

---

## 関連情報

| 項目 | 値 |
|------|-----|
| イニシアチブ | [INI-001: 認証・利用者管理基盤](../../../../01_vision/initiatives/INI-001/charter.md) |
| Use Case | [UC-001-004: 職員アカウント編集](../../../../01_vision/initiatives/INI-001/usecases/UC-001-004_職員アカウント編集.md) |
| 優先度 | Must |
| ステータス | Planned |

---

## ビジネス価値

管理者が職員情報を適切に管理し、権限の変更やパスワードリセットを行えるようにする。
職員の異動や役割変更に対応し、システムアクセス権を適切に維持する。

---

## 受け入れ条件

1. 管理者が職員アカウントの氏名、メールアドレス、権限を編集できること
2. メールアドレスの一意性が検証されること
3. 管理者が自分自身の権限を変更できないこと
4. 最後の管理者アカウントの権限を一般職員に変更できないこと
5. パスワードをリセットできること
6. パスワードリセット時に新しい一時パスワードが表示されること
7. 更新成功時に職員一覧画面にリダイレクトされること
8. 変更操作が監査ログに記録されること
9. 同時編集時に競合が検出されること

---

## 画面一覧

| 画面ID | 画面名 | パス | 説明 |
|--------|--------|------|------|
| SCR-001-005 | 職員アカウント編集 | `/staff/accounts/{id}/edit` | 職員情報編集フォーム |

---

## User Story 一覧

| ID | Story 名 | ポイント | 優先度 | ステータス |
|----|----------|---------|--------|----------|
| [ST-001](./stories/ST-001_職員アカウント編集API実装.md) | 職員アカウント編集 API の実装 | 3 | Must | Planned |
| [ST-002](./stories/ST-002_職員アカウント編集UI実装.md) | 職員アカウント編集 UI の実装 | 3 | Must | Planned |
| [ST-003](./stories/ST-003_パスワードリセット機能実装.md) | パスワードリセット機能の実装 | 2 | Must | Planned |

---

## 成果物

| 成果物 | 配置場所 | 説明 |
|--------|---------|------|
| UpdateStaffHandler | backend/packages/Domain/Staff/Application/UseCases/Commands/UpdateStaff/ | 職員更新処理 |
| ResetPasswordHandler | backend/packages/Domain/Staff/Application/UseCases/Commands/ResetPassword/ | パスワードリセット処理 |
| StaffAccountController（更新） | backend/app/Http/Controllers/Staff/StaffAccountController.php | 編集エンドポイント |
| UpdateStaffRequest | backend/app/Http/Requests/Staff/UpdateStaffRequest.php | バリデーション |
| StaffAccountsEditPage | frontend/src/pages/staff/StaffAccountsEditPage.tsx | 編集画面 |

---

## 技術仕様

### API エンドポイント

| メソッド | エンドポイント | 説明 | 認証 | 権限 |
|----------|---------------|------|------|------|
| GET | `/api/staff/accounts/{id}` | 職員詳細取得 | 必須 | 管理者 |
| PUT | `/api/staff/accounts/{id}` | 職員更新 | 必須 | 管理者 |
| POST | `/api/staff/accounts/{id}/reset-password` | パスワードリセット | 必須 | 管理者 |

### リクエスト/レスポンス

#### PUT /api/staff/accounts/{id}

**リクエスト:**
```json
{
  "name": "田中 花子",
  "email": "tanaka@example.com",
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
    "email": "tanaka@example.com",
    "role": "admin",
    "updatedAt": "2025-12-26T10:30:00+09:00"
  }
}
```

**競合エラーレスポンス (409):**
```json
{
  "message": "他のユーザーによって更新されています。最新の情報を確認してください"
}
```

**自己権限変更エラーレスポンス (422):**
```json
{
  "message": "自分自身の権限は変更できません"
}
```

**最後の管理者エラーレスポンス (422):**
```json
{
  "message": "最後の管理者アカウントの権限は変更できません"
}
```

#### POST /api/staff/accounts/{id}/reset-password

**成功レスポンス (200):**
```json
{
  "message": "パスワードをリセットしました",
  "temporaryPassword": "NewPass123!@#"
}
```

### 楽観的ロック

```php
// UpdateStaffHandler
public function handle(UpdateStaffCommand $command): Staff
{
    $staff = $this->staffRepository->findById(new StaffId($command->id));

    if ($staff->updatedAt()->format('c') !== $command->updatedAt) {
        throw new OptimisticLockException(
            '他のユーザーによって更新されています'
        );
    }

    // 更新処理...
}
```

---

## 依存関係

### 前提条件

| Epic ID | Epic 名 | 関係 |
|---------|---------|------|
| EPIC-003 | 職員アカウント作成機能 | 職員一覧・作成機能が完了していること |

### 後続タスク

| Epic ID | Epic 名 | 関係 |
|---------|---------|------|
| EPIC-005 | 職員アカウント無効化機能 | 編集画面から無効化へ遷移 |

---

## 非機能要件

| 項目 | 要件 |
|------|------|
| パフォーマンス | 情報更新は3秒以内に完了する |
| セキュリティ | リセットパスワードは bcrypt (cost=12) で暗号化 |
| セキュリティ | 管理者権限チェックを必須とする |
| 監査 | アカウント編集操作を security チャンネルに記録 |
| 整合性 | 楽観的ロックで同時編集の競合を検出 |

---

## ビジネスルール

| ルールID | ルール内容 |
|----------|-----------|
| BR-UC001-04-01 | メールアドレスはシステム内で一意であること |
| BR-UC001-04-02 | 管理者は自分自身の管理者権限を変更できない |
| BR-UC001-04-03 | パスワードリセット時は次回ログインでパスワード変更を強制（Phase 2） |
| BR-UC001-04-04 | 最後の管理者アカウントの権限を一般職員に変更できない |
| BR-UC001-04-05 | 変更操作は監査ログに記録される |

---

## 関連ドキュメント

- [UC-001-004: 職員アカウント編集](../../../../01_vision/initiatives/INI-001/usecases/UC-001-004_職員アカウント編集.md)
- [ワイヤーフレーム: 職員アカウント編集](../../../../01_vision/initiatives/INI-001/ui/wireframes/staff-accounts-edit.md)
- [セキュリティ標準](../../../../00_docs/20_tech/99_standard/security/00_Overview.md)

---

## 変更履歴

| 日付 | 変更内容 |
|------|---------|
| 2025-12-26 | 初版作成 |
