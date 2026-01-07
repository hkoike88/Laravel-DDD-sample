# Research: 職員ログアウト機能

**Feature Branch**: `001-staff-logout`
**Date**: 2026-01-06

---

## 既存実装の調査結果

### バックエンド実装状況

#### 1. LogoutUseCase (実装済み)
- **パス**: `backend/packages/Domain/Staff/Application/UseCases/Auth/LogoutUseCase.php`
- **状態**: 完全実装済み
- **機能**: 認証ガードの `logout()` メソッドを呼び出し

#### 2. AuthController::logout (実装済み)
- **パス**: `backend/app/Http/Controllers/Auth/AuthController.php`
- **状態**: 完全実装済み
- **機能**:
  - `Auth::guard('web')->logout()` でセッション無効化
  - `$request->session()->invalidate()` でセッション破棄
  - `$request->session()->regenerateToken()` でCSRFトークン再生成
  - レスポンス: `{ "message": "ログアウトしました" }`

#### 3. APIルート (実装済み)
- **エンドポイント**: `POST /api/auth/logout`
- **認証**: Sanctumミドルウェアで保護
- **状態**: 完全実装済み

---

### フロントエンド実装状況

#### 1. authApi.ts (実装済み)
- **パス**: `frontend/src/features/auth/api/authApi.ts`
- **状態**: 完全実装済み
- **機能**: `logout()` 関数で `POST /api/auth/logout` を呼び出し

#### 2. useLogout フック (実装済み)
- **パス**: `frontend/src/features/auth/hooks/useLogout.ts`
- **状態**: 完全実装済み
- **機能**:
  - API呼び出し
  - 認証状態クリア (`clearAuthentication`)
  - ログイン画面へリダイレクト (`navigate('/login', { replace: true })`)
  - エラー時もローカル状態をクリアして遷移

#### 3. Header コンポーネント (実装済み)
- **パス**: `frontend/src/components/layout/Header.tsx`
- **状態**: 完全実装済み
- **機能**:
  - ログアウトボタン表示（ユーザードロップダウンメニュー内）
  - ログアウト中の状態表示（「ログアウト中...」）
  - `onLogout` プロパティでログアウト処理を受け取り

#### 4. LoginPage (一部未実装)
- **パス**: `frontend/src/features/auth/pages/LoginPage.tsx`
- **状態**: ログアウト完了メッセージ表示が未実装
- **現在の動作**: ログアウト後にリダイレクトされるが、完了メッセージが表示されない

---

## 未実装項目

### 1. ログアウト完了メッセージの表示
- **要件**: ログアウト後、ログイン画面で「ログアウトしました」メッセージを表示
- **実装方針**:
  - ナビゲーション時に state を渡す（`navigate('/login', { state: { loggedOut: true } })`）
  - LoginPage で `useLocation` を使用して state を取得
  - メッセージを一定時間後に自動非表示（5秒程度）

---

## 技術的決定事項

### Decision 1: ログアウト完了メッセージの実装方法

**Decision**: React Router の navigate state を使用

**Rationale**:
- URL にクエリパラメータを露出させない
- ページリロード時にメッセージが再表示されない（state はリロードで消える）
- 既存のナビゲーションパターンと一致

**Alternatives considered**:
- クエリパラメータ (`/login?loggedOut=true`): URL が汚れる、ブックマーク時に問題
- Zustand グローバル状態: 過剰な状態管理、永続化の問題
- sessionStorage: ページリロード時に再表示される可能性

### Decision 2: メッセージ自動非表示の実装

**Decision**: `useEffect` と `setTimeout` で5秒後に非表示

**Rationale**:
- ユーザーが確認できる十分な時間
- 一般的なUXパターン
- シンプルな実装

**Alternatives considered**:
- アニメーションライブラリ: 依存関係追加は不要
- 手動閉じるのみ: ユーザーが閉じ忘れる可能性

---

## 実装スコープ

### 必要な変更

1. **useLogout.ts の更新**
   - `navigate('/login', { replace: true, state: { loggedOut: true } })` に変更

2. **LoginPage.tsx の更新**
   - `useLocation` で state を取得
   - ログアウト完了メッセージ表示コンポーネントを追加
   - 5秒後の自動非表示ロジック

3. **テストの追加/更新**
   - useLogout フックのテスト更新（state 渡しの確認）
   - LoginPage のテスト追加（メッセージ表示/非表示の確認）

---

## 既存実装との整合性

| 項目 | 既存実装 | 本機能での変更 |
|------|----------|---------------|
| ログアウトAPI | 実装済み | 変更なし |
| セッション破棄 | 実装済み | 変更なし |
| CSRF再生成 | 実装済み | 変更なし |
| ログアウトボタン | 実装済み | 変更なし |
| useLogout | 実装済み | state 追加のみ |
| LoginPage | 実装済み | メッセージ表示追加 |

---

## リスクと軽減策

| リスク | 影響 | 軽減策 |
|--------|------|--------|
| state がリロードで消える | メッセージ非表示 | 意図した動作、問題なし |
| useEffect の無限ループ | パフォーマンス低下 | 依存配列を正しく設定 |
| テスト漏れ | 品質低下 | 既存テストパターンに従う |
