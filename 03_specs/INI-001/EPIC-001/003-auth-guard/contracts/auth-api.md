# API Contract: 認証 API

**Branch**: `005-auth-guard` | **Date**: 2025-12-26

## 概要

本ドキュメントは認証ガード機能が依存する既存の認証 API コントラクトを定義する。本フィーチャーでは新規 API の追加は行わず、既存 API を使用する。

## エンドポイント一覧

| Method | Endpoint | 説明 | 認証 |
|--------|----------|------|------|
| GET | /api/auth/user | 認証ユーザー情報取得 | 必須 |
| POST | /api/auth/login | ログイン | 不要 |
| POST | /api/auth/logout | ログアウト | 必須 |

## API 詳細

### GET /api/auth/user

現在認証されているユーザーの情報を取得する。認証ガードがアプリケーション起動時に呼び出し、セッションの有効性を確認する。

#### リクエスト

```http
GET /api/auth/user HTTP/1.1
Host: api.example.com
Accept: application/json
Cookie: laravel_session=xxx; XSRF-TOKEN=xxx
```

#### レスポンス

**成功 (200 OK)**

```json
{
  "data": {
    "id": "01HXYZ1234567890123456789A",
    "name": "山田太郎",
    "email": "staff@example.com"
  }
}
```

**未認証 (401 Unauthorized)**

```json
{
  "message": "Unauthenticated."
}
```

#### フロントエンドの処理

| レスポンス | 処理 |
|-----------|------|
| 200 OK | `setAuthenticated(user)` を呼び出し、認証状態を設定 |
| 401 Unauthorized | `clearAuthentication()` を呼び出し、未認証状態に設定 |
| Network Error | `clearAuthentication()` を呼び出し、ログイン画面へリダイレクト |

---

### POST /api/auth/login

ログイン処理。成功時にセッションクッキーが設定される。

#### リクエスト

```http
POST /api/auth/login HTTP/1.1
Host: api.example.com
Content-Type: application/json
Accept: application/json
X-XSRF-TOKEN: xxx

{
  "email": "staff@example.com",
  "password": "password123"
}
```

#### レスポンス

**成功 (200 OK)**

```json
{
  "data": {
    "id": "01HXYZ1234567890123456789A",
    "name": "山田太郎",
    "email": "staff@example.com"
  }
}
```

**認証失敗 (401 Unauthorized)**

```json
{
  "message": "メールアドレスまたはパスワードが正しくありません"
}
```

**バリデーションエラー (422 Unprocessable Entity)**

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": ["メールアドレスは必須です"],
    "password": ["パスワードは8文字以上で入力してください"]
  }
}
```

**アカウントロック (423 Locked)**

```json
{
  "message": "アカウントがロックされています"
}
```

**レート制限 (429 Too Many Requests)**

```json
{
  "message": "ログイン試行回数が上限に達しました。しばらくしてから再試行してください"
}
```

---

### POST /api/auth/logout

ログアウト処理。セッションを無効化する。

#### リクエスト

```http
POST /api/auth/logout HTTP/1.1
Host: api.example.com
Accept: application/json
Cookie: laravel_session=xxx; XSRF-TOKEN=xxx
X-XSRF-TOKEN: xxx
```

#### レスポンス

**成功 (204 No Content)**

（レスポンスボディなし）

**エラー (500 Internal Server Error)**

```json
{
  "message": "Server Error"
}
```

#### フロントエンドの処理

| レスポンス | 処理 |
|-----------|------|
| 204 No Content | `clearAuthentication()` を呼び出し、ログイン画面へリダイレクト |
| エラー | `clearAuthentication()` を呼び出し（ローカル状態はクリア）、ログイン画面へリダイレクト |

---

## CSRF トークン取得

Laravel Sanctum を使用しているため、認証 API 呼び出し前に CSRF トークンを取得する必要がある。

### GET /sanctum/csrf-cookie

#### リクエスト

```http
GET /sanctum/csrf-cookie HTTP/1.1
Host: api.example.com
```

#### レスポンス

**成功 (204 No Content)**

レスポンスヘッダーに `Set-Cookie: XSRF-TOKEN=xxx` が含まれる。

---

## TypeScript 型定義

```typescript
// リクエスト型
export interface LoginRequest {
  email: string
  password: string
}

// レスポンス型
export interface Staff {
  id: string
  name: string
  email: string
}

export interface StaffResponse {
  data: Staff
}

export interface MessageResponse {
  message: string
}

export interface ErrorResponse {
  message: string
}

export interface ValidationErrorResponse {
  message: string
  errors: Record<string, string[]>
}
```

---

## 認証フロー図

```
┌──────────────┐     ┌──────────────┐     ┌──────────────┐
│   Browser    │     │   Frontend   │     │   Backend    │
└──────┬───────┘     └──────┬───────┘     └──────┬───────┘
       │                    │                    │
       │  アプリ起動         │                    │
       │────────────────────▶                    │
       │                    │                    │
       │                    │ GET /api/auth/user │
       │                    │────────────────────▶
       │                    │                    │
       │                    │  200 OK / 401      │
       │                    │◀────────────────────
       │                    │                    │
       │  (200: 認証済み)    │                    │
       │  保護ページ表示     │                    │
       │◀────────────────────                    │
       │                    │                    │
       │  (401: 未認証)      │                    │
       │  ログイン画面表示   │                    │
       │◀────────────────────                    │
       │                    │                    │
```
