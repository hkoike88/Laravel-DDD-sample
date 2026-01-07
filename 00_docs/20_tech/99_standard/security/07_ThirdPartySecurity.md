# サードパーティセキュリティ

## 概要

本ドキュメントは、サードパーティの依存関係および外部サービス連携におけるセキュリティ管理のポリシーを定義する。
外部コンポーネントに起因するリスクを適切に管理し、サプライチェーン攻撃を防止する。

**Last updated:** 2025-12-26

---

## 目次

- [サードパーティ管理の基本方針](#サードパーティ管理の基本方針)
- [依存関係の管理](#依存関係の管理)
- [外部サービス連携](#外部サービス連携)
- [ベンダー評価](#ベンダー評価)
- [API セキュリティ](#apiセキュリティ)
- [SaaS/クラウドサービス](#saasクラウドサービス)
- [オープンソースソフトウェア](#オープンソースソフトウェア)
- [監視・インシデント対応](#監視インシデント対応)
- [コンプライアンス](#コンプライアンス)

---

## サードパーティ管理の基本方針

### 原則

1. **最小限の依存**: 必要最小限の外部依存に抑える
2. **信頼性の検証**: 導入前にセキュリティ評価を実施
3. **継続的な監視**: 脆弱性情報を継続的に追跡
4. **更新の迅速化**: セキュリティパッチを迅速に適用
5. **代替策の準備**: サービス停止時の代替手段を確保

### 対象範囲

| カテゴリ | 例 | リスクレベル |
|---------|-----|:------------:|
| 言語パッケージ | Composer, npm パッケージ | 中〜高 |
| フレームワーク | Laravel, React | 高 |
| 外部 API | 決済、認証、メール送信 | 高 |
| SaaS | 監視、ログ管理、CI/CD | 中〜高 |
| クラウドサービス | AWS, GCP, Azure | 高 |
| CDN/静的ホスティング | CloudFlare, Vercel | 中 |

---

## 依存関係の管理

### 依存関係ポリシー

| 項目 | ポリシー |
|------|---------|
| 新規追加 | チームレビュー必須（P1/P2脆弱性がないこと） |
| バージョン固定 | ロックファイル（composer.lock, package-lock.json）必須 |
| 更新頻度 | 週次で脆弱性チェック、月次で更新レビュー |
| 廃止ライブラリ | 検出次第、代替への移行計画を策定 |

### 新規パッケージ導入基準

新規パッケージを導入する際のチェックリスト：

```markdown
## パッケージ導入チェックリスト

### 基本情報
- [ ] パッケージ名:
- [ ] バージョン:
- [ ] ライセンス: [MIT/Apache/GPL等]
- [ ] リポジトリ: [GitHub URL]

### セキュリティ評価
- [ ] 既知の脆弱性がないか確認（CVE検索）
- [ ] 依存関係に問題がないか確認
- [ ] メンテナンス状況（最終更新日、イシュー対応）
- [ ] ダウンロード数/スター数（信頼性の指標）

### コード品質
- [ ] テストカバレッジの確認
- [ ] セキュリティに関するドキュメントの有無
- [ ] 型安全性（TypeScript/PHPStan対応）

### ライセンス
- [ ] ライセンスがプロジェクトと互換性があるか
- [ ] 商用利用が許可されているか

### 代替案
- [ ] 他のパッケージと比較検討したか
- [ ] 自前実装のコスト比較
```

### 依存関係の脆弱性スキャン

```bash
# PHP: Composer Audit
cd backend
composer audit
composer audit --format=json > reports/composer-audit.json

# JavaScript: npm Audit
cd frontend
npm audit
npm audit --json > reports/npm-audit.json

# 高/Critical のみ表示
npm audit --audit-level=high
```

### 依存関係の更新フロー

```
┌─────────────────────────────────────────────────────────────────┐
│                     依存関係更新フロー                            │
└─────────────────────────────────────────────────────────────────┘

  [脆弱性検出/新バージョンリリース]
              │
              ▼
  ┌───────────────────────┐
  │ 影響評価               │ ← 変更内容、破壊的変更の確認
  └───────────┬───────────┘
              │
              ▼
  ┌───────────────────────┐
  │ 開発環境でテスト        │ ← ローカルでの動作確認
  └───────────┬───────────┘
              │
              ▼
  ┌───────────────────────┐
  │ CI パイプラインで検証    │ ← 自動テスト実行
  └───────────┬───────────┘
              │
              ▼
  ┌───────────────────────┐
  │ ステージング環境で検証   │ ← 統合テスト
  └───────────┬───────────┘
              │
              ▼
  ┌───────────────────────┐
  │ 本番環境にデプロイ       │ ← 段階的ロールアウト
  └───────────────────────┘
```

### Dependabot / Renovate 設定

```yaml
# .github/dependabot.yml
version: 2
updates:
  - package-ecosystem: "composer"
    directory: "/backend"
    schedule:
      interval: "weekly"
      day: "monday"
    open-pull-requests-limit: 10
    labels:
      - "dependencies"
      - "backend"
    reviewers:
      - "backend-team"
    groups:
      # セキュリティ更新は個別PR
      # 通常更新はグループ化
      minor-updates:
        patterns:
          - "*"
        update-types:
          - "minor"
          - "patch"

  - package-ecosystem: "npm"
    directory: "/frontend"
    schedule:
      interval: "weekly"
      day: "monday"
    open-pull-requests-limit: 10
    labels:
      - "dependencies"
      - "frontend"
    reviewers:
      - "frontend-team"
```

---

## 外部サービス連携

### 連携サービス一覧

| サービス種別 | 例 | データ種別 | リスクレベル |
|-------------|-----|-----------|:----------:|
| 決済 | Stripe, PayPay | 決済情報 | 高 |
| 認証 | Auth0, Firebase | 認証情報 | 高 |
| メール | SendGrid, AWS SES | メールアドレス | 中 |
| SMS | Twilio | 電話番号 | 中 |
| ストレージ | AWS S3, GCS | ファイル | 中〜高 |
| 分析 | Google Analytics | 行動データ | 低〜中 |
| 監視 | Datadog, Sentry | ログ・エラー | 中 |

### 連携前のセキュリティ評価

```markdown
## 外部サービス連携 評価チェックリスト

### サービス情報
- サービス名:
- ベンダー:
- 用途:
- 連携データ:

### セキュリティ認証
- [ ] SOC 2 Type II 取得済み
- [ ] ISO 27001 認証済み
- [ ] PCI DSS 準拠（決済の場合）
- [ ] GDPR 準拠

### 技術的セキュリティ
- [ ] API 認証方式（OAuth2, API Key）
- [ ] 通信暗号化（TLS 1.2+）
- [ ] データ暗号化（保存時）
- [ ] 監査ログの提供

### 契約・法務
- [ ] SLA の確認
- [ ] データ処理契約（DPA）
- [ ] インシデント通知義務
- [ ] データ削除ポリシー

### 運用
- [ ] 障害時の代替手段
- [ ] サポート体制
- [ ] セキュリティアップデートの通知
```

### API キー・シークレットの管理

```php
// NG: ハードコード
$apiKey = 'sk_live_xxxxxxxxxxxx';

// NG: バージョン管理にコミット
// .env ファイルを直接コミット

// OK: 環境変数から取得
$apiKey = config('services.stripe.secret');

// OK: シークレット管理サービス
// AWS Secrets Manager, HashiCorp Vault 等

// config/services.php
return [
    'stripe' => [
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    ],
];
```

### シークレットのローテーション

| シークレット種別 | ローテーション周期 | 方法 |
|-----------------|:------------------:|------|
| API キー | 90日 | ベンダー管理画面で再発行 |
| Webhook シークレット | 90日 | 再生成後、エンドポイント更新 |
| OAuth クライアントシークレット | 180日 | 新旧両方を一時的に許可 |
| 暗号化キー | 365日 | 段階的な鍵ローテーション |

---

## ベンダー評価

### 評価基準

| カテゴリ | 評価項目 | 重み |
|---------|---------|:----:|
| セキュリティ認証 | SOC 2, ISO 27001, PCI DSS | 高 |
| 技術的セキュリティ | 暗号化、認証、監査ログ | 高 |
| 運用実績 | 稼働率、インシデント履歴 | 中 |
| サポート | 対応時間、エスカレーション | 中 |
| 契約条件 | SLA、データ所有権、終了条件 | 中 |
| 財務安定性 | 企業規模、資金調達状況 | 低 |

### ベンダーリスク評価マトリクス

```
              │    データ機密性
   依存度      │  低    中    高
  ────────────┼──────────────────
    低        │  低    低    中
    中        │  低    中    高
    高        │  中    高   最高
```

### 定期レビュー

| リスクレベル | レビュー頻度 | 内容 |
|:----------:|:----------:|------|
| 最高 | 四半期 | セキュリティ状況、インシデント、SLA達成率 |
| 高 | 半年 | セキュリティ認証の有効性、変更点 |
| 中 | 年次 | 契約更新時にレビュー |
| 低 | 2年 | 大きな変更がある場合のみ |

---

## APIセキュリティ

### 外部APIを呼び出す際のセキュリティ

```php
// app/Services/External/SecureApiClient.php
final class SecureApiClient
{
    private const TIMEOUT = 30;
    private const RETRY_ATTEMPTS = 3;

    public function __construct(
        private readonly Http $http,
        private readonly LoggerInterface $logger,
    ) {}

    public function request(string $method, string $url, array $options = []): Response
    {
        // TLS 1.2 以上を強制
        $options['verify'] = true;

        // タイムアウト設定
        $options['timeout'] = self::TIMEOUT;

        // リトライ設定
        $response = $this->http
            ->retry(self::RETRY_ATTEMPTS, 100, throw: false)
            ->withHeaders($this->getSecureHeaders())
            ->send($method, $url, $options);

        // レスポンスログ（機密情報を除外）
        $this->logResponse($url, $response);

        return $response;
    }

    private function getSecureHeaders(): array
    {
        return [
            'User-Agent' => config('app.name') . '/' . config('app.version'),
            'Accept' => 'application/json',
            'X-Request-ID' => (string) Str::uuid(),
        ];
    }

    private function logResponse(string $url, Response $response): void
    {
        $this->logger->info('External API call', [
            'url' => $this->sanitizeUrl($url),
            'status' => $response->status(),
            'duration_ms' => $response->transferStats?->getTransferTime() * 1000,
        ]);
    }

    private function sanitizeUrl(string $url): string
    {
        // クエリパラメータから機密情報を除去
        return preg_replace('/([?&])(api_key|token|secret)=[^&]*/i', '$1$2=***', $url);
    }
}
```

### Webhook の検証

```php
// app/Http/Controllers/Webhook/StripeWebhookController.php
final class StripeWebhookController extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature');

        try {
            // 署名検証
            $event = Webhook::constructEvent(
                $payload,
                $signature,
                config('services.stripe.webhook_secret')
            );
        } catch (SignatureVerificationException $e) {
            Log::channel('security')->warning('Webhook signature verification failed', [
                'ip' => $request->ip(),
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'Invalid signature'], 400);
        }

        // イベント処理
        $this->processEvent($event);

        return response()->json(['received' => true]);
    }
}
```

### APIレート制限の遵守

```php
// app/Services/External/RateLimitedClient.php
final class RateLimitedClient
{
    private const RATE_LIMIT_HEADER = 'X-RateLimit-Remaining';
    private const RETRY_AFTER_HEADER = 'Retry-After';

    public function request(string $url): Response
    {
        $response = Http::get($url);

        // レート制限の確認
        $remaining = $response->header(self::RATE_LIMIT_HEADER);

        if ($remaining !== null && (int) $remaining < 10) {
            Log::warning('API rate limit approaching', [
                'url' => $url,
                'remaining' => $remaining,
            ]);
        }

        // レート制限超過時の処理
        if ($response->status() === 429) {
            $retryAfter = $response->header(self::RETRY_AFTER_HEADER, 60);
            throw new RateLimitExceededException(
                "Rate limit exceeded. Retry after {$retryAfter} seconds."
            );
        }

        return $response;
    }
}
```

---

## SaaS/クラウドサービス

### クラウドサービス利用ガイドライン

| 項目 | ガイドライン |
|------|-------------|
| アカウント管理 | SSO/SAML 連携、MFA 必須 |
| 権限管理 | 最小権限の原則、定期的な棚卸し |
| データ保護 | 暗号化、アクセス制御、バックアップ |
| ログ | 監査ログの有効化、長期保存 |
| ネットワーク | IP 制限、VPC/VPN 接続 |

### AWS セキュリティベストプラクティス

```yaml
# IAM ポリシー例：最小権限
{
  "Version": "2012-10-17",
  "Statement": [
    {
      "Effect": "Allow",
      "Action": [
        "s3:GetObject",
        "s3:PutObject"
      ],
      "Resource": "arn:aws:s3:::my-bucket/uploads/*"
    }
  ]
}
```

```bash
# AWS セキュリティ設定確認
# S3 バケットのパブリックアクセス確認
aws s3api get-public-access-block --bucket my-bucket

# IAM ユーザーの MFA 状態確認
aws iam list-mfa-devices --user-name myuser

# CloudTrail の有効化確認
aws cloudtrail describe-trails
```

### SaaS 利用時のデータフロー

```
┌─────────────────────────────────────────────────────────────────┐
│                    データフロー管理                               │
└─────────────────────────────────────────────────────────────────┘

  [自社システム] ──TLS──> [SaaS サービス]
        │                      │
        │                      ├── データ保存場所の確認
        │                      ├── 暗号化状態の確認
        │                      └── バックアップポリシー
        │
        └── 送信データの分類
            ├── PII を含むか？
            ├── 機密データを含むか？
            └── 匿名化/仮名化の検討
```

---

## オープンソースソフトウェア

### OSS 利用ポリシー

| ポリシー | 内容 |
|---------|------|
| ライセンス確認 | 商用利用可能なライセンスのみ使用 |
| コピーレフト | GPL 系は慎重に検討（感染リスク） |
| 脆弱性管理 | 定期的なスキャン、迅速なパッチ適用 |
| 貢献 | 可能な範囲でアップストリームに貢献 |

### ライセンス互換性マトリクス

| ライセンス | 商用利用 | 改変 | 再配布 | 注意点 |
|-----------|:--------:|:----:|:------:|-------|
| MIT | ✓ | ✓ | ✓ | 著作権表示必要 |
| Apache 2.0 | ✓ | ✓ | ✓ | 特許条項あり |
| BSD | ✓ | ✓ | ✓ | 著作権表示必要 |
| LGPL | ✓ | ✓ | ✓ | 動的リンクは可 |
| GPL | ✓ | ✓ | △ | ソース公開義務あり |
| AGPL | △ | ✓ | △ | ネットワーク利用も対象 |

### OSS セキュリティ評価

```markdown
## OSS セキュリティ評価チェックリスト

### リポジトリ情報
- [ ] スター数: [X,XXX以上推奨]
- [ ] フォーク数: [活発なコミュニティの指標]
- [ ] 最終コミット: [3ヶ月以内推奨]
- [ ] オープンイシュー: [放置されていないか]

### セキュリティ
- [ ] 08_SecurityScanning.md の有無
- [ ] 過去のCVE と対応状況
- [ ] 依存関係の脆弱性
- [ ] セキュリティアドバイザリの確認

### メンテナンス
- [ ] メンテナー数: [複数推奨]
- [ ] リリース頻度
- [ ] イシュー対応速度
- [ ] ドキュメント品質
```

---

## 監視・インシデント対応

### 監視項目

| 監視対象 | 項目 | アラート条件 |
|---------|------|-------------|
| 依存関係 | 脆弱性 | Critical/High 検出時 |
| 外部API | 応答時間 | 5秒以上 |
| 外部API | エラー率 | 5%以上 |
| 外部API | 証明書有効期限 | 30日以内 |
| SaaS | 障害情報 | ステータスページ監視 |

### サプライチェーン攻撃への対応

| 攻撃タイプ | 対策 |
|-----------|------|
| タイポスクワッティング | パッケージ名の正確な確認 |
| 依存関係の乗っ取り | ロックファイルの使用、ハッシュ検証 |
| ビルド時の改ざん | 再現可能なビルド、署名検証 |
| アカウント乗っ取り | 2FA、組織管理 |

### インシデント対応フロー

```
┌─────────────────────────────────────────────────────────────────┐
│              サードパーティ関連インシデント対応                     │
└─────────────────────────────────────────────────────────────────┘

  [脆弱性公開 / 障害発生]
              │
              ▼
  ┌───────────────────────┐
  │ 影響確認               │ ← 該当バージョン使用有無
  └───────────┬───────────┘
              │
      ┌───────┴───────┐
      │               │
   影響あり         影響なし
      │               │
      ▼               ▼
  ┌────────────┐  ┌────────────┐
  │ 緊急対応    │  │ 経過観察    │
  │ - 緩和策    │  │ - 情報収集  │
  │ - パッチ適用│  └────────────┘
  │ - 代替実装  │
  └────────────┘
```

---

## コンプライアンス

### 規制要件への対応

| 規制 | サードパーティに関する要件 |
|------|---------------------------|
| 個人情報保護法 | 委託先の監督義務 |
| GDPR | データ処理者との契約義務 |
| PCI DSS | サービスプロバイダの管理 |
| SOC 2 | ベンダー管理プログラム |

### 契約要件

サードパーティとの契約に含めるべき条項：

- データ保護とセキュリティ要件
- インシデント通知義務
- 監査権
- 下請け（再委託）の制限
- 契約終了時のデータ返却・削除
- 責任と補償
- SLA と罰則

### ドキュメント管理

| ドキュメント | 保管期間 | 管理者 |
|-------------|:--------:|-------|
| ベンダー評価結果 | 契約終了後5年 | セキュリティチーム |
| セキュリティ認証の写し | 有効期間中 | セキュリティチーム |
| 契約書 | 契約終了後10年 | 法務 |
| インシデント記録 | 5年 | セキュリティチーム |

---

## 関連ドキュメント

- [06_VulnerabilityManagement.md](./06_VulnerabilityManagement.md) - 脆弱性管理プロセス
- [03_DataClassification.md](./03_DataClassification.md) - データ分類・保護ポリシー
- [09_ExternalIntegration.md](../backend/09_ExternalIntegration.md) - 外部連携設計標準

---

## 改訂履歴

| バージョン | 日付 | 変更内容 | 担当者 |
|-----------|------|---------|-------|
| 1.0.0 | 2025-12-26 | 初版作成 | - |
