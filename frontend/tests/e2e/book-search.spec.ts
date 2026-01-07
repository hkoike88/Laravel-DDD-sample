/**
 * 蔵書検索画面 E2Eテスト
 * TC-N001〜N003, TC-N009, TC-N010, TC-N017, TC-EC015, TC-EC020, TC-EC021
 *
 * 注意: これらのテストはバックエンドAPIが起動している状態で実行する必要があります
 * または、Playwrightのルートモックを使用してAPIレスポンスをシミュレートします
 */
import { test, expect, type Page, type Route } from '@playwright/test'

/**
 * モック用の蔵書データ
 */
const mockBooks = [
  {
    id: '01HQXYZ123456789ABCDEFG',
    title: '吾輩は猫である',
    author: '夏目漱石',
    isbn: '9784003101018',
    publisher: '岩波書店',
    published_year: 1905,
    genre: '文学',
    status: 'available',
  },
  {
    id: '01HQXYZ123456789ABCDEFH',
    title: '坊っちゃん',
    author: '夏目漱石',
    isbn: '9784101010014',
    publisher: '新潮社',
    published_year: 1906,
    genre: '文学',
    status: 'borrowed',
  },
  {
    id: '01HQXYZ123456789ABCDEFI',
    title: '羅生門',
    author: '芥川龍之介',
    isbn: '9784003107010',
    publisher: '岩波書店',
    published_year: 1915,
    genre: '文学',
    status: 'reserved',
  },
  {
    id: '01HQXYZ123456789ABCDEFJ',
    title: '人間失格',
    author: '太宰治',
    isbn: '9784101006017',
    publisher: '新潮社',
    published_year: 1948,
    genre: '文学',
    status: 'available',
  },
  {
    id: '01HQXYZ123456789ABCDEFK',
    title: '雪国',
    author: '川端康成',
    isbn: '9784101001012',
    publisher: '新潮社',
    published_year: 1937,
    genre: '文学',
    status: 'available',
  },
]

/**
 * APIモックを設定するヘルパー関数
 */
async function setupApiMock(page: Page) {
  await page.route('**/api/books*', async (route: Route) => {
    const url = new URL(route.request().url())
    const title = url.searchParams.get('title')
    const author = url.searchParams.get('author')
    const isbn = url.searchParams.get('isbn')
    const pageNum = parseInt(url.searchParams.get('page') || '1', 10)
    const perPage = parseInt(url.searchParams.get('per_page') || '20', 10)

    let filteredBooks = [...mockBooks]

    if (title) {
      filteredBooks = filteredBooks.filter((book) =>
        book.title.toLowerCase().includes(title.toLowerCase())
      )
    }

    if (author) {
      filteredBooks = filteredBooks.filter((book) =>
        book.author.toLowerCase().includes(author.toLowerCase())
      )
    }

    if (isbn) {
      const normalizedIsbn = isbn.replace(/-/g, '')
      filteredBooks = filteredBooks.filter((book) => book.isbn === normalizedIsbn)
    }

    const total = filteredBooks.length
    const lastPage = Math.ceil(total / perPage) || 1
    const startIndex = (pageNum - 1) * perPage
    const paginatedBooks = filteredBooks.slice(startIndex, startIndex + perPage)

    await route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({
        data: paginatedBooks,
        meta: {
          total,
          page: pageNum,
          per_page: perPage,
          last_page: lastPage,
        },
      }),
    })
  })
}

test.describe('蔵書検索画面', () => {
  test.beforeEach(async ({ page }) => {
    await setupApiMock(page)
    await page.goto('/books')
    // ページの読み込みを待機
    await page.waitForLoadState('networkidle')
    // 蔵書検索のタイトルが表示されるまで待機
    await expect(page.getByRole('heading', { name: '蔵書検索' })).toBeVisible({ timeout: 10000 })
  })

  /**
   * TC-N001: タイトルでの部分一致検索
   */
  test('TC-N001: タイトルで部分一致検索ができる', async ({ page }) => {
    // タイトル入力
    await page.getByLabel('タイトル').fill('猫')

    // 検索実行
    await page.getByRole('button', { name: /検索/i }).click()

    // 結果確認
    await expect(page.getByText('吾輩は猫である')).toBeVisible()
    await expect(page.getByText('検索結果:')).toBeVisible()
    await expect(page.getByText('1', { exact: true }).first()).toBeVisible()
  })

  /**
   * TC-N002: 著者名での部分一致検索
   */
  test('TC-N002: 著者名で部分一致検索ができる', async ({ page }) => {
    // 著者入力
    await page.getByLabel('著者').fill('夏目')

    // 検索実行
    await page.getByRole('button', { name: /検索/i }).click()

    // 結果確認（夏目漱石の作品が2件）
    await expect(page.getByText('吾輩は猫である')).toBeVisible()
    await expect(page.getByText('坊っちゃん')).toBeVisible()
    await expect(page.getByText('2', { exact: true }).first()).toBeVisible()
  })

  /**
   * TC-N003: タイトルと著者の複合検索
   */
  test('TC-N003: タイトルと著者で複合検索ができる', async ({ page }) => {
    // タイトルと著者を入力
    await page.getByLabel('タイトル').fill('坊')
    await page.getByLabel('著者').fill('夏目')

    // 検索実行
    await page.getByRole('button', { name: /検索/i }).click()

    // 結果確認
    await expect(page.getByText('坊っちゃん')).toBeVisible()
    await expect(page.getByText('吾輩は猫である')).not.toBeVisible()
  })

  /**
   * TC-N009: ISBN-13検索
   */
  test('TC-N009: ISBN-13で検索ができる', async ({ page }) => {
    // ISBN入力
    await page.getByLabel('ISBN').fill('9784003101018')

    // 検索実行
    await page.getByRole('button', { name: /検索/i }).click()

    // 結果確認
    await expect(page.getByText('吾輩は猫である')).toBeVisible()
    await expect(page.getByText('ISBN: 9784003101018')).toBeVisible()
  })

  /**
   * TC-N010: ISBN-10検索（ハイフン付き）
   */
  test('TC-N010: ハイフン付きISBNで検索ができる', async ({ page }) => {
    // ハイフン付きISBN入力
    await page.getByLabel('ISBN').fill('978-4-00-310101-8')

    // 検索実行
    await page.getByRole('button', { name: /検索/i }).click()

    // 結果確認
    await expect(page.getByText('吾輩は猫である')).toBeVisible()
  })

  /**
   * TC-N017: 条件なし全件検索
   */
  test('TC-N017: 条件なしで全件検索ができる', async ({ page }) => {
    // 条件なしで検索実行
    await page.getByRole('button', { name: /検索/i }).click()

    // 全件が表示される
    await expect(page.getByText('吾輩は猫である')).toBeVisible()
    await expect(page.getByText('坊っちゃん')).toBeVisible()
    await expect(page.getByText('羅生門')).toBeVisible()
    await expect(page.getByText('人間失格')).toBeVisible()
    await expect(page.getByText('雪国')).toBeVisible()
    await expect(page.getByText('5', { exact: true }).first()).toBeVisible()
  })

  /**
   * TC-EC020: ブラウザの戻る/進む
   */
  test('TC-EC020: ブラウザの戻る/進む操作が正常に動作する', async ({ page }) => {
    // 最初の検索
    await page.getByLabel('タイトル').fill('猫')
    await page.getByRole('button', { name: /検索/i }).click()
    await expect(page.getByText('吾輩は猫である')).toBeVisible()

    // 2回目の検索
    await page.getByLabel('タイトル').fill('坊')
    await page.getByRole('button', { name: /検索/i }).click()
    await expect(page.getByText('坊っちゃん')).toBeVisible()

    // 戻る（ページ遷移ではないため、状態は変わらない可能性がある）
    await page.goBack()

    // 進む
    await page.goForward()

    // ページが正常に表示されることを確認
    await expect(page.getByRole('heading', { name: '蔵書検索' })).toBeVisible()
  })

  /**
   * TC-EC021: ページリロード
   */
  test('TC-EC021: ページリロード後も正常に動作する', async ({ page }) => {
    // 検索を実行
    await page.getByLabel('タイトル').fill('猫')
    await page.getByRole('button', { name: /検索/i }).click()
    await expect(page.getByText('吾輩は猫である')).toBeVisible()

    // リロード
    await page.reload()

    // APIモックを再設定（リロード後は必要）
    await setupApiMock(page)

    // ページが正常に表示される
    await expect(page.getByRole('heading', { name: '蔵書検索' })).toBeVisible()
    await expect(page.getByLabel('タイトル')).toBeVisible()
    await expect(page.getByRole('button', { name: /検索/i })).toBeVisible()
  })

  /**
   * 0件時の表示
   */
  test('検索結果が0件の場合にメッセージが表示される', async ({ page }) => {
    // 存在しないタイトルで検索
    await page.getByLabel('タイトル').fill('存在しない本のタイトル')
    await page.getByRole('button', { name: /検索/i }).click()

    // 0件メッセージ確認
    await expect(page.getByText('検索条件に一致する蔵書が見つかりませんでした')).toBeVisible()
  })

  /**
   * 状態バッジの表示
   */
  test('蔵書の状態バッジが正しく表示される', async ({ page }) => {
    // 全件検索
    await page.getByRole('button', { name: /検索/i }).click()

    // 各状態バッジを確認
    await expect(page.getByText('貸出可').first()).toBeVisible()
    await expect(page.getByText('貸出中')).toBeVisible()
    await expect(page.getByText('予約あり')).toBeVisible()
  })
})

test.describe('蔵書検索画面 - ローディング', () => {
  test('検索中にローディング表示がされる', async ({ page }) => {
    // 遅延レスポンスを設定
    await page.route('**/api/books*', async (route) => {
      await new Promise((resolve) => setTimeout(resolve, 500))
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          data: mockBooks,
          meta: { total: 5, page: 1, per_page: 20, last_page: 1 },
        }),
      })
    })

    await page.goto('/books')
    await page.waitForLoadState('networkidle')
    await expect(page.getByRole('heading', { name: '蔵書検索' })).toBeVisible({ timeout: 10000 })

    // 検索実行
    await page.getByRole('button', { name: /検索/i }).click()

    // ローディング表示を確認（ボタン内のテキストを使用）
    await expect(page.getByRole('button', { name: '検索中' })).toBeVisible()

    // 結果が表示されることを確認
    await expect(page.getByText('吾輩は猫である')).toBeVisible({ timeout: 5000 })
  })
})
