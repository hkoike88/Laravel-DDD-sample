/**
 * BookSearchPage統合テスト
 * TC-N004, TC-N005, TC-N012〜N015, TC-EC013, TC-EC016〜EC019
 */
import { describe, it, expect, beforeEach } from 'vitest'
import { render, screen, waitFor } from '@/test/test-utils'
import userEvent from '@testing-library/user-event'
import { BookSearchPage } from './BookSearchPage'
import { server } from '@/mocks/server'
import { http, HttpResponse, delay } from 'msw'
import type { BookSearchResponse } from '../types/book'

describe('BookSearchPage統合テスト', () => {
  beforeEach(() => {
    server.resetHandlers()
  })

  /**
   * TC-N004: ローディング表示
   */
  describe('ローディング表示', () => {
    it('検索実行時にローディングスピナーが表示される', async () => {
      const user = userEvent.setup()

      // 遅延レスポンスを設定
      server.use(
        http.get('*/api/books', async () => {
          await delay(500)
          return HttpResponse.json({
            data: [],
            meta: { total: 0, page: 1, per_page: 20, last_page: 1 },
          })
        })
      )

      render(<BookSearchPage />)

      await user.type(screen.getByLabelText('タイトル'), 'テスト')
      await user.click(screen.getByRole('button', { name: /検索/i }))

      // ローディング表示を確認（「検索中...」がボタンとスピナー両方に表示される）
      const loadingTexts = screen.getAllByText('検索中...')
      expect(loadingTexts.length).toBeGreaterThanOrEqual(1)
    })

    it('ローディング中はスピナーアニメーションが表示される', async () => {
      const user = userEvent.setup()

      server.use(
        http.get('*/api/books', async () => {
          await delay(500)
          return HttpResponse.json({
            data: [],
            meta: { total: 0, page: 1, per_page: 20, last_page: 1 },
          })
        })
      )

      const { container } = render(<BookSearchPage />)

      await user.type(screen.getByLabelText('タイトル'), 'テスト')
      await user.click(screen.getByRole('button', { name: /検索/i }))

      // スピナー要素を確認
      const spinner = container.querySelector('.animate-spin')
      expect(spinner).toBeInTheDocument()
    })

    it('検索完了後にローディングが非表示になる', async () => {
      const user = userEvent.setup()

      server.use(
        http.get('*/api/books', async () => {
          await delay(200)
          return HttpResponse.json({
            data: [
              {
                id: '01HQXYZ123456789ABCDEFG',
                title: 'テスト本',
                author: 'テスト著者',
                isbn: '9784003101018',
                publisher: 'テスト出版社',
                published_year: 2024,
                genre: '文学',
                status: 'available',
              },
            ],
            meta: { total: 1, page: 1, per_page: 20, last_page: 1 },
          })
        })
      )

      const { container } = render(<BookSearchPage />)

      await user.type(screen.getByLabelText('タイトル'), 'テスト')
      await user.click(screen.getByRole('button', { name: /検索/i }))

      // ローディングが表示されることを確認（スピナー要素）
      await waitFor(() => {
        expect(container.querySelector('.animate-spin')).toBeInTheDocument()
      })

      // 検索結果が表示されることを確認
      await waitFor(() => {
        expect(screen.getByText('テスト本')).toBeInTheDocument()
      })

      // スピナーが消えたことを確認
      expect(container.querySelector('.animate-spin')).not.toBeInTheDocument()
    })
  })

  /**
   * TC-N005: 検索結果件数表示
   */
  describe('検索結果件数表示', () => {
    it('検索結果の件数が表示される', async () => {
      const user = userEvent.setup()

      server.use(
        http.get('*/api/books', () => {
          return HttpResponse.json({
            data: [
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
            ],
            meta: { total: 25, page: 1, per_page: 20, last_page: 2 },
          })
        })
      )

      render(<BookSearchPage />)

      await user.type(screen.getByLabelText('著者'), '夏目')
      await user.click(screen.getByRole('button', { name: /検索/i }))

      await waitFor(() => {
        expect(screen.getByText(/件中/)).toBeInTheDocument()
      })
      expect(screen.getByText('25')).toBeInTheDocument()
    })

    it('0件の場合も件数が表示される', async () => {
      const user = userEvent.setup()

      server.use(
        http.get('*/api/books', () => {
          return HttpResponse.json({
            data: [],
            meta: { total: 0, page: 1, per_page: 20, last_page: 1 },
          })
        })
      )

      render(<BookSearchPage />)

      await user.type(screen.getByLabelText('タイトル'), '存在しない本')
      await user.click(screen.getByRole('button', { name: /検索/i }))

      await waitFor(() => {
        expect(screen.getByText('0')).toBeInTheDocument()
      })
    })
  })

  /**
   * TC-N012〜N014: ページネーション操作
   */
  describe('ページネーション操作', () => {
    const createPaginatedResponse = (page: number): BookSearchResponse => ({
      data: [
        {
          id: `01HQXYZ123456789ABCDEF${page}`,
          title: `本${page}`,
          author: '著者',
          isbn: '9784003101018',
          publisher: '出版社',
          published_year: 2024,
          genre: '文学',
          status: 'available',
          registered_by: '01HQXYZ000000000STAFF01',
          registered_at: '2024-01-15T10:00:00Z',
        },
      ],
      meta: { total: 100, page, per_page: 20, last_page: 5 },
    })

    it('TC-N012: ページ番号クリックでそのページが読み込まれる', async () => {
      const user = userEvent.setup()
      let requestedPage = 1

      server.use(
        http.get('*/api/books', ({ request }) => {
          const url = new URL(request.url)
          requestedPage = parseInt(url.searchParams.get('page') || '1', 10)
          return HttpResponse.json(createPaginatedResponse(requestedPage))
        })
      )

      render(<BookSearchPage />)

      // 初回検索
      await user.click(screen.getByRole('button', { name: /検索/i }))

      await waitFor(() => {
        expect(screen.getByText('本1')).toBeInTheDocument()
      })

      // ページ2をクリック
      await user.click(screen.getByText('2'))

      await waitFor(() => {
        expect(screen.getByText('本2')).toBeInTheDocument()
        expect(requestedPage).toBe(2)
      })
    })

    it('TC-N013: 「次へ」ボタンで次ページが読み込まれる', async () => {
      const user = userEvent.setup()
      let requestedPage = 1

      server.use(
        http.get('*/api/books', ({ request }) => {
          const url = new URL(request.url)
          requestedPage = parseInt(url.searchParams.get('page') || '1', 10)
          return HttpResponse.json(createPaginatedResponse(requestedPage))
        })
      )

      render(<BookSearchPage />)

      await user.click(screen.getByRole('button', { name: /検索/i }))

      await waitFor(() => {
        expect(screen.getByText('本1')).toBeInTheDocument()
      })

      // 「次へ」をクリック
      await user.click(screen.getByLabelText('次のページ'))

      await waitFor(() => {
        expect(screen.getByText('本2')).toBeInTheDocument()
        expect(requestedPage).toBe(2)
      })
    })

    it('TC-N014: 「前へ」ボタンで前ページが読み込まれる', async () => {
      const user = userEvent.setup()
      let requestedPage = 1

      server.use(
        http.get('*/api/books', ({ request }) => {
          const url = new URL(request.url)
          requestedPage = parseInt(url.searchParams.get('page') || '1', 10)
          return HttpResponse.json(createPaginatedResponse(requestedPage))
        })
      )

      render(<BookSearchPage />)

      await user.click(screen.getByRole('button', { name: /検索/i }))

      await waitFor(() => {
        expect(screen.getByText('本1')).toBeInTheDocument()
      })

      // ページ3に移動
      await user.click(screen.getByText('3'))
      await waitFor(() => {
        expect(screen.getByText('本3')).toBeInTheDocument()
      })

      // 「前へ」をクリック
      await user.click(screen.getByLabelText('前のページ'))

      await waitFor(() => {
        expect(screen.getByText('本2')).toBeInTheDocument()
        expect(requestedPage).toBe(2)
      })
    })
  })

  /**
   * TC-N015, TC-EC016: 0件時の表示
   */
  describe('0件時の表示', () => {
    it('TC-N015: 0件時にメッセージが表示される', async () => {
      const user = userEvent.setup()

      server.use(
        http.get('*/api/books', () => {
          return HttpResponse.json({
            data: [],
            meta: { total: 0, page: 1, per_page: 20, last_page: 1 },
          })
        })
      )

      render(<BookSearchPage />)

      await user.type(screen.getByLabelText('タイトル'), '存在しない本')
      await user.click(screen.getByRole('button', { name: /検索/i }))

      await waitFor(() => {
        expect(screen.getByText('検索条件に一致する蔵書が見つかりませんでした')).toBeInTheDocument()
      })
    })

    it('TC-EC016: 空配列レスポンス時にテーブルが表示されない', async () => {
      const user = userEvent.setup()

      server.use(
        http.get('*/api/books', () => {
          return HttpResponse.json({
            data: [],
            meta: { total: 0, page: 1, per_page: 20, last_page: 1 },
          })
        })
      )

      render(<BookSearchPage />)

      await user.type(screen.getByLabelText('タイトル'), '存在しない本')
      await user.click(screen.getByRole('button', { name: /検索/i }))

      await waitFor(() => {
        expect(screen.queryByRole('table')).not.toBeInTheDocument()
      })
    })
  })

  /**
   * TC-EC017: 検索中に再度クリック
   */
  describe('検索中の操作', () => {
    it('TC-EC017: 検索中は検索ボタンが無効化される', async () => {
      const user = userEvent.setup()

      server.use(
        http.get('*/api/books', async () => {
          await delay(500)
          return HttpResponse.json({
            data: [],
            meta: { total: 0, page: 1, per_page: 20, last_page: 1 },
          })
        })
      )

      render(<BookSearchPage />)

      await user.type(screen.getByLabelText('タイトル'), 'テスト')
      await user.click(screen.getByRole('button', { name: /検索/i }))

      // ボタンが無効化されていることを確認
      const button = screen.getByRole('button')
      expect(button).toBeDisabled()
      expect(button).toHaveTextContent('検索中...')
    })
  })

  /**
   * TC-EC019: 高速連続ページ遷移
   * 注: このUIではローディング中にページネーションが非表示になるため、
   * 連続クリックのテストは各ページ読み込み完了を待つ形式で実施
   */
  describe('連続ページ遷移', () => {
    it('複数回のページ遷移が正しく動作する', async () => {
      const user = userEvent.setup()
      const requestedPages: number[] = []

      server.use(
        http.get('*/api/books', async ({ request }) => {
          const url = new URL(request.url)
          const page = parseInt(url.searchParams.get('page') || '1', 10)
          requestedPages.push(page)

          await delay(50)

          return HttpResponse.json({
            data: [
              {
                id: `01HQXYZ123456789ABCDEF${page}`,
                title: `ページ${page}の本`,
                author: '著者',
                isbn: '9784003101018',
                publisher: '出版社',
                published_year: 2024,
                genre: '文学',
                status: 'available',
              },
            ],
            meta: { total: 100, page, per_page: 20, last_page: 5 },
          })
        })
      )

      render(<BookSearchPage />)

      // 初回検索
      await user.click(screen.getByRole('button', { name: /検索/i }))

      await waitFor(() => {
        expect(screen.getByText('ページ1の本')).toBeInTheDocument()
      })

      // ページ2に移動
      await user.click(screen.getByRole('button', { name: '2' }))
      await waitFor(() => {
        expect(screen.getByText('ページ2の本')).toBeInTheDocument()
      })

      // ページ3に移動
      await user.click(screen.getByRole('button', { name: '3' }))
      await waitFor(() => {
        expect(screen.getByText('ページ3の本')).toBeInTheDocument()
      })

      // 全ページが正しく要求されたことを確認
      expect(requestedPages).toContain(1)
      expect(requestedPages).toContain(2)
      expect(requestedPages).toContain(3)
    })
  })
})
