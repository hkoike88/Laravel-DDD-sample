# Tasks: フロントエンド初期設定

**Input**: Design documents from `/specs/004-frontend-setup/`
**Prerequisites**: plan.md, spec.md, research.md, data-model.md

**Tests**: このフィーチャーではテストタスクは明示的に要求されていないため、テストタスクは含みません。

**Organization**: タスクはユーザーストーリーごとにグループ化されています。

## Format: `[ID] [P?] [Story] Description`

- **[P]**: 並列実行可能（異なるファイル、依存関係なし）
- **[Story]**: タスクが属するユーザーストーリー（US1, US2, US3 など）
- 説明には正確なファイルパスを含める

## Path Conventions

- **Web app**: `frontend/` にフロントエンドソースコード
- **Feature-based structure**: `frontend/src/` に Feature-based ディレクトリ構成

---

## Phase 1: Setup（プロジェクト初期化）

**Purpose**: Docker 環境の確認と Vite プロジェクトの作成

- [x] T001 Docker 環境の起動確認（docker compose ps で全サービス Running 確認）
- [x] T002 フロントエンドコンテナへのアクセス確認（docker compose exec frontend bash）
- [x] T003 既存の frontend/ ディレクトリ内容の確認

---

## Phase 2: Foundational（基盤構築）

**Purpose**: すべてのユーザーストーリーの前提となるコア基盤

**⚠️ CRITICAL**: このフェーズが完了するまでユーザーストーリーの作業は開始できません

- [x] T004 Vite プロジェクトの作成（npm create vite@latest . -- --template react-ts in frontend/）
- [x] T005 依存関係のインストール（npm install in frontend/）
- [x] T006 vite.config.ts の Docker 対応設定（server.host: '0.0.0.0' 追加）
- [x] T007 [P] tsconfig.json のパスエイリアス設定（@/ → src/ マッピング追加）
- [x] T008 [P] vite.config.ts のパスエイリアス設定（resolve.alias 追加）

**Checkpoint**: Vite 基盤準備完了 - `npm run dev` で開発サーバー起動確認

---

## Phase 3: User Story 1 - Vite + React + TypeScript プロジェクトの作成と開発サーバー起動 (Priority: P1) 🎯 MVP

**Goal**: React + TypeScript + Vite プロジェクトが Docker コンテナ内で正常に動作し、開発サーバーでアプリケーションが表示される状態にする

**Independent Test**: `npm run dev` で開発サーバーが起動し、http://localhost:5173 でアプリケーションが表示される

### Implementation for User Story 1

- [x] T009 [US1] 開発サーバーの起動確認（npm run dev で http://localhost:5173 にアクセス）
- [x] T010 [US1] ホットリロードの動作確認（TypeScript ファイル編集後にブラウザ自動更新）
- [x] T011 [US1] プロダクションビルドの確認（npm run build で dist/ ディレクトリ生成）
- [x] T012 [US1] ビルド成果物のプレビュー確認（npm run preview）

**Checkpoint**: User Story 1 完了 - 開発サーバーが動作し、ビルドが成功する

---

## Phase 4: User Story 2 - Feature-based ディレクトリ構成の作成 (Priority: P1)

**Goal**: Feature-based アーキテクチャに基づいたディレクトリ構成を整備

**Independent Test**: src/ 配下に 7 つのディレクトリ（app/, pages/, features/, components/, hooks/, lib/, types/）が存在する

### Implementation for User Story 2

- [x] T013 [P] [US2] app/ ディレクトリ構成の作成（frontend/src/app/, frontend/src/app/providers/）
- [x] T014 [P] [US2] pages/ ディレクトリの作成（frontend/src/pages/）
- [x] T015 [P] [US2] features/ ディレクトリの作成（frontend/src/features/）
- [x] T016 [P] [US2] components/ ディレクトリ構成の作成（frontend/src/components/ui/, frontend/src/components/layout/）
- [x] T017 [P] [US2] hooks/ ディレクトリの作成（frontend/src/hooks/）
- [x] T018 [P] [US2] lib/ ディレクトリの作成（frontend/src/lib/）
- [x] T019 [P] [US2] types/ ディレクトリの作成（frontend/src/types/）
- [x] T020 [US2] App.tsx を src/app/App.tsx に移動
- [x] T021 [US2] router.tsx の作成（frontend/src/app/router.tsx）
- [x] T022 [US2] 各ディレクトリに .gitkeep ファイルを配置
- [x] T023 [US2] main.tsx のインポートパス修正

**Checkpoint**: User Story 2 完了 - Feature-based ディレクトリ構成が存在

---

## Phase 5: User Story 3 - TypeScript 型チェックの動作確認 (Priority: P1)

**Goal**: TypeScript の型チェックが strict モードで正しく動作する環境を整備

**Independent Test**: `npx tsc --noEmit` コマンドがエラー 0 件で完了する

### Implementation for User Story 3

- [x] T024 [US3] tsconfig.json の strict モード確認（"strict": true）
- [x] T025 [US3] tsconfig.json の追加設定（noUnusedLocals, noUnusedParameters 等）
- [x] T026 [US3] 型チェック実行と確認（npx tsc --noEmit）
- [x] T027 [US3] 型エラーがあれば修正
- [x] T028 [US3] パスエイリアス（@/）の動作確認（サンプルインポートで検証）

**Checkpoint**: User Story 3 完了 - TypeScript 型チェックがエラー 0 件で完了

---

## Phase 6: User Story 4 - ESLint / Prettier によるコード品質管理 (Priority: P2)

**Goal**: ESLint と Prettier が設定され、コード品質とフォーマットが統一された状態にする

**Independent Test**: `npm run lint` と `npm run format` が正常に実行できる

### Implementation for User Story 4

- [x] T029 [US4] Prettier のインストール（npm install -D prettier eslint-config-prettier eslint-plugin-prettier）
- [x] T030 [US4] .prettierrc 設定ファイルの作成（frontend/.prettierrc）
- [x] T031 [US4] eslint.config.js の更新（Prettier 連携設定追加）
- [x] T032 [US4] package.json に lint スクリプト追加（"lint": "eslint src"）
- [x] T033 [US4] package.json に format スクリプト追加（"format": "prettier --write src"）
- [x] T034 [US4] ESLint 実行と確認（npm run lint）
- [x] T035 [US4] Prettier 実行と確認（npm run format）
- [x] T036 [US4] 検出されたエラーの修正

**Checkpoint**: User Story 4 完了 - ESLint / Prettier がエラー 0 件で完了

---

## Phase 7: User Story 5 - Tailwind CSS によるスタイリング環境 (Priority: P2)

**Goal**: Tailwind CSS が正しく設定され、ユーティリティクラスでスタイリングできる状態にする

**Independent Test**: Tailwind CSS のユーティリティクラスがコンポーネントに適用され、ブラウザで正しく表示される

### Implementation for User Story 5

- [x] T037 [US5] Tailwind CSS のインストール（npm install -D tailwindcss postcss autoprefixer）
- [x] T038 [US5] Tailwind CSS の初期化（npx tailwindcss init -p）
- [x] T039 [US5] tailwind.config.js の content パス設定
- [x] T040 [US5] src/index.css に Tailwind ディレクティブ追加（@tailwind base/components/utilities）
- [x] T041 [US5] サンプルコンポーネントで Tailwind クラス適用確認
- [x] T042 [US5] ビルド後の CSS 出力確認（未使用クラス除去）

**Checkpoint**: User Story 5 完了 - Tailwind CSS が正しく動作

---

## Phase 8: User Story 6 - 必要なパッケージのインストールと動作確認 (Priority: P2)

**Goal**: 開発に必要なパッケージがインストールされ、使用可能な状態にする

**Independent Test**: package.json に必要なパッケージが含まれ、`npm ls` で確認できる

### Implementation for User Story 6

- [x] T043 [US6] React Router のインストール（npm install react-router-dom）
- [x] T044 [US6] TanStack Query のインストール（npm install @tanstack/react-query）
- [x] T045 [US6] Zustand のインストール（npm install zustand）
- [x] T046 [US6] Axios のインストール（npm install axios）
- [x] T047 [US6] React Hook Form と Zod のインストール（npm install react-hook-form zod @hookform/resolvers）
- [x] T048 [US6] パッケージインストール確認（npm ls で依存関係表示）
- [x] T049 [US6] ビルド確認（npm run build で依存関係エラーがないこと）

**Checkpoint**: User Story 6 完了 - 必要なパッケージがすべてインストールされている

---

## Phase 9: Polish & Cross-Cutting Concerns

**Purpose**: 最終確認とドキュメント整備

- [x] T050 [P] 全成功基準の検証（SC-001〜SC-007 を順番に確認）
- [x] T051 [P] quickstart.md の手順に従って一通り動作確認
- [x] T052 不要なファイルのクリーンアップ（Vite デフォルトファイルの整理）
- [x] T053 [P] frontend/.gitignore の確認と更新
- [x] T054 最終動作確認（全コマンドが正常に実行できることを確認）

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: 依存なし - 即座に開始可能
- **Foundational (Phase 2)**: Setup 完了後 - すべてのユーザーストーリーをブロック
- **User Story 1 (Phase 3)**: Foundational 完了後
- **User Story 2 (Phase 4)**: US1 完了後（開発サーバーが動作している前提）
- **User Story 3 (Phase 5)**: US2 完了後（ディレクトリ構成が存在する前提）
- **User Story 4 (Phase 6)**: US1 完了後（プロジェクトが動作している前提）- US3 と並列可能
- **User Story 5 (Phase 7)**: US1 完了後（プロジェクトが動作している前提）- US4 と並列可能
- **User Story 6 (Phase 8)**: US1 完了後（プロジェクトが動作している前提）- US4, US5 と並列可能
- **Polish (Phase 9)**: すべてのユーザーストーリー完了後

### User Story Dependencies

| Story | Depends On | Can Run With |
|-------|-----------|--------------|
| US1 (P1) | Foundational | - |
| US2 (P1) | US1 | - |
| US3 (P1) | US2 | - |
| US4 (P2) | US1 | US5, US6 |
| US5 (P2) | US1 | US4, US6 |
| US6 (P2) | US1 | US4, US5 |

### Parallel Opportunities

- **Phase 4 (US2)**: T013-T019 は異なるディレクトリなので並列実行可能
- **Phase 6-8**: US4, US5, US6 は並列実行可能
- **Phase 9**: T050, T051, T053 は並列実行可能

---

## Parallel Example: User Story 2

```bash
# ディレクトリ作成は並列実行可能:
Task: "T013 [P] [US2] Create app/ directory structure"
Task: "T014 [P] [US2] Create pages/ directory"
Task: "T015 [P] [US2] Create features/ directory"
Task: "T016 [P] [US2] Create components/ directory structure"
Task: "T017 [P] [US2] Create hooks/ directory"
Task: "T018 [P] [US2] Create lib/ directory"
Task: "T019 [P] [US2] Create types/ directory"
```

---

## Implementation Strategy

### MVP First (User Story 1 Only)

1. Complete Phase 1: Setup
2. Complete Phase 2: Foundational (CRITICAL)
3. Complete Phase 3: User Story 1
4. **STOP and VALIDATE**: `npm run dev` と http://localhost:5173 で動作確認
5. Deploy/demo if ready

### Incremental Delivery

1. Setup + Foundational → Vite 基盤準備完了
2. User Story 1 → 開発サーバー動作確認 → **MVP!**
3. User Story 2 → ディレクトリ構成完成
4. User Story 3 → TypeScript 型チェック動作
5. User Story 4-6 → コード品質 + スタイリング + パッケージ
6. Each story adds value without breaking previous stories

### P1 完了後の状態

User Story 1-3（すべて P1）完了後:
- 開発サーバーが動作
- Feature-based ディレクトリ構成が存在
- TypeScript 型チェックが通る
- 開発者は基本的な開発を開始可能

---

## Notes

- すべてのコマンドは `docker compose exec frontend` 経由で実行
- [P] タスク = 異なるファイル、依存関係なし
- [Story] ラベルはタスクを特定のユーザーストーリーにマッピング
- 各ユーザーストーリーは独立して完了・テスト可能
- 各タスクまたは論理グループ完了後にコミット推奨
- チェックポイントで独立してストーリーを検証可能
