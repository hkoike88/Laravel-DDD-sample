# Research: 権限別メニュー表示

**Feature**: 003-role-based-menu
**Date**: 2025-12-26

## 1. 既存コード調査

### 1.1 バックエンド（認証・認可）

#### Staff エンティティと is_admin フラグ

`backend/packages/Domain/Staff/Domain/Model/Staff.php` で `isAdmin()` メソッドが定義されている。

```php
public function isAdmin(): bool
```

#### StaffResponse DTO

`backend/packages/Domain/Staff/Application/DTOs/Auth/StaffResponse.php` で API レスポンスに `is_admin` を含めている。

```php
public function toArray(): array
{
    return [
        'id' => $this->id,
        'name' => $this->name,
        'email' => $this->email,
        'is_admin' => $this->isAdmin,  // ← 既に返却されている
    ];
}
```

#### 既存ミドルウェア

- `AbsoluteSessionTimeout.php`: 絶対タイムアウト
- `ConcurrentSessionLimit.php`: 同時ログイン制限
- `auth:sanctum`: Sanctum による認証

管理者権限チェック用のミドルウェアは未実装。

#### ルート定義

`backend/routes/api.php`:
- `/api/auth/login` - ログイン
- `/api/auth/logout` - ログアウト（auth:sanctum + absolute.timeout）
- `/api/auth/user` - ユーザー情報取得（auth:sanctum + absolute.timeout）

管理者専用ルート（/staff/accounts 等）は未定義。

### 1.2 フロントエンド（認証状態管理）

#### Staff 型定義

`frontend/src/features/auth/types/auth.ts`:

```typescript
export interface Staff {
  id: string
  name: string
  email: string
  // is_admin が含まれていない ← 修正が必要
}
```

**課題**: バックエンドは `is_admin` を返しているが、フロントエンドの型定義に含まれていない。

#### 認証ストア

`frontend/src/features/auth/stores/authStore.ts`:
- `currentUser: Staff | null`
- `isAdmin()` ヘルパー関数は未定義

#### ダッシュボードページ

`frontend/src/features/dashboard/pages/DashboardPage.tsx`:
- `currentUser` から権限チェックを追加可能
- 現在は単一の `MenuGrid` のみ表示

#### メニュー項目

`frontend/src/features/dashboard/constants/menuItems.tsx`:
- 5 つの業務メニュー（蔵書管理、貸出処理、返却処理、利用者管理、予約管理）
- すべて一般職員向け

管理メニュー項目は未定義。

### 1.3 認可パターン

#### 既存の AuthGuard

`frontend/src/components/guards/AuthGuard.tsx`（推定パス）:
- 認証済みでない場合にログインページへリダイレクト

AdminGuard は未実装。

## 2. 技術的アプローチ

### 2.1 フロントエンド変更

1. **Staff 型に is_admin を追加**
   ```typescript
   export interface Staff {
     id: string
     name: string
     email: string
     is_admin: boolean  // 追加
   }
   ```

2. **AdminMenuSection コンポーネント作成**
   - `currentUser.is_admin` が true の場合のみ表示
   - 管理メニュー項目をグリッド表示

3. **DashboardPage の更新**
   - 業務メニューの後に AdminMenuSection を条件付きで表示

4. **AdminGuard コンポーネント作成**
   - 管理者でない場合に 403 エラーページまたはダッシュボードへリダイレクト

### 2.2 バックエンド変更

1. **RequireAdmin ミドルウェア作成**
   ```php
   class RequireAdmin
   {
       public function handle(Request $request, Closure $next): Response
       {
           $user = $request->user();
           if (!$user || !$user->is_admin) {
               return response()->json([
                   'message' => 'この操作を行う権限がありません',
               ], 403);
           }
           return $next($request);
       }
   }
   ```

2. **管理者専用ルートの追加**
   ```php
   Route::prefix('staff')->middleware(['auth:sanctum', 'require.admin'])->group(function () {
       Route::get('/accounts', [StaffAccountController::class, 'index']);
       // 将来の管理者専用エンドポイント
   });
   ```

## 3. 依存関係

### 既存機能への依存

- セッション管理機能（005-session-management）: ✅ 実装済み
- is_admin フラグ: ✅ Staff エンティティに実装済み
- 認証 API（is_admin を含む）: ✅ 実装済み

### 後続機能

- 職員管理 CRUD: 本フィーチャー後に実装
  - /staff/accounts への遷移先として必要

## 4. セキュリティ考慮事項

### 多層防御

1. **UI レベル**: メニュー非表示（一般職員）
2. **ルーティングレベル**: AdminGuard（フロントエンド）
3. **API レベル**: RequireAdmin ミドルウェア（バックエンド）

### 権限情報の信頼性

- 権限情報はセッション確立時に取得
- セッション中の権限変更は次回ログイン時に反映（spec で定義済み）
- 権限情報が null/undefined の場合は一般職員として扱う（FR-007）

## 5. テスト戦略

### バックエンド

- 管理者が /staff/accounts にアクセス → 200
- 一般職員が /staff/accounts にアクセス → 403
- 未ログインが /staff/accounts にアクセス → 401

### フロントエンド

- 管理者ログイン → AdminMenuSection 表示
- 一般職員ログイン → AdminMenuSection 非表示
- 一般職員が /staff/accounts に直接アクセス → 403 ページ表示

## 6. 結論

既存の認証基盤が整っているため、追加実装は最小限で済む：

1. フロントエンドの Staff 型に `is_admin` を追加
2. 管理メニューセクションと管理者ガードを追加
3. バックエンドに管理者権限ミドルウェアを追加

管理者専用ルート（/staff/accounts）は本フィーチャーでは「アクセス制御のみ」を実装し、実際の CRUD 機能は後続フィーチャーで実装する。
