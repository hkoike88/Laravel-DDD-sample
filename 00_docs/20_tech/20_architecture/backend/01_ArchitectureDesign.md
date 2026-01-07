# バックエンド アーキテクチャ設計標準

## 概要

本プロジェクトのバックエンドは、**ドメイン駆動設計（DDD）** に基づく **ドメイン別グループ化アーキテクチャ** を採用する。

---

## 採用アーキテクチャ

### 方針

- **ドメイン別グループ化**アプローチを採用
- Domain Model と Eloquent Model を分離
- レイヤードアーキテクチャによる責務分離

### 採用理由

| 観点 | 本アーキテクチャの利点 |
|------|----------------------|
| 保守性 | ビジネスロジックが Domain 層に集約され、変更の影響範囲が局所化 |
| テスト容易性 | Domain 層は DB に依存しないため、高速な Unit テストが可能 |
| チーム開発 | ドメイン単位で分割開発が可能 |
| 拡張性 | 将来のマイクロサービス化に対応しやすい構造 |
| Laravel との親和性 | フレームワークの機能を活かしつつ DDD を実現 |

---

## ドキュメント構成

| ファイル | 内容 |
|----------|------|
| [00_概要.md](./01_ArchitectureDesign/00_概要.md) | DDD の基本概念・用語定義 |
| [01_アーキテクチャ設計.md](./01_ArchitectureDesign/01_アーキテクチャ設計.md) | レイヤー構成・依存関係・設計原則 |
| [02_実装パターン.md](./01_ArchitectureDesign/02_実装パターン.md) | コードサンプル・実装規約 |
| [03_ディレクトリ構成例.md](./01_ArchitectureDesign/03_ディレクトリ構成例.md) | ディレクトリ構成・命名規約 |
| [04_テスト戦略.md](./01_ArchitectureDesign/04_テスト戦略.md) | テスト構成・カバレッジ目標 |
| [05_段階的移行ガイド.md](./01_ArchitectureDesign/05_段階的移行ガイド.md) | 既存コードの移行手順 |

---

## アーキテクチャ概要

### レイヤー構成

```
┌─────────────────────────────────────────────────────┐
│                  Presentation                        │
│              (Controller / CLI / API)                │
└─────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────┐
│                    Application                       │
│                  (UseCase / DTO)                     │
└─────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────┐
│                      Domain                          │
│          (Entity / ValueObject / Service)            │
└─────────────────────────────────────────────────────┘
                          ↑
┌─────────────────────────────────────────────────────┐
│                   Infrastructure                     │
│              (Eloquent / API / Cache)                │
└─────────────────────────────────────────────────────┘
```

### 依存関係ルール

```
[Presentation] → [Application] → [Domain] ← [Infrastructure]
```

| レイヤー | 依存先 | 禁止事項 |
|----------|--------|----------|
| Presentation | Application | ビジネスロジックの実装禁止 |
| Application | Domain | Eloquent の直接操作禁止 |
| Domain | **なし** | Laravel / DB への依存禁止 |
| Infrastructure | Domain | ビジネスロジックの実装禁止 |

---

## ディレクトリ構成

```
packages/
├── {BoundedContext}/          # 境界づけられたコンテキスト
│   └── {Domain}/              # ドメイン
│       ├── Domain/            # ビジネスロジック
│       │   ├── Model/         # Entity / ValueObject
│       │   ├── Repositories/  # Repository Interface
│       │   ├── Services/      # Domain Service
│       │   └── Exceptions/    # Domain 例外
│       ├── Application/       # ユースケース
│       │   ├── UseCases/      # Command / Query
│       │   ├── DTO/           # データ転送オブジェクト
│       │   ├── Repositories/  # Repository 実装
│       │   └── Providers/     # ServiceProvider
│       ├── Presentation/      # HTTP / CLI
│       │   └── HTTP/          # Controller / Request / Resource
│       └── Infrastructure/    # Eloquent / 外部サービス
│           └── EloquentModels/
└── Common/                    # 共有リソース
    ├── Domain/
    └── Infrastructure/
```

---

## 主要コンポーネント

| コンポーネント | 責務 | 配置 |
|---------------|------|------|
| Entity | ID で識別されるオブジェクト、状態遷移・ビジネスルール | Domain |
| ValueObject | 値で識別される不変オブジェクト | Domain |
| Aggregate | 整合性の単位となるエンティティの集合 | Domain |
| Repository Interface | 永続化の抽象定義 | Domain |
| Repository 実装 | Eloquent を使った具体実装 | Application |
| UseCase | 業務フローの調整（Command / Query） | Application |
| DTO | レイヤー間のデータ転送 | Application |
| Eloquent Model | DB テーブルのマッピング | Infrastructure |
| Controller | HTTP リクエスト/レスポンス | Presentation |

---

## 命名規約

| 種類 | 規約 | 例 |
|------|------|-----|
| Entity | `{Name}.php` | `Order.php` |
| ValueObject | `{Name}.php` | `OrderId.php`, `Money.php` |
| Repository Interface | `{Name}RepositoryInterface.php` | `OrderRepositoryInterface.php` |
| Repository 実装 | `Eloquent{Name}Repository.php` | `EloquentOrderRepository.php` |
| Eloquent Model | `{Name}Record.php` | `OrderRecord.php` |
| UseCase Command | `{Action}{Entity}Command.php` | `PlaceOrderCommand.php` |
| UseCase Handler | `{Action}{Entity}Handler.php` | `PlaceOrderHandler.php` |
| DTO | `{Name}DTO.php` | `OrderDTO.php` |

---

## 開発ルール

### 必須

- [ ] Domain 層は Laravel / Eloquent に依存しないこと
- [ ] ビジネスロジックは Domain 層に実装すること
- [ ] Repository は Interface を Domain 層、実装を Application 層に配置すること
- [ ] Eloquent Model にビジネスロジックを書かないこと
- [ ] 新規ドメイン追加時は ServiceProvider を作成し登録すること

### 推奨

- [ ] 1 UseCase = 1 責務（単一責任の原則）
- [ ] 集約間の参照は ID のみ（オブジェクト参照禁止）
- [ ] Domain 層のテストカバレッジ 90% 以上

---

## テスト方針

| 種類 | 対象 | 目標カバレッジ |
|------|------|--------------|
| Unit | Domain Model | 90%+ |
| Feature | UseCase | 80%+ |
| Feature | Controller | 70%+ |
| Integration | Repository | 60%+ |

詳細は [04_テスト戦略.md](./01_ArchitectureDesign/04_テスト戦略.md) を参照。

---

## 関連ドキュメント

- [02_CodingStandards.md](./02_CodingStandards.md) - コーディング規約
- [03_SecurityDesign.md](./03_SecurityDesign.md) - セキュリティ設計
- [04_Non-FunctionalRequirements.md](./04_Non-FunctionalRequirements.md) - 非機能要件
