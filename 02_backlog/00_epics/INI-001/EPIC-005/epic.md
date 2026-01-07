# EPIC-005: 職員アカウント無効化機能

最終更新: 2025-12-26

---

## 概要

管理者が職員アカウントを無効化する機能を実装する。無効化された職員はシステムにログインできなくなり、全セッションが即時終了する。データは論理削除として保持され、必要に応じて再有効化が可能。

---

## 関連情報

| 項目 | 値 |
|------|-----|
| イニシアチブ | [INI-001: 認証・利用者管理基盤](../../../../01_vision/initiatives/INI-001/charter.md) |
| Use Case | [UC-001-005: 職員アカウント無効化](../../../../01_vision/initiatives/INI-001/usecases/UC-001-005_職員アカウント無効化.md) |
| 優先度 | Must |
| ステータス | Planned |

---

## ビジネス価値

退職・異動した職員のシステムアクセス権を適切に停止し、セキュリティを維持する。
論理削除により履歴データを保持しつつ、必要に応じて再有効化できる柔軟性を提供する。

---

## 受け入れ条件

1. 管理者が職員アカウントを無効化できること
2. 無効化時に理由の入力が必須であること
3. 無効化された職員がログインできなくなること
4. 無効化時に対象職員の全セッションが即時終了すること
5. 管理者が自分自身を無効化できないこと
6. 最後の管理者アカウントを無効化できないこと
7. 無効化されたアカウントを再有効化できること
8. 無効化操作が監査ログに記録されること

---

## 画面一覧

| 画面ID | 画面名 | パス | 説明 |
|--------|--------|------|------|
| SCR-001-003 | 職員アカウント一覧 | `/staff/accounts` | 無効化ボタン表示 |

---

## User Story 一覧

| ID | Story 名 | ポイント | 優先度 | ステータス |
|----|----------|---------|--------|----------|
| [ST-001](./stories/ST-001_職員アカウント無効化API実装.md) | 職員アカウント無効化 API の実装 | 3 | Must | Planned |
| [ST-002](./stories/ST-002_職員アカウント無効化UI実装.md) | 職員アカウント無効化 UI の実装 | 2 | Must | Planned |
| [ST-003](./stories/ST-003_職員アカウント再有効化機能実装.md) | 職員アカウント再有効化機能の実装 | 2 | Must | Planned |

---

## 成果物

| 成果物 | 配置場所 | 説明 |
|--------|---------|------|
| DeactivateStaffHandler | backend/packages/Domain/Staff/Application/UseCases/Commands/DeactivateStaff/ | 無効化処理 |
| ReactivateStaffHandler | backend/packages/Domain/Staff/Application/UseCases/Commands/ReactivateStaff/ | 再有効化処理 |
| StaffAccountController（更新） | backend/app/Http/Controllers/Staff/StaffAccountController.php | 無効化エンドポイント |
| DeactivateDialog | frontend/src/features/staff/components/DeactivateDialog.tsx | 無効化確認ダイアログ |

---

## 技術仕様

### API エンドポイント

| メソッド | エンドポイント | 説明 | 認証 | 権限 |
|----------|---------------|------|------|------|
| DELETE | `/api/staff/accounts/{id}` | 職員無効化（論理削除） | 必須 | 管理者 |
| POST | `/api/staff/accounts/{id}/reactivate` | 職員再有効化 | 必須 | 管理者 |

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

#### POST /api/staff/accounts/{id}/reactivate

**成功レスポンス (200):**
```json
{
  "message": "職員アカウントを再有効化しました",
  "staff": {
    "id": "01HV...",
    "name": "田中 花子",
    "isActive": true
  }
}
```

### セッション無効化

```php
// DeactivateStaffHandler
public function handle(DeactivateStaffCommand $command): void
{
    $staff = $this->staffRepository->findById(new StaffId($command->staffId));

    // 自己無効化チェック
    if ($command->currentUserId === $command->staffId) {
        throw new SelfDeactivationException();
    }

    // 最後の管理者チェック
    if ($staff->role() === Role::ADMIN && $this->staffRepository->countActiveAdmins() === 1) {
        throw new LastAdminException();
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
```

---

## 依存関係

### 前提条件

| Epic ID | Epic 名 | 関係 |
|---------|---------|------|
| EPIC-003 | 職員アカウント作成機能 | 職員一覧機能が完了していること |
| EPIC-004 | 職員アカウント編集機能 | 編集画面から無効化へ遷移 |

### 後続タスク

なし（本 Epic で職員管理機能は完了）

---

## 非機能要件

| 項目 | 要件 |
|------|------|
| パフォーマンス | 無効化処理は3秒以内に完了する |
| セキュリティ | 無効化時に全セッションを即時無効化 |
| 監査 | 無効化操作と理由を security チャンネルに記録 |
| データ保持 | 論理削除によりデータを保持（物理削除しない） |

---

## ビジネスルール

| ルールID | ルール内容 |
|----------|-----------|
| BR-UC001-05-01 | 管理者は自分自身のアカウントを無効化できない |
| BR-UC001-05-02 | システムに最低1人の管理者が存在する必要がある |
| BR-UC001-05-03 | 無効化時は対象職員の全セッションを即時終了する |
| BR-UC001-05-04 | 無効化されたアカウントのデータは論理削除として保持 |
| BR-UC001-05-05 | 無効化操作は監査ログに記録される（理由含む） |
| BR-UC001-05-06 | 無効化されたアカウントは再有効化が可能 |

---

## 関連ドキュメント

- [UC-001-005: 職員アカウント無効化](../../../../01_vision/initiatives/INI-001/usecases/UC-001-005_職員アカウント無効化.md)
- [ワイヤーフレーム: 職員アカウント一覧](../../../../01_vision/initiatives/INI-001/ui/wireframes/staff-accounts-list.md)
- [セキュリティ標準](../../../../00_docs/20_tech/99_standard/security/00_Overview.md)

---

## 変更履歴

| 日付 | 変更内容 |
|------|---------|
| 2025-12-26 | 初版作成 |
