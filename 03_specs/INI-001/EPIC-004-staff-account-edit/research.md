# Research: 職員アカウント編集機能

**Date**: 2026-01-06
**Feature**: EPIC-004 職員アカウント編集機能

## 1. 楽観的ロック実装パターン

### Decision
Laravelの `updated_at` タイムスタンプを使用した楽観的ロックを実装する。

### Rationale
- 既存の `staffs` テーブルに `updated_at` カラムが存在する
- Laravelの標準的なタイムスタンプ機能を活用できる
- 追加のバージョンカラムが不要
- EPIC-004のエピックドキュメントで示されているパターンと一致

### Alternatives Considered
| 代替案 | 不採用理由 |
|--------|-----------|
| バージョン番号カラム追加 | テーブル変更が必要、既存の updated_at で十分 |
| 悲観的ロック（SELECT FOR UPDATE） | 同時編集の頻度が低く、オーバーヘッドが大きい |
| ETag ヘッダー方式 | 実装複雑度が高い、updated_at で十分 |

### Implementation Notes
- フロントエンドは編集画面読み込み時に `updated_at` を取得
- 更新リクエスト時に `updated_at` を送信
- バックエンドで現在の `updated_at` と比較し、不一致なら 409 Conflict を返す

---

## 2. 自己権限変更防止の実装

### Decision
UpdateStaffHandler内で操作者IDと対象職員IDを比較し、一致かつ権限変更の場合はエラーを返す。

### Rationale
- ドメイン層でビジネスルールを集約
- UIでも権限フィールドを無効化することで二重防御
- EPIC-004のビジネスルール BR-UC001-04-02 に準拠

### Alternatives Considered
| 代替案 | 不採用理由 |
|--------|-----------|
| ミドルウェアでの制御 | ビジネスロジックがインフラ層に漏れる |
| フロントエンドのみで制御 | APIを直接呼び出す場合に防げない |

---

## 3. 最後の管理者保護の実装

### Decision
StaffRepositoryInterfaceに `countAdmins()` メソッドを追加し、UpdateStaffHandlerで管理者数をチェックする。

### Rationale
- 管理者権限を一般職員に変更する前に管理者数を確認
- 管理者が1人の場合は変更を拒否
- EPIC-004のビジネスルール BR-UC001-04-04 に準拠

### Implementation Notes
```php
// StaffRepositoryInterface
public function countAdmins(): int;

// UpdateStaffHandler
if ($currentStaff->isAdmin() && !$input->isAdmin) {
    if ($this->staffRepository->countAdmins() <= 1) {
        throw new LastAdminProtectionException();
    }
}
```

---

## 4. 監査ログ記録の実装

### Decision
既存の StaffAuditLogger を拡張し、`logStaffUpdated()` と `logPasswordReset()` メソッドを追加する。

### Rationale
- EPIC-003で確立したパターンを継承
- セキュリティチャンネルへのログ出力を統一
- 変更内容の詳細を記録（変更前後の値）

### Implementation Notes
- 更新時: 操作者ID、対象職員ID、変更フィールド（before/after）、タイムスタンプ
- パスワードリセット時: 操作者ID、対象職員ID、タイムスタンプ（パスワード値は記録しない）

---

## 5. パスワードリセット実装

### Decision
EPIC-003で実装済みの PasswordGenerator サービスを再利用する。

### Rationale
- 既存のパスワード生成ロジックを再利用
- セキュリティ要件（16文字、英数字記号混合）を満たす
- bcrypt (cost=12) でのハッシュ化を統一

---

## 6. フロントエンド状態管理

### Decision
TanStack Query の mutation + 既存の authStore を使用する。

### Rationale
- EPIC-003で確立したパターンを継承
- サーバー状態とクライアント状態の分離
- キャッシュの自動無効化

### Implementation Notes
- `useStaffDetail`: 職員詳細の取得（キャッシュ対応）
- `useUpdateStaff`: 職員更新のmutation（成功時に一覧を再取得）
- `useResetPassword`: パスワードリセットのmutation
- 競合エラー（409）時は専用のエラーハンドリング

---

## 7. メールアドレス一意性検証

### Decision
既存の `existsByEmail()` メソッドを使用し、自分自身のメールアドレスは除外する。

### Rationale
- 他の職員と重複するメールアドレスへの変更を防止
- 自分自身のメールアドレスは変更なしでも許可

### Implementation Notes
```php
// UpdateStaffHandler
$newEmail = Email::create($input->email);
if ($newEmail->value() !== $currentStaff->email()->value()) {
    if ($this->staffRepository->existsByEmail($newEmail)) {
        throw new DuplicateEmailException($newEmail);
    }
}
```

---

## 8. API エンドポイント設計

### Decision
RESTful なエンドポイント設計を採用する。

### Endpoints
| Method | Path | Description |
|--------|------|-------------|
| GET | `/api/staff/accounts/{id}` | 職員詳細取得 |
| PUT | `/api/staff/accounts/{id}` | 職員更新 |
| POST | `/api/staff/accounts/{id}/reset-password` | パスワードリセット |

### Response Codes
- 200: 成功
- 400: バリデーションエラー
- 401: 未認証
- 403: 権限不足
- 404: 職員が見つからない
- 409: 競合（楽観的ロック）
- 422: ビジネスルール違反（自己権限変更、最後の管理者）

---

## Summary

すべての技術的な不明点は解消済み。既存のEPIC-003の実装パターンを継承しつつ、楽観的ロックや権限保護などの新規要件を追加実装する方針を確定。
