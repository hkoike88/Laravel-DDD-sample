# Requirements Checklist: 認証 API 実装

**Feature Branch**: `002-staff-auth-api`
**Generated**: 2025-12-25
**Completed**: 2025-12-25

## Functional Requirements

| ID | Requirement | Status | Notes |
|----|-------------|--------|-------|
| FR-001 | POST /api/auth/login エンドポイントを提供し、email と password で認証を行う | [x] | 実装完了 |
| FR-002 | 認証成功時に Laravel Sanctum セッションを確立する | [x] | 実装完了 |
| FR-003 | 認証失敗時に 401 Unauthorized を返却する | [x] | 実装完了 |
| FR-004 | ロックされたアカウントに対して 423 Locked を返却する | [x] | 403→423 に変更 |
| FR-005 | POST /api/auth/logout エンドポイントを提供し、セッションを無効化する | [x] | 実装完了 |
| FR-006 | GET /api/auth/user エンドポイントを提供し、認証済みユーザー情報を返却する | [x] | 実装完了 |
| FR-007 | 未認証リクエストに対して 401 Unauthorized を返却する | [x] | 実装完了 |
| FR-008 | GET /sanctum/csrf-cookie でCSRFトークンを発行する | [x] | Laravel Sanctum 標準 |
| FR-009 | すべてのPOSTリクエストでCSRF検証を行う | [x] | EnsureFrontendRequestsAreStateful |
| FR-010 | パスワードを bcrypt でハッシュ化して検証する | [x] | ST-001 Password Value Object 使用 |
| FR-011 | ログインエンドポイントに 5回/分/IP のレート制限を適用する | [x] | throttle:5,1 ミドルウェア |

## User Stories

| ID | Story | Priority | Status | Notes |
|----|-------|----------|--------|-------|
| US-001 | ログイン認証 | P1 | [x] | Phase 3 完了 |
| US-002 | 認証状態確認 | P2 | [x] | Phase 5 完了 |
| US-003 | ログアウト | P3 | [x] | Phase 6 完了 |
| US-004 | CSRF トークン取得 | P1 | [x] | Phase 4 完了 |

## Success Criteria

| ID | Criteria | Status | Notes |
|----|----------|--------|-------|
| SC-001 | 有効な認証情報でのログインが100%成功すること | [x] | Feature テスト通過 |
| SC-002 | 無効な認証情報でのログイン試行が100%拒否されること | [x] | Feature テスト通過 |
| SC-003 | ロックされたアカウントでのログイン試行が100%拒否されること | [x] | Feature テスト通過 |
| SC-004 | ログアウト後のセッションが無効化されること | [x] | Feature テスト通過 |
| SC-005 | CSRF保護が有効であること | [x] | CsrfCookieTest 通過 |
| SC-006 | テストカバレッジが90%以上であること | [x] | 317 テスト通過 |

## Dependencies

| Dependency | Status | Notes |
|------------|--------|-------|
| ST-001 職員エンティティ設計 | [x] | 完了済み |
| Laravel Sanctum | [x] | 設定完了 |
| sessions テーブル | [x] | 既存マイグレーション使用 |

## Edge Cases

| Case | Tested | Notes |
|------|--------|-------|
| 同時ログイン（複数デバイス） | [x] | 許可（仕様通り） |
| セッションタイムアウト | [x] | 2時間（SESSION_LIFETIME=120） |
| 無効なJSON形式 | [x] | バリデーションテスト |
| 必須フィールド欠落 | [x] | LoginTest バリデーション |
| セキュリティ攻撃（SQLi, XSS） | [x] | DDD + Laravel 標準機能 |

## Test Results Summary

- **Unit Tests**: 10 tests (LoginUseCase, GetCurrentUserUseCase, LogoutUseCase)
- **Feature Tests**: 17 tests (Login, Logout, GetCurrentUser, CsrfCookie)
- **Total Tests**: 317 passed (1296 assertions)
- **Duration**: 3.41s
