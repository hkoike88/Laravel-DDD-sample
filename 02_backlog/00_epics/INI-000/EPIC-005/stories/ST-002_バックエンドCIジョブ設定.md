# ST-002: バックエンド CI ジョブの設定

最終更新: 2025-12-23

---

## ストーリー

**開発者として**、バックエンドの静的解析とテストを CI で自動実行したい。
**なぜなら**、PHP コードの品質を継続的にチェックし、リグレッションを防ぎたいからだ。

---

## 関連情報

| 項目 | 値 |
|------|-----|
| Epic | [EPIC-005: CI/CD 環境構築](../epic.md) |
| ポイント | 3 |
| 優先度 | Must |
| ステータス | Draft |

---

## 受け入れ条件

1. [ ] PHP 8.3 環境がセットアップされること
2. [ ] MySQL 8.0 サービスが起動されること
3. [ ] Composer 依存パッケージがキャッシュされること
4. [ ] PHPStan による静的解析が実行されること
5. [ ] Pint によるコードスタイルチェックが実行されること
6. [ ] Pest によるテストが実行されること
7. [ ] テストがデータベースに接続できること

---

## 技術仕様

### ジョブ定義

```yaml
backend:
  name: Backend
  runs-on: ubuntu-latest
  defaults:
    run:
      working-directory: backend

  services:
    mysql:
      image: mysql:8.0
      env:
        MYSQL_ROOT_PASSWORD: secret
        MYSQL_DATABASE: library_test
        MYSQL_USER: library
        MYSQL_PASSWORD: secret
      ports:
        - 3306:3306
      options: >-
        --health-cmd="mysqladmin ping"
        --health-interval=10s
        --health-timeout=5s
        --health-retries=5

  steps:
    - name: Checkout
      uses: actions/checkout@v4

    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: ${{ env.PHP_VERSION }}
        extensions: dom, curl, libxml, mbstring, zip, pcntl, pdo, pdo_mysql
        coverage: none

    - name: Get Composer cache directory
      id: composer-cache
      run: echo "dir=$(composer config cache-files-dir)" >> $GITHUB_OUTPUT

    - name: Cache Composer dependencies
      uses: actions/cache@v4
      with:
        path: ${{ steps.composer-cache.outputs.dir }}
        key: ${{ runner.os }}-composer-${{ hashFiles('backend/composer.lock') }}
        restore-keys: ${{ runner.os }}-composer-

    - name: Install dependencies
      run: composer install --no-interaction --prefer-dist

    - name: Copy .env
      run: cp .env.testing .env

    - name: Generate application key
      run: php artisan key:generate

    - name: PHPStan
      run: ./vendor/bin/phpstan analyse --memory-limit=512M --no-progress

    - name: Pint
      run: ./vendor/bin/pint --test

    - name: Run tests
      run: ./vendor/bin/pest --parallel
      env:
        DB_CONNECTION: mysql
        DB_HOST: 127.0.0.1
        DB_PORT: 3306
        DB_DATABASE: library_test
        DB_USERNAME: library
        DB_PASSWORD: secret
```

### MySQL サービス設定

| 設定 | 値 | 説明 |
|------|-----|------|
| イメージ | mysql:8.0 | MySQL バージョン |
| DB 名 | library_test | テスト用データベース |
| ユーザー | library | 接続ユーザー |
| パスワード | secret | 接続パスワード |
| Health Check | mysqladmin ping | サービス起動確認 |

### 実行ステップ

| ステップ | 内容 | 失敗時 |
|----------|------|--------|
| PHPStan | 静的解析 | ジョブ失敗 |
| Pint | コードスタイルチェック | ジョブ失敗 |
| Pest | ユニット・機能テスト | ジョブ失敗 |

---

## 成果物

| 成果物 | 配置場所 |
|--------|---------|
| Backend ジョブ定義 | .github/workflows/ci.yml |
| テスト用環境設定 | backend/.env.testing |

---

## タスク

### Design Tasks（外部設計）

- [ ] MySQL サービス設定の決定
- [ ] 実行するチェック項目の決定
- [ ] キャッシュ戦略の決定

### Spec Tasks（詳細設計）

- [ ] backend ジョブの定義
- [ ] MySQL サービスの設定
- [ ] Composer キャッシュの設定
- [ ] PHPStan ステップの追加
- [ ] Pint ステップの追加
- [ ] Pest ステップの追加
- [ ] .env.testing の作成

---

## トラブルシューティング

| 問題 | 原因 | 解決策 |
|------|------|--------|
| MySQL 接続失敗 | サービス未起動 | health check オプション確認 |
| PHPStan メモリ不足 | デフォルト制限 | --memory-limit=512M 指定 |
| Composer インストール遅い | キャッシュミス | キャッシュキー確認 |

---

## 変更履歴

| 日付 | 変更内容 |
|------|---------|
| 2025-12-23 | 初版作成 |
