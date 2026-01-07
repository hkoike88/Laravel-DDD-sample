# API Contract: ログイン API（アカウントロック対応）

**Branch**: `006-account-lock` | **Date**: 2025-12-26

## 概要

ログイン API のアカウントロック関連のレスポンス仕様を定義する。

---

## POST /api/auth/login

職員のログイン認証を行う。

### リクエスト

```json
{
  "email": "string (required, email format, max 255)",
  "password": "string (required, min 8)"
}
```

### レスポンス

#### 成功（200 OK）

```json
{
  "data": {
    "id": "01HXYZ1234567890123456789A",
    "name": "山田 太郎",
    "email": "yamada@example.com"
  }
}
```

#### 認証失敗（401 Unauthorized）

メールアドレスが存在しない、またはパスワードが一致しない場合。

```json
{
  "message": "メールアドレスまたはパスワードが正しくありません"
}
```

**注意**: アカウントの存在有無を推測できないよう、同一のメッセージを返す。

#### アカウントロック（423 Locked）

5回連続でログインに失敗し、アカウントがロックされた場合。

```json
{
  "message": "アカウントがロックされています。管理者にお問い合わせください"
}
```

**ヘッダー**:
- `Retry-After: 1800`（30分後にリトライ可能）

#### バリデーションエラー（422 Unprocessable Entity）

```json
{
  "message": "入力内容に誤りがあります",
  "errors": {
    "email": ["メールアドレスは必須です"],
    "password": ["パスワードは8文字以上で入力してください"]
  }
}
```

---

## 動作シーケンス

### 通常のログイン成功

```
Client                    Server
  |                         |
  |-- POST /api/auth/login -->
  |                         | (1) メールアドレスで職員検索
  |                         | (2) ロック状態確認 → OK
  |                         | (3) パスワード検証 → OK
  |                         | (4) 失敗回数リセット
  |                         | (5) セッション開始
  |<-- 200 OK + Staff ---------|
  |                         |
```

### ログイン失敗（ロックまで）

```
Client                    Server
  |                         |
  |-- POST (失敗1回目) ----->|
  |                         | (1) パスワード検証 → NG
  |                         | (2) failedLoginAttempts = 1
  |<-- 401 Unauthorized -----|
  |                         |
  |-- POST (失敗2回目) ----->|
  |                         | failedLoginAttempts = 2
  |<-- 401 Unauthorized -----|
  |                         |
  ... (繰り返し) ...
  |                         |
  |-- POST (失敗5回目) ----->|
  |                         | (1) パスワード検証 → NG
  |                         | (2) failedLoginAttempts = 5
  |                         | (3) ロック実行
  |<-- 401 Unauthorized -----|
  |                         |
  |-- POST (失敗6回目) ----->|
  |                         | (1) ロック状態確認 → ロック中
  |<-- 423 Locked -----------|
  |                         |
```

### ロック直前での成功

```
Client                    Server
  |                         |
  |-- POST (失敗4回目) ----->|
  |                         | failedLoginAttempts = 4
  |<-- 401 Unauthorized -----|
  |                         |
  |-- POST (成功) ---------->|
  |                         | (1) パスワード検証 → OK
  |                         | (2) failedLoginAttempts = 0 (リセット)
  |<-- 200 OK + Staff ---------|
  |                         |
```

---

## エラーコード対応表

| HTTP Status | エラータイプ | 説明 |
|-------------|-------------|------|
| 401 | authentication | 認証失敗（メールアドレス不正またはパスワード不正） |
| 422 | validation | バリデーションエラー |
| 423 | locked | アカウントロック |
| 429 | rate_limit | レート制限（本機能外） |
| 500 | server | サーバーエラー |
