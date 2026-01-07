# Research: ダッシュボード画面

**Feature**: 004-dashboard-ui
**Date**: 2025-12-25

## 1. 既存実装の調査

### 1.1 認証機能（003-login-ui）

**Decision**: 既存の認証ストア（authStore）とAPIクライアント（authApi）を拡張して使用

**Rationale**:
- 認証状態（isAuthenticated, currentUser）は既に authStore で管理されている
- ログアウト API は既に authApi.logout() として実装済み
- useLogout フックを新規追加してログアウト処理をカプセル化

**Alternatives considered**:
- 新規ストア作成 → 不要な重複になるため却下

### 1.2 ルーティング構成

**Decision**: React Router の既存構成を拡張

**Rationale**:
- /login, /dashboard ルートは既に定義済み
- 業務画面（/books, /loans/*, /users, /reservations）をプレースホルダーとして追加
- ProtectedRoute は既存のものを再利用

**Alternatives considered**:
- ネストルーティング → 現時点ではシンプルなフラットルーティングで十分

## 2. 共通レイアウトパターン

### 2.1 レイアウトコンポーネント構成

**Decision**: MainLayout, Header, Footer の3コンポーネント構成

**Rationale**:
- Header: ナビゲーション、ユーザーメニュー、ログアウトボタン
- Footer: 著作権表示
- MainLayout: Header + children + Footer のラッパー
- 全ての認証済みページで共通使用

**Alternatives considered**:
- 単一の Layout コンポーネント → 責務が大きくなりすぎるため分割
- Outlet パターン → 現時点ではシンプルな children パターンで十分

### 2.2 ヘッダーナビゲーション

**Decision**: 固定ナビゲーション + ユーザードロップダウンメニュー

**Rationale**:
- メインナビ: 蔵書管理、貸出・返却、利用者管理（常時表示）
- ユーザーメニュー: 職員名クリックでログアウトオプション表示
- レスポンシブ: モバイルではハンバーガーメニュー

**Alternatives considered**:
- サイドバーナビゲーション → ダッシュボードのカードメニューと重複するため不要

## 3. ダッシュボードメニューカード

### 3.1 メニュー項目定義

**Decision**: 静的な配列でメニュー項目を定義

**Rationale**:
- 5つの業務メニュー（蔵書管理、貸出処理、返却処理、利用者管理、予約管理）
- 各メニューに icon, label, path, enabled フラグを持たせる
- 未実装メニュー（統計）は enabled: false で無効化

**Alternatives considered**:
- API からメニュー取得 → 静的コンテンツのため不要なオーバーヘッド

### 3.2 カードコンポーネント設計

**Decision**: MenuCard + MenuGrid の2コンポーネント構成

**Rationale**:
- MenuCard: 個別メニューのUI（アイコン、ラベル、リンク）
- MenuGrid: メニューカードのグリッドレイアウト
- レスポンシブグリッド（モバイル1列、タブレット2列、デスクトップ3列）

**Alternatives considered**:
- 単一コンポーネント → 責務分離のため分割

## 4. ログアウト機能

### 4.1 ログアウトフロー

**Decision**: useLogout フックで API 呼び出しと状態クリアを一元管理

**Rationale**:
- authApi.logout() を呼び出し
- 成功時: authStore をクリア、/login にリダイレクト
- 失敗時: エラーメッセージ表示（ネットワークエラーなど）

**Alternatives considered**:
- 直接コンポーネントで処理 → 再利用性のためフック化

### 4.2 エラーハンドリング

**Decision**: ログアウト失敗時もローカル状態をクリアしてログイン画面へ遷移

**Rationale**:
- サーバー側でセッションが既に無効化されている可能性
- ユーザー体験を優先し、クライアント側で確実にログアウト状態にする
- エラーはコンソールに記録するが、ユーザーには遷移を優先

**Alternatives considered**:
- リトライ機構 → UX 的に遅延が発生するため却下
- エラー表示で停止 → ユーザーが操作できなくなるため却下

## 5. レスポンシブデザイン

### 5.1 ブレークポイント

**Decision**: Tailwind CSS 標準ブレークポイントを使用

**Rationale**:
- sm: 640px（タブレット）
- md: 768px
- lg: 1024px（デスクトップ）
- 既存の books フィーチャーと統一

**Alternatives considered**:
- カスタムブレークポイント → 標準で十分

## 6. アクセシビリティ

### 6.1 キーボードナビゲーション

**Decision**: 全てのインタラクティブ要素にフォーカス可能

**Rationale**:
- メニューカード: button または link 要素として実装
- ヘッダーナビ: nav 要素と適切な aria-label
- ドロップダウン: Escape キーで閉じる

### 6.2 スクリーンリーダー対応

**Decision**: セマンティック HTML + ARIA 属性

**Rationale**:
- main, nav, header, footer のランドマーク使用
- メニューカードに aria-label でアクセス先を明示
- 現在のページを aria-current で示す

## 7. テスト戦略

### 7.1 ユニットテスト

**Decision**: 各コンポーネントの描画とインタラクションをテスト

**Rationale**:
- MenuCard: クリック時の遷移、無効状態の表示
- Header: ナビゲーションリンク、ユーザーメニュー表示
- useLogout: API 呼び出しと状態遷移

### 7.2 E2E テスト

**Decision**: ログイン→ダッシュボード→ログアウトの一連のフローをテスト

**Rationale**:
- ログイン後のダッシュボード表示確認
- メニューカードからの画面遷移
- ログアウト後のリダイレクト確認
