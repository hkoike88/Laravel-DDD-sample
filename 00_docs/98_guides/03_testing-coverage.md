# テストカバレッジ取得ガイド

## 概要

このプロジェクトでは、テストカバレッジの取得に **PCOV** を使用します。
PCOVはXdebugよりも高速で、CI/CD環境でのテスト実行に適しています。

---

## 必要な環境

### PCOV拡張のインストール

**Dockerコンテナ内で実行する場合**、PCOV拡張がインストールされている必要があります。

#### インストール確認

```bash
docker compose exec backend php -m | grep pcov
```

出力例：
```
pcov
```

#### インストール方法（未インストールの場合）

Dockerfileに以下を追加：

```dockerfile
# PCOV拡張をインストール（テストカバレッジ用）
RUN pecl install pcov && docker-php-ext-enable pcov
```

---

## カバレッジ取得方法

### 1. HTML形式でカバレッジを取得

```bash
# Dockerコンテナ内で実行
docker compose exec backend php artisan test --coverage-html=build/coverage

# または Pest コマンドで直接実行
docker compose exec backend ./vendor/bin/pest --coverage --coverage-html=build/coverage
```

生成されたHTMLレポートは `build/coverage/index.html` で確認できます。

ブラウザで開く：
```bash
# ローカルマシンから
open backend/build/coverage/index.html  # macOS
xdg-open backend/build/coverage/index.html  # Linux
```

### 2. ターミナルにカバレッジを表示

```bash
# シンプルなサマリー表示
docker compose exec backend php artisan test --coverage

# 詳細表示（各ファイルのカバレッジ率を表示）
docker compose exec backend ./vendor/bin/pest --coverage --min=80
```

出力例：
```
  PASS  Tests\Unit\ExampleTest
  ✓ that true is true

  PASS  Tests\Feature\ExampleTest
  ✓ the application returns a successful response

  Tests:    2 passed (2 assertions)
  Duration: 0.15s

  Code Coverage ................................................... 85.5%
   Packages\Domain\Book\Domain\Model\Book ........................ 92.3%
   Packages\Domain\Book\Application\UseCases\Search .............. 78.5%
   App\Http\Controllers\BookController ........................... 90.0%
```

### 3. XML形式でカバレッジを取得（CI/CD用）

```bash
docker compose exec backend ./vendor/bin/pest --coverage --coverage-clover=build/coverage/clover.xml
```

---

## カバレッジ設定

### phpunit.xml の設定

カバレッジ対象のディレクトリ設定：

```xml
<source>
    <include>
        <directory>app</directory>
        <directory>packages</directory>
    </include>
    <exclude>
        <!-- Eloquent モデルは除外（ビジネスロジックがない） -->
        <directory>packages/*/Infrastructure/EloquentModels</directory>
        <!-- API Resource は除外（シンプルな変換のみ） -->
        <directory>packages/*/Presentation/HTTP/Resources</directory>
    </exclude>
</source>

<coverage>
    <report>
        <!-- HTML レポート出力先 -->
        <html outputDirectory="build/coverage"/>
        <!-- ターミナル出力設定 -->
        <text outputFile="php://stdout" showUncoveredFiles="false"/>
    </report>
</coverage>
```

### カバレッジ除外対象

以下のファイル/ディレクトリはカバレッジ計測から除外しています：

1. **Eloquent Models** (`packages/*/Infrastructure/EloquentModels`)
   - 理由: ビジネスロジックを含まない、DBとのマッピングのみ

2. **API Resources** (`packages/*/Presentation/HTTP/Resources`)
   - 理由: シンプルなデータ変換のみ

---

## カバレッジ目標

### 推奨カバレッジ率

| レイヤー | 目標カバレッジ率 | 理由 |
|---------|----------------|------|
| Domain層 | 90%以上 | ビジネスロジックの中核 |
| Application層 | 85%以上 | UseCase・DTO |
| Presentation層 | 70%以上 | Controller・FormRequest |
| Infrastructure層 | 50%以上 | Repository実装 |

### 最低カバレッジ率の設定

CI/CDでカバレッジ率をチェックする場合：

```bash
# 最低80%のカバレッジを要求
docker compose exec backend ./vendor/bin/pest --coverage --min=80
```

カバレッジが80%未満の場合、テストは失敗します。

---

## PCOV vs Xdebug

### なぜPCOVを使うのか？

| 項目 | PCOV | Xdebug |
|------|------|--------|
| 速度 | 🟢 非常に高速 | 🔴 遅い |
| メモリ使用量 | 🟢 少ない | 🟡 多い |
| カバレッジ取得 | ✅ | ✅ |
| デバッグ機能 | ❌ | ✅ |
| CI/CD適性 | 🟢 最適 | 🟡 可能だが遅い |

**結論**: カバレッジ取得にはPCOV、デバッグにはXdebugを使い分ける。

### PCOVの有効化/無効化

**有効化**:
```bash
# php.ini に追加
extension=pcov.so
pcov.enabled=1
```

**無効化**:
```bash
# php.ini から削除、または
pcov.enabled=0
```

---

## トラブルシューティング

### PCOV拡張が見つからない

**エラー**:
```
PHP Fatal error: PCOV is not loaded
```

**解決方法**:
1. PCOV拡張をインストール
   ```bash
   # Dockerコンテナ内
   pecl install pcov
   docker-php-ext-enable pcov
   ```

2. コンテナを再起動
   ```bash
   docker compose restart backend
   ```

### カバレッジが0%と表示される

**原因**: PCOVが無効化されている

**解決方法**:
```bash
# PCOVの状態を確認
docker compose exec backend php -i | grep pcov

# pcov.enabled=1 になっているか確認
```

### packages/ ディレクトリが除外される

**原因**: autoload設定が正しくない

**解決方法**:
`composer.json` の `autoload` セクションを確認：
```json
{
    "autoload": {
        "psr-4": {
            "App\\": "app/",
            "Packages\\": "packages/"
        }
    }
}
```

設定後、オートロードを再生成：
```bash
docker compose exec backend composer dump-autoload
```

---

## CI/CD 統合

### GitHub Actions の例

```yaml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4

      - name: Setup PHP with PCOV
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
          coverage: pcov

      - name: Install Dependencies
        run: composer install

      - name: Run Tests with Coverage
        run: ./vendor/bin/pest --coverage --min=80 --coverage-clover=coverage.xml

      - name: Upload Coverage to Codecov
        uses: codecov/codecov-action@v4
        with:
          files: ./coverage.xml
```

---

## 参考リンク

- [PCOV - GitHub](https://github.com/krakjoe/pcov)
- [Pest - Code Coverage](https://pestphp.com/docs/code-coverage)
- [PHPUnit - Code Coverage](https://docs.phpunit.de/en/11.5/code-coverage.html)

---

**最終更新**: 2026-01-12
