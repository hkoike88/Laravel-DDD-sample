# Feature Specification: 認証ガードの実装

**Feature Branch**: `005-auth-guard`
**Created**: 2025-12-26
**Status**: Draft
**Input**: ST-005: 認証ガードの実装 - 未ログインユーザーが職員向け画面にアクセスした場合にログイン画面にリダイレクトし、認証が必要な機能を保護する

## 現状分析

本フィーチャーの基本機能は既に以下のコンポーネントで実装済み：

| コンポーネント | 配置場所 | 状態 |
|--------------|---------|------|
| ProtectedRoute | frontend/src/features/auth/components/ProtectedRoute.tsx | 実装済み |
| GuestRoute | frontend/src/features/auth/components/GuestRoute.tsx | 実装済み |
| authStore | frontend/src/features/auth/stores/authStore.ts | 実装済み |
| AuthProvider | frontend/src/features/auth/components/AuthProvider.tsx | 実装済み |
| useAuthCheck | frontend/src/features/auth/hooks/useAuthCheck.ts | 実装済み |
| router.tsx | frontend/src/app/router.tsx | 実装済み |

**追加実装が必要な項目**:
1. useAuth フック（authStore のラッパー）の追加（設計整合性のため）
2. 単体テストの追加
3. E2E テストによる受け入れ条件の検証

## User Scenarios & Testing *(mandatory)*

### User Story 1 - 未認証ユーザーの保護ページアクセス制御 (Priority: P1)

システムとして、未ログインユーザーが職員向け画面にアクセスした場合、ログイン画面にリダイレクトしたい。

**Why this priority**: セキュリティの根幹機能。認証が必要なページを保護しなければシステム全体が脆弱になる。

**Independent Test**: 未認証状態でダッシュボードURLに直接アクセスし、ログイン画面にリダイレクトされることを確認する。

**Acceptance Scenarios**:

1. **Given** 未ログイン状態で、**When** `/dashboard` にアクセスすると、**Then** `/login` にリダイレクトされる
2. **Given** 未ログイン状態で、**When** `/books/manage` にアクセスすると、**Then** `/login` にリダイレクトされる
3. **Given** 未ログイン状態で、**When** `/loans/checkout` にアクセスすると、**Then** `/login` にリダイレクトされる
4. **Given** 未ログイン状態で、**When** 任意の保護ページにアクセスすると、**Then** `/login` にリダイレクトされる

---

### User Story 2 - 認証済みユーザーのログイン画面アクセス制御 (Priority: P1)

認証済みユーザーがログイン画面にアクセスした場合、ダッシュボードにリダイレクトしたい。

**Why this priority**: 認証済みユーザーがログイン画面にアクセスするのは不自然であり、ダッシュボードに誘導することでUXを向上させる。

**Independent Test**: ログイン済み状態でログインURLに直接アクセスし、ダッシュボードにリダイレクトされることを確認する。

**Acceptance Scenarios**:

1. **Given** ログイン済み状態で、**When** `/login` にアクセスすると、**Then** `/dashboard` にリダイレクトされる
2. **Given** ログイン済み状態で、**When** ブラウザの戻るボタンでログイン画面に戻ろうとしても、**Then** `/dashboard` に留まる

---

### User Story 3 - 認証状態のグローバル管理 (Priority: P1)

認証状態がアプリケーション全体で一元管理され、どのページからでも参照できるようにしたい。

**Why this priority**: 認証状態がグローバルに管理されていないと、ページごとに認証チェックが分散し、一貫性のないセキュリティになる。

**Independent Test**: 複数のページで認証状態が一貫していることを確認する。ログアウト後、全ての保護ページでリダイレクトされることを検証する。

**Acceptance Scenarios**:

1. **Given** ログイン済み状態で、**When** 複数の保護ページを遷移しても、**Then** 認証状態が維持される
2. **Given** ログアウト後、**When** 任意の保護ページにアクセスすると、**Then** ログイン画面にリダイレクトされる
3. **Given** アプリケーション起動時、**When** セッションが有効な場合、**Then** 認証状態が自動的に復元される

---

### User Story 4 - ページ遷移時の認証チェック (Priority: P2)

ページ遷移のたびに認証状態がチェックされ、セキュリティが維持されるようにしたい。

**Why this priority**: リアルタイムの認証チェックにより、セッション期限切れ後も不正アクセスを防止できる。

**Independent Test**: ページ遷移時に認証チェックが行われることを確認する。

**Acceptance Scenarios**:

1. **Given** 認証済み状態で、**When** 別の保護ページに遷移すると、**Then** 認証チェックが行われ正常に遷移する
2. **Given** 認証確認中の状態で、**When** 保護ページにアクセスすると、**Then** ローディング表示が表示される

---

### User Story 5 - セッション期限切れ時の自動リダイレクト (Priority: P2)

セッションが期限切れになった場合、次の操作時に自動でログイン画面にリダイレクトしたい。

**Why this priority**: セキュリティ上重要だが、通常の認証ガードで対応可能なため、専用の実装は後続フェーズで対応。

**Independent Test**: セッション期限切れ後のAPI呼び出しで401エラーが返却された場合、ログイン画面にリダイレクトされることを確認する。

**Acceptance Scenarios**:

1. **Given** セッションが期限切れの状態で、**When** API を呼び出すと、**Then** 401 エラーが検出されログイン画面にリダイレクトされる
2. **Given** セッション期限切れでリダイレクトされた場合、**When** 再ログインすると、**Then** 元のページに戻る（将来の拡張）

---

### Edge Cases

- 認証API がタイムアウトした場合、エラーメッセージを表示しログイン画面に遷移する
- ブラウザのセッションストレージが無効な場合でも動作する
- 複数タブでのログイン/ログアウトが一貫して反映される（将来の拡張）

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: システムは未認証ユーザーが保護ページにアクセスした場合、ログイン画面（`/login`）にリダイレクトしなければならない
- **FR-002**: システムは認証済みユーザーがログイン画面にアクセスした場合、ダッシュボード（`/dashboard`）にリダイレクトしなければならない
- **FR-003**: システムは認証状態を Zustand ストアでグローバルに管理しなければならない
- **FR-004**: システムはアプリケーション起動時に認証状態を確認しなければならない
- **FR-005**: 認証確認中は適切なローディング表示をしなければならない
- **FR-006**: システムは `ProtectedRoute` コンポーネントで保護ページをラップしなければならない
- **FR-007**: システムは `GuestRoute` コンポーネントでゲスト専用ページをラップしなければならない
- **FR-008**: システムは `AuthProvider` でアプリケーション全体をラップし、認証初期化を行わなければならない
- **FR-009**: 認証チェック用の `useAuth` フックを提供しなければならない

### Key Entities

- **AuthState**: 認証状態を表す。isAuthenticated（認証済みフラグ）、currentUser（現在のユーザー）、isLoading（確認中フラグ）を持つ
- **Staff**: ログイン中のユーザー。ID、名前、メールアドレスを持つ
- **ProtectedRoute**: 認証必須ページのラッパーコンポーネント
- **GuestRoute**: ゲスト専用ページのラッパーコンポーネント

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: 未認証ユーザーが保護ページにアクセスした場合、1秒以内にログイン画面にリダイレクトされる
- **SC-002**: 認証済みユーザーがログイン画面にアクセスした場合、1秒以内にダッシュボードにリダイレクトされる
- **SC-003**: アプリケーション起動時の認証確認が3秒以内に完了する
- **SC-004**: 全ての保護ページ（6画面：ダッシュボード、蔵書管理、貸出処理、返却処理、利用者管理、予約管理）が ProtectedRoute で保護されている
- **SC-005**: 認証ガードの単体テストカバレッジが80%以上である
- **SC-006**: E2E テストで全ての受け入れ条件が検証されている

## Assumptions

- 認証 API（`/api/auth/user`）は実装済みで、セッションが有効な場合にユーザー情報を返す
- ログイン機能は既に実装済み（003-login-ui）
- ダッシュボード画面は既に実装済み（004-dashboard-ui）
- TanStack Query によるデータフェッチとキャッシュ管理を使用
- Zustand による状態管理を使用
- React Router 7.x による SPA ルーティングを使用

## 既存実装との差異

| 項目 | ストーリー定義 | 既存実装 | 対応 |
|-----|--------------|---------|------|
| AuthGuard | AuthGuard.tsx | ProtectedRoute.tsx | 名称は異なるが同等機能。変更不要 |
| GuestGuard | GuestGuard.tsx | GuestRoute.tsx | 名称は異なるが同等機能。変更不要 |
| useAuth | hooks/useAuth.ts | 未実装（authStore直接使用） | 追加実装 |
| 単体テスト | 必須 | 未実装 | 追加実装 |
| E2E テスト | 必須 | 部分的に実装（dashboard.spec.ts） | 拡充 |
