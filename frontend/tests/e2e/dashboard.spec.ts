/**
 * ダッシュボード画面 E2E テスト
 *
 * ダッシュボード機能の統合テスト。
 * バックエンド API をモックして各シナリオをテスト。
 *
 * @feature 004-dashboard-ui
 */
import { test, expect, type Page, type Route } from '@playwright/test'

/**
 * モック用の職員データ
 */
const mockStaff = {
  id: '01HXYZ1234567890123456789A',
  name: '山田太郎',
  email: 'staff@example.com',
}

/**
 * API モックを設定するヘルパー関数
 */
async function setupAuthMock(
  page: Page,
  options: {
    isAuthenticated?: boolean
    logoutSuccess?: boolean
  } = {}
) {
  const { isAuthenticated = true, logoutSuccess = true } = options

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
    if (logoutSuccess) {
      await route.fulfill({
        status: 204,
      })
    } else {
      await route.fulfill({
        status: 500,
        contentType: 'application/json',
        body: JSON.stringify({ message: 'Server Error' }),
      })
    }
  })
}

test.describe('ダッシュボード画面', () => {
  test.beforeEach(async ({ page }) => {
    await setupAuthMock(page, { isAuthenticated: true })
  })

  /**
   * 基本表示テスト
   */
  test.describe('基本表示', () => {
    test('ダッシュボード画面が表示される', async ({ page }) => {
      await page.goto('/dashboard')

      await expect(page.getByText('業務メニュー')).toBeVisible()
      await expect(page.getByText('操作を選択してください')).toBeVisible()
    })

    test('ウェルカムメッセージに職員名が表示される', async ({ page }) => {
      await page.goto('/dashboard')

      await expect(page.getByText('ようこそ、')).toBeVisible()
      await expect(page.getByText('山田太郎')).toBeVisible()
    })

    test('ヘッダーが表示される', async ({ page }) => {
      await page.goto('/dashboard')

      await expect(page.getByRole('banner')).toBeVisible()
      await expect(page.getByText('青空市立図書館')).toBeVisible()
    })

    test('フッターが表示される', async ({ page }) => {
      await page.goto('/dashboard')

      await expect(page.getByRole('contentinfo')).toBeVisible()
    })

    test('5つの業務メニューカードが表示される', async ({ page }) => {
      await page.goto('/dashboard')

      await expect(page.getByText('蔵書管理')).toBeVisible()
      await expect(page.getByText('貸出処理')).toBeVisible()
      await expect(page.getByText('返却処理')).toBeVisible()
      await expect(page.getByText('利用者管理')).toBeVisible()
      await expect(page.getByText('予約管理')).toBeVisible()
    })
  })

  /**
   * メニュー遷移テスト
   */
  test.describe('メニュー遷移', () => {
    test('「蔵書管理」をクリックすると蔵書管理ページに遷移する', async ({ page }) => {
      await page.goto('/dashboard')

      await page.getByRole('link', { name: '蔵書管理' }).click()

      await expect(page).toHaveURL('/books/manage')
      await expect(page.getByRole('heading', { name: '蔵書管理' })).toBeVisible()
    })

    test('「貸出処理」をクリックすると貸出処理ページに遷移する', async ({ page }) => {
      await page.goto('/dashboard')

      await page.getByRole('link', { name: '貸出処理' }).click()

      await expect(page).toHaveURL('/loans/checkout')
      await expect(page.getByRole('heading', { name: '貸出処理' })).toBeVisible()
    })

    test('「返却処理」をクリックすると返却処理ページに遷移する', async ({ page }) => {
      await page.goto('/dashboard')

      await page.getByRole('link', { name: '返却処理' }).click()

      await expect(page).toHaveURL('/loans/return')
      await expect(page.getByRole('heading', { name: '返却処理' })).toBeVisible()
    })

    test('「利用者管理」をクリックすると利用者管理ページに遷移する', async ({ page }) => {
      await page.goto('/dashboard')

      await page.getByRole('link', { name: '利用者管理' }).click()

      await expect(page).toHaveURL('/users')
      await expect(page.getByRole('heading', { name: '利用者管理' })).toBeVisible()
    })

    test('「予約管理」をクリックすると予約管理ページに遷移する', async ({ page }) => {
      await page.goto('/dashboard')

      await page.getByRole('link', { name: '予約管理' }).click()

      await expect(page).toHaveURL('/reservations')
      await expect(page.getByRole('heading', { name: '予約管理' })).toBeVisible()
    })
  })

  /**
   * ヘッダーナビゲーションテスト
   */
  test.describe('ヘッダーナビゲーション', () => {
    test('ロゴをクリックするとダッシュボードに戻る', async ({ page }) => {
      await page.goto('/books/manage')

      await page.getByRole('link', { name: '青空市立図書館' }).click()

      await expect(page).toHaveURL('/dashboard')
    })

    test('ヘッダーにナビゲーションリンクが表示される', async ({ page }) => {
      await page.goto('/dashboard')

      // デスクトップビューでナビゲーションを確認
      await expect(page.getByRole('navigation', { name: 'メインナビゲーション' })).toBeVisible()
    })
  })

  /**
   * ログアウトテスト
   */
  test.describe('ログアウト', () => {
    test('ログアウトするとログイン画面に遷移する', async ({ page }) => {
      await page.goto('/dashboard')

      // ユーザーメニューを開く
      await page.getByRole('button', { name: /山田太郎/ }).click()

      // ログアウトをクリック
      await page.getByRole('menuitem', { name: 'ログアウト' }).click()

      await expect(page).toHaveURL('/login')
    })

    test('ログアウト失敗時もログイン画面に遷移する', async ({ page }) => {
      await setupAuthMock(page, { isAuthenticated: true, logoutSuccess: false })

      await page.goto('/dashboard')

      // ユーザーメニューを開く
      await page.getByRole('button', { name: /山田太郎/ }).click()

      // ログアウトをクリック
      await page.getByRole('menuitem', { name: 'ログアウト' }).click()

      // API エラーでもローカル状態クリアでログイン画面に遷移
      await expect(page).toHaveURL('/login')
    })
  })

  /**
   * 認証状態テスト
   */
  test.describe('認証状態', () => {
    test('未認証ユーザーがダッシュボードにアクセスするとログイン画面へリダイレクトされる', async ({
      page,
    }) => {
      await setupAuthMock(page, { isAuthenticated: false })

      await page.goto('/dashboard')

      await expect(page).toHaveURL('/login')
    })

    test('ログアウト後にダッシュボードにアクセスするとログイン画面へリダイレクトされる', async ({
      page,
    }) => {
      await page.goto('/dashboard')

      // ログアウト
      await page.getByRole('button', { name: /山田太郎/ }).click()
      await page.getByRole('menuitem', { name: 'ログアウト' }).click()

      await expect(page).toHaveURL('/login')

      // 認証状態を未認証に変更
      await setupAuthMock(page, { isAuthenticated: false })

      // ダッシュボードに直接アクセス
      await page.goto('/dashboard')

      await expect(page).toHaveURL('/login')
    })
  })

  /**
   * レスポンシブデザインテスト
   */
  test.describe('レスポンシブデザイン', () => {
    test('モバイルサイズでハンバーガーメニューが表示される', async ({ page }) => {
      await page.setViewportSize({ width: 375, height: 667 })
      await page.goto('/dashboard')

      // ハンバーガーメニューボタンが表示される
      await expect(page.getByRole('button', { name: 'メニューを開く' })).toBeVisible()
    })

    test('モバイルでハンバーガーメニューを開くとナビゲーションが表示される', async ({ page }) => {
      await page.setViewportSize({ width: 375, height: 667 })
      await page.goto('/dashboard')

      // ハンバーガーメニューをクリック
      await page.getByRole('button', { name: 'メニューを開く' }).click()

      // モバイルナビゲーションが表示される
      await expect(
        page.getByRole('navigation', { name: 'モバイルナビゲーション' })
      ).toBeVisible()
    })
  })
})
