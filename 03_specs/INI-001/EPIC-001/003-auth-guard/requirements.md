# Requirements Checklist: 認証ガードの実装

**Purpose**: ST-005 の受け入れ条件を満たすための品質チェックリスト
**Created**: 2025-12-26
**Feature**: [spec.md](./spec.md)

## 受け入れ条件チェック

### 基本機能

- [x] CHK001 未ログイン状態で保護されたページにアクセスするとログイン画面にリダイレクトされること
  - 実装済み: `ProtectedRoute.tsx` で対応
- [x] CHK002 ログイン状態でログイン画面にアクセスするとダッシュボードにリダイレクトされること
  - 実装済み: `GuestRoute.tsx` で対応
- [x] CHK003 認証状態がグローバルに管理されていること
  - 実装済み: `authStore.ts` で Zustand による管理
- [x] CHK004 ページ遷移時に認証状態がチェックされること
  - 実装済み: `ProtectedRoute.tsx` と `GuestRoute.tsx` で各ルートに適用
- [ ] CHK005 セッション切れ時に自動でログイン画面にリダイレクトされること
  - 部分実装: API 呼び出し時の 401 エラーハンドリングは未実装

## 実装コンポーネント

### 認証ガード

- [x] CHK006 `ProtectedRoute` コンポーネントが実装されていること
- [x] CHK007 `GuestRoute` コンポーネントが実装されていること
- [x] CHK008 `AuthProvider` コンポーネントが実装されていること
- [ ] CHK009 `useAuth` フックが実装されていること

### ルーティング

- [x] CHK010 `/dashboard` が `ProtectedRoute` で保護されていること
- [x] CHK011 `/books/manage` が `ProtectedRoute` で保護されていること
- [x] CHK012 `/loans/checkout` が `ProtectedRoute` で保護されていること
- [x] CHK013 `/loans/return` が `ProtectedRoute` で保護されていること
- [x] CHK014 `/users` が `ProtectedRoute` で保護されていること
- [x] CHK015 `/reservations` が `ProtectedRoute` で保護されていること
- [x] CHK016 `/login` が `GuestRoute` でラップされていること

### ローディング表示

- [x] CHK017 認証確認中にローディング表示が表示されること
- [x] CHK018 ローディング表示が適切にスタイリングされていること

## テスト

### 単体テスト

- [ ] CHK019 `ProtectedRoute` の単体テストが作成されていること
- [ ] CHK020 `GuestRoute` の単体テストが作成されていること
- [ ] CHK021 `authStore` の単体テストが作成されていること
- [ ] CHK022 `useAuth` フックの単体テストが作成されていること
- [ ] CHK023 `useAuthCheck` フックの単体テストが作成されていること

### E2E テスト

- [x] CHK024 未認証時のダッシュボードアクセスがテストされていること
  - 実装済み: `dashboard.spec.ts` の「認証状態」セクション
- [ ] CHK025 認証済み時のログイン画面アクセスがテストされていること
- [x] CHK026 ログアウト後のアクセス制御がテストされていること
  - 実装済み: `dashboard.spec.ts` の「ログアウト後」テスト

## アクセシビリティ

- [x] CHK027 ローディング表示が視覚的に認識できること
- [ ] CHK028 ローディング状態がスクリーンリーダーで認識できること

## セキュリティ

- [x] CHK029 認証状態が不正に書き換えられないこと
- [x] CHK030 リダイレクトが `replace` で行われ、戻るボタンでアクセスできないこと

## Notes

- `ProtectedRoute` と `GuestRoute` はストーリーの `AuthGuard` と `GuestGuard` に相当
- 基本機能は 003-login-ui と 004-dashboard-ui で実装済み
- 本フィーチャーでは `useAuth` フックの追加とテスト拡充が主な作業
- セッション期限切れの自動検知は将来の拡張として対応予定
