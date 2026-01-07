# ST-002: バックエンド用 Dockerfile の作成

最終更新: 2025-12-23

---

## ストーリー

**開発者として**、Laravel アプリケーションを実行できるコンテナ環境を構築したい。
**なぜなら**、PHP と必要な拡張機能がインストールされた統一環境で開発を行いたいからだ。

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

1. [ ] PHP 8.3 が利用可能なこと
2. [ ] Laravel に必要な PHP 拡張がインストールされていること
3. [ ] Composer がインストールされていること
4. [ ] `php artisan serve` で開発サーバーが起動できること
5. [ ] ホットリロードが機能すること（ファイル変更が即時反映）

---

## 技術仕様

### ベースイメージ

- `php:8.3-fpm-alpine`（軽量で高速）

### 必要な PHP 拡張

| 拡張 | 用途 |
|------|------|
| pdo_mysql | MySQL 接続 |
| bcmath | 精度の高い数値計算 |
| mbstring | マルチバイト文字列 |
| exif | 画像メタデータ |
| pcntl | プロセス制御 |
| zip | ZIP ファイル操作 |
| gd | 画像処理 |

### Dockerfile 構成

```dockerfile
FROM php:8.3-fpm-alpine

# システム依存パッケージのインストール
RUN apk add --no-cache \
    git \
    curl \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    zip \
    unzip \
    libzip-dev

# PHP 拡張のインストール
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        bcmath \
        mbstring \
        exif \
        pcntl \
        zip \
        gd

# Composer のインストール
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 作業ディレクトリの設定
WORKDIR /var/www/html

# ユーザー設定（権限問題の回避）
RUN addgroup -g 1000 -S www && \
    adduser -u 1000 -S www -G www

USER www

EXPOSE 8000

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
```

---

## 成果物

| 成果物 | 配置場所 |
|--------|---------|
| Dockerfile | backend/ |
| .dockerignore | backend/ |

---

## タスク

### Design Tasks（外部設計）

- [ ] PHP バージョンの確定
- [ ] 必要な拡張の洗い出し

### Spec Tasks（詳細設計）

- [ ] Dockerfile の作成
- [ ] .dockerignore の作成
- [ ] ビルドテスト

---

## 変更履歴

| 日付 | 変更内容 |
|------|---------|
| 2025-12-23 | 初版作成 |
