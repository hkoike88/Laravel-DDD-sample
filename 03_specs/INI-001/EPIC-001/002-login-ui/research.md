# Research: ログイン画面実装

**Feature**: 003-login-ui
**Date**: 2025-12-25

## 技術選定

### フォーム管理: React Hook Form + Zod

**Decision**: React Hook Form 7.x + Zod 4.x（@hookform/resolvers 経由）

**Rationale**:
- プロジェクトで既に採用済み（package.json で確認）
- 非制御コンポーネントベースで高パフォーマンス
- Zod との統合によりスキーマファーストのバリデーション
- TypeScript との相性が良く、型安全なフォーム処理

**Alternatives considered**:
- Formik: 制御コンポーネントベースでパフォーマンスに難あり
- 自前実装: 保守性・機能性で劣る

### API 通信: TanStack Query + Axios

**Decision**: TanStack Query 5.x + Axios 1.x

**Rationale**:
- プロジェクトで既に採用済み
- ミューテーション（useMutation）でログイン API 呼び出し
- エラーハンドリング、ローディング状態管理が組み込み
- Axios インターセプターで CSRF トークン自動付与

**Alternatives considered**:
- fetch API のみ: キャッシュ管理、エラーハンドリングが煩雑
- SWR: 採用済みの TanStack Query で統一

### 認証状態管理: Zustand

**Decision**: Zustand 5.x

**Rationale**:
- プロジェクトで既に採用済み
- シンプルな API でグローバル状態管理
- 認証状態（isAuthenticated, currentUser）の保持に適切
- React Query との組み合わせで効率的なデータフェッチ

**Alternatives considered**:
- Context API のみ: 大規模になると再レンダリング問題
- Redux: 今回の規模ではオーバースペック

### ルーティング: React Router

**Decision**: React Router 7.x

**Rationale**:
- プロジェクトで既に採用済み
- Protected Route パターンで認証ガード実装
- Navigate コンポーネントでリダイレクト処理

**Alternatives considered**:
- TanStack Router: 既存の React Router で統一

### スタイリング: Tailwind CSS

**Decision**: Tailwind CSS 3.x

**Rationale**:
- プロジェクトで既に採用済み
- ユーティリティファーストで迅速な UI 構築
- レスポンシブデザイン対応が容易

**Alternatives considered**:
- CSS Modules: 既存の Tailwind で統一
- styled-components: 追加依存が不要

## 認証フロー調査

### Laravel Sanctum SPA 認証

**調査結果**:

1. **CSRF トークン取得** (GET /sanctum/csrf-cookie)
   - ログイン前に必ず呼び出す
   - XSRF-TOKEN クッキーが設定される
   - Axios は自動的に X-XSRF-TOKEN ヘッダーを付与

2. **ログイン** (POST /api/auth/login)
   - リクエストボディ: `{ email, password }`
   - 成功時: 200 + StaffResponse + セッションクッキー
   - 失敗時: 401（認証エラー）、423（アカウントロック）、422（バリデーション）、429（レート制限）

3. **認証状態確認** (GET /api/auth/user)
   - セッションクッキーで認証
   - 認証済み: 200 + StaffResponse
   - 未認証: 401

4. **ログアウト** (POST /api/auth/logout)
   - セッション無効化

### Axios CSRF 設定

**実装方針**:

```typescript
// lib/axios.ts
axios.defaults.withCredentials = true; // クッキー送信を有効化
axios.defaults.withXSRFToken = true;   // XSRF トークン自動付与
```

## アクセシビリティ調査

### WCAG 2.1 AA 準拠要件

1. **キーボード操作** (2.1.1)
   - Tab でフォーカス移動
   - Enter でフォーム送信
   - Escape でエラーメッセージクリア（オプション）

2. **フォーカス表示** (2.4.7)
   - 入力フィールドにフォーカスリングを表示
   - Tailwind: `focus:ring-2 focus:ring-blue-500`

3. **エラー識別** (3.3.1)
   - エラーメッセージを入力フィールドに関連付け
   - `aria-describedby` でエラーメッセージを参照
   - `aria-invalid="true"` でエラー状態を示す

4. **ラベル** (3.3.2)
   - すべての入力フィールドに `<label>` を関連付け
   - `htmlFor` と `id` で関連付け

5. **色だけに依存しない** (1.4.1)
   - エラーはテキストとアイコンで表示
   - 赤色だけでなくメッセージテキストも併用

## エラーハンドリング設計

### API エラー分類

| HTTP Status | エラー種別 | 表示メッセージ |
|-------------|-----------|---------------|
| 401 | 認証エラー | メールアドレスまたはパスワードが正しくありません |
| 422 | バリデーションエラー | フィールドごとのエラーメッセージを表示 |
| 423 | アカウントロック | アカウントがロックされています |
| 429 | レート制限 | ログイン試行回数が上限に達しました。しばらくしてから再試行してください |
| 500 | サーバーエラー | サーバーエラーが発生しました。しばらくしてから再試行してください |
| Network Error | ネットワークエラー | 通信エラーが発生しました。ネットワーク接続を確認してください |

### エラー表示位置

- **フィールドバリデーションエラー**: 各フィールドの直下
- **API エラー（認証失敗等）**: フォーム下部のアラートエリア

## テスト戦略

### ユニットテスト（Vitest + Testing Library）

1. **LoginForm コンポーネント**
   - 初期表示の確認
   - バリデーションエラー表示
   - ローディング状態
   - フォーム送信イベント

2. **useLogin フック**
   - ログイン成功時の動作
   - ログイン失敗時のエラーハンドリング

3. **loginSchema**
   - バリデーションルールの確認

### E2E テスト（Playwright）

1. **ログイン成功シナリオ**
   - 有効な認証情報でログイン → ダッシュボードへ遷移

2. **ログイン失敗シナリオ**
   - 無効な認証情報 → エラーメッセージ表示
   - アカウントロック → ロックメッセージ表示

3. **バリデーションシナリオ**
   - 空欄送信 → バリデーションエラー表示
   - 不正なメール形式 → バリデーションエラー表示

4. **認証済みリダイレクト**
   - 認証済みでログイン画面アクセス → ダッシュボードへリダイレクト
