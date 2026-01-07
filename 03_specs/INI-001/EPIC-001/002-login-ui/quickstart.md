# Quickstart: ログイン画面実装

**Feature**: 003-login-ui
**Date**: 2025-12-25

## 概要

職員ログイン画面のフロントエンド実装。React + TypeScript + TanStack Query + React Hook Form を使用。

## 前提条件

- Node.js 20.x
- バックエンド API 起動済み（ST-002 実装済み）
- Docker Compose でバックエンド起動済み

## セットアップ

```bash
# フロントエンドディレクトリへ移動
cd frontend

# 依存関係インストール（既にインストール済みの場合はスキップ）
npm install

# 開発サーバー起動
npm run dev
```

## 動作確認

### 1. バックエンド起動確認

```bash
# プロジェクトルートで実行
docker compose up -d

# API ヘルスチェック
curl http://localhost:8000/api/health
```

### 2. テストユーザー作成（必要に応じて）

```bash
# バックエンドコンテナで seeder 実行
docker compose exec backend php artisan db:seed --class=StaffSeeder
```

### 3. ログイン画面アクセス

```
http://localhost:5173/login
```

### 4. テストログイン

| 項目 | 値 |
|-----|-----|
| メールアドレス | staff@example.com |
| パスワード | password123 |

## 手動テストシナリオ

### シナリオ 1: ログイン成功

1. `/login` にアクセス
2. メールアドレス `staff@example.com` を入力
3. パスワード `password123` を入力
4. 「ログイン」ボタンをクリック
5. **期待結果**: `/dashboard` にリダイレクト

### シナリオ 2: 認証エラー

1. `/login` にアクセス
2. 無効なメールアドレスを入力
3. 任意のパスワードを入力
4. 「ログイン」ボタンをクリック
5. **期待結果**: 「メールアドレスまたはパスワードが正しくありません」と表示

### シナリオ 3: バリデーションエラー

1. `/login` にアクセス
2. 何も入力せずに「ログイン」ボタンをクリック
3. **期待結果**: 各フィールドにエラーメッセージが表示

### シナリオ 4: 認証済みリダイレクト

1. ログイン成功後、ブラウザの戻るボタンでログイン画面に戻る
2. **期待結果**: `/dashboard` に自動リダイレクト

### シナリオ 5: アクセシビリティ

1. `/login` にアクセス
2. Tab キーでフォーカス移動
3. **期待結果**: メール → パスワード → ボタンの順でフォーカス移動
4. Enter キーでフォーム送信
5. **期待結果**: フォームが送信される

## 自動テスト実行

```bash
# ユニットテスト
npm run test:run

# E2E テスト（バックエンド起動必須）
npm run test:e2e

# カバレッジ付きテスト
npm run test:coverage
```

## トラブルシューティング

### CORS エラー

```
Access to XMLHttpRequest at 'http://localhost:8000' from origin 'http://localhost:5173' has been blocked by CORS policy
```

**解決方法**:
- バックエンドの `config/cors.php` で `http://localhost:5173` が許可されているか確認
- `SANCTUM_STATEFUL_DOMAINS` に `localhost:5173` が含まれているか確認

### CSRF トークンエラー

```
419 | CSRF token mismatch
```

**解決方法**:
- ログイン前に `/sanctum/csrf-cookie` が呼ばれているか確認
- Axios の `withCredentials: true` が設定されているか確認
- Cookie がブラウザに保存されているか確認（開発者ツール → Application → Cookies）

### セッションが維持されない

**解決方法**:
- `SESSION_DOMAIN` が正しく設定されているか確認
- `SESSION_SECURE_COOKIE=false`（ローカル開発時）か確認
