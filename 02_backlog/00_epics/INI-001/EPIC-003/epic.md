# EPIC-003: 職員アカウント作成機能

最終更新: 2025-12-26

---

## 概要

管理者が新しい職員アカウントを作成する機能を実装する。職員の基本情報（氏名、メールアドレス）と権限（一般職員/管理者）を設定し、初期パスワードを生成してシステムへのアクセスを可能にする。

---

## 関連情報

| 項目 | 値 |
|------|-----|
| イニシアチブ | [INI-001: 認証・利用者管理基盤](../../../../01_vision/initiatives/INI-001/charter.md) |
| Use Case | [UC-001-003: 職員アカウント作成](../../../../01_vision/initiatives/INI-001/usecases/UC-001-003_職員アカウント作成.md) |
| 優先度 | Must |
| ステータス | Planned |

---

## ビジネス価値

管理者が新しい職員をシステムに登録し、業務機能へのアクセス権を付与できるようにする。
適切な権限管理により、職員の役割に応じたシステム利用を実現する。

---

## 受け入れ条件

1. 管理者のみが職員アカウント作成画面にアクセスできること
2. 氏名、メールアドレス、権限を入力して職員アカウントを作成できること
3. メールアドレスの一意性が検証されること
4. 初期パスワードが自動生成されること
5. 作成成功時に職員一覧画面にリダイレクトされること
6. 作成成功時にメッセージが表示されること
7. アカウント作成操作が監査ログに記録されること
8. アカウント作成が3秒以内に完了すること

---

## 画面一覧

| 画面ID | 画面名 | パス | 説明 |
|--------|--------|------|------|
| SCR-001-003 | 職員アカウント一覧 | `/staff/accounts` | 職員一覧表示 |
| SCR-001-004 | 職員アカウント登録 | `/staff/accounts/new` | 新規職員登録フォーム |

---

## User Story 一覧

| ID | Story 名 | ポイント | 優先度 | ステータス |
|----|----------|---------|--------|----------|
| [ST-001](./stories/ST-001_職員アカウント作成API実装.md) | 職員アカウント作成 API の実装 | 3 | Must | Planned |
| [ST-002](./stories/ST-002_職員アカウント作成UI実装.md) | 職員アカウント作成 UI の実装 | 3 | Must | Planned |
| [ST-003](./stories/ST-003_職員一覧API実装.md) | 職員一覧 API の実装 | 2 | Must | Planned |
| [ST-004](./stories/ST-004_職員一覧UI実装.md) | 職員一覧 UI の実装 | 2 | Must | Planned |

---

## 成果物

| 成果物 | 配置場所 | 説明 |
|--------|---------|------|
| CreateStaffHandler | backend/packages/Domain/Staff/Application/UseCases/Commands/CreateStaff/ | 職員作成処理 |
| StaffAccountController | backend/app/Http/Controllers/Staff/StaffAccountController.php | 職員管理API |
| CreateStaffRequest | backend/app/Http/Requests/Staff/CreateStaffRequest.php | バリデーション |
| StaffAccountsNewPage | frontend/src/pages/staff/StaffAccountsNewPage.tsx | 作成画面 |
| StaffAccountsListPage | frontend/src/pages/staff/StaffAccountsListPage.tsx | 一覧画面 |

---

## 技術仕様

### API エンドポイント

| メソッド | エンドポイント | 説明 | 認証 | 権限 |
|----------|---------------|------|------|------|
| GET | `/api/staff/accounts` | 職員一覧取得 | 必須 | 管理者 |
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
    "email": ["このメールアドレスは既に登録されています"]
  }
}
```

**権限エラーレスポンス (403):**
```json
{
  "message": "この操作を行う権限がありません"
}
```

### 入力項目

| 項目 | 必須 | 形式 | バリデーション |
|------|:----:|------|---------------|
| 氏名 | ○ | 文字列 | 50文字以内 |
| メールアドレス | ○ | メール形式 | 一意制約、255文字以内 |
| 権限 | ○ | 選択 | staff / admin |

### 初期パスワード生成

```php
// PasswordGenerator
class PasswordGenerator
{
    public function generate(int $length = 16): string
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
        return collect(range(1, $length))
            ->map(fn() => $chars[random_int(0, strlen($chars) - 1)])
            ->implode('');
    }
}
```

---

## 依存関係

### 前提条件

| Epic ID | Epic 名 | 関係 |
|---------|---------|------|
| EPIC-001 | 職員ログイン機能 | 認証基盤が完了していること |
| EPIC-002 | 職員ダッシュボード機能 | 管理メニューが実装されていること |

### 後続タスク

| Epic ID | Epic 名 | 関係 |
|---------|---------|------|
| EPIC-004 | 職員アカウント編集機能 | 作成後の編集 |
| EPIC-005 | 職員アカウント無効化機能 | 作成後の無効化 |

---

## 非機能要件

| 項目 | 要件 |
|------|------|
| パフォーマンス | アカウント作成は3秒以内に完了する |
| セキュリティ | 初期パスワードは bcrypt (cost=12) で暗号化 |
| セキュリティ | 管理者権限チェックを必須とする |
| 監査 | アカウント作成操作を security チャンネルに記録 |

---

## ビジネスルール

| ルールID | ルール内容 |
|----------|-----------|
| BR-UC001-03-01 | メールアドレスはシステム内で一意であること |
| BR-UC001-03-02 | 初期パスワードはセキュリティ要件を満たすランダム文字列（16文字） |
| BR-UC001-03-03 | 初回ログイン時にパスワード変更を強制する（Phase 2） |
| BR-UC001-03-04 | 管理者権限の付与は既存管理者のみ可能 |

---

## 関連ドキュメント

- [UC-001-003: 職員アカウント作成](../../../../01_vision/initiatives/INI-001/usecases/UC-001-003_職員アカウント作成.md)
- [ワイヤーフレーム: 職員アカウント登録](../../../../01_vision/initiatives/INI-001/ui/wireframes/staff-accounts-new.md)
- [ワイヤーフレーム: 職員アカウント一覧](../../../../01_vision/initiatives/INI-001/ui/wireframes/staff-accounts-list.md)
- [セキュリティ標準](../../../../00_docs/20_tech/99_standard/security/00_Overview.md)

---

## 変更履歴

| 日付 | 変更内容 |
|------|---------|
| 2025-12-26 | 初版作成 |
