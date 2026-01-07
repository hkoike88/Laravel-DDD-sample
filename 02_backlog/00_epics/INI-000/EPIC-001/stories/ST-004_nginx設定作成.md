# ST-004: Nginx 設定の作成

最終更新: 2025-12-23

---

## ストーリー

**開発者として**、フロントエンドとバックエンドへのリクエストを適切にルーティングしたい。
**なぜなら**、単一のエントリーポイントから両サービスにアクセスできるようにしたいからだ。

---

## 関連情報

| 項目 | 値 |
|------|-----|
| Epic | [EPIC-001: Docker 環境構築](../epic.md) |
| ポイント | 2 |
| 優先度 | Must |
| ステータス | Draft |

---

## 受け入れ条件

1. [ ] `/api/*` へのリクエストがバックエンドに転送されること
2. [ ] `/` へのリクエストがフロントエンドに転送されること
3. [ ] WebSocket 接続（HMR用）が正常に動作すること
4. [ ] CORS 関連のヘッダーが適切に設定されること

---

## 技術仕様

### ルーティング設計

| パス | 転送先 | 用途 |
|------|--------|------|
| `/api/*` | backend:8000 | REST API |
| `/sanctum/*` | backend:8000 | Laravel Sanctum |
| `/*` | frontend:5173 | React SPA |
| `/_hmr` | frontend:5173 | Vite HMR WebSocket |

### Nginx 設定

```nginx
# /infrastructure/nginx/default.conf

upstream frontend {
    server frontend:5173;
}

upstream backend {
    server backend:8000;
}

server {
    listen 80;
    server_name localhost;

    # バックエンド API
    location /api {
        proxy_pass http://backend;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }

    # Laravel Sanctum
    location /sanctum {
        proxy_pass http://backend;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }

    # フロントエンド
    location / {
        proxy_pass http://frontend;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;

        # WebSocket 対応（Vite HMR）
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
    }
}
```

---

## 成果物

| 成果物 | 配置場所 |
|--------|---------|
| default.conf | infrastructure/nginx/ |

---

## タスク

### Design Tasks（外部設計）

- [ ] ルーティング設計の確定
- [ ] WebSocket 要件の確認

### Spec Tasks（詳細設計）

- [ ] default.conf の作成
- [ ] ルーティングテスト
- [ ] WebSocket 動作確認

---

## 変更履歴

| 日付 | 変更内容 |
|------|---------|
| 2025-12-23 | 初版作成 |
