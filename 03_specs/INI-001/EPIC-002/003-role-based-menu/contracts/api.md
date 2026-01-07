# API Contract: 権限別メニュー表示

**Feature**: 003-role-based-menu
**Date**: 2025-12-26

## 1. 概要

本フィーチャーで追加・変更する API エンドポイントを定義する。

## 2. 既存 API の確認

### 2.1 GET /api/auth/user

認証済み職員の情報を取得する（変更なし）。

**認証**: 必須（Laravel Sanctum）

**レスポンス**:

```json
{
  "data": {
    "id": "string (ULID)",
    "name": "string",
    "email": "string",
    "is_admin": "boolean"
  }
}
```

**注意**: `is_admin` は既に返却されているが、フロントエンドの型定義に含まれていないため、型定義の更新が必要。

## 3. 新規 API

### 3.1 GET /api/staff/accounts

職員アカウント一覧を取得する（管理者専用）。

**認証**: 必須（Laravel Sanctum + 管理者権限）

**ミドルウェア**: `auth:sanctum`, `absolute.timeout`, `require.admin`

**成功レスポンス** (200 OK):

```json
{
  "data": [
    {
      "id": "string (ULID)",
      "name": "string",
      "email": "string",
      "is_admin": "boolean",
      "is_locked": "boolean",
      "created_at": "string (ISO 8601)"
    }
  ],
  "meta": {
    "total": "number",
    "page": "number",
    "per_page": "number"
  }
}
```

**注意**: 本フィーチャーでは API のプレースホルダー（403 制御のみ）を実装。実際のレスポンスは後続の職員管理フィーチャーで実装する。

**エラーレスポンス**:

| ステータス | 説明 | レスポンス |
|-----------|------|-----------|
| 401 | 未認証 | `{ "message": "Unauthenticated." }` |
| 403 | 権限なし | `{ "message": "この操作を行う権限がありません" }` |
| 419 | セッション期限切れ | `{ "message": "ページの有効期限が切れました" }` |

## 4. エラーレスポンス形式

### 4.1 403 Forbidden

```json
{
  "message": "この操作を行う権限がありません"
}
```

### 4.2 401 Unauthorized

```json
{
  "message": "Unauthenticated."
}
```

## 5. ミドルウェア

### 5.1 RequireAdmin

管理者権限を持つ職員のみアクセスを許可するミドルウェア。

**動作**:
1. 認証済みユーザーを取得
2. ユーザーが存在しない、または `is_admin` が false の場合は 403 を返す
3. それ以外の場合はリクエストを次へ渡す

**実装場所**: `app/Http/Middleware/RequireAdmin.php`

**登録**: `bootstrap/app.php` で `require.admin` エイリアスとして登録

## 6. ルート定義

```php
// routes/api.php

// 管理者専用ルート
Route::prefix('staff')
    ->middleware(['auth:sanctum', 'absolute.timeout', 'require.admin'])
    ->group(function () {
        // 職員アカウント一覧（プレースホルダー）
        Route::get('/accounts', function () {
            return response()->json([
                'data' => [],
                'meta' => [
                    'total' => 0,
                    'page' => 1,
                    'per_page' => 20,
                ],
            ]);
        })->name('staff.accounts.index');
    });
```

**注意**: 実際のコントローラー実装は後続の職員管理フィーチャーで行う。

## 7. フロントエンド API クライアント

### 7.1 権限チェックヘルパー（オプション）

```typescript
// frontend/src/features/auth/utils/permissions.ts
export const isAdmin = (user: Staff | null): boolean => {
  return user?.is_admin === true
}
```

### 7.2 API エラーハンドリング

403 エラー発生時の処理:

```typescript
// frontend/src/lib/api/client.ts
axios.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 403) {
      // 権限エラーページへリダイレクト、またはエラー表示
    }
    return Promise.reject(error)
  }
)
```
