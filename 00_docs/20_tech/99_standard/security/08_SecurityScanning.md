# Security Scanning Guide

このプロジェクトのセキュリティスキャンツールとその使用方法をまとめたガイドです。

**Last updated:** 2025-12-23

## 目次

- [概要](#概要)
- [SAST（静的解析）](#sast静的解析)
- [DAST（動的解析）](#dast動的解析)
  - [OWASP ZAP](#owasp-zap)
  - [OpenVAS](#openvas)
  - [Burp Suite](#burp-suite)
- [レポート一覧](#セキュリティスキャンレポート一覧)

---

## 概要

このプロジェクトでは、以下のセキュリティスキャンを実施しています：

- **SAST (Static Application Security Testing)**: ソースコードの静的解析
- **DAST (Dynamic Application Security Testing)**: 実行中のアプリケーションの動的解析

### クイックスタート

```bash
# すべてのセキュリティスキャンを実行
make security-scan-all

# レポートのサマリーを表示
make security-report

# CRITICAL/HIGHのみ表示
make security-report-high
```

---

## SAST（静的解析）

ソースコードを解析して脆弱性を検出します。

### Backend（PHP/Laravel）

#### 1. Composer Audit

Composer依存パッケージの既知の脆弱性をセキュリティアドバイザリと照合します。

```bash
cd backend

# 脆弱性スキャン
composer audit

# JSON形式で出力
composer audit --format=json > reports/security/composer-audit.json

# ロックファイルのみチェック（高速）
composer audit --locked
```

**レポート形式:** JSON, テキスト
**出力先:** `backend/reports/security/composer-audit.json`

#### 2. PHPStan / Larastan

PHPコードの静的解析ツール。LarastanはLaravel特化の拡張です。

```bash
cd backend

# PHPStanインストール（初回のみ）
composer require --dev phpstan/phpstan nunomaduro/larastan

# 静的解析実行
./vendor/bin/phpstan analyse

# レベル指定（0-9、高いほど厳格）
./vendor/bin/phpstan analyse --level=5

# 特定ディレクトリのみ
./vendor/bin/phpstan analyse app/Http app/Services
```

**設定ファイル:** `phpstan.neon`

```neon
# phpstan.neon
includes:
    - vendor/nunomaduro/larastan/extension.neon

parameters:
    paths:
        - app/
    level: 5
    ignoreErrors:
        # 必要に応じて除外ルールを追加
    checkMissingIterableValueType: false
```

**レポート形式:** テキスト, JSON
**出力先:** `backend/reports/security/phpstan-report.json`

#### 3. PHP_CodeSniffer（セキュリティルール）

コーディング規約とセキュリティパターンのチェック

```bash
cd backend

# インストール
composer require --dev squizlabs/php_codesniffer

# 実行
./vendor/bin/phpcs --standard=PSR12 app/

# 自動修正
./vendor/bin/phpcbf --standard=PSR12 app/
```

#### 4. Psalm（オプション）

より厳格な静的解析ツール（型安全性重視）

```bash
cd backend

# インストール
composer require --dev vimeo/psalm

# 初期化
./vendor/bin/psalm --init

# 実行
./vendor/bin/psalm

# セキュリティ解析（taint analysis）
./vendor/bin/psalm --taint-analysis
```

詳細は [STATIC_ANALYSIS.md](../STATIC_ANALYSIS.md) を参照。

### Frontend（TypeScript/React）

#### 1. npm audit

npm依存関係の脆弱性スキャン

```bash
cd frontend
npm audit --json > reports/security/npm-audit.json

# 修正可能な脆弱性を自動修正
npm audit fix
```

**レポート形式:** JSON
**出力先:** `frontend/reports/security/npm-audit.json`

#### 2. ESLint security

ESLintのセキュリティプラグインを使用したコードスキャン

```bash
cd frontend
npm run lint
```

**出力:** コンソール

### Makefileコマンド

```bash
# すべてのSASTスキャンを実行
make security-scan-all

# バックエンドのみ
make security-scan-backend

# フロントエンドのみ
make security-scan-frontend

# レポートサマリー表示
make security-report

# CRITICAL/HIGHのみ表示
make security-report-high

# CI環境モード（CRITICAL/HIGHで失敗）
make security-scan-ci
```

---

## DAST（動的解析）

実行中のアプリケーションをスキャンして脆弱性を検出します。

---

## OWASP ZAP

Webアプリケーション脆弱性スキャナー。実行中のアプリケーションに対して攻撃をシミュレートし、脆弱性を検出します。

### 前提条件

ZAPデーモンが起動している必要があります。

```bash
# ZAPデーモン起動（初回のみ、または停止している場合）
make security-zap-start
# または: /opt/zaproxy/zap.sh -daemon -host 0.0.0.0 -port 8090 -config api.key=changeme &

# ZAPデーモン停止
make security-zap-stop
```

### フロントエンド（Vite開発サーバー）のスキャン

```bash
# 1. フロントエンドを起動（localhost:5200で起動）
cd frontend && npm run dev

# 2. Makefile経由でスキャン（推奨）
make security-scan-zap
```

#### 手動スキャン

```bash
# Spider Scan（クローリング）
curl "http://localhost:8090/JSON/spider/action/scan/?url=http://localhost:5200/&maxChildren=0&recurse=true&apikey=changeme"

# Active Scan（脆弱性スキャン）
curl "http://localhost:8090/JSON/ascan/action/scan/?url=http://localhost:5200/&recurse=true&apikey=changeme"

# HTMLレポート生成
curl "http://localhost:8090/OTHER/core/other/htmlreport/?apikey=changeme" > frontend/reports/security/zap-report.html
```

#### 検出項目

- セキュリティヘッダー不足（X-Frame-Options, CSP, X-Content-Type-Options）
- クリックジャッキング脆弱性
- MIME sniffingリスク

#### レポート出力先

- HTML: `frontend/reports/security/zap-report.html`

#### 本番環境対応

- 設定ファイル: `infrastructure/nginx/security-headers.conf`
- X-Frame-Options、Content-Security-Policy、X-Content-Type-Options を設定

### バックエンドAPI（Laravel）のスキャン

```bash
# 1. バックエンドを起動（localhost:8000で起動）
make dev-up
# または: cd backend && php artisan serve --port=8000

# 2. 新しいZAPセッション作成（フロントエンドのアラートをクリア）
curl "http://localhost:8090/JSON/core/action/newSession/?name=backend-scan&overwrite=true&apikey=changeme"

# 3. Spider + Active Scan実行
curl "http://localhost:8090/JSON/spider/action/scan/?url=http://localhost:8000/&maxChildren=0&recurse=true&apikey=changeme"
sleep 5
curl "http://localhost:8090/JSON/ascan/action/scan/?url=http://localhost:8000/&recurse=true&apikey=changeme"
sleep 10

# 4. HTMLレポート生成
mkdir -p backend/reports/security
curl "http://localhost:8090/OTHER/core/other/htmlreport/?apikey=changeme" > backend/reports/security/zap-report.html

# 5. アラート確認
curl "http://localhost:8090/JSON/core/view/alerts/?baseurl=http://localhost:8000/&apikey=changeme"
```

#### 検出項目

- 🔴 HIGH: デバッグモード有効時の情報漏洩（`APP_DEBUG=true` の場合）
- 🟡 MEDIUM: CSRFトークン不備（API以外のルート）
- 🟡 MEDIUM: セッション設定の不備（`SESSION_SECURE_COOKIE`）
- 🟢 LOW: X-Powered-By ヘッダー露出
- 🟢 LOW: Server ヘッダー露出

#### レポート出力先

- HTML: `backend/reports/security/zap-report.html`
- 詳細分析: `backend/reports/security/ZAP_SCAN_RESULTS.md`

#### 本番環境対応

- 設定ファイル: `backend/.env.production`

```bash
# .env.production の設定例
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

# セッションセキュリティ
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax

# HTTPS強制
FORCE_HTTPS=true
```

- `config/app.php` でデバッグ情報の制御
- Nginxでセキュリティヘッダーを設定（`infrastructure/nginx/security-headers.conf`）

### ZAP スキャン進捗確認

```bash
# Spider Scan進捗
curl "http://localhost:8090/JSON/spider/view/status/?scanId=0&apikey=changeme"

# Active Scan進捗
curl "http://localhost:8090/JSON/ascan/view/status/?scanId=0&apikey=changeme"
```

---

## OpenAPI定義を使用したZAPスキャン（推奨）

### OpenAPI定義ファイル

- ファイル: `backend/openapi-current.yaml`
- 形式: OpenAPI 3.0.3
- 内容: 実装済みの19エンドポイントを定義（Authentication、Domains、Topics、Questions、Bookmarks、Dashboard、SRS、Admin）

### メリット

- ✅ すべてのエンドポイントを自動検出（手動で各エンドポイントをスキャンする必要がない）
- ✅ リクエスト/レスポンススキーマが定義されているため、より正確なテストが可能
- ✅ APIドキュメントとして再利用可能

### OpenAPI定義を使用したスキャン手順（認証なし）

```bash
# 1. OpenAPI定義の検証
python3 -c "import yaml; yaml.safe_load(open('backend/openapi-current.yaml'))"

# 2. 新しいZAPセッション作成
curl "http://localhost:8090/JSON/core/action/newSession/?name=backend-openapi-scan&overwrite=true&apikey=changeme"

# 3. OpenAPI定義をZAPにインポート
curl "http://localhost:8090/JSON/openapi/action/importFile/?file=/absolute/path/to/backend/openapi-current.yaml&apikey=changeme"

# 4. Active Scan実行
curl "http://localhost:8090/JSON/ascan/action/scan/?url=http://localhost:8080&recurse=true&apikey=changeme"

# 5. スキャン進捗確認
curl "http://localhost:8090/JSON/ascan/view/status/?scanId=0&apikey=changeme"

# 6. HTMLレポート生成
curl "http://localhost:8090/OTHER/core/other/htmlreport/?apikey=changeme" > backend/reports/security/zap-openapi-scan-report.html
```

### 検出項目（OpenAPIスキャン - 認証なし）

- 🟢 LOW: Application Error Disclosure（エラーページに機密情報が含まれる）
- 🟢 LOW: Information Disclosure - Debug Error Messages
- 認証付きエンドポイントは401エラーで制限されるため、完全なテストにはJWT認証設定が必要

### レポート出力先（認証なし）

- HTML: `backend/reports/security/zap-openapi-scan-report.html`
- 詳細分析: `backend/reports/security/ZAP_OPENAPI_SCAN_RESULTS.md`

---

## Sanctum認証付きOpenAPIスキャン（完全版）

### 前提条件: テストユーザーとAPIトークンの準備

Laravel Sanctumを使用してAPIトークンを取得します。

```bash
# 1. テストユーザーの登録（初回のみ）
http --ignore-stdin POST http://localhost:8000/api/v1/auth/register \
  name='ZAP Scanner' \
  email=zapscan@example.com \
  password='ZapScan@123' \
  password_confirmation='ZapScan@123'

# 2. メール認証フラグを有効化（開発環境のみ）
docker-compose exec mysql mysql -u root -p -e \
  "UPDATE users SET email_verified_at = NOW() WHERE email = 'zapscan@example.com';" your_database

# または tinker を使用
php artisan tinker --execute="User::where('email', 'zapscan@example.com')->update(['email_verified_at' => now()]);"

# 3. Sanctumトークンを取得
http --ignore-stdin POST http://localhost:8000/api/v1/auth/login \
  email=zapscan@example.com \
  password='ZapScan@123' \
  | jq -r '.data.token' > /tmp/zap-sanctum-token.txt

# トークンを確認
cat /tmp/zap-sanctum-token.txt
```

#### Sanctumトークン発行の実装例

```php
// app/Http/Controllers/Api/AuthController.php
public function login(Request $request)
{
    $credentials = $request->validate([
        'email' => 'required|email',
        'password' => 'required',
    ]);

    if (!Auth::attempt($credentials)) {
        return response()->json(['message' => 'Invalid credentials'], 401);
    }

    $user = Auth::user();
    $token = $user->createToken('api-token')->plainTextToken;

    return response()->json([
        'data' => [
            'token' => $token,
            'user' => $user,
        ]
    ]);
}
```

### 認証付きスキャン手順

```bash
# 1. 新しいZAPセッション作成
curl "http://localhost:8090/JSON/core/action/newSession/?name=backend-auth-scan&overwrite=true&apikey=changeme"

# 2. ZAP Replacerルール追加（全リクエストにSanctumトークンを自動付与）
TOKEN=$(cat /tmp/zap-sanctum-token.txt)
curl "http://localhost:8090/JSON/replacer/action/addRule/?description=Add%20Sanctum%20Token&enabled=true&matchType=REQ_HEADER&matchRegex=false&matchString=Authorization&replacement=Bearer%20${TOKEN}&initiators=&apikey=changeme"

# 3. OpenAPI定義をインポート
curl "http://localhost:8090/JSON/openapi/action/importFile/?file=$(pwd)/backend/openapi-current.yaml&apikey=changeme"

# 4. Active Scan実行
curl "http://localhost:8090/JSON/ascan/action/scan/?url=http://localhost:8000&recurse=true&apikey=changeme"

# 5. スキャン進捗確認
curl "http://localhost:8090/JSON/ascan/view/status/?scanId=0&apikey=changeme"

# 6. HTMLレポート生成
curl "http://localhost:8090/OTHER/core/other/htmlreport/?apikey=changeme" > backend/reports/security/zap-authenticated-scan-report.html
```

### 検出項目（認証付きスキャン）

- **認証付きエンドポイント**: すべてのAPIルートをスキャン可能
- 🟡 MEDIUM: Mass Assignment 脆弱性（$fillableの設定不備）
- 🟡 MEDIUM: IDOR（Insecure Direct Object Reference）
- 🟢 LOW: Application Error Disclosure（`APP_DEBUG=true` の場合）
- 🟢 LOW: Information Disclosure - Stack Trace
- スキャン対象URL: 認証なしの約2倍

### レポート出力先（認証付き）

- HTML: `backend/reports/security/zap-authenticated-scan-report.html`
- 詳細分析: `backend/reports/security/ZAP_AUTHENTICATED_SCAN_RESULTS.md`

### 認証付きスキャンのメリット

- ✅ 認証必須のAPIエンドポイントのテストが可能
- ✅ ビジネスロジックの脆弱性を検出
- ✅ アクセス制御（Policy/Gate）が正常に機能しているかを検証
- ✅ より包括的なセキュリティテスト

### Laravel固有のセキュリティチェックポイント

| チェック項目 | 確認方法 | 対策 |
|-------------|---------|------|
| Mass Assignment | `$fillable` / `$guarded` の設定確認 | モデルで明示的に定義 |
| IDOR | 他ユーザーのリソースへのアクセス試行 | Policy/Gateで認可チェック |
| SQL Injection | Eloquentの生クエリ使用箇所 | プレースホルダー使用 |
| XSS | Bladeテンプレートのエスケープ | `{{ }}` を使用（`{!! !!}` は避ける） |
| CSRF | APIルート以外のPOST/PUT/DELETE | `@csrf` ディレクティブ使用 |

### OpenAPI定義の更新

新しいエンドポイントを追加した場合、OpenAPI定義を更新します。

```bash
# 1. L5-Swagger を使用して自動生成（推奨）
composer require darkaonline/l5-swagger
php artisan vendor:publish --provider "L5Swagger\L5SwaggerServiceProvider"
php artisan l5-swagger:generate

# 2. 手動で編集する場合
vim backend/openapi-current.yaml

# 3. YAML構文を検証
python3 -c "import yaml; yaml.safe_load(open('backend/openapi-current.yaml'))"

# 4. 再度ZAPスキャンを実行（上記手順を繰り返す）
```

---

## OpenVAS

### 概要

**OpenVAS (Open Vulnerability Assessment System)** は、Greenbone Community Editionとして提供されるネットワークおよびWebアプリケーション脆弱性スキャナーです。

### 主な機能

- ✅ 131,000以上の脆弱性テスト（NVT: Network Vulnerability Tests）
- ✅ ネットワーク/ポートスキャン
- ✅ Webアプリケーション脆弱性検出
- ✅ CVE（既知の脆弱性）データベースとの照合
- ✅ PDF、HTML、XML形式のレポート出力

### セットアップ

```bash
# OpenVASコンテナを起動
sg docker -c "docker-compose -f docker-compose-openvas.yml up -d"

# 初期化の進捗確認（10〜20分程度かかります）
sg docker -c "docker logs -f openvas"

# ヘルスチェック確認
sg docker -c "docker inspect openvas --format='{{.State.Health.Status}}'"
```

### アクセス情報

- **URL**: https://localhost:9392
- **Username**: admin
- **Password**: admin（初回ログイン後に変更推奨）

### スキャン手順

1. Web UI（https://localhost:9392）にログイン
2. **Configuration → Targets** で対象ホストを設定
   - ホスト: `172.18.0.1:8080`（WSL2のIPアドレス + ポート）
3. **Scans → Tasks** で新しいタスクを作成
   - スキャンプロファイル: **Full and fast**（推奨）
4. タスクを開始して結果を確認

### 詳細ドキュメント

[OPENVAS_SETUP.md](../OPENVAS_SETUP.md)

---

## Burp Suite

**Burp Suite** は、PortSwigger社が開発するWebアプリケーションセキュリティテストのための統合プラットフォームです。手動のペネトレーションテストから自動スキャンまで、幅広いセキュリティテストに対応しています。

### エディションの比較

| 機能 | Community Edition（無料） | Professional（有料） |
|------|--------------------------|---------------------|
| Proxy（通信傍受） | ✅ | ✅ |
| Repeater（リクエスト再送） | ✅ | ✅ |
| Intruder（自動攻撃） | 制限あり（低速） | ✅ 無制限 |
| Scanner（自動脆弱性スキャン） | ❌ | ✅ |
| Collaborator（OAST） | ❌ | ✅ |
| プロジェクト保存 | ❌ | ✅ |
| 拡張機能 | 一部制限 | ✅ フル対応 |

### インストール

```bash
# 公式サイトからダウンロード
# https://portswigger.net/burp/releases

# Linux（JARファイル）
java -jar burpsuite_community_v2024.x.x.jar

# または公式インストーラーを使用
chmod +x burpsuite_community_linux_v2024_x_x.sh
./burpsuite_community_linux_v2024_x_x.sh
```

### 初期設定

#### 1. プロキシ設定

Burp Suiteはデフォルトで `127.0.0.1:8080` でプロキシとして動作します。

```bash
# ブラウザのプロキシ設定
# HTTP Proxy: 127.0.0.1
# Port: 8080

# または環境変数で設定
export HTTP_PROXY=http://127.0.0.1:8080
export HTTPS_PROXY=http://127.0.0.1:8080
```

#### 2. CA証明書のインストール

HTTPS通信を傍受するには、Burp SuiteのCA証明書をブラウザにインストールする必要があります。

1. ブラウザで `http://burp` にアクセス
2. "CA Certificate" をクリックしてダウンロード
3. ブラウザの証明書設定でインポート

```bash
# Firefoxの場合
# 設定 → プライバシーとセキュリティ → 証明書を表示 → 認証局証明書 → インポート

# Chromeの場合
# 設定 → プライバシーとセキュリティ → セキュリティ → 証明書の管理 → 認証局 → インポート
```

---

### 基本機能

#### Proxy（通信傍受・改変）

ブラウザとサーバー間の通信をリアルタイムで傍受・改変できます。

**使用手順：**

1. **Proxy → Intercept** タブを開く
2. **Intercept is on** を確認
3. ブラウザでターゲットサイトにアクセス
4. リクエストが傍受され、編集可能な状態で表示される
5. **Forward** で送信、**Drop** で破棄

```
# 傍受したリクエストの例
POST /v1/auth/login HTTP/1.1
Host: localhost:8080
Content-Type: application/json

{"email":"test@example.com","password":"password123"}
```

**よく使う操作：**
- `Ctrl+F`: Forward（リクエスト送信）
- `Ctrl+D`: Drop（リクエスト破棄）
- `Ctrl+I`: Intruderへ送信
- `Ctrl+R`: Repeaterへ送信

#### Repeater（リクエスト再送・編集）

傍受したリクエストを何度も編集・再送信できます。

**使用シナリオ：**
- 認証バイパスのテスト
- パラメータ改ざんの検証
- SQLインジェクションの手動テスト

```bash
# 手順
1. Proxy → HTTP history からリクエストを選択
2. 右クリック → Send to Repeater
3. Repeater タブでパラメータを編集
4. Send をクリックしてリクエスト送信
5. レスポンスを確認
```

**テスト例：認証バイパス**

```
# 元のリクエスト
GET /v1/admin/users HTTP/1.1
Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...

# テスト1: 認証ヘッダーを削除
GET /v1/admin/users HTTP/1.1
(Authorization ヘッダーなし)

# テスト2: 別ユーザーのトークンに置換
GET /v1/admin/users HTTP/1.1
Authorization: Bearer <別ユーザーのトークン>
```

#### Intruder（自動攻撃）

パラメータに対して自動的に複数のペイロードを試行します。

**攻撃タイプ：**
- **Sniper**: 1つのペイロードセットを各位置に順番に適用
- **Battering ram**: 同じペイロードを全位置に同時適用
- **Pitchfork**: 複数のペイロードセットを対応する位置に並列適用
- **Cluster bomb**: 全てのペイロード組み合わせを試行

**使用例：ブルートフォース攻撃**

```bash
# 手順
1. リクエストをIntruderへ送信（Ctrl+I）
2. Positions タブで攻撃対象パラメータを選択（§ で囲む）
3. Payloads タブでペイロードリストを設定
4. Start attack をクリック

# 攻撃対象の設定例
POST /v1/auth/login HTTP/1.1
Content-Type: application/json

{"email":"admin@example.com","password":"§password§"}
```

**ペイロードリスト例（パスワード）：**

```
password
Password123
admin123
letmein
123456
password1
```

> **注意**: Community Editionでは攻撃速度が制限されます

#### Target（スコープ管理）

テスト対象のスコープを管理します。

```bash
# スコープに追加
1. Target → Site map からドメインを選択
2. 右クリック → Add to scope

# スコープ設定
Target → Scope settings
- Include in scope: テスト対象URL
- Exclude from scope: 除外URL（例：ログアウト、本番API）
```

---

### 自動スキャン（Professional版のみ）

#### アクティブスキャン

Webアプリケーションの脆弱性を自動検出します。

```bash
# 手順
1. Target → Site map でターゲットを選択
2. 右クリック → Scan
3. Scan configuration を選択
   - Crawl and audit: クロール + 脆弱性スキャン
   - Audit selected items: 選択項目のみスキャン
4. スキャン開始

# スキャン設定のカスタマイズ
Dashboard → New scan → Scan configuration
- Crawl: クロールの深さ、除外パス
- Audit: テストする脆弱性の種類
```

**検出可能な脆弱性：**

| カテゴリ | 脆弱性例 |
|---------|---------|
| インジェクション | SQLi, XSS, XXE, Command Injection |
| 認証・認可 | セッション管理、アクセス制御 |
| 設定 | セキュリティヘッダー、情報漏洩 |
| ビジネスロジック | レースコンディション、IDOR |

#### スキャン結果の確認

```bash
# 結果確認
Dashboard → Issue activity
または
Target → Site map → Issues

# 脆弱性の詳細
- Severity: High/Medium/Low/Info
- Confidence: Certain/Firm/Tentative
- Evidence: 検出の根拠
- Remediation: 修正方法
```

---

### APIテスト

#### REST APIのテスト

```bash
# 1. OpenAPI定義のインポート
Target → Site map → 右クリック → Import → OpenAPI definition
ファイル: backend/openapi-current.yaml

# 2. インポート後、各エンドポイントが Site map に表示される

# 3. 各エンドポイントを Repeater で手動テスト
# または Intruder で自動テスト
```

#### 認証付きAPIテスト

```bash
# JWTトークンの自動付与設定
Project options → Sessions → Session handling rules → Add

# ルール設定
1. Rule actions → Add → Set a specific cookie/header value
2. Header name: Authorization
3. Header value: Bearer <JWTトークン>
4. Scope: 対象URL
```

**APIテストのチェックリスト：**

- [ ] 認証バイパス（認証ヘッダー削除）
- [ ] IDOR（他ユーザーのリソースアクセス）
- [ ] レート制限（Intruderで大量リクエスト）
- [ ] SQLインジェクション（パラメータ改ざん）
- [ ] JSONインジェクション（ネストされたオブジェクト）
- [ ] Mass Assignment（余分なパラメータ追加）

#### GraphQL APIのテスト

```bash
# GraphQLエンドポイントの設定
1. Proxy で GraphQL リクエストを傍受
2. InQL 拡張機能をインストール（BApp Store）
3. InQL → Set endpoint: http://localhost:8080/graphql
4. スキーマを取得してクエリを自動生成

# イントロスペクションクエリ
POST /graphql HTTP/1.1
Content-Type: application/json

{"query":"{__schema{types{name fields{name}}}}"}
```

---

### 便利な拡張機能（BApp Store）

```bash
# 拡張機能のインストール
Extender → BApp Store → 検索 → Install
```

| 拡張機能 | 用途 |
|---------|------|
| **Logger++** | 詳細なリクエストログ |
| **Autorize** | 認可テストの自動化 |
| **JSON Web Tokens** | JWT解析・改ざん |
| **InQL** | GraphQLスキーマ分析 |
| **Param Miner** | 隠しパラメータ検出 |
| **Turbo Intruder** | 高速Intruder（Python） |
| **SQLMap Integration** | SQLMap連携 |

---

### レポート出力（Professional版）

```bash
# HTMLレポート生成
1. Target → Site map または Dashboard → Issues
2. 右クリック → Report selected issues
3. Format: HTML, XML, または PDF
4. 出力先: backend/reports/security/burp-report.html

# レポート内容
- Executive Summary: 概要サマリー
- Issue Details: 各脆弱性の詳細
- Remediation: 修正推奨事項
```

**レポート出力先：**
- HTML: `backend/reports/security/burp-report.html`
- XML: `backend/reports/security/burp-report.xml`

---

### OWASP ZAP との比較

| 観点 | Burp Suite | OWASP ZAP |
|------|------------|-----------|
| 価格 | 有料（Pro版） | 無料 |
| 手動テスト | 優秀 | 良好 |
| 自動スキャン | Pro版のみ | 無料で利用可 |
| 拡張機能 | BApp Store | Marketplace |
| 学習曲線 | やや高い | 低い |
| CI/CD統合 | Enterprise版 | 容易 |
| **推奨用途** | 手動ペネトレーションテスト | 自動スキャン・CI/CD |

**使い分けの指針：**
- **Burp Suite**: 手動での詳細なペネトレーションテスト、認証・認可テスト
- **OWASP ZAP**: CI/CDパイプラインでの自動スキャン、定期的な脆弱性チェック

---

## セキュリティスキャンレポート一覧

| ツール | 対象 | レポート形式 | 出力先 |
|--------|------|------------|--------|
| Composer audit | Backend (PHP) | JSON, テキスト | `backend/reports/security/composer-audit.json` |
| PHPStan / Larastan | Backend (PHP) | JSON, テキスト | `backend/reports/security/phpstan-report.json` |
| PHP_CodeSniffer | Backend (PHP) | テキスト | コンソール |
| npm audit | Frontend (React) | JSON | `frontend/reports/security/npm-audit.json` |
| ESLint security | Frontend (React) | コンソール | - |
| OWASP ZAP (DAST) | Frontend | HTML | `frontend/reports/security/zap-report.html` |
| OWASP ZAP (DAST) | Backend API (Laravel) | HTML, MD | `backend/reports/security/zap-report.html`<br>`backend/reports/security/ZAP_SCAN_RESULTS.md` |
| OWASP ZAP + OpenAPI (DAST) | Backend API (Laravel) | HTML, MD | `backend/reports/security/zap-openapi-scan-report.html`<br>`backend/reports/security/ZAP_OPENAPI_SCAN_RESULTS.md` |
| OWASP ZAP + Sanctum (DAST) | Backend API (認証付き) | HTML, MD | `backend/reports/security/zap-authenticated-scan-report.html`<br>`backend/reports/security/ZAP_AUTHENTICATED_SCAN_RESULTS.md` |
| OpenVAS (ネットワーク脆弱性) | インフラ・アプリケーション | PDF, HTML, XML | OpenVAS Web UI（https://localhost:9392） |
| Burp Suite (手動ペネトレーション) | Webアプリケーション・API | HTML, XML | `backend/reports/security/burp-report.{html,xml}` |

---

## ベストプラクティス

### 定期的なスキャン

```bash
# 毎週実行を推奨
make security-scan-all
make security-report
```

### CI/CDパイプラインへの統合

```bash
# CI環境でCRITICAL/HIGHが検出されたら失敗
make security-scan-ci
```

### 脆弱性の修正

1. レポートで検出された脆弱性を確認
2. 優先度（CRITICAL > HIGH > MEDIUM > LOW）に従って修正
3. 依存関係の脆弱性:
   - PHP: `composer update` で依存関係をバージョンアップ
   - JavaScript: `npm audit fix` で自動修正、または `npm update` でバージョンアップ
4. コードの脆弱性は手動で修正
5. Laravel固有の対応:
   - `APP_DEBUG=false` を本番環境で確認
   - `$fillable` / `$guarded` の設定を確認
   - Policy/Gateによる認可チェックを実装

### 参考リンク

- [DEVELOPMENT.md](../DEVELOPMENT.md) - 開発環境セットアップ
- [TROUBLESHOOTING.md](./TROUBLESHOOTING.md) - トラブルシューティング
- [API.md](./API.md) - API仕様
- [OWASP Top 10](https://owasp.org/www-project-top-ten/) - Webアプリケーションの主要な脆弱性
