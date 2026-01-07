## Project Structure

プロジェクトのディレクトリ構成：

| ディレクトリ | 内容 |
|-------------|------|
| `00_docs/` | プロジェクトドキュメント |
| `backend/` | バックエンドソースコード（PHP/Laravel） |
| `frontend/` | フロントエンドソースコード（TypeScript/React） |
| `infrastructure/` | 環境・インフラ設定（Docker、Nginx等） |

### 00_docs/ ドキュメント構成

```
00_docs/
├── 10_business/                    # ビジネスドキュメント
│   └── asis/                       # 現状分析
│       ├── data-flow/              # データフロー図
│       ├── swimlane/               # 業務プロセスフロー
│       │   ├── reservation-process.md      # 予約プロセス
│       │   ├── book-registration-process.md # 書籍登録プロセス
│       │   ├── user-registration-process.md # ユーザー登録プロセス
│       │   ├── lending-process.md          # 貸出プロセス
│       │   └── return-process.md           # 返却プロセス
│       ├── business-rules/         # ビジネスルール
│       ├── nonfunctional-needs/    # 非機能要件
│       └── pain-points/            # 課題分析
│
└── 20_tech/                        # 技術ドキュメント
    ├── 10_team/                    # チーム標準
    │   ├── dod.md                  # Definition of Done
    │   └── dor.md                  # Definition of Ready
    │
    ├── 20_architecture/            # アーキテクチャ設計
    │   ├── 01_SystemOverview.md    # システム概要
    │   ├── adr/                    # アーキテクチャ決定記録（ADR）
    │   │   ├── README.md
    │   │   ├── ADR-0001_Frontend-Framework.md
    │   │   ├── ADR-0002_Backend-Framework.md
    │   │   ├── ADR-0003_Database.md
    │   │   ├── ADR-0004_State-Management.md
    │   │   ├── ADR-0005_Authentication.md
    │   │   ├── ADR-0006_ID-Strategy.md
    │   │   ├── ADR-0007_CSS-Framework.md
    │   │   └── ADR-0008_Build-Tool.md
    │   ├── backend/                # バックエンドアーキテクチャ
    │   │   ├── 01_ArchitectureDesign.md
    │   │   └── 01_ArchitectureDesign/  # 詳細ドキュメント
    │   │       ├── 00_概要.md
    │   │       ├── 01_アーキテクチャ設計.md
    │   │       ├── 02_実装パターン.md
    │   │       ├── 03_ディレクトリ構成例.md
    │   │       ├── 04_テスト戦略.md
    │   │       └── 05_段階的移行ガイド.md
    │   └── frontend/               # フロントエンドアーキテクチャ
    │       └── 01_ArchitectureDesign.md
    │
    └── 99_standard/                # 設計標準・規約
        ├── security/               # セキュリティ
        │   ├── 00_Overview.md              # 概要
        │   ├── 01_PasswordPolicy.md        # パスワードポリシー
        │   ├── 02_SessionManagement.md     # セッション管理
        │   ├── 03_DataClassification.md    # データ分類
        │   ├── 04_EncryptionPolicy.md      # 暗号化ポリシー
        │   ├── 05_IncidentResponse.md      # インシデント対応
        │   ├── 06_VulnerabilityManagement.md  # 脆弱性管理
        │   ├── 07_ThirdPartySecurity.md    # サードパーティセキュリティ
        │   └── 08_SecurityScanning.md      # セキュリティスキャン
        ├── backend/                # バックエンド標準
        │   ├── 00_Overview.md              # 概要・構成一覧
        │   ├── 01_CodingStandards.md       # コーディング規約
        │   ├── 02_SecurityDesign.md        # セキュリティ設計
        │   ├── 03_Non-FunctionalRequirements.md  # 非機能要件
        │   ├── 04_LoggingDesign.md         # ログ設計
        │   ├── 05_ApiDesign.md             # API 設計
        │   ├── 06_ValidationDesign.md      # バリデーション設計
        │   ├── 07_ErrorHandling.md         # エラーハンドリング
        │   ├── 08_DatabaseDesign.md        # データベース設計
        │   ├── 09_TransactionDesign.md     # トランザクション設計
        │   ├── 10_TransactionConsistencyDesign.md  # トランザクション整合性保証設計
        │   ├── 11_TransactionConsistencyChecklist.md  # トランザクション整合性チェックリスト
        │   ├── 12_EventDrivenDesign.md     # イベント駆動設計
        │   ├── 13_ExternalIntegration.md   # 外部連携設計
        │   └── 14_BatchProcessing.md       # バッチ処理設計
        └── frontend/               # フロントエンド標準
            ├── 00_Overview.md              # 概要・構成一覧
            ├── 01_CodingStandards.md       # コーディング規約
            ├── 02_SecurityDesign.md        # セキュリティ設計
            └── 03_Non-FunctionalRequirements.md  # 非機能要件
```

---

## Work Policy

**重要: 時間短縮は考えず、ひとつづつ丁寧に作業を行うこと**

- すべてのタスクは品質を最優先し、丁寧に実施する
- 時間効率よりも正確性と完成度を重視する
- 各ステップを確実に完了してから次に進む
- 急がず、焦らず、確実に作業を進める

## Design Guidelines

**重要: 設計・実装時は以下のドキュメントを必ず参照すること**

### システム概要

プロジェクト全体のアーキテクチャを理解するために最初に参照すること：

- **`00_docs/20_tech/20_architecture/01_SystemOverview.md`** - システム全体のアーキテクチャ、技術スタック、通信設計

### アーキテクチャ設計

#### バックエンド設計

- **`00_docs/20_tech/20_architecture/backend/01_ArchitectureDesign.md`** - バックエンドアーキテクチャ設計の概要
- **`00_docs/20_tech/20_architecture/backend/01_ArchitectureDesign/`** - 詳細ドキュメント
  - `00_概要.md` - 概要
  - `01_アーキテクチャ設計.md` - アーキテクチャ設計
  - `02_実装パターン.md` - 実装パターン
  - `03_ディレクトリ構成例.md` - ディレクトリ構成例
  - `04_テスト戦略.md` - テスト戦略
  - `05_段階的移行ガイド.md` - 段階的移行ガイド

#### フロントエンド設計

- **`00_docs/20_tech/20_architecture/frontend/01_ArchitectureDesign.md`** - フロントエンドアーキテクチャ設計（Feature-based）

### ADR（アーキテクチャ決定記録）

技術選定の背景・理由を確認する際に参照すること：

- **`00_docs/20_tech/20_architecture/adr/README.md`** - ADR 一覧
  - ADR-0001: フロントエンドフレームワーク（React）
  - ADR-0002: バックエンドフレームワーク（Laravel + DDD）
  - ADR-0003: データベース（MySQL）
  - ADR-0004: 状態管理（TanStack Query + Zustand）
  - ADR-0005: 認証方式（Laravel Sanctum）
  - ADR-0006: ID 生成戦略（ULID）
  - ADR-0007: CSS フレームワーク（Tailwind CSS）
  - ADR-0008: ビルドツール（Vite）

### チーム標準

タスク着手・完了判定の基準として参照すること：

- **`00_docs/20_tech/10_team/dor.md`** - Definition of Ready（着手可能の定義）
- **`00_docs/20_tech/10_team/dod.md`** - Definition of Done（完了の定義）

### 設計標準・規約

#### バックエンド標準

- **`00_docs/20_tech/99_standard/backend/00_Overview.md`** - 概要・構成一覧
- 主要ドキュメント:
  - `01_CodingStandards.md` - コーディング規約（PSR-12、DDD パターン）
  - `02_SecurityDesign.md` - セキュリティ設計（OWASP Top 10 対応）
  - `05_ApiDesign.md` - API 設計（RESTful 設計原則）
  - `06_ValidationDesign.md` - バリデーション設計
  - `07_ErrorHandling.md` - エラーハンドリング
  - `08_DatabaseDesign.md` - データベース設計（命名規約、ULID）
  - `09_TransactionDesign.md` - トランザクション設計（競合状態防止、ロック）
  - `10_TransactionConsistencyDesign.md` - トランザクション整合性保証設計（Saga、Outbox、冪等性）
  - `11_TransactionConsistencyChecklist.md` - トランザクション整合性チェックリスト
  - `12_EventDrivenDesign.md` - イベント駆動設計（At-least-once、コンシューマ冪等性）
  - `13_ExternalIntegration.md` - 外部連携設計（タイムアウト、リトライ、サーキットブレーカー）
  - `14_BatchProcessing.md` - バッチ処理設計（冪等性、再開可能性、監視可能性）

#### フロントエンド標準

- **`00_docs/20_tech/99_standard/frontend/00_Overview.md`** - 概要・構成一覧
- 主要ドキュメント:
  - `01_CodingStandards.md` - コーディング規約（React/TypeScript）
  - `02_SecurityDesign.md` - セキュリティ設計（XSS、CSRF 対策）
  - `03_Non-FunctionalRequirements.md` - 非機能要件（Core Web Vitals）

#### セキュリティ標準

- **`00_docs/20_tech/99_standard/security/00_Overview.md`** - 概要・構成一覧
- 主要ドキュメント:
  - `01_PasswordPolicy.md` - パスワードポリシー（NIST SP 800-63B 準拠）
  - `02_SessionManagement.md` - セッション管理
  - `04_EncryptionPolicy.md` - 暗号化ポリシー

## Communication Guidelines

**重要: すべての返答は日本語で行うこと**

- ユーザーとのコミュニケーションは常に日本語で行う
- コードコメント、コミットメッセージ、ドキュメントも日本語で記述する
- エラーメッセージやログも可能な限り日本語で説明する

## Code Documentation Guidelines

**重要: すべての新規追加・修正時にはドキュメンテーションコメントを記載すること**

### ドキュメント記載のタイミング

- **新規コード追加時**: 実装と同時にドキュメントを記載
- **既存コード修正時**: 変更内容に応じてドキュメントを更新
- **コードレビュー時**: ドキュメントの有無と正確性を確認
