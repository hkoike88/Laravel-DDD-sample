# Implementation Plan: 職員エンティティの設計

**Branch**: `001-staff-entity-design` | **Date**: 2025-12-25 | **Spec**: [spec.md](./spec.md)
**Input**: Feature specification from `/specs/001-staff-entity-design/spec.md`

**Note**: This template is filled in by the `/speckit.plan` command. See `.specify/templates/commands/plan.md` for the execution workflow.

## Summary

職員認証機能の基盤となる Staff エンティティと関連する値オブジェクト（StaffId, Email, Password, StaffName）を DDD アーキテクチャに基づいて設計・実装する。既存の Book ドメインと同様のパターンを踏襲し、リポジトリインターフェースと Eloquent 実装、マイグレーションを作成する。

## Technical Context

**Language/Version**: PHP 8.3
**Primary Dependencies**: Laravel 11.x, symfony/uid（ULID生成）, Pest（テスト）
**Storage**: MySQL 8.0（Docker コンテナ）
**Testing**: Pest
**Target Platform**: Linux server（Docker コンテナ）
**Project Type**: web（バックエンド）
**Performance Goals**: 職員情報の登録・取得操作は1秒以内、ID検索は100,000件で0.1秒以内
**Constraints**: パスワードはbcryptでハッシュ化（72文字制限）、メールアドレスは小文字正規化
**Scale/Scope**: 想定職員数 1,000〜10,000名

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

constitution.md がテンプレート状態のため、プロジェクト固有のゲートは設定されていません。
既存の Book ドメイン実装パターンとアーキテクチャ設計標準に準拠することで品質を担保します。

**準拠確認項目**:
- [x] Domain 層は Laravel / Eloquent に依存しない
- [x] ビジネスロジックは Domain 層に実装
- [x] Repository は Interface を Domain 層、実装を Application 層に配置
- [x] Eloquent Model にビジネスロジックを書かない
- [x] 新規ドメイン追加時は ServiceProvider を作成し登録

## Project Structure

### Documentation (this feature)

```text
specs/001-staff-entity-design/
├── plan.md              # This file (/speckit.plan command output)
├── research.md          # Phase 0 output (/speckit.plan command)
├── data-model.md        # Phase 1 output (/speckit.plan command)
├── quickstart.md        # Phase 1 output (/speckit.plan command)
├── contracts/           # Phase 1 output (/speckit.plan command)
└── tasks.md             # Phase 2 output (/speckit.tasks command - NOT created by /speckit.plan)
```

### Source Code (repository root)

```text
backend/packages/Domain/Staff/
├── Domain/
│   ├── Model/
│   │   └── Staff.php                    # 職員エンティティ（集約ルート）
│   ├── ValueObjects/
│   │   ├── StaffId.php                  # 職員ID（ULID）
│   │   ├── Email.php                    # メールアドレス
│   │   ├── Password.php                 # パスワード（ハッシュ化）
│   │   └── StaffName.php                # 職員名
│   ├── Repositories/
│   │   └── StaffRepositoryInterface.php # リポジトリインターフェース
│   └── Exceptions/
│       ├── InvalidEmailException.php    # メール形式エラー
│       ├── InvalidPasswordException.php # パスワード制約エラー
│       ├── StaffNotFoundException.php   # 職員未検出エラー
│       └── DuplicateEmailException.php  # メール重複エラー
├── Application/
│   ├── Repositories/
│   │   └── EloquentStaffRepository.php  # Eloquent リポジトリ実装
│   └── Providers/
│       └── StaffServiceProvider.php     # サービスプロバイダー
└── Infrastructure/
    └── EloquentModels/
        └── StaffRecord.php              # Eloquent モデル

backend/database/migrations/
└── 2025_01_01_000000_create_staffs_table.php  # マイグレーション

backend/tests/Unit/Packages/Domain/Staff/
├── Domain/
│   ├── Model/
│   │   └── StaffTest.php
│   └── ValueObjects/
│       ├── StaffIdTest.php
│       ├── EmailTest.php
│       ├── PasswordTest.php
│       └── StaffNameTest.php
└── Application/
    └── Repositories/
        └── EloquentStaffRepositoryTest.php
```

**Structure Decision**: 既存の Book ドメインと同様の DDD ドメイン別グループ化アーキテクチャを採用。Domain/Application/Infrastructure レイヤーを分離し、依存性逆転原則に従う。

## Complexity Tracking

> **Fill ONLY if Constitution Check has violations that must be justified**

該当なし - すべての設計は既存のアーキテクチャパターンに準拠しています。
