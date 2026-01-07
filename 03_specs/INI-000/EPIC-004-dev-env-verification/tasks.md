# Tasks: 開発環境動作確認

**Input**: Design documents from `/specs/005-dev-env-verification/`
**Prerequisites**: plan.md, spec.md, research.md, data-model.md, quickstart.md, contracts/

**Tests**: このフィーチャーは環境検証タスクであり、コード実装を伴わないため、テストタスクは含みません。

**Organization**: タスクはユーザーストーリーごとにグループ化されています。各ストーリーは独立して検証可能です。

## Format: `[ID] [P?] [Story] Description`

- **[P]**: 並列実行可能（異なる検証項目、依存関係なし）
- **[Story]**: タスクが属するユーザーストーリー（US1, US2, US3 など）
- 各タスクには具体的なコマンドまたは検証手順を含める

## Path Conventions

- **Docker Compose**: プロジェクトルートで `docker compose` コマンドを実行
- **Backend**: `docker compose exec backend` 経由でコマンド実行
- **Frontend**: `docker compose exec frontend` 経由でコマンド実行

---

## Phase 1: Setup（環境準備）

**Purpose**: 検証を開始する前の環境準備

- [x] T001 既存の Docker コンテナを停止（docker compose down）
- [x] T002 Docker イメージの再ビルド（docker compose build --no-cache）
- [x] T003 環境変数ファイルの確認（.env ファイルの存在確認）

---

## Phase 2: User Story 1 - 全サービスの起動確認 (Priority: P1) 🎯 MVP

**Goal**: Docker 環境で全サービスが正常に起動し、各エンドポイントにアクセスできる状態を確認する

**Independent Test**: `docker compose up -d` で全サービスが起動し、各 URL にアクセスできることを確認

### Implementation for User Story 1

- [x] T004 [US1] 全サービスの起動（docker compose up -d）
- [x] T005 [US1] 起動完了の待機（60 秒以内に全サービスが起動することを確認）
- [x] T006 [US1] 全サービスのステータス確認（docker compose ps で全サービスが Running 状態）
- [x] T007 [P] [US1] フロントエンドアクセス確認（http://localhost:5173 でアプリケーション表示）
- [x] T008 [P] [US1] バックエンド API アクセス確認（http://localhost:80/api/health でレスポンス取得）
- [x] T009 [P] [US1] phpMyAdmin アクセス確認（http://localhost:8080 で管理画面表示）

**Checkpoint**: User Story 1 完了 - 全サービスが起動し、各エンドポイントにアクセス可能

---

## Phase 3: User Story 2 - バックエンドとデータベースの接続確認 (Priority: P1)

**Goal**: バックエンドアプリケーションがデータベースに正常に接続できることを確認する

**Independent Test**: マイグレーションが正常に実行でき、DB ヘルスチェック API が正常なレスポンスを返す

### Implementation for User Story 2

- [x] T010 [US2] データベースヘルスチェック API 確認（http://localhost:80/api/health/db で接続状態確認）
- [x] T011 [US2] マイグレーション状態確認（docker compose exec backend php artisan migrate:status）
- [x] T012 [US2] マイグレーション実行（docker compose exec backend php artisan migrate）
- [x] T013 [US2] phpMyAdmin でテーブル作成確認（ブラウザで http://localhost:8080 にアクセスしテーブル確認）

**Checkpoint**: User Story 2 完了 - DB 接続とマイグレーションが正常に動作

---

## Phase 4: User Story 3 - フロントエンドとバックエンドの連携確認 (Priority: P1)

**Goal**: フロントエンドからバックエンド API を呼び出して正常にデータを取得できることを確認する

**Independent Test**: フロントエンドからバックエンド API を呼び出し、レスポンスが正常に取得できる

### Implementation for User Story 3

- [x] T014 [US3] Nginx 経由での API アクセス確認（http://localhost:80/api/health）
- [x] T015 [US3] フロントエンドからの API 呼び出し確認（ブラウザ開発者ツールで確認）
- [x] T016 [US3] レスポンスデータ形式の確認（JSON 形式で期待するフィールドが含まれる）

**Checkpoint**: User Story 3 完了 - フロントエンドとバックエンドの連携が正常に動作

---

## Phase 5: User Story 4 - CORS 設定確認 (Priority: P1)

**Goal**: クロスオリジンリクエストが正しく設定され、フロントエンドからバックエンドへの通信が CORS エラーなく行えることを確認する

**Independent Test**: フロントエンド（localhost:5173）から直接バックエンド API にリクエストを送信し、CORS エラーが発生しないことを確認

### Implementation for User Story 4

- [x] T017 [US4] 直接 API アクセスでの CORS 確認（curl -H "Origin: http://localhost:5173" http://localhost:80/api/health）
- [x] T018 [US4] プリフライトリクエストの確認（curl -X OPTIONS -H "Origin: http://localhost:5173" -H "Access-Control-Request-Method: GET" http://localhost:80/api/health）
- [x] T019 [US4] ブラウザでの CORS エラー確認（開発者ツールで Network タブを確認、エラーがないこと）

**Checkpoint**: User Story 4 完了 - CORS 設定が正しく機能

---

## Phase 6: User Story 5 - バックエンドテスト実行確認 (Priority: P2)

**Goal**: バックエンドのテストスイートと静的解析が正常に実行できることを確認する

**Independent Test**: Pest テストと PHPStan 静的解析がエラー 0 件で完了する

### Implementation for User Story 5

- [x] T020 [P] [US5] Pest テスト実行（docker compose exec backend php artisan test）
- [x] T021 [P] [US5] PHPStan 静的解析実行（docker compose exec backend ./vendor/bin/phpstan analyse）
- [x] T022 [P] [US5] Pint フォーマットチェック（docker compose exec backend ./vendor/bin/pint --test）

**Checkpoint**: User Story 5 完了 - バックエンドテストと静的解析がすべて成功

---

## Phase 7: User Story 6 - フロントエンドテスト・リント実行確認 (Priority: P2)

**Goal**: フロントエンドのリント、型チェック、ビルドが正常に実行できることを確認する

**Independent Test**: ESLint、TypeScript 型チェック、ビルドがすべてエラー 0 件で完了する

### Implementation for User Story 6

- [x] T023 [P] [US6] ESLint 実行（docker compose exec frontend npm run lint）
- [x] T024 [P] [US6] TypeScript 型チェック実行（docker compose exec frontend npm run typecheck）
- [x] T025 [P] [US6] Prettier フォーマットチェック（docker compose exec frontend npm run format:check）
- [x] T026 [US6] プロダクションビルド実行（docker compose exec frontend npm run build）

**Checkpoint**: User Story 6 完了 - フロントエンドのリント、型チェック、ビルドがすべて成功

---

## Phase 8: Polish & Cross-Cutting Concerns

**Purpose**: 最終確認と検証結果の記録

- [x] T027 [P] 全成功基準の最終確認（SC-001〜SC-013 を順番に確認）
- [x] T028 [P] quickstart.md の手順に従って一通り動作確認
- [x] T029 検証結果の記録（問題があれば issue として記録）
- [x] T030 環境のクリーンアップ（必要に応じて docker compose down）

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: 依存なし - 即座に開始可能
- **User Story 1 (Phase 2)**: Setup 完了後 - 他のすべてのストーリーをブロック
- **User Story 2 (Phase 3)**: US1 完了後（サービスが起動している前提）
- **User Story 3 (Phase 4)**: US1 完了後（サービスが起動している前提）
- **User Story 4 (Phase 5)**: US1 完了後（サービスが起動している前提）
- **User Story 5 (Phase 6)**: US1 完了後（サービスが起動している前提）
- **User Story 6 (Phase 7)**: US1 完了後（サービスが起動している前提）
- **Polish (Phase 8)**: すべてのユーザーストーリー完了後

### User Story Dependencies

| Story | Depends On | Can Run With |
|-------|-----------|--------------|
| US1 (P1) | Setup | - |
| US2 (P1) | US1 | US3, US4 |
| US3 (P1) | US1 | US2, US4 |
| US4 (P1) | US1 | US2, US3 |
| US5 (P2) | US1 | US2, US3, US4, US6 |
| US6 (P2) | US1 | US2, US3, US4, US5 |

### Parallel Opportunities

- **Phase 2 (US1)**: T007, T008, T009 は異なるエンドポイントなので並列実行可能
- **Phase 6 (US5)**: T020, T021, T022 は異なるツールなので並列実行可能
- **Phase 7 (US6)**: T023, T024, T025 は異なるツールなので並列実行可能
- **Phase 8**: T027, T028 は並列実行可能

---

## Parallel Example: User Story 1

```bash
# エンドポイント確認は並列実行可能:
Task: "T007 [P] [US1] フロントエンドアクセス確認（http://localhost:5173 でアプリケーション表示）"
Task: "T008 [P] [US1] バックエンド API アクセス確認（http://localhost:80/api/health でレスポンス取得）"
Task: "T009 [P] [US1] phpMyAdmin アクセス確認（http://localhost:8080 で管理画面表示）"
```

## Parallel Example: User Story 5 & 6

```bash
# バックエンドテストは並列実行可能:
Task: "T020 [P] [US5] Pest テスト実行"
Task: "T021 [P] [US5] PHPStan 静的解析実行"
Task: "T022 [P] [US5] Pint フォーマットチェック"

# フロントエンドリントは並列実行可能:
Task: "T023 [P] [US6] ESLint 実行"
Task: "T024 [P] [US6] TypeScript 型チェック実行"
Task: "T025 [P] [US6] Prettier フォーマットチェック"
```

---

## Implementation Strategy

### MVP First (User Story 1 Only)

1. Complete Phase 1: Setup
2. Complete Phase 2: User Story 1
3. **STOP and VALIDATE**: 全サービスが起動し、アクセス可能であることを確認
4. この時点で開発環境の基本的な動作が保証される

### Incremental Delivery

1. Setup → 環境準備完了
2. User Story 1 → サービス起動確認 → **MVP!**
3. User Story 2 → DB 接続確認
4. User Story 3 → F/E - B/E 連携確認
5. User Story 4 → CORS 確認
6. User Story 5 → バックエンドテスト確認
7. User Story 6 → フロントエンドテスト確認
8. Each story validates additional aspects of the development environment

### P1 完了後の状態

User Story 1-4（すべて P1）完了後:
- 全サービスが正常に起動
- データベース接続が確立
- フロントエンドとバックエンドの連携が動作
- CORS が正しく設定
- 開発者はアプリケーション開発を開始可能

---

## Notes

- すべてのコマンドはプロジェクトルートで実行
- [P] タスク = 異なる検証項目、依存関係なし
- [Story] ラベルはタスクを特定のユーザーストーリーにマッピング
- 各ユーザーストーリーは独立して検証可能
- 問題が発生した場合は docker compose logs で詳細を確認
- 各チェックポイントで独立してストーリーを検証可能
