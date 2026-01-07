# Quickstart: セッション管理実装

**Feature**: 001-session-management
**Date**: 2025-12-26

## 前提条件

- Docker / Docker Compose が起動済み
- `docker compose up -d` でコンテナが稼働中
- データベースマイグレーション済み

## 開発環境セットアップ

### 1. ブランチをチェックアウト

```bash
git checkout 001-session-management
```

### 2. 依存関係をインストール

```bash
# バックエンド
docker compose exec backend composer install

# フロントエンド
docker compose exec frontend npm install
```

### 3. マイグレーション実行

```bash
docker compose exec backend php artisan migrate
```

### 4. 開発サーバー起動

```bash
# バックエンド（既に artisan serve で起動中の場合は不要）
docker compose exec backend php artisan serve --host=0.0.0.0 --port=8000

# フロントエンド
docker compose exec frontend npm run dev
```

---

## 実装手順の概要

### Phase 1: データベース更新

1. **staffs テーブルに is_admin カラム追加**
   ```bash
   docker compose exec backend php artisan make:migration add_is_admin_to_staffs_table
   ```

2. **マイグレーション実行**
   ```bash
   docker compose exec backend php artisan migrate
   ```

### Phase 2: ドメインモデル更新

1. **Staff エンティティに isAdmin プロパティ追加**
   - `packages/Domain/Staff/Domain/Model/Staff.php`

2. **StaffRecord に is_admin カラム追加**
   - `packages/Domain/Staff/Infrastructure/EloquentModels/StaffRecord.php`

### Phase 3: ミドルウェア実装

1. **AbsoluteSessionTimeout ミドルウェア作成**
   ```bash
   docker compose exec backend php artisan make:middleware AbsoluteSessionTimeout
   ```

2. **ConcurrentSessionLimit ミドルウェア作成**
   ```bash
   docker compose exec backend php artisan make:middleware ConcurrentSessionLimit
   ```

3. **bootstrap/app.php でミドルウェア登録**

### Phase 4: セッション設定更新

1. **config/session.php 更新**
   - lifetime: 30（アイドルタイムアウト）
   - encrypt: true
   - secure: true
   - http_only: true
   - same_site: lax

### Phase 5: フロントエンド更新

1. **Axios インターセプターでセッション切れ処理追加**
   - `frontend/src/lib/axios.ts`

2. **authStore にセッション切れハンドリング追加**
   - `frontend/src/stores/authStore.ts`

### Phase 6: テスト実装

1. **バックエンド Feature テスト**
   ```bash
   docker compose exec backend php artisan pest tests/Feature/Auth/SessionTest.php
   ```

2. **フロントエンド Unit テスト**
   ```bash
   docker compose exec frontend npm run test
   ```

---

## 動作確認

### セッションタイムアウトの確認

1. ログイン後、30分以上放置
2. 任意の操作を試みる
3. 「セッションがタイムアウトしました」メッセージが表示されることを確認
4. ログイン画面にリダイレクトされることを確認

### 同時ログイン制限の確認

1. 一般職員アカウントで3台のブラウザからログイン
2. 4台目のブラウザからログイン
3. 最初のブラウザでセッション切れになることを確認

### 管理者同時ログイン制限の確認

1. 管理者アカウントで1台のブラウザからログイン
2. 2台目のブラウザからログイン
3. 最初のブラウザでセッション切れになることを確認

---

## トラブルシューティング

### セッションが保存されない

```bash
# sessions テーブルの確認
docker compose exec backend php artisan tinker
>>> DB::table('sessions')->get();
```

### マイグレーションエラー

```bash
# マイグレーション状態確認
docker compose exec backend php artisan migrate:status

# ロールバック
docker compose exec backend php artisan migrate:rollback
```

### CSRF トークンエラー

```bash
# フロントエンドで CSRF Cookie を取得
curl -c cookies.txt http://localhost:8000/sanctum/csrf-cookie
```

---

## 関連ドキュメント

- [仕様書](./spec.md)
- [実装計画](./plan.md)
- [調査結果](./research.md)
- [データモデル](./data-model.md)
- [API 仕様](./contracts/session-api.yaml)
