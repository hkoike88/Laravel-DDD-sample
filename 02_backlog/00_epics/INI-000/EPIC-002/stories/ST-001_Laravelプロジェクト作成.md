# ST-001: Laravel プロジェクトの作成

最終更新: 2025-12-23

---

## ストーリー

**開発者として**、Laravel 11.x プロジェクトを作成したい。
**なぜなら**、バックエンド API 開発の基盤を整備したいからだ。

---

## 関連情報

| 項目 | 値 |
|------|-----|
| Epic | [EPIC-002: バックエンド初期設定](../epic.md) |
| ポイント | 2 |
| 優先度 | Must |
| ステータス | Draft |

---

## 受け入れ条件

1. [ ] Laravel 11.x がインストールされていること
2. [ ] `php artisan --version` でバージョンが確認できること
3. [ ] `php artisan serve` で開発サーバーが起動できること
4. [ ] ウェルカムページ（/）にアクセスできること
5. [ ] APP_KEY が生成されていること

---

## 技術仕様

### インストールコマンド

```bash
# バックエンドコンテナに入る
docker compose exec backend bash

# Laravel プロジェクトを作成（現在のディレクトリに展開）
composer create-project laravel/laravel . "11.*"

# または既存ディレクトリにインストール
composer create-project laravel/laravel temp "11.*"
mv temp/* temp/.[!.]* .
rmdir temp
```

### 初期設定

```bash
# アプリケーションキーの生成
php artisan key:generate

# ストレージリンクの作成
php artisan storage:link

# キャッシュクリア
php artisan config:clear
php artisan cache:clear
```

### 確認コマンド

```bash
# バージョン確認
php artisan --version
# 出力例: Laravel Framework 11.36.x

# 開発サーバー起動
php artisan serve --host=0.0.0.0 --port=8000
```

---

## 成果物

| 成果物 | 配置場所 |
|--------|---------|
| Laravel プロジェクト一式 | backend/ |
| 環境設定ファイル | backend/.env |

---

## タスク

### Design Tasks（外部設計）

- [ ] Laravel バージョンの確定（11.x）

### Spec Tasks（詳細設計）

- [ ] Laravel プロジェクトの作成
- [ ] APP_KEY の生成
- [ ] 動作確認

---

## 変更履歴

| 日付 | 変更内容 |
|------|---------|
| 2025-12-23 | 初版作成 |
