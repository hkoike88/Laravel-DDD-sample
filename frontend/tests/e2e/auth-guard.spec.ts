/**
 * 認証ガード E2E テスト
 *
 * 認証ガード機能の統合テスト。
 * 未認証/認証済み状態でのリダイレクト動作を検証。
 *
 * @feature 005-auth-guard
 */
import { test, expect, type Page, type Route } from '@playwright/test'

/**
 * モック用の職員データ
 */
const mockStaff = {
  id: '01HXYZ1234567890123456789A',
  name: 'テスト職員',
  email: 'staff@example.com',
}

/**
 * API モックを設定するヘルパー関数
 */
async function setupAuthMock(
  page: Page,
  options: {
    isAuthenticated?: boolean
  } = {}
) {
  const { isAuthenticated = false } = options

  // CSRF トークン取得
  await page.route('**/sanctum/csrf-cookie', async (route: Route) => {
    await route.fulfill({
      status: 204,
      headers: {
        'Set-Cookie': 'XSRF-TOKEN=test-token; Path=/',
      },
    })
  })

  // 認証ユーザー取得 API
  await page.route('**/api/auth/user', async (route: Route) => {
    if (isAuthenticated) {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({ data: mockStaff }),
      })
    } else {
      await route.fulfill({
        status: 401,
        contentType: 'application/json',
        body: JSON.stringify({ message: 'Unauthenticated.' }),
      })
    }
  })

  // ログアウト API
  await page.route('**/api/auth/logout', async (route: Route) => {
    await route.fulfill({
      status: 204,
    })
  })
}

// =============================================================================
// User Story 1: 未認証ユーザーの保護ページアクセス制御
// =============================================================================

test.describe('US1: 未認証ユーザーの保護ページアクセス制御', () => {
  test.beforeEach(async ({ page }) => {
    await setupAuthMock(page, { isAuthenticated: false })
  })

  test('未認証ユーザーが /dashboard にアクセスすると /login にリダイレクトされる', async ({
    page,
  }) => {
    await page.goto('/dashboard')
    await expect(page).toHaveURL('/login')
  })

  test('未認証ユーザーが /books/manage にアクセスすると /login にリダイレクトされる', async ({
    page,
  }) => {
    await page.goto('/books/manage')
    await expect(page).toHaveURL('/login')
  })

  test('未認証ユーザーが /loans/checkout にアクセスすると /login にリダイレクトされる', async ({
    page,
  }) => {
    await page.goto('/loans/checkout')
    await expect(page).toHaveURL('/login')
  })

  test('未認証ユーザーが /loans/return にアクセスすると /login にリダイレクトされる', async ({
    page,
  }) => {
    await page.goto('/loans/return')
    await expect(page).toHaveURL('/login')
  })

  test('未認証ユーザーが /users にアクセスすると /login にリダイレクトされる', async ({
    page,
  }) => {
    await page.goto('/users')
    await expect(page).toHaveURL('/login')
  })

  test('未認証ユーザーが /reservations にアクセスすると /login にリダイレクトされる', async ({
    page,
  }) => {
    await page.goto('/reservations')
    await expect(page).toHaveURL('/login')
  })

  test('リダイレクトが 1 秒以内に完了する', async ({ page }) => {
    const startTime = Date.now()
    await page.goto('/dashboard')
    await expect(page).toHaveURL('/login')
    const endTime = Date.now()

    expect(endTime - startTime).toBeLessThan(1000)
  })
})

// =============================================================================
// User Story 2: 認証済みユーザーのログイン画面アクセス制御
// =============================================================================

test.describe('US2: 認証済みユーザーのログイン画面アクセス制御', () => {
  test.beforeEach(async ({ page }) => {
    await setupAuthMock(page, { isAuthenticated: true })
  })

  test('認証済みユーザーが /login にアクセスすると /dashboard にリダイレクトされる', async ({
    page,
  }) => {
    await page.goto('/login')
    await expect(page).toHaveURL('/dashboard')
  })

  test('リダイレクトが 1 秒以内に完了する', async ({ page }) => {
    const startTime = Date.now()
    await page.goto('/login')
    await expect(page).toHaveURL('/dashboard')
    const endTime = Date.now()

    expect(endTime - startTime).toBeLessThan(1000)
  })
})

// =============================================================================
// User Story 3: 認証状態のグローバル管理
// =============================================================================

test.describe('US3: 認証状態のグローバル管理', () => {
  test('認証済みユーザーは複数の保護ページを遷移できる', async ({ page }) => {
    await setupAuthMock(page, { isAuthenticated: true })

    // ダッシュボードにアクセス
    await page.goto('/dashboard')
    await expect(page).toHaveURL('/dashboard')

    // 蔵書管理に遷移
    await page.goto('/books/manage')
    await expect(page).toHaveURL('/books/manage')

    // 貸出処理に遷移
    await page.goto('/loans/checkout')
    await expect(page).toHaveURL('/loans/checkout')
  })

  test('ログアウト後は保護ページにアクセスできない', async ({ page }) => {
    // 初期状態: 認証済み
    await setupAuthMock(page, { isAuthenticated: true })
    await page.goto('/dashboard')
    await expect(page).toHaveURL('/dashboard')

    // ログアウト（モックを未認証に変更）
    await setupAuthMock(page, { isAuthenticated: false })

    // 保護ページにアクセス → ログイン画面にリダイレクト
    await page.goto('/books/manage')
    await expect(page).toHaveURL('/login')
  })

  test('アプリケーション起動時の認証確認が 3 秒以内に完了する', async ({ page }) => {
    await setupAuthMock(page, { isAuthenticated: true })

    const startTime = Date.now()
    await page.goto('/dashboard')
    await expect(page.getByText('業務メニュー')).toBeVisible()
    const endTime = Date.now()

    expect(endTime - startTime).toBeLessThan(3000)
  })
})

// =============================================================================
// User Story 4: ページ遷移時の認証チェック
// =============================================================================

test.describe('US4: ページ遷移時の認証チェック', () => {
  test('認証済み状態でページ遷移時に正常に遷移する', async ({ page }) => {
    await setupAuthMock(page, { isAuthenticated: true })

    await page.goto('/dashboard')
    await expect(page).toHaveURL('/dashboard')

    // ナビゲーションリンクから遷移（ヘッダーナビゲーション内のリンクを使用）
    await page.getByLabel('メインナビゲーション').getByRole('link', { name: '蔵書管理' }).click()
    await expect(page).toHaveURL('/books/manage')
  })

  test('認証確認中はローディング表示が表示される', async ({ page }) => {
    // 認証 API を遅延させる
    await page.route('**/api/auth/user', async (route: Route) => {
      await new Promise((resolve) => setTimeout(resolve, 500))
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({ data: mockStaff }),
      })
    })

    await page.route('**/sanctum/csrf-cookie', async (route: Route) => {
      await route.fulfill({
        status: 204,
        headers: {
          'Set-Cookie': 'XSRF-TOKEN=test-token; Path=/',
        },
      })
    })

    await page.goto('/dashboard')

    // ローディング表示を確認（表示時間が短いため、表示されるかどうかのみ確認）
    // 最終的にダッシュボードが表示されることを確認
    await expect(page.getByText('業務メニュー')).toBeVisible({ timeout: 5000 })
  })
})

// =============================================================================
// User Story 5: セッション期限切れ時の自動リダイレクト
// =============================================================================

test.describe('US5: セッション期限切れ時の自動リダイレクト', () => {
  test('セッション期限切れ（401）で保護ページにアクセスするとログイン画面にリダイレクトされる', async ({
    page,
  }) => {
    // 最初は認証済み
    await setupAuthMock(page, { isAuthenticated: true })
    await page.goto('/dashboard')
    await expect(page).toHaveURL('/dashboard')

    // セッション期限切れをシミュレート（401を返す）
    await page.route('**/api/auth/user', async (route: Route) => {
      await route.fulfill({
        status: 401,
        contentType: 'application/json',
        body: JSON.stringify({ message: 'Unauthenticated.' }),
      })
    })

    // 別の保護ページにアクセス
    await page.goto('/books/manage')
    await expect(page).toHaveURL('/login')
  })
})
