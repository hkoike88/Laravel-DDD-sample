# 暗号化ポリシー

## 概要

本ドキュメントは、システムにおけるデータ暗号化の設計標準とポリシーを定義する。
通信経路上のデータと保存データの両方を適切に保護し、機密性を確保する。

**Last updated:** 2025-12-26

---

## 目次

- [暗号化の基本方針](#暗号化の基本方針)
- [通信暗号化（TLS）](#通信暗号化tls)
- [保存データの暗号化](#保存データの暗号化)
- [パスワードのハッシュ化](#パスワードのハッシュ化)
- [鍵管理](#鍵管理)
- [証明書管理](#証明書管理)
- [暗号アルゴリズム選定基準](#暗号アルゴリズム選定基準)
- [実装ガイドライン](#実装ガイドライン)
- [監査・コンプライアンス](#監査コンプライアンス)

---

## 暗号化の基本方針

### 原則

1. **Defense in Depth**: 多層的な暗号化による保護
2. **標準準拠**: 業界標準のアルゴリズムと実装を使用
3. **鍵管理の徹底**: 鍵のライフサイクルを適切に管理
4. **将来への備え**: 量子耐性を考慮した計画

### 暗号化が必要なデータ

| データ種別 | 通信暗号化 | 保存時暗号化 | 備考 |
|-----------|:----------:|:----------:|------|
| 認証情報 | 必須 | 必須（ハッシュ） | パスワード、トークン |
| 個人情報（PII） | 必須 | 必須 | 氏名、住所、電話番号等 |
| 決済情報 | 必須 | 必須 | PCI DSS準拠 |
| セッションデータ | 必須 | 推奨 | セッションID、Cookie |
| ログデータ | 必須 | 推奨 | 機密情報を含む場合 |
| バックアップ | N/A | 必須 | 全バックアップを暗号化 |

---

## 通信暗号化（TLS）

### TLS要件

| 項目 | 要件 | 備考 |
|------|------|------|
| プロトコルバージョン | **TLS 1.2 以上** | TLS 1.0/1.1 は禁止 |
| 推奨バージョン | TLS 1.3 | 可能な限り使用 |
| 証明書 | 信頼された CA から発行 | 自己署名証明書は本番禁止 |
| 証明書の有効期間 | 最大 1 年 | 自動更新推奨 |

### 暗号スイート

#### TLS 1.3（推奨）

```
TLS_AES_256_GCM_SHA384
TLS_AES_128_GCM_SHA256
TLS_CHACHA20_POLY1305_SHA256
```

#### TLS 1.2（最低要件）

```nginx
# 推奨する暗号スイート（強い順）
ssl_ciphers 'ECDHE-ECDSA-AES256-GCM-SHA384:ECDHE-RSA-AES256-GCM-SHA384:ECDHE-ECDSA-CHACHA20-POLY1305:ECDHE-RSA-CHACHA20-POLY1305:ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256';

# 禁止する暗号スイート
# - CBC モードの暗号（BEAST 攻撃）
# - RC4（既知の脆弱性）
# - 3DES（Sweet32 攻撃）
# - NULL 暗号
# - 輸出グレード暗号
```

### Nginx設定例

```nginx
# /etc/nginx/conf.d/ssl.conf

# SSL/TLS 基本設定
ssl_protocols TLSv1.2 TLSv1.3;
ssl_prefer_server_ciphers on;
ssl_ciphers 'ECDHE-ECDSA-AES256-GCM-SHA384:ECDHE-RSA-AES256-GCM-SHA384:ECDHE-ECDSA-CHACHA20-POLY1305:ECDHE-RSA-CHACHA20-POLY1305';

# 証明書設定
ssl_certificate /etc/nginx/ssl/server.crt;
ssl_certificate_key /etc/nginx/ssl/server.key;

# セッション設定
ssl_session_timeout 1d;
ssl_session_cache shared:SSL:50m;
ssl_session_tickets off;

# OCSP Stapling
ssl_stapling on;
ssl_stapling_verify on;
ssl_trusted_certificate /etc/nginx/ssl/chain.pem;
resolver 8.8.8.8 8.8.4.4 valid=300s;
resolver_timeout 5s;

# HSTS（Strict Transport Security）
add_header Strict-Transport-Security "max-age=31536000; includeSubDomains; preload" always;
```

### 内部通信の暗号化

| 通信経路 | 暗号化要件 | 備考 |
|---------|:----------:|------|
| ブラウザ ↔ ロードバランサー | TLS 必須 | 公開経路 |
| ロードバランサー ↔ アプリケーション | TLS 推奨 | 内部でも暗号化 |
| アプリケーション ↔ データベース | TLS 推奨 | 機密データの場合は必須 |
| アプリケーション ↔ キャッシュ | TLS 推奨 | Redis/Memcached |

---

## 保存データの暗号化

### 暗号化レベル

| レベル | 説明 | 用途 |
|-------|------|------|
| **透過的暗号化（TDE）** | ディスク/DB レベルで自動暗号化 | 物理的な盗難対策 |
| **アプリケーション暗号化** | アプリケーション内で暗号化 | 特定フィールドの保護 |
| **クライアント暗号化** | クライアント側で暗号化 | E2E暗号化が必要な場合 |

### 対称暗号（データ暗号化）

| 用途 | 推奨アルゴリズム | 鍵長 |
|------|----------------|:----:|
| 一般データ暗号化 | AES-256-GCM | 256bit |
| 高速性が必要な場合 | AES-128-GCM | 128bit |
| 代替（モバイル等） | ChaCha20-Poly1305 | 256bit |

### 禁止するアルゴリズム

| アルゴリズム | 禁止理由 |
|-------------|---------|
| DES / 3DES | 鍵長不足、既知の攻撃 |
| RC4 | 既知の脆弱性 |
| ECB モード | パターン漏洩 |
| MD5 / SHA-1（署名用） | 衝突攻撃に脆弱 |

### Laravel での実装

```php
// config/app.php
'cipher' => 'AES-256-CBC',  // Laravel デフォルト

// 暗号化の使用
use Illuminate\Support\Facades\Crypt;

// 暗号化
$encrypted = Crypt::encryptString($plainText);

// 復号化
$decrypted = Crypt::decryptString($encrypted);

// モデルでの自動暗号化
class User extends Model
{
    protected $casts = [
        'phone_number' => 'encrypted',
        'address' => 'encrypted',
    ];
}
```

### データベース暗号化設定

```php
// MySQL 8.0 TDE（Transparent Data Encryption）
// my.cnf
[mysqld]
early-plugin-load=keyring_file.so
keyring_file_data=/var/lib/mysql-keyring/keyring

// テーブル作成時に暗号化を指定
CREATE TABLE sensitive_data (
    id INT PRIMARY KEY,
    data TEXT
) ENCRYPTION='Y';
```

---

## パスワードのハッシュ化

### ハッシュアルゴリズム

| アルゴリズム | 推奨度 | コスト/パラメータ |
|-------------|:------:|-----------------|
| **Argon2id** | 推奨 | memory=64MB, time=4, parallelism=4 |
| **bcrypt** | 許容 | cost=12 以上 |
| scrypt | 許容 | N=16384, r=8, p=1 |
| PBKDF2 | 非推奨 | 他が使えない場合のみ |

### Laravel での設定

```php
// config/hashing.php

// Argon2id（推奨）
'driver' => 'argon2id',
'argon' => [
    'memory' => 65536,   // 64MB
    'threads' => 4,
    'time' => 4,
],

// または bcrypt
'driver' => 'bcrypt',
'bcrypt' => [
    'rounds' => 12,
],
```

### ハッシュ化のガイドライン

```php
use Illuminate\Support\Facades\Hash;

// パスワードのハッシュ化
$hashedPassword = Hash::make($password);

// 検証
if (Hash::check($password, $hashedPassword)) {
    // 認証成功
}

// リハッシュが必要か確認（コスト変更時）
if (Hash::needsRehash($hashedPassword)) {
    $hashedPassword = Hash::make($password);
    // データベースを更新
}
```

---

## 鍵管理

### 鍵のライフサイクル

```
┌─────────┐   ┌─────────┐   ┌─────────┐   ┌─────────┐   ┌─────────┐
│  生成   │ → │  配布   │ → │  使用   │ → │ ローテーション │ → │  破棄   │
└─────────┘   └─────────┘   └─────────┘   └─────────┘   └─────────┘
     ↓             ↓             ↓             ↓             ↓
  安全な乱数     暗号化転送    アクセス制御   新鍵への移行    安全な消去
  十分な長さ     権限制限      監査ログ      旧鍵の保持      証跡記録
```

### 鍵の種類と管理

| 鍵の種類 | 保管場所 | ローテーション周期 | バックアップ |
|---------|---------|:------------------:|:----------:|
| APP_KEY（Laravel） | 環境変数 / シークレット管理 | 1年 | 必須 |
| データベース暗号化鍵 | KMS / HSM | 1年 | 必須 |
| TLS 秘密鍵 | ファイルシステム（制限付き） | 1年 | 必須 |
| API シークレット | 環境変数 / シークレット管理 | 90日 | 必須 |

### 鍵の生成

```bash
# Laravel APP_KEY の生成
php artisan key:generate

# 安全なランダムキーの生成
openssl rand -base64 32

# RSA 鍵ペアの生成（API署名用）
openssl genrsa -out private.pem 4096
openssl rsa -in private.pem -pubout -out public.pem
```

### 鍵の保管

```yaml
# 開発環境: .env ファイル（gitignore必須）
APP_KEY=base64:xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx

# 本番環境: シークレット管理サービス推奨
# - AWS Secrets Manager
# - HashiCorp Vault
# - Google Cloud Secret Manager
# - Azure Key Vault
```

### 鍵ローテーション手順

```php
// app/Console/Commands/RotateEncryptionKey.php
class RotateEncryptionKey extends Command
{
    protected $signature = 'encryption:rotate';

    public function handle(): void
    {
        // 1. 新しい鍵を生成
        $newKey = Str::random(32);

        // 2. 暗号化されたデータを再暗号化
        DB::table('users')->orderBy('id')->chunk(100, function ($users) use ($newKey) {
            foreach ($users as $user) {
                // 旧鍵で復号化
                $decrypted = Crypt::decryptString($user->phone_number);
                // 新鍵で再暗号化
                $reEncrypted = $this->encryptWithKey($decrypted, $newKey);
                // 更新
                DB::table('users')
                    ->where('id', $user->id)
                    ->update(['phone_number' => $reEncrypted]);
            }
        });

        // 3. 新しい鍵を環境変数に設定（手動またはシークレット管理経由）
        $this->info('Rotation complete. Update APP_KEY to: base64:' . base64_encode($newKey));
    }
}
```

---

## 証明書管理

### 証明書要件

| 項目 | 要件 |
|------|------|
| 発行元 | 信頼された認証局（CA） |
| 有効期間 | 最大 1年（自動更新推奨） |
| 鍵長 | RSA 2048bit 以上、または ECDSA P-256 以上 |
| 署名アルゴリズム | SHA-256 以上 |

### 証明書ライフサイクル

| フェーズ | 期限 | アクション |
|---------|:----:|-----------|
| 発行 | - | CA から証明書を取得 |
| 監視 | 常時 | 有効期限を監視 |
| 更新準備 | 30日前 | 更新手続きを開始 |
| 更新 | 14日前 | 新証明書をデプロイ |
| 失効確認 | 7日後 | 旧証明書が使用されていないことを確認 |

### Let's Encrypt 自動更新

```bash
# Certbot による自動更新設定
sudo certbot certonly --nginx -d example.com -d www.example.com

# 自動更新の確認
sudo certbot renew --dry-run

# cron による自動実行（1日2回）
0 0,12 * * * /usr/bin/certbot renew --quiet --post-hook "systemctl reload nginx"
```

### 証明書の監視

```bash
# 有効期限の確認スクリプト
#!/bin/bash
DOMAIN="example.com"
EXPIRY=$(echo | openssl s_client -servername $DOMAIN -connect $DOMAIN:443 2>/dev/null | openssl x509 -noout -enddate | cut -d= -f2)
EXPIRY_EPOCH=$(date -d "$EXPIRY" +%s)
NOW_EPOCH=$(date +%s)
DAYS_LEFT=$(( ($EXPIRY_EPOCH - $NOW_EPOCH) / 86400 ))

if [ $DAYS_LEFT -lt 30 ]; then
    echo "WARNING: Certificate expires in $DAYS_LEFT days"
    # アラート送信
fi
```

---

## 暗号アルゴリズム選定基準

### 選定フローチャート

```
用途を特定
    │
    ├── データ暗号化（対称） ─────────────→ AES-256-GCM
    │
    ├── 鍵交換 ────────────────────────→ ECDHE (P-256/X25519)
    │
    ├── デジタル署名 ───────────────────→ ECDSA (P-256) or Ed25519
    │
    ├── パスワードハッシュ ─────────────→ Argon2id
    │
    └── 一般ハッシュ（整合性） ──────────→ SHA-256/SHA-384
```

### アルゴリズム一覧

| 用途 | 推奨 | 許容 | 禁止 |
|------|------|------|------|
| 対称暗号 | AES-256-GCM | AES-128-GCM, ChaCha20 | DES, 3DES, RC4 |
| 鍵交換 | ECDHE (P-256, X25519) | DHE (2048bit+) | DH (1024bit以下) |
| 署名 | ECDSA, Ed25519 | RSA (2048bit+) | RSA (1024bit以下) |
| ハッシュ | SHA-256, SHA-384 | SHA-512 | MD5, SHA-1 |
| パスワード | Argon2id | bcrypt, scrypt | MD5, SHA-*, 平文 |

### 将来の移行計画

| 時期 | 対応 |
|------|------|
| 現在 | TLS 1.2/1.3、AES-256-GCM |
| 2025年 | TLS 1.3 優先、TLS 1.2 段階的廃止検討 |
| 2030年以降 | ポスト量子暗号への移行準備 |

---

## 実装ガイドライン

### セキュリティチェックリスト

#### 通信暗号化

- [ ] TLS 1.2 以上のみ許可
- [ ] 強力な暗号スイートのみ使用
- [ ] HSTS が設定されている
- [ ] 証明書の自動更新が設定されている
- [ ] 証明書の有効期限監視が設定されている

#### 保存データの暗号化

- [ ] 機密データは暗号化されている
- [ ] 暗号化キーは安全に管理されている
- [ ] 鍵のローテーション計画がある
- [ ] バックアップも暗号化されている

#### パスワード

- [ ] Argon2id または bcrypt を使用
- [ ] 適切なコストパラメータを設定
- [ ] 平文パスワードはログに記録されない

### コードレビューチェックポイント

```php
// NG: ハードコードされた鍵
$key = 'my-secret-key-12345';  // 禁止

// OK: 環境変数から取得
$key = config('app.key');

// NG: 弱いアルゴリズム
$hash = md5($password);  // 禁止
$encrypted = openssl_encrypt($data, 'DES-ECB', $key);  // 禁止

// OK: 強いアルゴリズム
$hash = Hash::make($password);
$encrypted = Crypt::encryptString($data);

// NG: 固定の IV
$iv = '1234567890123456';  // 禁止

// OK: ランダムな IV
$iv = random_bytes(16);
```

---

## 監査・コンプライアンス

### 定期監査

| 監査項目 | 頻度 | 担当 |
|---------|------|------|
| TLS 設定の確認 | 月次 | インフラチーム |
| 暗号化鍵の棚卸し | 四半期 | セキュリティチーム |
| 証明書の有効期限確認 | 週次（自動） | 監視システム |
| 暗号アルゴリズムの見直し | 年次 | セキュリティチーム |

### SSL Labs テスト

```bash
# SSL Labs による TLS 設定の評価
# https://www.ssllabs.com/ssltest/

# 目標: A+ 評価
# 確認項目:
# - プロトコルサポート
# - 鍵交換
# - 暗号強度
# - 証明書の有効性
```

### 脆弱性情報の収集

| 情報源 | 確認頻度 |
|-------|---------|
| [NIST NVD](https://nvd.nist.gov/) | 週次 |
| [OpenSSL Security](https://www.openssl.org/news/secadv/) | リリース時 |
| [Mozilla Security Blog](https://blog.mozilla.org/security/) | 月次 |

---

## 関連ドキュメント

- [01_PasswordPolicy.md](./01_PasswordPolicy.md) - パスワードポリシー
- [03_DataClassification.md](./03_DataClassification.md) - データ分類・保護ポリシー
- [02_SecurityDesign.md](../backend/02_SecurityDesign.md) - セキュリティ設計標準

---

## 改訂履歴

| バージョン | 日付 | 変更内容 | 担当者 |
|-----------|------|---------|-------|
| 1.0.0 | 2025-12-26 | 初版作成 | - |
