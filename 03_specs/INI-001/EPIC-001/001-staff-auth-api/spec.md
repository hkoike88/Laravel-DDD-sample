# Feature Specification: 認証 API 実装

**Feature Branch**: `002-staff-auth-api`
**Created**: 2025-12-25
**Status**: Draft
**Input**: ST-002_認証API実装.md

## Clarifications

### Session 2025-12-25

- Q: 同一職員が複数デバイスから同時にログインした場合の動作は？ → A: 複数デバイス同時ログイン許可（セッション独立）
- Q: セッションの有効期限は？ → A: 2時間（標準的な業務アプリケーション向け）
- Q: ログイン試行のレート制限は？ → A: 5回/分（標準的なセキュリティ設定）

---

## User Scenarios & Testing *(mandatory)*

### User Story 1 - ログイン認証 (Priority: P1)

職員がメールアドレスとパスワードを使用してシステムにログインできる。

**Why this priority**: ログイン機能は認証システムの中核であり、他のすべての認証関連機能の前提条件となる。

**Independent Test**: 有効な認証情報でPOST /api/auth/login を呼び出し、認証セッションが確立され職員情報が返却されることで検証可能。

**Acceptance Scenarios**:

1. **Given** 有効な認証情報を持つ職員が存在する, **When** 正しいメールアドレスとパスワードでログインリクエストを送信, **Then** 認証が成功し職員情報（id, name, email）が返却される
2. **Given** 職員が存在する, **When** 誤ったパスワードでログインリクエストを送信, **Then** 401エラーと「認証情報が正しくありません」メッセージが返却される
3. **Given** アカウントがロックされた職員, **When** 正しい認証情報でログインを試行, **Then** 403エラーと「アカウントがロックされています」メッセージが返却される
4. **Given** 存在しないメールアドレス, **When** ログインリクエストを送信, **Then** 401エラーが返却される（情報漏洩防止のため同じエラー）

---

### User Story 2 - 認証状態確認 (Priority: P2)

ログイン済みの職員が自身の情報を取得できる。

**Why this priority**: フロントエンドがセッション状態を確認し、ユーザー情報を表示するために必要。

**Independent Test**: ログイン後にGET /api/auth/user を呼び出し、現在の職員情報が返却されることで検証可能。

**Acceptance Scenarios**:

1. **Given** 認証済みセッションが存在する, **When** GET /api/auth/user を呼び出す, **Then** 現在ログイン中の職員情報が返却される
2. **Given** 認証されていない状態, **When** GET /api/auth/user を呼び出す, **Then** 401エラーが返却される

---

### User Story 3 - ログアウト (Priority: P3)

ログイン済みの職員がシステムからログアウトできる。

**Why this priority**: セキュリティ上必須だが、ログイン・認証確認より優先度は低い。

**Independent Test**: ログイン後にPOST /api/auth/logout を呼び出し、セッションが無効化されることで検証可能。

**Acceptance Scenarios**:

1. **Given** 認証済みセッションが存在する, **When** POST /api/auth/logout を呼び出す, **Then** セッションが無効化され成功レスポンスが返却される
2. **Given** ログアウト後, **When** GET /api/auth/user を呼び出す, **Then** 401エラーが返却される

---

### User Story 4 - CSRF トークン取得 (Priority: P1)

フロントエンドがCSRFトークンを取得してセキュアなリクエストを送信できる。

**Why this priority**: SPAからの認証リクエストに必須のセキュリティ機能。

**Independent Test**: GET /sanctum/csrf-cookie を呼び出し、XSRF-TOKENクッキーが設定されることで検証可能。

**Acceptance Scenarios**:

1. **Given** 新規ブラウザセッション, **When** GET /sanctum/csrf-cookie を呼び出す, **Then** XSRF-TOKENクッキーが設定される
2. **Given** CSRFトークンを取得済み, **When** X-XSRF-TOKENヘッダーを付与してPOSTリクエスト, **Then** CSRF検証が成功する

---

### Edge Cases

- 同時ログイン: 複数デバイスからの同時ログインを許可し、各セッションは独立して管理される
- セッションタイムアウト: 2時間経過後は 401 Unauthorized を返却し、再ログインを要求
- レート制限超過: 5回/分を超えた場合は 429 Too Many Requests を返却
- 無効なJSON形式のリクエストボディ
- 必須フィールドの欠落（email または password が未指定）
- SQLインジェクション・XSS攻撃の入力

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: System MUST POST /api/auth/login エンドポイントを提供し、email と password で認証を行う
- **FR-002**: System MUST 認証成功時に Laravel Sanctum セッションを確立する
- **FR-003**: System MUST 認証失敗時に 401 Unauthorized を返却する
- **FR-004**: System MUST ロックされたアカウントに対して 403 Forbidden を返却する
- **FR-005**: System MUST POST /api/auth/logout エンドポイントを提供し、セッションを無効化する
- **FR-006**: System MUST GET /api/auth/user エンドポイントを提供し、認証済みユーザー情報を返却する
- **FR-007**: System MUST 未認証リクエストに対して 401 Unauthorized を返却する
- **FR-008**: System MUST GET /sanctum/csrf-cookie でCSRFトークンを発行する（Laravel Sanctum 標準）
- **FR-009**: System MUST すべてのPOSTリクエストでCSRF検証を行う
- **FR-010**: System MUST パスワードを bcrypt でハッシュ化して検証する（ST-001 の Password Value Object を使用）
- **FR-011**: System MUST ログインエンドポイントに対してレート制限（5回/分/IP）を適用する

### Key Entities

- **Staff**: 認証対象の職員エンティティ（ST-001 で実装済み）
  - StaffId, Email, Password, StaffName, isLocked 属性を持つ
  - verifyPassword() メソッドでパスワード検証
- **Session**: Laravel Sanctum が管理するセッション情報
  - sessions テーブルで管理（Laravel 標準）

### API Specification

#### POST /api/auth/login

**Request Body**:
```json
{
  "email": "staff@example.com",
  "password": "password123"
}
```

**Success Response (200)**:
```json
{
  "data": {
    "id": "01HXYZ...",
    "name": "山田太郎",
    "email": "staff@example.com"
  }
}
```

**Error Response (401)**:
```json
{
  "message": "認証情報が正しくありません"
}
```

**Error Response (403)**:
```json
{
  "message": "アカウントがロックされています"
}
```

#### POST /api/auth/logout

**Success Response (200)**:
```json
{
  "message": "ログアウトしました"
}
```

#### GET /api/auth/user

**Success Response (200)**:
```json
{
  "data": {
    "id": "01HXYZ...",
    "name": "山田太郎",
    "email": "staff@example.com"
  }
}
```

**Error Response (401)**:
```json
{
  "message": "Unauthenticated."
}
```

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: 有効な認証情報でのログインが100%成功すること
- **SC-002**: 無効な認証情報でのログイン試行が100%拒否されること
- **SC-003**: ロックされたアカウントでのログイン試行が100%拒否されること
- **SC-004**: ログアウト後のセッションが無効化され、保護されたエンドポイントへのアクセスが拒否されること
- **SC-005**: CSRF保護が有効であり、CSRFトークンなしのPOSTリクエストが拒否されること
- **SC-006**: すべての認証APIエンドポイントに対するテストカバレッジが90%以上であること

## Dependencies

- **ST-001**: 職員エンティティ設計（完了済み）
  - Staff エンティティ、StaffRepositoryInterface、Password Value Object を使用
- **Laravel Sanctum**: セッションベース認証とCSRF保護
- **MySQL sessions テーブル**: セッション管理用

## Technical Notes

- Laravel Sanctum の SPA 認証モードを使用
- フロントエンドは同一ドメインまたは SANCTUM_STATEFUL_DOMAINS で設定されたドメインから接続
- セッションドライバは database を使用（sessions テーブル）
- セッション有効期限: 2時間（SESSION_LIFETIME=120）
- 認証 Controller は Application 層に配置
- Staff エンティティの verifyPassword() メソッドを認証ロジックで使用
