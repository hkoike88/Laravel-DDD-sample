/**
 * ログイン画面 E2E テスト
 *
 * ログイン機能の統合テスト。
 * バックエンド API をモックして各シナリオをテスト。
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
    loginSuccess?: boolean
    errorStatus?: number
    errorMessage?: string
    isAuthenticated?: boolean
  } = {}
) {
  const { loginSuccess = true, errorStatus, errorMessage, isAuthenticated = false } = options

  // CSRF トークン取得
  await page.route('**/sanctum/csrf-cookie', async (route: Route) => {
    await route.fulfill({
      status: 204,
      headers: {
        'Set-Cookie': 'XSRF-TOKEN=test-token; Path=/',
      },
    })
  })

  // ログイン API
  await page.route('**/api/auth/login', async (route: Route) => {
    if (loginSuccess) {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({ data: mockStaff }),
      })
    } else {
      await route.fulfill({
        status: errorStatus || 401,
        contentType: 'application/json',
        body: JSON.stringify({
          message: errorMessage || 'メールアドレスまたはパスワードが正しくありません',
        }),
      })
    }
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
}

test.describe('ログイン画面', () => {
  test.beforeEach(async ({ page }) => {
    await setupAuthMock(page)
  })

  /**
   * 基本表示テスト
   */
  test.describe('基本表示', () => {
    test('ログイン画面が表示される', async ({ page }) => {
      await page.goto('/login')

      await expect(page.getByRole('heading', { name: 'ログイン' })).toBeVisible()
      await expect(page.getByLabel('メールアドレス')).toBeVisible()
      await expect(page.getByLabel('パスワード')).toBeVisible()
      await expect(page.getByRole('button', { name: 'ログイン' })).toBeVisible()
    })

    test('パスワードがマスク表示される', async ({ page }) => {
      await page.goto('/login')

      const passwordInput = page.getByLabel('パスワード')
      await expect(passwordInput).toHaveAttribute('type', 'password')
    })
  })

  /**
   * ログイン成功テスト
   */
  test.describe('ログイン成功', () => {
    test('有効な認証情報でログインするとダッシュボードへ遷移する', async ({ page }) => {
      await page.goto('/login')

      await page.getByLabel('メールアドレス').fill('staff@example.com')
      await page.getByLabel('パスワード').fill('password123')
      await page.getByRole('button', { name: 'ログイン' }).click()

      await expect(page).toHaveURL('/dashboard')
      await expect(page.getByRole('heading', { name: 'ダッシュボード' })).toBeVisible()
    })

    test('Enter キーでログインできる', async ({ page }) => {
      await page.goto('/login')

      await page.getByLabel('メールアドレス').fill('staff@example.com')
      await page.getByLabel('パスワード').fill('password123')
      await page.getByLabel('パスワード').press('Enter')

      await expect(page).toHaveURL('/dashboard')
    })
  })

  /**
   * ログイン失敗テスト
   */
  test.describe('ログイン失敗', () => {
    test('無効な認証情報でエラーメッセージが表示される', async ({ page }) => {
      await setupAuthMock(page, {
        loginSuccess: false,
        errorStatus: 401,
        errorMessage: 'メールアドレスまたはパスワードが正しくありません',
      })

      await page.goto('/login')

      await page.getByLabel('メールアドレス').fill('wrong@example.com')
      await page.getByLabel('パスワード').fill('wrongpassword')
      await page.getByRole('button', { name: 'ログイン' }).click()

      await expect(
        page.getByText('メールアドレスまたはパスワードが正しくありません')
      ).toBeVisible()
      await expect(page).toHaveURL('/login')
    })

    test('アカウントロック時にエラーメッセージが表示される', async ({ page }) => {
      await setupAuthMock(page, {
        loginSuccess: false,
        errorStatus: 423,
        errorMessage: 'アカウントがロックされています',
      })

      await page.goto('/login')

      await page.getByLabel('メールアドレス').fill('locked@example.com')
      await page.getByLabel('パスワード').fill('password123')
      await page.getByRole('button', { name: 'ログイン' }).click()

      await expect(page.getByText('アカウントがロックされています')).toBeVisible()
    })

    test('レート制限時にエラーメッセージが表示される', async ({ page }) => {
      await setupAuthMock(page, {
        loginSuccess: false,
        errorStatus: 429,
        errorMessage: 'ログイン試行回数が上限に達しました。しばらくしてから再試行してください',
      })

      await page.goto('/login')

      await page.getByLabel('メールアドレス').fill('staff@example.com')
      await page.getByLabel('パスワード').fill('password123')
      await page.getByRole('button', { name: 'ログイン' }).click()

      await expect(
        page.getByText('ログイン試行回数が上限に達しました。しばらくしてから再試行してください')
      ).toBeVisible()
    })
  })

  /**
   * バリデーションテスト
   */
  test.describe('バリデーション', () => {
    test('空欄で送信するとエラーが表示される', async ({ page }) => {
      await page.goto('/login')

      await page.getByRole('button', { name: 'ログイン' }).click()

      await expect(page.getByText('メールアドレスを入力してください')).toBeVisible()
      await expect(page.getByText('パスワードを入力してください')).toBeVisible()
    })

    test('無効なメール形式でエラーが表示される', async ({ page }) => {
      await page.goto('/login')

      await page.getByLabel('メールアドレス').fill('invalid-email')
      await page.getByLabel('パスワード').fill('password123')
      await page.getByRole('button', { name: 'ログイン' }).click()

      await expect(page.getByText('有効なメールアドレスを入力してください')).toBeVisible()
    })

    test('8文字未満のパスワードでエラーが表示される', async ({ page }) => {
      await page.goto('/login')

      await page.getByLabel('メールアドレス').fill('staff@example.com')
      await page.getByLabel('パスワード').fill('short')
      await page.getByRole('button', { name: 'ログイン' }).click()

      await expect(page.getByText('パスワードは8文字以上で入力してください')).toBeVisible()
    })
  })

  /**
   * アクセシビリティテスト
   */
  test.describe('アクセシビリティ', () => {
    test('Tab キーでフォーカスが正しく移動する', async ({ page }) => {
      await page.goto('/login')

      await page.keyboard.press('Tab')
      await expect(page.getByLabel('メールアドレス')).toBeFocused()

      await page.keyboard.press('Tab')
      await expect(page.getByLabel('パスワード')).toBeFocused()

      await page.keyboard.press('Tab')
      await expect(page.getByRole('button', { name: 'ログイン' })).toBeFocused()
    })
  })

  /**
   * ローディング状態テスト
   */
  test.describe('ローディング状態', () => {
    test('送信中はボタンが無効化される', async ({ page }) => {
      // 遅延レスポンスを設定
      await page.route('**/api/auth/login', async (route: Route) => {
        await new Promise((resolve) => setTimeout(resolve, 1000))
        await route.fulfill({
          status: 200,
          contentType: 'application/json',
          body: JSON.stringify({ data: mockStaff }),
        })
      })

      await page.goto('/login')

      await page.getByLabel('メールアドレス').fill('staff@example.com')
      await page.getByLabel('パスワード').fill('password123')
      await page.getByRole('button', { name: 'ログイン' }).click()

      // ボタンが「ログイン中...」に変わることを確認
      await expect(page.getByRole('button', { name: 'ログイン中...' })).toBeVisible()
      await expect(page.getByRole('button', { name: 'ログイン中...' })).toBeDisabled()
    })
  })
})

test.describe('認証済みリダイレクト', () => {
  test('認証済みユーザーがログイン画面にアクセスするとダッシュボードへリダイレクトされる', async ({
    page,
  }) => {
    await setupAuthMock(page, { isAuthenticated: true })

    await page.goto('/login')

    await expect(page).toHaveURL('/dashboard')
  })

  test('未認証ユーザーがダッシュボードにアクセスするとログイン画面へリダイレクトされる', async ({
    page,
  }) => {
    await setupAuthMock(page, { isAuthenticated: false })

    await page.goto('/dashboard')

    await expect(page).toHaveURL('/login')
  })
})
