# Data Model: 権限別メニュー表示

**Feature**: 003-role-based-menu
**Date**: 2025-12-26

## 1. 概要

本フィーチャーでは新規のデータモデルは作成しない。既存の Staff エンティティの `is_admin` フラグを活用して権限別のメニュー表示を実現する。

## 2. 既存データモデル

### 2.1 Staff（職員）

**テーブル名**: `staffs`

| カラム | 型 | 説明 |
|--------|------|------|
| id | char(26) | ULID 形式の主キー |
| email | varchar(255) | メールアドレス（ユニーク） |
| password | varchar(255) | ハッシュ化されたパスワード |
| name | varchar(100) | 職員名 |
| **is_admin** | boolean | **管理者フラグ（本フィーチャーで使用）** |
| is_locked | boolean | アカウントロック状態 |
| failed_login_attempts | int | ログイン失敗回数 |
| locked_at | timestamp | ロック日時 |
| created_at | timestamp | 作成日時 |
| updated_at | timestamp | 更新日時 |

### 2.2 Session（セッション）

**テーブル名**: `sessions`

| カラム | 型 | 説明 |
|--------|------|------|
| id | varchar(255) | セッションID |
| user_id | char(26) | 職員ID（staffs.id への参照） |
| ip_address | varchar(45) | IPアドレス |
| user_agent | text | ユーザーエージェント |
| payload | longtext | セッションデータ |
| last_activity | int | 最終アクティビティ（Unix タイムスタンプ） |

## 3. フロントエンド型定義の更新

### 3.1 現在の Staff 型

```typescript
// frontend/src/features/auth/types/auth.ts
export interface Staff {
  id: string
  name: string
  email: string
}
```

### 3.2 更新後の Staff 型

```typescript
// frontend/src/features/auth/types/auth.ts
export interface Staff {
  id: string
  name: string
  email: string
  is_admin: boolean  // 追加
}
```

### 3.3 関連する型の更新

```typescript
// StaffResponse は変更不要（data フィールドに Staff を含む）
export interface StaffResponse {
  data: Staff
}
```

## 4. API レスポンス

### 4.1 GET /api/auth/user

**レスポンス例（管理者）**:
```json
{
  "data": {
    "id": "01HY1234567890ABCDEFGHIJKL",
    "name": "管理者 太郎",
    "email": "admin@example.com",
    "is_admin": true
  }
}
```

**レスポンス例（一般職員）**:
```json
{
  "data": {
    "id": "01HY1234567890ABCDEFGHIJKM",
    "name": "職員 花子",
    "email": "staff@example.com",
    "is_admin": false
  }
}
```

### 4.2 POST /api/auth/login

ログイン成功時のレスポンスも同様に `is_admin` を含む。

## 5. データフロー

```
[ログイン]
    ↓
[AuthController.login]
    ↓
[StaffResponse.toArray() → is_admin を含む JSON]
    ↓
[フロントエンド: authStore.setAuthenticated(staff)]
    ↓
[currentUser.is_admin で権限判定]
    ↓
[管理メニュー表示/非表示]
```

## 6. マイグレーション

本フィーチャーではマイグレーションは不要。`is_admin` カラムは既存の staffs テーブルに存在する。

## 7. シーダー（テスト用）

テスト環境用のシーダーデータ（既存）:

```php
// 管理者アカウント
StaffRecord::create([
    'id' => StaffId::generate()->value(),
    'email' => 'admin@example.com',
    'password' => Hash::make('password123'),
    'name' => 'テスト管理者',
    'is_admin' => true,
    'is_locked' => false,
    'failed_login_attempts' => 0,
]);

// 一般職員アカウント
StaffRecord::create([
    'id' => StaffId::generate()->value(),
    'email' => 'staff@example.com',
    'password' => Hash::make('password123'),
    'name' => 'テスト職員',
    'is_admin' => false,
    'is_locked' => false,
    'failed_login_attempts' => 0,
]);
```
