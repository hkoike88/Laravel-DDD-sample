# Tasks: バックエンド初期設定

**Input**: Design documents from `/specs/003-backend-setup/`
**Prerequisites**: plan.md, spec.md, research.md, data-model.md, contracts/

**Tests**: このフィーチャーではテストタスクは明示的に要求されていないため、テストタスクは含みません。

**Organization**: タスクはユーザーストーリーごとにグループ化されています。

## Format: `[ID] [P?] [Story] Description`

- **[P]**: 並列実行可能（異なるファイル、依存関係なし）
- **[Story]**: タスクが属するユーザーストーリー（US1, US2, US3 など）
- 説明には正確なファイルパスを含める

## Path Conventions

- **Web app**: `backend/` にバックエンドソースコード
- **DDD structure**: `backend/app/src/` に DDD ディレクトリ構成

---

## Phase 1: Setup（プロジェクト初期化）

**Purpose**: Laravel プロジェクトの基本セットアップ

- [x] T001 Docker 環境の起動確認（docker compose ps で全サービス Running 確認）
- [x] T002 バックエンドコンテナへのアクセス確認（docker compose exec backend bash）
- [x] T003 既存の backend/ ディレクトリ内容の確認とバックアップ

---

## Phase 2: Foundational（基盤構築）

**Purpose**: すべてのユーザーストーリーの前提となるコア基盤

**⚠️ CRITICAL**: このフェーズが完了するまでユーザーストーリーの作業は開始できません

- [x] T004 Laravel プロジェクトの作成（composer create-project laravel/laravel . --prefer-dist in backend/）
- [x] T005 環境設定ファイルの作成（backend/.env を backend/.env.example からコピー）
- [x] T006 アプリケーションキーの生成（php artisan key:generate in backend/）
- [x] T007 .env ファイルの DB 接続設定更新（DB_HOST=db, DB_DATABASE=library, DB_USERNAME=library, DB_PASSWORD=secret）
- [x] T008 [P] ファイル権限の設定（chmod -R 755 backend/storage backend/bootstrap/cache）

**Checkpoint**: Laravel 基盤準備完了 - `php artisan --version` で動作確認

---

## Phase 3: User Story 1 - Laravel プロジェクトの作成と基本動作確認 (Priority: P1) 🎯 MVP

**Goal**: Laravel プロジェクトを Docker コンテナ内で作成し、基本的なコマンドが実行できる状態にする

**Independent Test**: `php artisan --version` が Laravel バージョンを表示し、API エンドポイントが正常にレスポンスを返す

### Implementation for User Story 1

- [x] T009 [US1] Laravel バージョン確認（php artisan --version で Laravel 11.x が表示される）
- [x] T010 [US1] artisan コマンド一覧の確認（php artisan list が正常に実行される）
- [x] T011 [US1] ヘルスチェック API ルートの作成（backend/routes/api.php に /health エンドポイント追加）
- [x] T012 [US1] ヘルスチェックコントローラーの作成（backend/app/Http/Controllers/HealthController.php）
- [x] T013 [US1] API 動作確認（curl http://localhost/api/health で正常レスポンス確認）

**Checkpoint**: User Story 1 完了 - Laravel が動作し、API エンドポイントがレスポンスを返す

---

## Phase 4: User Story 2 - DDD ディレクトリ構成の作成 (Priority: P1)

**Goal**: DDD アーキテクチャに基づいたディレクトリ構成を整備

**Independent Test**: app/src/ 配下に DDD 構成ディレクトリが存在し、Composer オートローダーが正しく設定されている

### Implementation for User Story 2

- [x] T014 [P] [US2] Common ディレクトリ構成の作成（backend/app/src/Common/{Domain,Application,Infrastructure}）
- [x] T015 [P] [US2] BookManagement ディレクトリ構成の作成（backend/app/src/BookManagement/{Domain,Application,Infrastructure,Presentation}）
- [x] T016 [P] [US2] LoanManagement ディレクトリ構成の作成（backend/app/src/LoanManagement/{Domain,Application,Infrastructure,Presentation}）
- [x] T017 [P] [US2] UserManagement ディレクトリ構成の作成（backend/app/src/UserManagement/{Domain,Application,Infrastructure,Presentation}）
- [x] T018 [US2] composer.json の PSR-4 オートロード設定更新（App\\Src\\ => app/src/ 追加）
- [x] T019 [US2] Composer オートローダーの再生成（composer dump-autoload）
- [x] T020 [US2] 各ディレクトリに .gitkeep ファイルを配置
- [x] T021 [US2] オートロード動作確認（サンプルクラスを作成して名前空間解決を確認）

**Checkpoint**: User Story 2 完了 - DDD ディレクトリ構成が存在し、オートローダーが正しく動作

---

## Phase 5: User Story 3 - データベース接続の確認 (Priority: P1)

**Goal**: Laravel から MySQL データベースへの接続が正常に動作することを確認

**Independent Test**: `php artisan migrate` コマンドが正常に実行できる

### Implementation for User Story 3

- [x] T022 [US3] データベース接続設定の確認（.env の DB_* 設定が正しいことを確認）
- [x] T023 [US3] データベース接続テスト（php artisan db:show で接続情報表示）
- [x] T024 [US3] マイグレーション実行（php artisan migrate）
- [x] T025 [US3] マイグレーション状態確認（php artisan migrate:status でテーブル一覧表示）
- [x] T026 [US3] ヘルスチェック DB エンドポイントの作成（backend/routes/api.php に /health/db 追加）
- [x] T027 [US3] DB ヘルスチェック API 動作確認（curl http://localhost/api/health/db）

**Checkpoint**: User Story 3 完了 - データベース接続が正常に動作

---

## Phase 6: User Story 4 - 静的解析ツール（PHPStan）の設定 (Priority: P2)

**Goal**: PHPStan/Larastan による静的解析が実行できる環境を整備

**Independent Test**: `./vendor/bin/phpstan analyse` コマンドがエラーなく完了する

### Implementation for User Story 4

- [x] T028 [US4] Larastan のインストール（composer require larastan/larastan --dev）
- [x] T029 [US4] phpstan.neon 設定ファイルの作成（backend/phpstan.neon にレベル 5 設定）
- [x] T030 [US4] 静的解析の初回実行（./vendor/bin/phpstan analyse）
- [x] T031 [US4] 検出されたエラーの修正（エラーがあれば修正）
- [x] T032 [US4] 静的解析の再実行と確認（エラー 0 件で完了）

**Checkpoint**: User Story 4 完了 - PHPStan がエラー 0 件で完了

---

## Phase 7: User Story 5 - テスト環境（Pest）の設定 (Priority: P2)

**Goal**: Pest テストフレームワークが動作する環境を整備

**Independent Test**: `./vendor/bin/pest` コマンドがサンプルテストを実行できる

### Implementation for User Story 5

- [x] T033 [US5] Pest のインストール（composer require pestphp/pest --dev --with-all-dependencies）
- [x] T034 [US5] Pest Laravel プラグインのインストール（composer require pestphp/pest-plugin-laravel --dev）
- [x] T035 [US5] Pest の初期化（./vendor/bin/pest --init）
- [x] T036 [US5] サンプルテストの実行（./vendor/bin/pest）
- [x] T037 [US5] テストディレクトリ構成の確認（backend/tests/Feature/, backend/tests/Unit/）
- [x] T038 [US5] サンプル Feature テストの作成と実行確認

**Checkpoint**: User Story 5 完了 - Pest テストが正常に実行される

---

## Phase 8: User Story 6 - 認証パッケージ（Sanctum）の導入 (Priority: P3)

**Goal**: API 認証の基盤となる Laravel Sanctum がインストールされた状態にする

**Independent Test**: Sanctum の設定ファイルが存在し、personal_access_tokens テーブルが作成されている

### Implementation for User Story 6

- [x] T039 [US6] Sanctum のインストール（composer require laravel/sanctum）
- [x] T040 [US6] Sanctum 設定ファイルの公開（php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"）
- [x] T041 [US6] Sanctum マイグレーションの実行（php artisan migrate）
- [x] T042 [US6] 設定ファイル存在確認（backend/config/sanctum.php の存在確認）
- [x] T043 [US6] personal_access_tokens テーブル存在確認（データベースにテーブルが作成されていることを確認）

**Checkpoint**: User Story 6 完了 - Sanctum が正しくインストールされている

---

## Phase 9: Polish & Cross-Cutting Concerns

**Purpose**: 最終確認とドキュメント整備

- [x] T044 [P] 全成功基準の検証（SC-001〜SC-007 を順番に確認）
- [x] T045 [P] quickstart.md の手順に従って一通り動作確認
- [x] T046 不要なファイルのクリーンアップ（テスト用に作成した一時ファイルの削除）
- [x] T047 [P] backend/.gitignore の更新（必要に応じて追加エントリ）
- [x] T048 最終動作確認（全コマンドが正常に実行できることを確認）

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: 依存なし - 即座に開始可能
- **Foundational (Phase 2)**: Setup 完了後 - すべてのユーザーストーリーをブロック
- **User Story 1 (Phase 3)**: Foundational 完了後
- **User Story 2 (Phase 4)**: Foundational 完了後 - US1 と並列可能
- **User Story 3 (Phase 5)**: Foundational 完了後 - US1, US2 と並列可能
- **User Story 4 (Phase 6)**: US1 完了後（Laravel が動作している前提）
- **User Story 5 (Phase 7)**: US1 完了後（Laravel が動作している前提）- US4 と並列可能
- **User Story 6 (Phase 8)**: US3 完了後（DB マイグレーションが動作している前提）
- **Polish (Phase 9)**: すべてのユーザーストーリー完了後

### User Story Dependencies

| Story | Depends On | Can Run With |
|-------|-----------|--------------|
| US1 (P1) | Foundational | - |
| US2 (P1) | Foundational | US1, US3 |
| US3 (P1) | Foundational | US1, US2 |
| US4 (P2) | US1 | US5 |
| US5 (P2) | US1 | US4 |
| US6 (P3) | US3 | - |

### Parallel Opportunities

- **Phase 4 (US2)**: T014, T015, T016, T017 は異なるディレクトリなので並列実行可能
- **Phase 6-7**: US4 と US5 は並列実行可能
- **Phase 9**: T044, T045, T047 は並列実行可能

---

## Parallel Example: User Story 2

```bash
# DDD ディレクトリ作成は並列実行可能:
Task: "T014 [P] [US2] Create Common directory structure"
Task: "T015 [P] [US2] Create BookManagement directory structure"
Task: "T016 [P] [US2] Create LoanManagement directory structure"
Task: "T017 [P] [US2] Create UserManagement directory structure"
```

---

## Implementation Strategy

### MVP First (User Story 1 Only)

1. Complete Phase 1: Setup
2. Complete Phase 2: Foundational (CRITICAL)
3. Complete Phase 3: User Story 1
4. **STOP and VALIDATE**: `php artisan --version` と API 動作確認
5. Deploy/demo if ready

### Incremental Delivery

1. Setup + Foundational → 基盤準備完了
2. User Story 1 → `php artisan` 動作確認 → **MVP!**
3. User Story 2 → DDD 構成完成
4. User Story 3 → DB 接続確認
5. User Story 4 → 静的解析環境
6. User Story 5 → テスト環境
7. User Story 6 → 認証基盤
8. Each story adds value without breaking previous stories

### P1 完了後の状態

User Story 1-3（すべて P1）完了後:
- Laravel 11.x が動作
- DDD ディレクトリ構成が存在
- DB 接続が正常
- 開発者は基本的な開発を開始可能

---

## Notes

- すべてのコマンドは `docker compose exec backend` 経由で実行
- [P] タスク = 異なるファイル、依存関係なし
- [Story] ラベルはタスクを特定のユーザーストーリーにマッピング
- 各ユーザーストーリーは独立して完了・テスト可能
- 各タスクまたは論理グループ完了後にコミット推奨
- チェックポイントで独立してストーリーを検証可能
