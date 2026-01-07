# ST-004: CORS 設定確認

最終更新: 2025-12-23

---

## ストーリー

**開発者として**、CORS（Cross-Origin Resource Sharing）が正しく設定されていることを確認したい。
**なぜなら**、フロントエンド（localhost:5173）からバックエンド（localhost:8000）へのクロスオリジンリクエストを許可する必要があるからだ。

---

## 関連情報

| 項目 | 値 |
|------|-----|
| Epic | [EPIC-004: 開発環境動作確認](../epic.md) |
| ポイント | 1 |
| 優先度 | Must |
| ステータス | Draft |

---

## 受け入れ条件

1. [ ] config/cors.php が適切に設定されていること
2. [ ] OPTIONS プリフライトリクエストが成功すること
3. [ ] クロスオリジンリクエストで CORS エラーが発生しないこと
4. [ ] 認証付きリクエスト（credentials）が動作すること
5. [ ] Sanctum の CORS 設定が完了していること

---

## CORS 設定

### config/cors.php

```php
<?php
// config/cors.php

return [
    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'http://localhost:5173',  // Vite 開発サーバー
        'http://localhost:80',    // Nginx 経由
        'http://localhost',       // Nginx 経由
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,  // Cookie 送信を許可
];
```

### .env の Sanctum 設定

```bash
# .env
SANCTUM_STATEFUL_DOMAINS=localhost:5173,localhost:80,localhost
SESSION_DOMAIN=localhost
```

### config/sanctum.php

```php
<?php
// config/sanctum.php

return [
    'stateful' => explode(',', env(
        'SANCTUM_STATEFUL_DOMAINS',
        'localhost:5173,localhost:80,localhost'
    )),

    // ...
];
```

---

## 確認手順

### 1. OPTIONS プリフライトリクエストの確認

```bash
# プリフライトリクエスト
curl -X OPTIONS http://localhost:8000/api/health \
  -H "Origin: http://localhost:5173" \
  -H "Access-Control-Request-Method: GET" \
  -H "Access-Control-Request-Headers: Content-Type" \
  -v

# 期待されるレスポンスヘッダー
# Access-Control-Allow-Origin: http://localhost:5173
# Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS
# Access-Control-Allow-Headers: Content-Type, ...
# Access-Control-Allow-Credentials: true
```

### 2. 実際のリクエストでの確認

```bash
# Origin ヘッダー付きリクエスト
curl http://localhost:8000/api/health \
  -H "Origin: http://localhost:5173" \
  -v

# レスポンスヘッダーに以下が含まれることを確認
# Access-Control-Allow-Origin: http://localhost:5173
# Access-Control-Allow-Credentials: true
```

### 3. ブラウザでの確認

1. http://localhost:5173 にアクセス
2. 開発者ツールの Console タブを開く
3. API 呼び出しで CORS エラーが発生しないことを確認

```javascript
// Console で直接テスト
fetch('http://localhost:8000/api/health', {
  credentials: 'include'
})
.then(res => res.json())
.then(console.log)
.catch(console.error);
```

---

## 確認チェックリスト

| 確認項目 | 方法 | 確認 |
|----------|------|------|
| cors.php 設定 | ファイル確認 | [ ] |
| OPTIONS リクエスト | curl コマンド | [ ] |
| CORS ヘッダー | レスポンス確認 | [ ] |
| ブラウザ動作 | Console でエラーなし | [ ] |
| credentials | Cookie 送信 | [ ] |

---

## トラブルシューティング

| 問題 | 原因 | 解決策 |
|------|------|--------|
| Access-Control-Allow-Origin missing | cors.php 未設定 | allowed_origins を確認 |
| Credentials not supported | supports_credentials: false | true に変更 |
| CSRF token mismatch | Sanctum 設定不足 | SANCTUM_STATEFUL_DOMAINS を確認 |
| 設定変更が反映されない | キャッシュ | `php artisan config:clear` |

### キャッシュクリア

```bash
docker compose exec backend php artisan config:clear
docker compose exec backend php artisan cache:clear
docker compose exec backend php artisan route:clear
```

---

## タスク

### Spec Tasks（詳細設計）

- [ ] config/cors.php の設定確認
- [ ] .env の SANCTUM_STATEFUL_DOMAINS 設定
- [ ] OPTIONS リクエストのテスト
- [ ] ブラウザでの CORS 動作確認

---

## 変更履歴

| 日付 | 変更内容 |
|------|---------|
| 2025-12-23 | 初版作成 |
