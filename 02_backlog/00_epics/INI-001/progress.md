# INI-001: 認証・利用者管理基盤 進捗状況

最終更新: 2026-01-06

---

## 全体サマリー

| 指標 | 値 |
|------|-----|
| エピック数 | 11 |
| 完了エピック数 | 3 |
| 進行中エピック数 | 1 |
| 未着手エピック数 | 7 |
| 全体進捗率 | 約30% |

---

## エピック別進捗

| Epic ID | Epic 名 | ステータス | ストーリー数 | 完了 | 進行中 | 未着手 | 進捗率 |
|---------|---------|:----------:|:------------:|:----:|:------:|:------:|:------:|
| [EPIC-001](./EPIC-001/epic.md) | 職員ログイン機能 | **Done** | 5 | 5 | 0 | 0 | 100% |
| [EPIC-002](./EPIC-002/epic.md) | 職員ダッシュボード機能 | **Done** | 3 | 3 | 0 | 0 | 100% |
| [EPIC-003](./EPIC-003/epic.md) | 職員アカウント作成機能 | Planned | 4 | 0 | 0 | 4 | 0% |
| [EPIC-004](./EPIC-004/epic.md) | 職員アカウント編集機能 | Planned | 3 | 0 | 0 | 3 | 0% |
| [EPIC-005](./EPIC-005/epic.md) | 職員アカウント無効化機能 | Planned | 3 | 0 | 0 | 3 | 0% |
| [EPIC-006](./EPIC-006/epic.md) | 職員ログアウト機能 | **Done** | 3 | 3 | 0 | 0 | 100% |
| [EPIC-007](./EPIC-007/epic.md) | セキュリティ対策準備 | In Progress | 6 | 2 | 0 | 4 | 33% |
| [EPIC-008](./EPIC-008/epic.md) | 利用者アカウント登録機能 | Planned | 4 | 0 | 0 | 4 | 0% |
| [EPIC-009](./EPIC-009/epic.md) | 利用者アカウント編集機能 | Planned | 4 | 0 | 0 | 4 | 0% |
| [EPIC-010](./EPIC-010/epic.md) | 利用者アカウント無効化機能 | Planned | 3 | 0 | 0 | 3 | 0% |
| [EPIC-011](./EPIC-011/epic.md) | 利用者検索機能 | Planned | 3 | 0 | 0 | 3 | 0% |

---

## ストーリー詳細

### EPIC-001: 職員ログイン機能 (Done)

| Story ID | Story 名 | ポイント | 優先度 | ステータス |
|----------|----------|:-------:|:------:|:----------:|
| [ST-001](./EPIC-001/stories/ST-001_職員認証API実装.md) | 職員認証 API の実装 | 3 | Must | **Done** |
| [ST-002](./EPIC-001/stories/ST-002_ログインUI実装.md) | ログイン UI の実装 | 3 | Must | **Done** |
| [ST-003](./EPIC-001/stories/ST-003_認証ガード実装.md) | 認証ガードの実装 | 2 | Must | **Done** |
| [ST-004](./EPIC-001/stories/ST-004_アカウントロック実装.md) | アカウントロック機能の実装 | 2 | Must | **Done** |
| [ST-005](./EPIC-001/stories/ST-005_セッション管理実装.md) | セッション管理の実装 | 2 | Must | **Done** |

**合計ポイント: 12** | **完了ポイント: 12**

#### 実装済み成果物
- `backend/app/Http/Controllers/Auth/AuthController.php` - 認証コントローラー
- `backend/packages/Domain/Staff/Application/UseCases/Auth/LoginUseCase.php` - ログインユースケース
- `backend/packages/Domain/Staff/Domain/Exceptions/AccountLockedException.php` - アカウントロック例外
- `backend/app/Http/Middleware/AbsoluteSessionTimeout.php` - 絶対タイムアウト
- `backend/app/Http/Middleware/ConcurrentSessionLimit.php` - 同時ログイン制限
- `frontend/src/features/auth/components/LoginForm.tsx` - ログインフォーム
- `frontend/src/features/auth/components/ProtectedRoute.tsx` - 認証ガード
- `frontend/src/features/auth/components/GuestRoute.tsx` - ゲストルート
- `frontend/src/features/auth/components/AuthProvider.tsx` - 認証プロバイダー
- `frontend/src/features/auth/stores/authStore.ts` - 認証ストア
- `backend/tests/Feature/Auth/LoginTest.php` 他多数のテスト

---

### EPIC-002: 職員ダッシュボード機能 (Done)

| Story ID | Story 名 | ポイント | 優先度 | ステータス |
|----------|----------|:-------:|:------:|:----------:|
| [ST-001](./EPIC-002/stories/ST-001_ダッシュボードAPI実装.md) | ダッシュボード API の実装 | 2 | Must | **Done** |
| [ST-002](./EPIC-002/stories/ST-002_ダッシュボードUI実装.md) | ダッシュボード UI の実装 | 3 | Must | **Done** |
| [ST-003](./EPIC-002/stories/ST-003_権限別メニュー表示実装.md) | 権限別メニュー表示の実装 | 2 | Must | **Done** |

**合計ポイント: 7** | **完了ポイント: 7**

#### 実装済み成果物
- `frontend/src/features/dashboard/pages/DashboardPage.tsx` - ダッシュボードページ
- `frontend/src/features/dashboard/components/MenuGrid.tsx` - メニューグリッド
- `frontend/src/features/dashboard/components/MenuCard.tsx` - メニューカード
- `frontend/src/features/dashboard/components/WelcomeMessage.tsx` - ウェルカムメッセージ
- `frontend/src/features/dashboard/components/AdminMenuSection.tsx` - 管理者メニューセクション
- `frontend/src/features/dashboard/constants/menuItems.tsx` - 業務メニュー定義
- `frontend/src/features/dashboard/constants/adminMenuItems.tsx` - 管理者メニュー定義
- `backend/app/Http/Middleware/RequireAdmin.php` - 管理者権限ミドルウェア

---

### EPIC-003: 職員アカウント作成機能 (Planned)

| Story ID | Story 名 | ポイント | 優先度 | ステータス |
|----------|----------|:-------:|:------:|:----------:|
| [ST-001](./EPIC-003/stories/ST-001_職員アカウント作成API実装.md) | 職員アカウント作成 API の実装 | 3 | Must | Planned |
| [ST-002](./EPIC-003/stories/ST-002_職員アカウント作成UI実装.md) | 職員アカウント作成 UI の実装 | 3 | Must | Planned |
| [ST-003](./EPIC-003/stories/ST-003_職員一覧API実装.md) | 職員一覧 API の実装 | 2 | Must | Planned |
| [ST-004](./EPIC-003/stories/ST-004_職員一覧UI実装.md) | 職員一覧 UI の実装 | 2 | Must | Planned |

**合計ポイント: 10** | **完了ポイント: 0**

---

### EPIC-004: 職員アカウント編集機能 (Planned)

| Story ID | Story 名 | ポイント | 優先度 | ステータス |
|----------|----------|:-------:|:------:|:----------:|
| [ST-001](./EPIC-004/stories/ST-001_職員アカウント編集API実装.md) | 職員アカウント編集 API の実装 | 3 | Must | Planned |
| [ST-002](./EPIC-004/stories/ST-002_職員アカウント編集UI実装.md) | 職員アカウント編集 UI の実装 | 3 | Must | Planned |
| [ST-003](./EPIC-004/stories/ST-003_パスワードリセット機能実装.md) | パスワードリセット機能の実装 | 2 | Must | Planned |

**合計ポイント: 8** | **完了ポイント: 0**

---

### EPIC-005: 職員アカウント無効化機能 (Planned)

| Story ID | Story 名 | ポイント | 優先度 | ステータス |
|----------|----------|:-------:|:------:|:----------:|
| [ST-001](./EPIC-005/stories/ST-001_職員アカウント無効化API実装.md) | 職員アカウント無効化 API の実装 | 3 | Must | Planned |
| [ST-002](./EPIC-005/stories/ST-002_職員アカウント無効化UI実装.md) | 職員アカウント無効化 UI の実装 | 2 | Must | Planned |
| [ST-003](./EPIC-005/stories/ST-003_職員アカウント再有効化機能実装.md) | 職員アカウント再有効化機能の実装 | 2 | Must | Planned |

**合計ポイント: 7** | **完了ポイント: 0**

---

### EPIC-006: 職員ログアウト機能 (Done)

| Story ID | Story 名 | ポイント | 優先度 | ステータス |
|----------|----------|:-------:|:------:|:----------:|
| [ST-001](./EPIC-006/stories/ST-001_ログアウトAPI実装.md) | ログアウト API の実装 | 1 | Must | **Done** |
| [ST-002](./EPIC-006/stories/ST-002_ログアウトUI実装.md) | ログアウト UI の実装 | 2 | Must | **Done** |
| [ST-003](./EPIC-006/stories/ST-003_ログアウト完了通知実装.md) | ログアウト完了通知の実装 | 1 | Should | **Done** |

**合計ポイント: 4** | **完了ポイント: 4**

#### 実装済み成果物
- `backend/app/Http/Controllers/Auth/AuthController.php` (logout メソッド)
- `frontend/src/features/auth/hooks/useLogout.ts` - ログアウトフック
- `backend/tests/Feature/Auth/LogoutTest.php` - ログアウトテスト

---

### EPIC-007: セキュリティ対策準備 (In Progress)

| Story ID | Story 名 | ポイント | 優先度 | ステータス |
|----------|----------|:-------:|:------:|:----------:|
| [ST-001](./EPIC-007/stories/ST-001_パスワードポリシー実装.md) | パスワードポリシーの実装 | 3 | Must | Planned |
| [ST-002](./EPIC-007/stories/ST-002_セッション管理設定.md) | セッション管理の設定 | 3 | Must | **Done** |
| [ST-003](./EPIC-007/stories/ST-003_暗号化設定確認.md) | 暗号化設定の確認・調整 | 2 | Must | **Done** |
| [ST-004](./EPIC-007/stories/ST-004_セキュリティスキャン設定.md) | セキュリティスキャンの設定 | 3 | Must | Planned |
| [ST-005](./EPIC-007/stories/ST-005_依存関係脆弱性対応.md) | 依存関係の脆弱性対応 | 2 | Should | Planned |
| [ST-006](./EPIC-007/stories/ST-006_セキュリティログ設定.md) | セキュリティログの設定 | 2 | Should | Planned |

**合計ポイント: 15** | **完了ポイント: 5**

#### 実装済み成果物
- `backend/config/session.php` - セッション設定（データベースセッション）
- `backend/app/Http/Middleware/AbsoluteSessionTimeout.php` - 8時間絶対タイムアウト
- `backend/app/Http/Middleware/ConcurrentSessionLimit.php` - 同時ログイン制限（職員3台、管理者1台）
- `backend/tests/Feature/Auth/SessionTimeoutTest.php` - セッションタイムアウトテスト
- `backend/tests/Feature/Auth/ConcurrentSessionTest.php` - 同時セッションテスト

---

### EPIC-008: 利用者アカウント登録機能 (Planned)

| Story ID | Story 名 | ポイント | 優先度 | ステータス |
|----------|----------|:-------:|:------:|:----------:|
| [ST-001](./EPIC-008/stories/ST-001_利用者アカウント登録API実装.md) | 利用者アカウント登録 API の実装 | 5 | Must | Planned |
| [ST-002](./EPIC-008/stories/ST-002_利用者アカウント登録UI実装.md) | 利用者アカウント登録 UI の実装 | 3 | Must | Planned |
| [ST-003](./EPIC-008/stories/ST-003_利用者番号自動採番実装.md) | 利用者番号自動採番機能の実装 | 2 | Must | Planned |
| [ST-004](./EPIC-008/stories/ST-004_貸出カード印刷機能実装.md) | 貸出カード印刷機能の実装 | 3 | Should | Planned |

**合計ポイント: 13** | **完了ポイント: 0**

---

### EPIC-009: 利用者アカウント編集機能 (Planned)

| Story ID | Story 名 | ポイント | 優先度 | ステータス |
|----------|----------|:-------:|:------:|:----------:|
| [ST-001](./EPIC-009/stories/ST-001_利用者アカウント編集API実装.md) | 利用者アカウント編集 API の実装 | 3 | Must | Planned |
| [ST-002](./EPIC-009/stories/ST-002_利用者アカウント編集UI実装.md) | 利用者アカウント編集 UI の実装 | 3 | Must | Planned |
| [ST-003](./EPIC-009/stories/ST-003_有効期限更新機能実装.md) | 有効期限更新機能の実装 | 2 | Must | Planned |
| [ST-004](./EPIC-009/stories/ST-004_変更履歴機能実装.md) | 変更履歴機能の実装 | 2 | Should | Planned |

**合計ポイント: 10** | **完了ポイント: 0**

---

### EPIC-010: 利用者アカウント無効化機能 (Planned)

| Story ID | Story 名 | ポイント | 優先度 | ステータス |
|----------|----------|:-------:|:------:|:----------:|
| [ST-001](./EPIC-010/stories/ST-001_利用者アカウント無効化API実装.md) | 利用者アカウント無効化 API の実装 | 3 | Must | Planned |
| [ST-002](./EPIC-010/stories/ST-002_利用者アカウント無効化UI実装.md) | 利用者アカウント無効化 UI の実装 | 2 | Must | Planned |
| [ST-003](./EPIC-010/stories/ST-003_利用者アカウント再有効化機能実装.md) | 利用者アカウント再有効化機能の実装 | 2 | Must | Planned |

**合計ポイント: 7** | **完了ポイント: 0**

---

### EPIC-011: 利用者検索機能 (Planned)

| Story ID | Story 名 | ポイント | 優先度 | ステータス |
|----------|----------|:-------:|:------:|:----------:|
| [ST-001](./EPIC-011/stories/ST-001_利用者検索API実装.md) | 利用者検索 API の実装 | 3 | Must | Planned |
| [ST-002](./EPIC-011/stories/ST-002_利用者検索UI実装.md) | 利用者検索 UI の実装 | 3 | Must | Planned |
| [ST-003](./EPIC-011/stories/ST-003_利用者一覧表示機能実装.md) | 利用者一覧表示機能の実装 | 2 | Must | Planned |

**合計ポイント: 8** | **完了ポイント: 0**

---

## ポイント集計

| 項目 | 値 |
|------|-----|
| 全ストーリー数 | 41 |
| 全ポイント合計 | 101 |
| 完了ポイント | 28 |
| 残ポイント | 73 |
| **ポイントベース進捗率** | **約28%** |

---

## 優先度別集計

| 優先度 | ストーリー数 | ポイント合計 | 完了ポイント | 進捗率 |
|:------:|:------------:|:------------:|:------------:|:------:|
| Must | 35 | 87 | 24 | 28% |
| Should | 6 | 14 | 4 | 29% |

---

## 依存関係

### 実装推奨順序

```
1. EPIC-001: 職員ログイン機能（認証基盤）         [DONE]
   └→ 2. EPIC-002: 職員ダッシュボード機能         [DONE]
      └→ 3. EPIC-006: 職員ログアウト機能          [DONE]
   └→ 4. EPIC-003: 職員アカウント作成機能         [TODO]
      └→ 5. EPIC-004: 職員アカウント編集機能      [TODO]
         └→ 6. EPIC-005: 職員アカウント無効化機能 [TODO]
   └→ 7. EPIC-008: 利用者アカウント登録機能       [TODO]
      └→ 8. EPIC-009: 利用者アカウント編集機能    [TODO]
      └→ 9. EPIC-011: 利用者検索機能              [TODO]
         └→ 10. EPIC-010: 利用者アカウント無効化  [TODO]

※ EPIC-007: セキュリティ対策準備                  [IN PROGRESS]
  - セッション管理設定: DONE
  - 暗号化設定: DONE
  - パスワードポリシー: TODO
  - セキュリティスキャン: TODO
```

---

## 次のアクション

### 優先度: 高
1. **EPIC-003: 職員アカウント作成機能** - 管理者向け機能の基盤
2. **EPIC-007: セキュリティ対策準備（残タスク）** - パスワードポリシー、セキュリティスキャン

### 優先度: 中
3. **EPIC-004: 職員アカウント編集機能**
4. **EPIC-005: 職員アカウント無効化機能**
5. **EPIC-008: 利用者アカウント登録機能**

### 優先度: 低
6. EPIC-009〜011: 利用者管理機能

---

## 変更履歴

| 日付 | 変更内容 |
|------|---------|
| 2026-01-06 | 初版作成 - コードベース確認により実装状況を反映 |
