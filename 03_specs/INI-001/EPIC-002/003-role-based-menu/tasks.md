# Tasks: 権限別メニュー表示

**Input**: Design documents from `/specs/003-role-based-menu/`
**Prerequisites**: plan.md (required), spec.md (required), research.md, data-model.md, contracts/

**Tests**: テストタスクを含む（spec.md で独立テストが定義されているため）

**Organization**: タスクはユーザーストーリーごとにグループ化

## Format: `[ID] [P?] [Story] Description`

- **[P]**: 並列実行可能（異なるファイル、依存関係なし）
- **[Story]**: 所属するユーザーストーリー (US1, US2, US3)
- 説明には正確なファイルパスを含める

---

## Phase 1: Setup（セットアップ）

**Purpose**: プロジェクト初期化と基本構造

- [x] T001 ブランチ 003-role-based-menu が存在し、最新であることを確認

---

## Phase 2: Foundational（基盤 - 全ストーリー共通）

**Purpose**: すべてのユーザーストーリーが依存する基盤コード

**⚠️ CRITICAL**: このフェーズが完了するまでユーザーストーリーの実装は開始できません

- [x] T002 [P] Staff 型に is_admin プロパティを追加 in `frontend/src/features/auth/types/auth.ts`
- [x] T003 [P] 管理者アイコン（StaffIcon）を追加 in `frontend/src/features/dashboard/components/icons/MenuIcons.tsx`

**Checkpoint**: 基盤完了 - ユーザーストーリーの実装を開始可能

---

## Phase 3: User Story 1 & 2 - 管理者への管理メニュー表示 / 一般職員からの非表示 (Priority: P1) 🎯 MVP

**Goal**: 管理者にのみ「管理メニュー」セクションを表示し、一般職員には非表示にする

**Independent Test**:
- 管理者アカウントでログイン → ダッシュボードに「管理メニュー」が表示される
- 一般職員アカウントでログイン → ダッシュボードに「管理メニュー」が表示されない

### Implementation for User Story 1 & 2

- [x] T004 [P] [US1] 管理メニュー項目定義を作成 in `frontend/src/features/dashboard/constants/adminMenuItems.tsx`
- [x] T005 [US1] AdminMenuSection コンポーネントを作成 in `frontend/src/features/dashboard/components/AdminMenuSection.tsx`
- [x] T006 [US1] DashboardPage に AdminMenuSection を条件付きで追加 in `frontend/src/features/dashboard/pages/DashboardPage.tsx`

**Checkpoint**: US1 & US2 完了 - 管理者/一般職員でダッシュボードの表示が異なることを確認可能

---

## Phase 4: User Story 3 - 管理者専用URLへの直接アクセス制御 (Priority: P1)

**Goal**: 一般職員が /staff/accounts に直接アクセスした場合、403 エラーを返す

**Independent Test**:
- 一般職員でログイン後、/staff/accounts に直接アクセス → 403 エラーページが表示
- 管理者でログイン後、/staff/accounts にアクセス → 正常にページ表示
- 未ログイン状態で /staff/accounts にアクセス → ログインページへリダイレクト

### Backend Implementation for User Story 3

- [x] T007 [P] [US3] RequireAdmin ミドルウェアを作成 in `backend/app/Http/Middleware/RequireAdmin.php`
- [x] T008 [US3] RequireAdmin ミドルウェアを登録 in `backend/bootstrap/app.php`
- [x] T009 [US3] 管理者専用ルート /api/staff/accounts を追加 in `backend/routes/api.php`
- [x] T010 [US3] AdminAccessTest を作成 in `backend/tests/Feature/Authorization/AdminAccessTest.php`

### Frontend Implementation for User Story 3

- [x] T011 [P] [US3] ForbiddenPage（403エラーページ）を作成 in `frontend/src/pages/errors/ForbiddenPage.tsx`
- [x] T012 [US3] AdminGuard コンポーネントを作成 in `frontend/src/components/guards/AdminGuard.tsx`
- [x] T013 [P] [US3] StaffAccountsPage プレースホルダーを作成 in `frontend/src/features/staff/pages/StaffAccountsPage.tsx`
- [x] T014 [US3] ルーターに /staff/accounts ルートを追加 in `frontend/src/app/router.tsx`

**Checkpoint**: US3 完了 - URL 直接アクセスが適切に制御されることを確認可能

---

## Phase 5: Polish & Cross-Cutting Concerns

**Purpose**: 複数のユーザーストーリーに影響する改善

- [x] T015 [P] バックエンドテストを実行して全テストがパスすることを確認
- [x] T016 [P] フロントエンドビルドを実行してエラーがないことを確認
- [x] T017 [P] PHPStan でバックエンドの型エラーがないことを確認
- [x] T018 quickstart.md に従って手動テストを実施

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: 依存なし - すぐに開始可能
- **Foundational (Phase 2)**: Setup 完了後 - すべてのユーザーストーリーをブロック
- **User Stories (Phase 3-4)**: Foundational 完了後
  - Phase 3 (US1 & US2) と Phase 4 (US3) は並列実行可能
- **Polish (Phase 5)**: すべてのユーザーストーリー完了後

### User Story Dependencies

- **User Story 1 & 2 (P1)**: Foundational 完了後開始可能 - 他ストーリーへの依存なし
- **User Story 3 (P1)**: Foundational 完了後開始可能 - US1/US2 と並列実行可能

### Within Each User Story

- モデル/型定義 → コンポーネント → ページ統合
- バックエンド → ミドルウェア → ルート → テスト
- フロントエンド → ガード → ページ → ルーター

### Parallel Opportunities

- T002, T003: 基盤タスクは並列実行可能
- T004: US1 の定数ファイルは他タスクと並列実行可能
- T007, T011, T013: 異なるファイルのため並列実行可能
- T015, T016, T017: テスト/ビルド確認は並列実行可能

---

## Parallel Example: Phase 2 (Foundational)

```bash
# 基盤タスクを並列で実行:
Task: "Staff 型に is_admin プロパティを追加 in frontend/src/features/auth/types/auth.ts"
Task: "管理者アイコン（SettingsIcon）を追加 in frontend/src/features/dashboard/components/icons/MenuIcons.tsx"
```

## Parallel Example: Phase 4 (US3)

```bash
# バックエンドとフロントエンドの独立したタスクを並列で実行:
Task: "RequireAdmin ミドルウェアを作成 in backend/app/Http/Middleware/RequireAdmin.php"
Task: "ForbiddenPage を作成 in frontend/src/pages/errors/ForbiddenPage.tsx"
Task: "StaffAccountsPage プレースホルダーを作成 in frontend/src/features/staff/pages/StaffAccountsPage.tsx"
```

---

## Implementation Strategy

### MVP First (User Story 1 & 2)

1. Phase 1: Setup 完了
2. Phase 2: Foundational 完了
3. Phase 3: User Story 1 & 2 完了
4. **STOP and VALIDATE**: 管理者/一般職員でダッシュボードの表示確認
5. デプロイ可能な MVP

### Full Implementation

1. Setup + Foundational → 基盤完了
2. User Story 1 & 2 → ダッシュボードのメニュー表示制御
3. User Story 3 → URL 直接アクセス制御
4. Polish → テスト実行、ビルド確認

### Parallel Team Strategy

2人の開発者がいる場合:
1. チームで Setup + Foundational を完了
2. Developer A: User Story 1 & 2 (フロントエンド)
3. Developer B: User Story 3 (バックエンド → フロントエンド)
4. 統合テスト

---

## Notes

- [P] タスク = 異なるファイル、依存関係なし
- [Story] ラベル = 追跡可能性のためのユーザーストーリーマッピング
- US1 と US2 は同じコンポーネント（AdminMenuSection）で実現されるため Phase 3 で統合
- 各チェックポイントで独立したテストが可能
- 論理的なグループごとにコミット
