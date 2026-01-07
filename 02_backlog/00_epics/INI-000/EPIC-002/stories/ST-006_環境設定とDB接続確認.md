# ST-006: 環境設定とデータベース接続確認

最終更新: 2025-12-23

---

## ストーリー

**開発者として**、環境設定を完了し、データベース接続を確認したい。
**なぜなら**、アプリケーションが正常にデータベースと通信できることを保証したいからだ。

---

## 関連情報

| 項目 | 値 |
|------|-----|
| Epic | [EPIC-002: バックエンド初期設定](../epic.md) |
| ポイント | 1 |
| 優先度 | Must |
| ステータス | Draft |

---

## 受け入れ条件

1. [ ] .env ファイルが適切に設定されていること
2. [ ] `php artisan migrate` が成功すること
3. [ ] `php artisan db:show` でデータベース情報が表示されること
4. [ ] テストでインメモリ SQLite が使用されること
5. [ ] API ヘルスチェックエンドポイントが動作すること

---

## 技術仕様

### 環境設定（.env）

```bash
# アプリケーション
APP_NAME="Library System"
APP_ENV=local
APP_KEY=base64:...
APP_DEBUG=true
APP_URL=http://localhost:8000

# データベース
DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=library
DB_USERNAME=library
DB_PASSWORD=secret

# キャッシュ・セッション
CACHE_DRIVER=file
SESSION_DRIVER=file
QUEUE_CONNECTION=sync

# ログ
LOG_CHANNEL=stack
LOG_LEVEL=debug

# Sanctum
SANCTUM_STATEFUL_DOMAINS=localhost:5173,localhost:80
```

### ヘルスチェック API

```php
<?php
// routes/api.php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;

Route::get('/health', function () {
    try {
        DB::connection()->getPdo();
        return response()->json([
            'status' => 'ok',
            'database' => 'connected',
            'timestamp' => now()->toIso8601String(),
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'database' => 'disconnected',
            'message' => $e->getMessage(),
        ], 500);
    }
});
```

### 確認コマンド

```bash
# データベース接続確認
php artisan db:show

# マイグレーション実行
php artisan migrate

# マイグレーション状態確認
php artisan migrate:status

# API ヘルスチェック
curl http://localhost:8000/api/health
```

### 期待されるレスポンス

```json
{
    "status": "ok",
    "database": "connected",
    "timestamp": "2025-12-23T10:00:00+09:00"
}
```

---

## 成果物

| 成果物 | 配置場所 |
|--------|---------|
| 環境設定ファイル | backend/.env |
| ヘルスチェック API | backend/routes/api.php |

---

## タスク

### Design Tasks（外部設計）

- [ ] 環境変数の洗い出し
- [ ] ヘルスチェック API 仕様の確定

### Spec Tasks（詳細設計）

- [ ] .env ファイルの設定
- [ ] ヘルスチェック API の実装
- [ ] マイグレーション実行
- [ ] 接続確認テスト

---

## トラブルシューティング

| 問題 | 原因 | 解決策 |
|------|------|--------|
| SQLSTATE[HY000] [2002] | DB ホスト名誤り | DB_HOST=db（コンテナ名）に修正 |
| Access denied | 認証情報誤り | DB_USERNAME/PASSWORD を確認 |
| Unknown database | DB 未作成 | docker compose up db で再起動 |

---

## 変更履歴

| 日付 | 変更内容 |
|------|---------|
| 2025-12-23 | 初版作成 |
