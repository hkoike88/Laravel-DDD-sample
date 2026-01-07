# Quickstart: 職員アカウント編集機能

**Date**: 2026-01-06
**Feature**: EPIC-004 職員アカウント編集機能

## Prerequisites

- Docker Desktop が起動していること
- EPIC-003（職員アカウント作成機能）が完了していること
- 管理者アカウントでログイン可能な状態であること

## Setup

```bash
# リポジトリルートに移動
cd /home/koike/workspace/practice/Laravel-DDD-sample/sample-001

# ブランチを確認
git branch --show-current
# → 001-epic-004-staff-account-edit

# Docker コンテナを起動
docker compose up -d

# バックエンドの依存関係を更新
docker compose exec backend composer install

# フロントエンドの依存関係を更新
docker compose exec frontend npm install
```

## Development Workflow

### バックエンド

```bash
# PHPStan 静的解析
docker compose exec backend ./vendor/bin/phpstan analyse

# Pint コードフォーマット
docker compose exec backend ./vendor/bin/pint

# テスト実行
docker compose exec backend ./vendor/bin/pest

# 特定テストの実行
docker compose exec backend ./vendor/bin/pest tests/Unit/Packages/Domain/Staff/Application/UseCases/Commands/UpdateStaff
```

### フロントエンド

```bash
# TypeScript チェック
docker compose exec frontend npm run typecheck

# ESLint
docker compose exec frontend npm run lint

# Prettier
docker compose exec frontend npm run format

# テスト実行
docker compose exec frontend npm run test

# 開発サーバー（すでに起動中）
# http://localhost:5173
```

## Key Files to Modify/Create

### Backend (Priority Order)

1. **Domain Exceptions** (新規)
   - `backend/packages/Domain/Staff/Domain/Exceptions/OptimisticLockException.php`
   - `backend/packages/Domain/Staff/Domain/Exceptions/SelfRoleChangeException.php`
   - `backend/packages/Domain/Staff/Domain/Exceptions/LastAdminProtectionException.php`

2. **Repository Interface** (修正)
   - `backend/packages/Domain/Staff/Domain/Repositories/StaffRepositoryInterface.php`
   - 追加: `countAdmins(): int`

3. **Repository Implementation** (修正)
   - `backend/packages/Domain/Staff/Application/Repositories/EloquentStaffRepository.php`
   - 追加: `countAdmins()` の実装

4. **DTOs** (新規)
   - `backend/packages/Domain/Staff/Application/DTO/StaffAccount/UpdateStaffInput.php`
   - `backend/packages/Domain/Staff/Application/DTO/StaffAccount/UpdateStaffOutput.php`
   - `backend/packages/Domain/Staff/Application/DTO/StaffAccount/ResetPasswordOutput.php`
   - `backend/packages/Domain/Staff/Application/DTO/StaffAccount/StaffDetailOutput.php`

5. **Use Cases** (新規)
   - `backend/packages/Domain/Staff/Application/UseCases/Queries/GetStaffDetail/`
   - `backend/packages/Domain/Staff/Application/UseCases/Commands/UpdateStaff/`
   - `backend/packages/Domain/Staff/Application/UseCases/Commands/ResetPassword/`

6. **Audit Logger** (修正)
   - `backend/packages/Domain/Staff/Infrastructure/AuditLog/StaffAuditLogger.php`
   - 追加: `logStaffUpdated()`, `logPasswordReset()`

7. **Controller & Request** (修正/新規)
   - `backend/packages/Domain/Staff/Presentation/HTTP/Controllers/StaffAccountController.php`
   - `backend/packages/Domain/Staff/Presentation/HTTP/Requests/UpdateStaffRequest.php`

8. **Routes** (修正)
   - `backend/routes/api.php`

### Frontend (Priority Order)

1. **Types** (修正)
   - `frontend/src/features/staff-accounts/types/staffAccount.ts`

2. **API Client** (修正)
   - `frontend/src/features/staff-accounts/api/staffAccountsApi.ts`

3. **Schemas** (新規)
   - `frontend/src/features/staff-accounts/schemas/updateStaffSchema.ts`

4. **Hooks** (新規)
   - `frontend/src/features/staff-accounts/hooks/useStaffDetail.ts`
   - `frontend/src/features/staff-accounts/hooks/useUpdateStaff.ts`
   - `frontend/src/features/staff-accounts/hooks/useResetPassword.ts`

5. **Components** (新規)
   - `frontend/src/features/staff-accounts/components/StaffEditForm.tsx`
   - `frontend/src/features/staff-accounts/components/PasswordResetDialog.tsx`

6. **Pages** (新規)
   - `frontend/src/pages/staff/StaffAccountsEditPage.tsx`

7. **Router** (修正)
   - `frontend/src/app/router.tsx`

## Testing

### Manual Testing

1. ブラウザで http://localhost:5173 にアクセス
2. 管理者アカウントでログイン
3. ダッシュボードから「職員管理」を選択
4. 一覧から任意の職員の「編集」をクリック
5. 以下のシナリオをテスト:
   - 氏名の変更 → 保存成功
   - メールアドレスの変更 → 保存成功
   - 権限の変更 → 保存成功（条件付き）
   - パスワードリセット → 一時パスワード表示
   - 同時編集の競合 → エラー表示

### API Testing (curl)

```bash
# CSRF トークン取得
curl -c cookies.txt http://localhost/sanctum/csrf-cookie

# ログイン
XSRF_TOKEN=$(grep XSRF-TOKEN cookies.txt | awk '{print $7}' | python3 -c "import sys, urllib.parse; print(urllib.parse.unquote(sys.stdin.read().strip()))")
curl -b cookies.txt -c cookies.txt -X POST http://localhost/api/auth/login \
  -H "Content-Type: application/json" \
  -H "X-XSRF-TOKEN: $XSRF_TOKEN" \
  -d '{"email":"admin@example.com","password":"password123"}'

# 職員詳細取得
curl -b cookies.txt -H "X-XSRF-TOKEN: $XSRF_TOKEN" \
  http://localhost/api/staff/accounts/{staff-id}

# 職員更新
curl -b cookies.txt -X PUT http://localhost/api/staff/accounts/{staff-id} \
  -H "Content-Type: application/json" \
  -H "X-XSRF-TOKEN: $XSRF_TOKEN" \
  -d '{"name":"更新後の名前","email":"updated@example.com","role":"staff","updatedAt":"2026-01-06T10:00:00+09:00"}'

# パスワードリセット
curl -b cookies.txt -X POST http://localhost/api/staff/accounts/{staff-id}/reset-password \
  -H "X-XSRF-TOKEN: $XSRF_TOKEN"
```

## Commit Guidelines

```bash
# コミット前のチェック（pre-commit hook で自動実行）
docker compose exec backend ./vendor/bin/phpstan analyse
docker compose exec backend ./vendor/bin/pint --test
docker compose exec frontend npm run lint
docker compose exec frontend npm run format:check
docker compose exec frontend npm run typecheck

# コミットメッセージ形式
git commit -m "feat(staff): 職員アカウント編集機能の実装"
```

## Related Documents

- [spec.md](./spec.md) - 機能仕様
- [research.md](./research.md) - 技術調査結果
- [data-model.md](./data-model.md) - データモデル
- [contracts/openapi.yaml](./contracts/openapi.yaml) - API仕様
- [EPIC-004 Epic](../../02_backlog/00_epics/INI-001/EPIC-004/epic.md) - エピック定義
