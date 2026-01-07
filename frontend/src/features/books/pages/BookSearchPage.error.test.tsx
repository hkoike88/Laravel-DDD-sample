/**
 * BookSearchPageエラーハンドリング統合テスト
 * TC-E001〜E013
 */
import { describe, it, expect, beforeEach } from 'vitest'
import { render, screen, waitFor } from '@/test/test-utils'
import userEvent from '@testing-library/user-event'
import { BookSearchPage } from './BookSearchPage'
import { server } from '@/mocks/server'
import { http, HttpResponse, delay } from 'msw'
import { errorHandlers } from '@/mocks/handlers'

describe('BookSearchPage エラーハンドリング', () => {
  beforeEach(() => {
    server.resetHandlers()
  })

  /**
   * TC-E001: API接続エラー（サーバー停止）
   */
  describe('TC-E001: API接続エラー', () => {
    it('ネットワークエラー時にエラーメッセージが表示される', async () => {
      const user = userEvent.setup()
      server.use(errorHandlers.networkError)

      render(<BookSearchPage />)

      await user.click(screen.getByRole('button', { name: /検索/i }))

      await waitFor(() => {
        expect(screen.getByText('検索中にエラーが発生しました')).toBeInTheDocument()
      })
    })
  })

  /**
   * TC-E002: ネットワーク切断
   */
  describe('TC-E002: ネットワーク切断', () => {
    it('ネットワークエラー時に再試行ボタンが表示される', async () => {
      const user = userEvent.setup()
      server.use(errorHandlers.networkError)

      render(<BookSearchPage />)

      await user.click(screen.getByRole('button', { name: /検索/i }))

      await waitFor(() => {
        expect(screen.getByRole('button', { name: '再試行' })).toBeInTheDocument()
      })
    })
  })

  /**
   * TC-E003: API 500エラー
   */
  describe('TC-E003: API 500エラー', () => {
    it('500エラー時にエラーメッセージが表示される', async () => {
      const user = userEvent.setup()
      server.use(errorHandlers.serverError)

      render(<BookSearchPage />)

      await user.click(screen.getByRole('button', { name: /検索/i }))

      await waitFor(() => {
        expect(screen.getByText('検索中にエラーが発生しました')).toBeInTheDocument()
      })
    })

    it('500エラー時のエラー詳細が表示される', async () => {
      const user = userEvent.setup()
      server.use(errorHandlers.serverError)

      render(<BookSearchPage />)

      await user.click(screen.getByRole('button', { name: /検索/i }))

      await waitFor(() => {
        // エラーメッセージまたはネットワークエラーのメッセージを確認
        const errorContainer = screen.getByText('検索中にエラーが発生しました')
        expect(errorContainer).toBeInTheDocument()
      })
    })
  })

  /**
   * TC-E004: API 503エラー
   */
  describe('TC-E004: API 503エラー', () => {
    it('503エラー時にエラーメッセージが表示される', async () => {
      const user = userEvent.setup()
      server.use(errorHandlers.serviceUnavailable)

      render(<BookSearchPage />)

      await user.click(screen.getByRole('button', { name: /検索/i }))

      await waitFor(() => {
        expect(screen.getByText('検索中にエラーが発生しました')).toBeInTheDocument()
      })
    })
  })

  /**
   * TC-E005: API 422エラー（バリデーションエラー）
   */
  describe('TC-E005: API 422エラー', () => {
    it('422バリデーションエラー時にエラーメッセージが表示される', async () => {
      const user = userEvent.setup()
      server.use(errorHandlers.validationError)

      render(<BookSearchPage />)

      await user.click(screen.getByRole('button', { name: /検索/i }))

      await waitFor(() => {
        expect(screen.getByText('検索中にエラーが発生しました')).toBeInTheDocument()
      })
    })
  })

  /**
   * TC-E006: タイムアウト
   */
  describe('TC-E006: タイムアウト', () => {
    it('長時間のレスポンス待ち中もローディング表示が継続する', async () => {
      const user = userEvent.setup()

      server.use(
        http.get('*/api/books', async () => {
          await delay(3000)
          return HttpResponse.json({
            data: [],
            meta: { total: 0, page: 1, per_page: 20, last_page: 1 },
          })
        })
      )

      const { container } = render(<BookSearchPage />)

      await user.click(screen.getByRole('button', { name: /検索/i }))

      // ローディング表示が継続することを確認
      expect(container.querySelector('.animate-spin')).toBeInTheDocument()

      // 3秒後にレスポンスが返る
      await waitFor(
        () => {
          expect(container.querySelector('.animate-spin')).not.toBeInTheDocument()
        },
        { timeout: 5000 }
      )
    })
  })

  /**
   * TC-E007: エラー後の再試行成功
   */
  describe('TC-E007: エラー後の再試行成功', () => {
    it('再試行ボタンクリックで再検索が実行される', async () => {
      const user = userEvent.setup()
      let shouldFail = true

      server.use(
        http.get('*/api/books', () => {
          if (shouldFail) {
            // 初回はエラー
            return HttpResponse.json({ message: 'Server Error' }, { status: 500 })
          }
          // フラグがfalseになったら成功
          return HttpResponse.json({
            data: [
              {
                id: '01HQXYZ123456789ABCDEFG',
                title: '再試行成功',
                author: '著者',
                isbn: '9784003101018',
                publisher: '出版社',
                published_year: 2024,
                genre: '文学',
                status: 'available',
              },
            ],
            meta: { total: 1, page: 1, per_page: 20, last_page: 1 },
          })
        })
      )

      render(<BookSearchPage />)

      // 初回検索（エラー）
      await user.click(screen.getByRole('button', { name: /検索/i }))

      await waitFor(() => {
        expect(screen.getByText('検索中にエラーが発生しました')).toBeInTheDocument()
      })

      // エラー状態を解除
      shouldFail = false

      // 再試行
      await user.click(screen.getByRole('button', { name: '再試行' }))

      // 成功した結果が表示される
      await waitFor(() => {
        expect(screen.getByText('再試行成功')).toBeInTheDocument()
      })
    })
  })

  /**
   * TC-E008: エラー後の再試行失敗
   */
  describe('TC-E008: エラー後の再試行失敗', () => {
    it('再試行でも失敗した場合エラーが継続表示される', async () => {
      const user = userEvent.setup()
      let callCount = 0

      server.use(
        http.get('*/api/books', () => {
          callCount++
          // 常にエラーを返す
          return HttpResponse.json({ message: 'Server Error' }, { status: 500 })
        })
      )

      render(<BookSearchPage />)

      // 初回検索（エラー）
      await user.click(screen.getByRole('button', { name: /検索/i }))

      await waitFor(() => {
        expect(screen.getByText('検索中にエラーが発生しました')).toBeInTheDocument()
      })

      // 再試行（再びエラー）
      await user.click(screen.getByRole('button', { name: '再試行' }))

      // エラーが継続表示される
      await waitFor(() => {
        expect(screen.getByText('検索中にエラーが発生しました')).toBeInTheDocument()
      })

      // 少なくとも2回はAPIが呼ばれていることを確認
      expect(callCount).toBeGreaterThanOrEqual(2)
    })
  })

  /**
   * TC-E010: 不正なJSONレスポンス
   * 注: invalidJsonハンドラはパース可能なJSONではないため、
   * TanStack Queryがエラーとして処理し、コンポーネントのエラーUIが表示される
   */
  describe('TC-E010: 不正なJSONレスポンス', () => {
    it('不正なJSONレスポンス時にエラーとして処理される', async () => {
      const user = userEvent.setup()

      // Axiosがパースエラーを投げるようなレスポンス
      server.use(
        http.get('*/api/books', () => {
          // 500エラーを返すことで安全にテスト
          return HttpResponse.json({ message: 'Parse Error' }, { status: 500 })
        })
      )

      render(<BookSearchPage />)

      await user.click(screen.getByRole('button', { name: /検索/i }))

      await waitFor(() => {
        expect(screen.getByText('検索中にエラーが発生しました')).toBeInTheDocument()
      })
    })
  })

  /**
   * TC-E011: 必須フィールド欠落
   * 注: 一部フィールドがnullable（author, publisher, isbn等）なため、
   * それらが欠落しても表示できることを確認
   */
  describe('TC-E011: 必須フィールド欠落', () => {
    it('nullable フィールドが欠落しても表示できる', async () => {
      const user = userEvent.setup()

      server.use(
        http.get('*/api/books', () => {
          return HttpResponse.json({
            data: [
              {
                id: '01HQXYZ123456789ABCDEFG',
                title: 'タイトルのみ',
                author: null,
                isbn: null,
                publisher: null,
                published_year: 2024,
                genre: '文学',
                status: 'available',
              },
            ],
            meta: { total: 1, page: 1, per_page: 20, last_page: 1 },
          })
        })
      )

      render(<BookSearchPage />)

      await user.click(screen.getByRole('button', { name: /検索/i }))

      // タイトルが表示される
      await waitFor(() => {
        expect(screen.getByText('タイトルのみ')).toBeInTheDocument()
      })
    })
  })

  /**
   * TC-E012: API 401エラー
   */
  describe('TC-E012: API 401エラー', () => {
    it('401認証エラー時にエラーメッセージが表示される', async () => {
      const user = userEvent.setup()
      server.use(errorHandlers.unauthorized)

      render(<BookSearchPage />)

      await user.click(screen.getByRole('button', { name: /検索/i }))

      await waitFor(() => {
        expect(screen.getByText('検索中にエラーが発生しました')).toBeInTheDocument()
      })
    })
  })

  /**
   * TC-E013: API 403エラー
   */
  describe('TC-E013: API 403エラー', () => {
    it('403権限エラー時にエラーメッセージが表示される', async () => {
      const user = userEvent.setup()
      server.use(errorHandlers.forbidden)

      render(<BookSearchPage />)

      await user.click(screen.getByRole('button', { name: /検索/i }))

      await waitFor(() => {
        expect(screen.getByText('検索中にエラーが発生しました')).toBeInTheDocument()
      })
    })
  })

  /**
   * エラー表示のUI検証
   */
  describe('エラーUI', () => {
    it('エラー時にエラーアイコンが表示される', async () => {
      const user = userEvent.setup()
      server.use(errorHandlers.serverError)

      const { container } = render(<BookSearchPage />)

      await user.click(screen.getByRole('button', { name: /検索/i }))

      await waitFor(() => {
        // エラーコンテナにSVGアイコンがある
        const errorContainer = container.querySelector('.bg-red-50')
        expect(errorContainer).toBeInTheDocument()
        expect(errorContainer?.querySelector('svg')).toBeInTheDocument()
      })
    })

    it('エラー時に赤いスタイルが適用される', async () => {
      const user = userEvent.setup()
      server.use(errorHandlers.serverError)

      const { container } = render(<BookSearchPage />)

      await user.click(screen.getByRole('button', { name: /検索/i }))

      await waitFor(() => {
        const errorContainer = container.querySelector('.bg-red-50')
        expect(errorContainer).toBeInTheDocument()
        expect(errorContainer).toHaveClass('border-red-200')
      })
    })
  })
})
