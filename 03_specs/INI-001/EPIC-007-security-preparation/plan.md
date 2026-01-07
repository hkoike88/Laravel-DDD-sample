# Implementation Plan: セキュリティ対策準備

**Branch**: `001-security-preparation` | **Date**: 2026-01-06 | **Spec**: [spec.md](./spec.md)
**Input**: Feature specification from `/specs/001-security-preparation/spec.md`

---

## Summary

セキュリティ基盤を強化するため、パスワードポリシー（12文字以上、複雑性要件、漏洩チェック、5世代再利用禁止）、セッション管理（アイドル30分/絶対8時間タイムアウト、同時ログイン制限）、暗号化設定（bcrypt cost=12）、CI/CD セキュリティスキャンを実装する。

---

## Technical Context

**Language/Version**: PHP 8.3 / TypeScript 5.3
**Primary Dependencies**: Laravel 11.x, Laravel Sanctum, React 18.x, TanStack Query 5.x
**Storage**: MySQL 8.0（sessions テーブル既存、password_histories テーブル新規）
**Testing**: Pest（バックエンド）、Vitest（フロントエンド）
**Target Platform**: Linux server（Docker）、Web ブラウザ
**Project Type**: Web application（backend + frontend）
**Performance Goals**: パスワードハッシュ（bcrypt cost=12）: 300ms 以内
**Constraints**: Have I Been Pwned API タイムアウト 5秒、セッション最大数（一般3、管理者1）
**Scale/Scope**: 職員数 100名程度を想定

---

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

- ✅ 最大3プロジェクト以内（backend、frontend の2プロジェクト）
- ✅ 外部ライブラリは Laravel 標準機能を最大限活用
- ✅ DDD アーキテクチャに準拠
- ✅ 既存の Staff エンティティを拡張せず、関連エンティティとして PasswordHistory を追加
- ✅ セキュリティ標準ドキュメントに準拠

---

## Project Structure

### Documentation (this feature)

```text
specs/001-security-preparation/
├── plan.md              # This file
├── research.md          # Phase 0 output - 調査結果
├── data-model.md        # Phase 1 output - データモデル
├── quickstart.md        # Phase 1 output - クイックスタートガイド
├── contracts/           # Phase 1 output - API コントラクト
│   └── api.yaml         # OpenAPI 3.1 スキーマ
├── checklists/          # 品質チェックリスト
│   └── requirements.md
└── tasks.md             # Phase 2 output (/speckit.tasks)
```

### Source Code (repository root)

```text
backend/
├── app/
│   └── Http/
│       └── Middleware/
│           ├── AbsoluteSessionTimeout.php    # 新規: 絶対タイムアウト
│           └── SessionManager.php            # 新規: 同時ログイン制御
├── config/
│   ├── hashing.php                           # 新規: ハッシュ設定
│   └── logging.php                           # 既存: security チャンネル追加
├── packages/Domain/Staff/
│   ├── Domain/
│   │   ├── Model/
│   │   │   └── PasswordHistory.php           # 新規: パスワード履歴エンティティ
│   │   ├── Services/
│   │   │   ├── PasswordHistoryService.php    # 新規: パスワード履歴サービス
│   │   │   └── SessionManagerService.php     # 新規: セッション管理サービス
│   │   └── Repositories/
│   │       └── PasswordHistoryRepositoryInterface.php  # 新規
│   ├── Application/
│   │   ├── Repositories/
│   │   │   └── PasswordHistoryRepository.php # 新規
│   │   └── UseCases/
│   │       └── ChangePassword/               # 新規: パスワード変更ユースケース
│   └── Infrastructure/
│       └── EloquentModels/
│           └── EloquentPasswordHistory.php   # 新規
└── database/migrations/
    └── xxxx_create_password_histories_table.php  # 新規

frontend/
├── src/
│   └── features/
│       ├── auth/
│       │   ├── components/
│       │   │   └── SessionList.tsx           # 新規: セッション一覧
│       │   ├── hooks/
│       │   │   └── useSessions.ts            # 新規: セッション管理フック
│       │   └── services/
│       │       └── sessionApi.ts             # 新規: セッション API
│       └── settings/
│           └── components/
│               └── PasswordChangeForm.tsx    # 新規: パスワード変更フォーム
└── tests/

.github/workflows/
└── security.yml                              # 新規: セキュリティスキャン
```

**Structure Decision**: 既存の DDD アーキテクチャ（backend/packages/Domain/Staff/）に準拠し、セキュリティ関連の新機能を Staff ドメインに追加する。

---

## Complexity Tracking

| Violation | Why Needed | Simpler Alternative Rejected Because |
|-----------|------------|-------------------------------------|
| なし | - | - |

---

## Implementation Phases

### Phase 1: バックエンド基盤

1. パスワードハッシュ設定（config/hashing.php）
2. パスワード履歴エンティティ・リポジトリ
3. パスワード履歴サービス
4. パスワード変更 API

### Phase 2: セッション管理

1. 絶対タイムアウトミドルウェア
2. 同時ログイン制御サービス
3. セッション管理 API
4. ログイン処理への統合

### Phase 3: セキュリティログ・監査

1. security ログチャンネル設定
2. セキュリティイベントリスナー
3. CI/CD セキュリティスキャン

### Phase 4: フロントエンド

1. セッション一覧 UI
2. パスワード変更フォーム
3. タイムアウト警告表示（将来実装）

---

## Dependencies

| 依存元 | 依存先 | 関係 |
|--------|--------|------|
| パスワード履歴 | Staff エンティティ | staff_id で関連付け |
| セッション管理 | 認証機能（EPIC-001） | ログイン処理に統合 |
| パスワード変更 API | パスワードポリシー | バリデーションで使用 |
| パスワード変更 API | パスワード履歴サービス | 再利用チェック |

---

## Risks and Mitigations

| リスク | 影響 | 対策 |
|--------|------|------|
| Have I Been Pwned API 障害 | パスワード漏洩チェック不可 | タイムアウト5秒、スキップして警告ログ |
| bcrypt cost=12 のパフォーマンス | ログイン遅延 | 許容範囲（300ms）、必要に応じて Argon2id に移行 |
| セッション絶対タイムアウト精度 | 業務中断 | 将来的に5分前警告表示を実装 |

---

## Generated Artifacts

- [research.md](./research.md) - 調査結果
- [data-model.md](./data-model.md) - データモデル
- [quickstart.md](./quickstart.md) - クイックスタートガイド
- [contracts/api.yaml](./contracts/api.yaml) - API コントラクト（OpenAPI 3.1）
