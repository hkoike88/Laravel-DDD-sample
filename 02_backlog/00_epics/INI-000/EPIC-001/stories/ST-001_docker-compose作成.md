# ST-001: docker-compose.yml の作成

最終更新: 2025-12-23

---

## ストーリー

**開発者として**、`docker compose up` コマンド一つで全サービスを起動できるようにしたい。
**なぜなら**、手動で各サービスを起動する手間を省き、環境構築を簡素化したいからだ。

---

## 関連情報

| 項目 | 値 |
|------|-----|
| Epic | [EPIC-001: Docker 環境構築](../epic.md) |
| ポイント | 3 |
| 優先度 | Must |
| ステータス | Draft |

---

## 受け入れ条件

1. [ ] `docker compose up -d` で全サービスが起動すること
2. [ ] `docker compose down` で全サービスが停止すること
3. [ ] `docker compose ps` で全サービスのステータスが確認できること
4. [ ] データベースデータがボリュームで永続化されること
5. [ ] 各サービス間のネットワーク通信が可能なこと

---

## 技術仕様

### サービス定義

```yaml
services:
  frontend:
    build: ./frontend
    ports:
      - "${FRONTEND_PORT:-5173}:5173"
    volumes:
      - ./frontend:/app
      - /app/node_modules
    depends_on:
      - backend

  backend:
    build: ./backend
    ports:
      - "${BACKEND_PORT:-8000}:8000"
    volumes:
      - ./backend:/var/www/html
    depends_on:
      - db
    environment:
      - DB_HOST=db
      - DB_DATABASE=${DB_DATABASE:-library}
      - DB_USERNAME=${DB_USERNAME:-library}
      - DB_PASSWORD=${DB_PASSWORD:-secret}

  db:
    image: mysql:8.0
    ports:
      - "${DB_PORT:-3306}:3306"
    environment:
      MYSQL_DATABASE: ${DB_DATABASE:-library}
      MYSQL_USER: ${DB_USERNAME:-library}
      MYSQL_PASSWORD: ${DB_PASSWORD:-secret}
      MYSQL_ROOT_PASSWORD: ${DB_ROOT_PASSWORD:-secret}
    volumes:
      - db_data:/var/lib/mysql

  phpmyadmin:
    image: phpmyadmin:latest
    ports:
      - "${PHPMYADMIN_PORT:-8080}:80"
    environment:
      PMA_HOST: db
      PMA_USER: ${DB_USERNAME:-library}
      PMA_PASSWORD: ${DB_PASSWORD:-secret}
    depends_on:
      - db

  nginx:
    image: nginx:alpine
    ports:
      - "${NGINX_PORT:-80}:80"
    volumes:
      - ./infrastructure/nginx/default.conf:/etc/nginx/conf.d/default.conf
    depends_on:
      - frontend
      - backend

volumes:
  db_data:
```

---

## 成果物

| 成果物 | 配置場所 |
|--------|---------|
| docker-compose.yml | プロジェクトルート |

---

## タスク

### Design Tasks（外部設計）

- [ ] サービス構成の確定
- [ ] ポート割り当ての確定
- [ ] ボリュームマウント設計

### Spec Tasks（詳細設計）

- [ ] docker-compose.yml の作成
- [ ] 動作確認テスト

---

## 変更履歴

| 日付 | 変更内容 |
|------|---------|
| 2025-12-23 | 初版作成 |
