# ドキュメント体系

本プロジェクトのドキュメント構成を説明します。

---

## ディレクトリ構成

```
00_docs/
├── 10_business/              # 業務関連ドキュメント
│   └── asis/                 # 現行業務の分析（AS-IS）
│       ├── swimlane/         # 業務プロセスフロー
│       ├── data-flow/        # データフロー図
│       ├── business-rules/   # ビジネスルール
│       ├── nonfunctional-needs/  # 非機能要件
│       └── pain-points/      # 課題分析
│
└── 20_tech/                  # 技術関連ドキュメント
    ├── 10_team/              # チーム運営（DoR/DoD）
    ├── 20_architecture/      # アーキテクチャ設計
    │   └── adr/              # アーキテクチャ決定記録
    └── 99_standard/          # コーディング規約・設計標準
        ├── backend/          # バックエンド標準
        ├── frontend/         # フロントエンド標準
        └── security/         # セキュリティ標準
```

---

## 各ディレクトリの役割

### 10_business/ - 業務関連

| ディレクトリ | 内容 |
|-------------|------|
| `asis/swimlane/` | 現行業務プロセスフロー（貸出、返却、予約、蔵書登録、利用者登録） |
| `asis/data-flow/` | データフロー図 |
| `asis/business-rules/` | ビジネスルール定義 |
| `asis/nonfunctional-needs/` | 非機能要件の整理 |
| `asis/pain-points/` | 現行業務の課題分析 |

### 20_tech/ - 技術関連

| ディレクトリ | 内容 |
|-------------|------|
| `10_team/` | チーム運営ドキュメント（DoR: Definition of Ready、DoD: Definition of Done） |
| `20_architecture/` | システム全体のアーキテクチャ概要 |
| `20_architecture/adr/` | ADR（Architecture Decision Records）- 技術選定の記録 |
| `99_standard/backend/` | バックエンド設計標準、コーディング規約 |
| `99_standard/frontend/` | フロントエンド設計標準、コーディング規約 |
| `99_standard/security/` | セキュリティ標準 |

---

## 関連ディレクトリ

| ディレクトリ | 内容 |
|-------------|------|
| `01_vision/` | プロジェクトビジョン、イニシアチブ、ユースケース、UI設計 |
| `02_backlog/` | Epic、Story、バックログ管理 |
| `03_specs/` | 詳細設計、実装仕様（フィーチャー単位） |
| `99_memo/` | メタ情報（学習教材、シナリオ設定） |

---

## ドキュメント作成のガイドライン

### 原則

1. **分かっていることは具体的に書く**
   - 「等」「など」の曖昧表現は避ける
   - 詳細は [01_agile-documentation-guideline.md](./99_guideline/01_agile-documentation-guideline.md) を参照

2. **メタ情報とプロジェクト成果物を区別する**
   - `99_memo/` = 学習教材・シナリオ設定（メタ情報）
   - その他 = プロジェクト成果物（チームで共有する公式ドキュメント）
   - 詳細は [MetaInfoVsProjectArtifacts.md](../99_memo/MetaInfoVsProjectArtifacts.md) を参照

3. **Markdown形式で記述**
   - Docs-as-Code の原則に従う
   - バージョン管理（Git）で変更履歴を追跡

---

## 変更履歴

| 日付 | 変更内容 |
|------|---------|
| 2024-04-01 | 初版作成 |
| 2025-12-24 | ディレクトリ構成を実態に合わせて更新（10_team追加、フォルダ番号変更） |
