# Quickstart: 職員アカウント作成機能

**Branch**: `007-staff-account-create` | **Date**: 2026-01-06

## 概要

管理者が職員アカウントを作成・管理するための機能。職員一覧の表示、新規職員の作成、初期パスワードの確認が可能。

---

## 前提条件

- Docker 環境が起動していること
- 管理者アカウントでログイン済みであること
- EPIC-001（職員ログイン機能）が実装済みであること
- EPIC-002（職員ダッシュボード機能）が実装済みであること

---

## 画面一覧

| 画面名 | パス | 説明 |
|--------|------|------|
| 職員一覧 | `/staff/accounts` | 登録済み職員の一覧表示（20件/ページ） |
| 職員作成 | `/staff/accounts/new` | 新規職員の登録フォーム |
| 作成結果 | `/staff/accounts/result` | 作成完了後の初期パスワード表示 |

---

## 基本フロー

### 1. 職員一覧を表示する

```bash
# ブラウザでアクセス
http://localhost:3000/staff/accounts

# API 直接呼び出し（cURL）
curl -X GET "http://localhost:8000/api/staff/accounts?page=1" \
  -H "Accept: application/json" \
  -H "Cookie: laravel_session=xxx"
```

**レスポンス例**:
```json
{
  "data": [
    {
      "id": "01HV...",
      "name": "山田 太郎",
      "email": "yamada@example.com",
      "role": "admin",
      "createdAt": "2026-01-06T10:00:00+09:00"
    }
  ],
  "meta": {
    "currentPage": 1,
    "lastPage": 1,
    "perPage": 20,
    "total": 1,
    "from": 1,
    "to": 1
  },
  "links": {
    "first": "http://localhost:8000/api/staff/accounts?page=1",
    "last": "http://localhost:8000/api/staff/accounts?page=1",
    "prev": null,
    "next": null
  }
}
```

### 2. 新規職員を作成する

```bash
# API 呼び出し
curl -X POST "http://localhost:8000/api/staff/accounts" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Cookie: laravel_session=xxx" \
  -d '{
    "name": "田中 花子",
    "email": "tanaka@example.com",
    "role": "staff"
  }'
```

**成功レスポンス（201）**:
```json
{
  "message": "職員アカウントを作成しました",
  "staff": {
    "id": "01HV...",
    "name": "田中 花子",
    "email": "tanaka@example.com",
    "role": "staff",
    "createdAt": "2026-01-06T10:00:00+09:00"
  },
  "temporaryPassword": "Abc123!@#xyz1234"
}
```

**バリデーションエラー（422）**:
```json
{
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "入力内容に誤りがあります",
    "details": [
      {
        "field": "email",
        "code": "VALIDATION_ERROR",
        "message": "このメールアドレスは既に登録されています"
      }
    ]
  }
}
```

---

## UI 操作手順

### 職員作成の手順

1. 管理者としてログイン
2. ダッシュボードから「職員管理」メニューをクリック
3. 職員一覧画面で「新規作成」ボタンをクリック
4. 職員作成フォームに入力:
   - **氏名**: 職員の氏名（50文字以内）
   - **メールアドレス**: 有効なメールアドレス
   - **権限**: 「一般職員」または「管理者」を選択
5. 「作成」ボタンをクリック
6. 作成結果画面で初期パスワードを確認:
   - 「表示」ボタンで平文表示
   - 「コピー」ボタンでクリップボードにコピー
7. 「一覧へ戻る」ボタンで職員一覧に戻る

### 初期パスワードの取り扱い

- 初期パスワードは作成直後の画面でのみ表示される
- 画面を離れると再表示不可
- コピー機能を使用して安全に職員へ伝達する
- パスワードリセット機能は別途実装（本機能のスコープ外）

---

## 入力バリデーション

### クライアント側（即時フィードバック）

| フィールド | バリデーション | エラーメッセージ |
|------------|----------------|------------------|
| 氏名 | 必須 | 氏名は必須です |
| 氏名 | 50文字以内 | 氏名は50文字以内で入力してください |
| メールアドレス | 必須 | メールアドレスは必須です |
| メールアドレス | メール形式 | 有効なメールアドレスを入力してください |
| 権限 | 必須 | 権限を選択してください |

### サーバー側（送信時）

| フィールド | バリデーション | エラーメッセージ |
|------------|----------------|------------------|
| メールアドレス | 一意制約 | このメールアドレスは既に登録されています |

---

## 権限について

| 操作 | 一般職員 | 管理者 |
|------|:--------:|:------:|
| 職員一覧表示 | × | ○ |
| 職員作成 | × | ○ |
| 管理者権限の付与 | - | ○ |

一般職員がアクセスした場合、403 エラーとなる:
```json
{
  "error": {
    "code": "AUTHZ_PERMISSION_DENIED",
    "message": "この操作を行う権限がありません"
  }
}
```

---

## トラブルシューティング

### 「認証が必要です」エラー

**原因**: ログインしていない、またはセッションが切れている

**解決策**:
1. ログイン画面にリダイレクトされるので、再度ログインする
2. ブラウザのCookieがブロックされていないか確認

### 「このメールアドレスは既に登録されています」エラー

**原因**: 同じメールアドレスの職員が既に存在する

**解決策**:
1. 職員一覧で既存の職員を確認
2. 別のメールアドレスを使用する

### 初期パスワードを見逃した

**原因**: 作成結果画面を離れてしまった

**解決策**:
1. パスワードリセット機能を使用（別途実装が必要）
2. 職員アカウントを削除して再作成（削除機能は EPIC-005）

---

## 開発時のテスト方法

### バックエンドテスト

```bash
# 職員アカウント作成のテスト
docker compose exec backend php artisan test --filter=StaffAccountControllerTest

# パスワード生成のテスト
docker compose exec backend php artisan test --filter=PasswordGeneratorTest
```

### フロントエンドテスト

```bash
# コンポーネントテスト
docker compose exec frontend npm run test -- --filter=StaffCreateForm

# E2E テスト
docker compose exec frontend npm run test:e2e -- --filter=staff-accounts
```

---

## API エンドポイント一覧

| メソッド | エンドポイント | 説明 | 認証 | 権限 |
|----------|---------------|------|:----:|:----:|
| GET | `/api/staff/accounts` | 職員一覧取得 | 必須 | 管理者 |
| POST | `/api/staff/accounts` | 職員作成 | 必須 | 管理者 |

詳細な API 仕様は [contracts/openapi.yaml](./contracts/openapi.yaml) を参照。
