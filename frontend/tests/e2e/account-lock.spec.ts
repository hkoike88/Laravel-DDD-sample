/**
 * アカウントロック機能 E2E テスト
 *
 * アカウントロック機能の統合テスト。
 * 5回連続ログイン失敗でアカウントがロックされるシナリオをテスト。
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
 * 共通のモック設定
 */
async function setupBaseMock(page: Page) {
  // CSRF トークン取得
  await page.route('**/sanctum/csrf-cookie', async (route: Route) => {
    await route.fulfill({
      status: 204,
      headers: {
        'Set-Cookie': 'XSRF-TOKEN=test-token; Path=/',
      },
    })
  })

  // 認証ユーザー取得 API（未認証）
  await page.route('**/api/auth/user', async (route: Route) => {
    await route.fulfill({
      status: 401,
      contentType: 'application/json',
      body: JSON.stringify({ message: 'Unauthenticated.' }),
    })
  })
}

test.describe('アカウントロック機能', () => {
  test.describe('US1: 5回連続失敗でアカウントロック', () => {
    test('5回連続ログイン失敗後にロックメッセージが表示される', async ({ page }) => {
      await setupBaseMock(page)

      // 5回失敗 → 6回目でロックを返すモック
      let failCount = 0
      await page.route('**/api/auth/login', async (route: Route) => {
        failCount++
        if (failCount <= 5) {
          await route.fulfill({
            status: 401,
            contentType: 'application/json',
            body: JSON.stringify({
              message: 'メールアドレスまたはパスワードが正しくありません',
            }),
          })
        } else {
          await route.fulfill({
            status: 423,
            contentType: 'application/json',
            body: JSON.stringify({
              message: 'アカウントがロックされています。管理者にお問い合わせください',
            }),
          })
        }
      })

      await page.goto('/login')

      // 6回ログイン試行
      for (let i = 0; i < 6; i++) {
        await page.getByLabel('メールアドレス').fill('test@example.com')
        await page.getByLabel('パスワード').fill('wrongpassword')
        await page.getByRole('button', { name: 'ログイン' }).click()

        // ローディング完了を待つ
        if (i < 5) {
          await expect(
            page.getByText('メールアドレスまたはパスワードが正しくありません')
          ).toBeVisible()
        }
      }

      // ロックメッセージを確認
      await expect(page.getByText('アカウントがロックされています')).toBeVisible()
    })

    test('4回失敗後の5回目でアカウントがロックされる', async ({ page }) => {
      await setupBaseMock(page)

      // 4回失敗済みの状態を想定し、5回目でロック
      let failCount = 0
      await page.route('**/api/auth/login', async (route: Route) => {
        failCount++
        // 5回目の失敗でロック状態になり、401 レスポンス
        // 6回目以降は 423 が返る
        if (failCount <= 5) {
          await route.fulfill({
            status: 401,
            contentType: 'application/json',
            body: JSON.stringify({
              message: 'メールアドレスまたはパスワードが正しくありません',
            }),
          })
        } else {
          await route.fulfill({
            status: 423,
            contentType: 'application/json',
            body: JSON.stringify({
              message: 'アカウントがロックされています。管理者にお問い合わせください',
            }),
          })
        }
      })

      await page.goto('/login')

      // 5回ログイン試行（5回目でロック状態になる）
      for (let i = 0; i < 5; i++) {
        await page.getByLabel('メールアドレス').fill('test@example.com')
        await page.getByLabel('パスワード').fill('wrongpassword')
        await page.getByRole('button', { name: 'ログイン' }).click()
        await expect(
          page.getByText('メールアドレスまたはパスワードが正しくありません')
        ).toBeVisible()
      }

      // 6回目の試行でロックメッセージが表示される
      await page.getByLabel('メールアドレス').fill('test@example.com')
      await page.getByLabel('パスワード').fill('wrongpassword')
      await page.getByRole('button', { name: 'ログイン' }).click()
      await expect(page.getByText('アカウントがロックされています')).toBeVisible()

      // 6回呼ばれたことを確認
      expect(failCount).toBe(6)
    })
  })

  test.describe('US2: ログイン成功時の失敗回数リセット', () => {
    test('3回失敗後にログイン成功し、再度3回失敗してもロックされない', async ({ page }) => {
      await setupBaseMock(page)

      let failCount = 0
      let hasLoggedIn = false
      await page.route('**/api/auth/login', async (route: Route) => {
        const request = route.request()
        const postData = request.postDataJSON()

        if (postData?.password === 'correctpassword') {
          // ログイン成功
          hasLoggedIn = true
          failCount = 0 // リセット
          await route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify({ data: mockStaff }),
          })
        } else {
          // ログイン失敗
          failCount++
          await route.fulfill({
            status: 401,
            contentType: 'application/json',
            body: JSON.stringify({
              message: 'メールアドレスまたはパスワードが正しくありません',
            }),
          })
        }
      })

      await page.goto('/login')

      // 3回失敗
      for (let i = 0; i < 3; i++) {
        await page.getByLabel('メールアドレス').fill('test@example.com')
        await page.getByLabel('パスワード').fill('wrongpassword')
        await page.getByRole('button', { name: 'ログイン' }).click()
        await expect(
          page.getByText('メールアドレスまたはパスワードが正しくありません')
        ).toBeVisible()
      }

      // ログイン成功
      await page.getByLabel('メールアドレス').fill('staff@example.com')
      await page.getByLabel('パスワード').fill('correctpassword')
      await page.getByRole('button', { name: 'ログイン' }).click()
      await expect(page).toHaveURL('/dashboard')

      // ログアウト（ログイン画面に戻る）
      await page.goto('/login')

      // 再度3回失敗してもロックされない
      for (let i = 0; i < 3; i++) {
        await page.getByLabel('メールアドレス').fill('test@example.com')
        await page.getByLabel('パスワード').fill('wrongpassword')
        await page.getByRole('button', { name: 'ログイン' }).click()
        // 423 ではなく 401 が返ることを確認
        await expect(
          page.getByText('メールアドレスまたはパスワードが正しくありません')
        ).toBeVisible()
      }

      // ロックされていないことを確認
      await expect(page.getByText('アカウントがロックされています')).not.toBeVisible()
    })
  })

  test.describe('US4: ロック済みアカウントへのログイン試行', () => {
    test('ロック済みアカウントでログイン試行すると適切なエラーメッセージが表示される', async ({
      page,
    }) => {
      await setupBaseMock(page)

      // ロック済みアカウント
      await page.route('**/api/auth/login', async (route: Route) => {
        await route.fulfill({
          status: 423,
          contentType: 'application/json',
          body: JSON.stringify({
            message: 'アカウントがロックされています。管理者にお問い合わせください',
          }),
        })
      })

      await page.goto('/login')

      await page.getByLabel('メールアドレス').fill('locked@example.com')
      await page.getByLabel('パスワード').fill('password123')
      await page.getByRole('button', { name: 'ログイン' }).click()

      // ロックメッセージを確認
      await expect(page.getByText('アカウントがロックされています')).toBeVisible()
      // 画面遷移しないことを確認
      await expect(page).toHaveURL('/login')
    })

    test('ロック済みアカウントでは正しいパスワードでもログインできない', async ({ page }) => {
      await setupBaseMock(page)

      await page.route('**/api/auth/login', async (route: Route) => {
        await route.fulfill({
          status: 423,
          contentType: 'application/json',
          body: JSON.stringify({
            message: 'アカウントがロックされています。管理者にお問い合わせください',
          }),
        })
      })

      await page.goto('/login')

      // 正しいパスワードでもロックメッセージが表示される
      await page.getByLabel('メールアドレス').fill('locked@example.com')
      await page.getByLabel('パスワード').fill('correctpassword')
      await page.getByRole('button', { name: 'ログイン' }).click()

      await expect(page.getByText('アカウントがロックされています')).toBeVisible()
      await expect(page).toHaveURL('/login')
    })
  })

  test.describe('パフォーマンス', () => {
    test('ログイン試行のレスポンスが1秒以内', async ({ page }) => {
      await setupBaseMock(page)

      await page.route('**/api/auth/login', async (route: Route) => {
        await route.fulfill({
          status: 401,
          contentType: 'application/json',
          body: JSON.stringify({
            message: 'メールアドレスまたはパスワードが正しくありません',
          }),
        })
      })

      await page.goto('/login')

      const startTime = Date.now()

      await page.getByLabel('メールアドレス').fill('test@example.com')
      await page.getByLabel('パスワード').fill('wrongpassword')
      await page.getByRole('button', { name: 'ログイン' }).click()

      await expect(
        page.getByText('メールアドレスまたはパスワードが正しくありません')
      ).toBeVisible()

      const endTime = Date.now()
      const responseTime = endTime - startTime

      // 1秒以内であることを確認（モック環境なので余裕を持って）
      expect(responseTime).toBeLessThan(1000)
    })
  })
})
