# Tasks: Docker 環境構築

**Input**: Design documents from `/specs/002-docker-environment/`
**Prerequisites**: plan.md, spec.md, research.md, data-model.md, contracts/

**Tests**: テストは明示的に要求されていないため、含まれていません。

**Organization**: タスクはユーザーストーリーごとにグループ化され、各ストーリーの独立した実装とテストを可能にします。

## Format: `[ID] [P?] [Story] Description`

- **[P]**: 並列実行可能（異なるファイル、依存関係なし）
- **[Story]**: タスクが属するユーザーストーリー（例: US1, US2, US3）
- 説明には正確なファイルパスを含める

## Path Conventions

- **docker-compose.yml**: リポジトリルート
- **.env.example**: リポジトリルート
- **infrastructure/nginx/**: Nginx 設定
- **backend/Dockerfile**: バックエンド Docker 設定
- **frontend/Dockerfile**: フロントエンド Docker 設定

---

## Phase 1: Setup (プロジェクト初期化)

**Purpose**: Docker Compose 環境の基盤構築

- [x] T001 infrastructure/nginx/ ディレクトリを作成
- [x] T002 [P] .env.example ファイルを作成（ポート設定とDB認証情報のデフォルト値）
- [x] T003 [P] .gitignore に .env を追加

---

## Phase 2: Foundational (ブロッキング前提条件)

**Purpose**: 全ユーザーストーリーに必要なコア設定

**⚠️ CRITICAL**: このフェーズが完了するまで、ユーザーストーリーの作業は開始できません

- [x] T004 docker-compose.yml のベース構造を作成（version, networks, volumes 定義）
- [x] T005 [P] frontend/Dockerfile を作成（Node.js + Vite 開発サーバー）
- [x] T006 [P] backend/Dockerfile を作成（PHP + Laravel 開発サーバー）
- [x] T007 infrastructure/nginx/default.conf を作成（リバースプロキシ + WebSocket 対応）

**Checkpoint**: 基盤準備完了 - ユーザーストーリーの実装を開始できます

---

## Phase 3: User Story 1 - 開発環境の一括起動 (Priority: P1) 🎯 MVP

**Goal**: `docker compose up -d` で5つの全サービスを起動可能にする

**Independent Test**: `docker compose up -d` 実行後、`docker compose ps` で全サービスが Running 状態になることを確認

### Implementation for User Story 1

- [x] T008 [US1] docker-compose.yml に db サービスを定義（MySQL 8.0 + healthcheck）
- [x] T009 [US1] docker-compose.yml に frontend サービスを定義（Vite 開発サーバー）
- [x] T010 [US1] docker-compose.yml に backend サービスを定義（Laravel + depends_on: db healthy）
- [x] T011 [US1] docker-compose.yml に phpmyadmin サービスを定義（depends_on: db started）
- [x] T012 [US1] docker-compose.yml に nginx サービスを定義（depends_on: frontend, backend）
- [x] T013 [US1] docker compose config で構文検証を実行
- [x] T014 [US1] docker compose up -d で全サービスの起動を確認

**Checkpoint**: User Story 1 完了 - 全サービスが一括起動可能

---

## Phase 4: User Story 2 - 開発環境の一括停止 (Priority: P1)

**Goal**: `docker compose down` で全サービスを停止・削除可能にする

**Independent Test**: `docker compose down` 実行後、`docker compose ps` でコンテナが表示されないことを確認

### Implementation for User Story 2

- [x] T015 [US2] docker compose down で全コンテナの停止を確認
- [x] T016 [US2] docker compose ps で稼働中コンテナがないことを確認

**Checkpoint**: User Story 2 完了 - 全サービスが一括停止可能

---

## Phase 5: User Story 3 - データベースデータの永続化 (Priority: P1)

**Goal**: コンテナ再起動後もデータベースデータが保持される

**Independent Test**: テストデータ投入 → `docker compose down` → `docker compose up -d` → データ存在確認

### Implementation for User Story 3

- [x] T017 [US3] docker-compose.yml の volumes セクションに db_data Named Volume を定義
- [x] T018 [US3] db サービスに db_data:/var/lib/mysql ボリュームマウントを設定
- [x] T019 [US3] テストデータ投入 → 再起動 → データ保持の確認

**Checkpoint**: User Story 3 完了 - データベースデータが永続化される

---

## Phase 6: User Story 4 - サービス間通信 (Priority: P2)

**Goal**: frontend ↔ backend ↔ db 間の通信が正常に動作

**Independent Test**: バックエンドからDB接続確認、Nginx 経由でのフロントエンド・バックエンドアクセス確認

### Implementation for User Story 4

- [x] T020 [US4] docker-compose.yml で全サービスを app-network に接続
- [x] T021 [US4] backend コンテナから db への接続テスト（mysql コマンド）
- [x] T022 [US4] nginx 経由で frontend へのアクセス確認（curl http://localhost/）
- [x] T023 [US4] nginx 経由で backend へのアクセス確認（curl http://localhost/api）

**Checkpoint**: User Story 4 完了 - サービス間通信が正常

---

## Phase 7: User Story 5 - ホットリロード対応 (Priority: P2)

**Goal**: ソースコード変更が即座にブラウザに反映される

**Independent Test**: フロントエンドのコードを変更し、ブラウザで自動更新を確認

### Implementation for User Story 5

- [x] T024 [US5] frontend サービスに ./frontend:/app ボリュームマウントを設定
- [x] T025 [US5] frontend サービスに /app/node_modules 匿名ボリュームを設定
- [x] T026 [US5] backend サービスに ./backend:/var/www/html ボリュームマウントを設定
- [x] T027 [US5] infrastructure/nginx/default.conf に WebSocket 対応設定を追加
- [x] T028 [US5] フロントエンドコード変更でHMR動作を確認

**Checkpoint**: User Story 5 完了 - ホットリロードが動作

---

## Phase 8: User Story 6 - ポート設定のカスタマイズ (Priority: P3)

**Goal**: 環境変数でポート番号を変更可能

**Independent Test**: .env でポート変更 → サービス起動 → 指定ポートでアクセス確認

### Implementation for User Story 6

- [x] T029 [US6] docker-compose.yml の全ポート設定を環境変数化（${VAR:-default} 形式）
- [x] T030 [US6] .env.example に全ポート変数のデフォルト値を記載
- [x] T031 [US6] カスタムポートでの起動・アクセス確認

**Checkpoint**: User Story 6 完了 - ポートのカスタマイズが可能

---

## Phase 9: Polish & Cross-Cutting Concerns

**Purpose**: 複数ユーザーストーリーに影響する改善

- [x] T032 [P] quickstart.md の手順に従って全機能を検証
- [x] T033 [P] README.md に Docker Compose 起動手順を追記
- [x] T034 docker compose down -v でボリューム削除の動作確認

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: 依存関係なし - 即座に開始可能
- **Foundational (Phase 2)**: Setup 完了に依存 - 全ユーザーストーリーをブロック
- **User Stories (Phase 3-8)**: Foundational フェーズ完了に依存
  - User Story 1, 2, 3 は全て P1（最優先）
  - User Story 4, 5 (P2) は US1-3 完了後
  - User Story 6 (P3) は US1-5 完了後
- **Polish (Phase 9)**: 希望するユーザーストーリー完了に依存

### User Story Dependencies

- **User Story 1 (P1)**: Foundational 後に開始可能 - 他ストーリーへの依存なし
- **User Story 2 (P1)**: US1 完了後（起動してから停止をテスト）
- **User Story 3 (P1)**: US1 完了後（起動してからデータ永続化をテスト）
- **User Story 4 (P2)**: US1 完了後（起動してから通信をテスト）
- **User Story 5 (P2)**: US1 完了後（起動してからホットリロードをテスト）
- **User Story 6 (P3)**: US1 完了後（起動してからポート変更をテスト）

### Within Each User Story

- 設定ファイル作成 → 動作確認の順序
- 各ストーリー完了後にチェックポイントで独立検証

### Parallel Opportunities

- T002, T003 は並列実行可能
- T005, T006, T007 は並列実行可能
- T032, T033 は並列実行可能

---

## Parallel Example: Phase 2 (Foundational)

```bash
# 以下のタスクを並列で実行可能:
Task: "frontend/Dockerfile を作成"
Task: "backend/Dockerfile を作成"
Task: "infrastructure/nginx/default.conf を作成"
```

---

## Implementation Strategy

### MVP First (User Story 1 Only)

1. Phase 1: Setup 完了
2. Phase 2: Foundational 完了（CRITICAL - 全ストーリーをブロック）
3. Phase 3: User Story 1 完了
4. **STOP and VALIDATE**: `docker compose up -d` と `docker compose ps` で独立検証
5. デプロイ/デモ可能

### Incremental Delivery

1. Setup + Foundational 完了 → 基盤準備完了
2. User Story 1 追加 → 独立テスト → デモ（MVP!）
3. User Story 2 追加 → 独立テスト → デモ
4. User Story 3 追加 → 独立テスト → デモ
5. User Story 4 追加 → 独立テスト → デモ
6. User Story 5 追加 → 独立テスト → デモ
7. User Story 6 追加 → 独立テスト → デモ
8. 各ストーリーは以前のストーリーを壊さずに価値を追加

### Single Developer Strategy

1. Phase 1 → Phase 2 を順次完了
2. User Story 1 → 2 → 3 → 4 → 5 → 6 を優先順に実装
3. 各ストーリー完了後にチェックポイントで検証

---

## Notes

- [P] タスク = 異なるファイル、依存関係なし
- [Story] ラベルはタスクを特定のユーザーストーリーにマッピング
- 各ユーザーストーリーは独立して完了・テスト可能
- 各タスクまたは論理グループ後にコミット
- 任意のチェックポイントで停止してストーリーを独立検証可能
- 回避: 曖昧なタスク、同一ファイルの競合、独立性を損なうクロスストーリー依存
