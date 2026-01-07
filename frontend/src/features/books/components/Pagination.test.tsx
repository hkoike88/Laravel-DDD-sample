/**
 * Paginationコンポーネントのユニットテスト
 * TC-N011, TC-EC010, TC-EC011, TC-EC012, TC-EC014
 */
import { describe, it, expect, vi } from 'vitest'
import { render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { Pagination } from './Pagination'
import type { PaginationMeta } from '../types/book'

describe('Pagination', () => {
  const defaultMeta: PaginationMeta = {
    total: 100,
    page: 1,
    per_page: 20,
    last_page: 5,
  }

  /**
   * TC-N011: ページネーション表示
   */
  describe('ページネーション表示', () => {
    it('ページ番号が表示される', () => {
      const onPageChange = vi.fn()
      render(<Pagination meta={defaultMeta} onPageChange={onPageChange} />)

      expect(screen.getByText('1')).toBeInTheDocument()
      expect(screen.getByText('2')).toBeInTheDocument()
      expect(screen.getByText('3')).toBeInTheDocument()
    })

    it('「前へ」「次へ」ボタンが表示される', () => {
      const onPageChange = vi.fn()
      render(<Pagination meta={defaultMeta} onPageChange={onPageChange} />)

      expect(screen.getByLabelText('前のページ')).toBeInTheDocument()
      expect(screen.getByLabelText('次のページ')).toBeInTheDocument()
    })

    it('現在ページがハイライトされる', () => {
      const onPageChange = vi.fn()
      render(<Pagination meta={defaultMeta} onPageChange={onPageChange} />)

      const currentPageButton = screen.getByText('1')
      expect(currentPageButton).toHaveClass('bg-blue-600')
      expect(currentPageButton).toHaveClass('text-white')
      expect(currentPageButton).toHaveAttribute('aria-current', 'page')
    })
  })

  /**
   * TC-EC010: 最初のページで「前へ」クリック
   */
  describe('最初のページで「前へ」', () => {
    it('「前へ」ボタンが無効化されている', () => {
      const onPageChange = vi.fn()
      render(<Pagination meta={defaultMeta} onPageChange={onPageChange} />)

      const prevButton = screen.getByLabelText('前のページ')
      expect(prevButton).toBeDisabled()
    })

    it('無効化されたボタンにdisabledスタイルが適用される', () => {
      const onPageChange = vi.fn()
      render(<Pagination meta={defaultMeta} onPageChange={onPageChange} />)

      const prevButton = screen.getByLabelText('前のページ')
      expect(prevButton).toHaveClass('disabled:opacity-50')
      expect(prevButton).toHaveClass('disabled:cursor-not-allowed')
    })
  })

  /**
   * TC-EC011: 最後のページで「次へ」クリック
   */
  describe('最後のページで「次へ」', () => {
    it('「次へ」ボタンが無効化されている', () => {
      const onPageChange = vi.fn()
      const lastPageMeta: PaginationMeta = { ...defaultMeta, page: 5 }
      render(<Pagination meta={lastPageMeta} onPageChange={onPageChange} />)

      const nextButton = screen.getByLabelText('次のページ')
      expect(nextButton).toBeDisabled()
    })
  })

  /**
   * TC-EC012: 1ページのみの結果
   */
  describe('1ページのみの結果', () => {
    it('ページネーションが表示されない', () => {
      const onPageChange = vi.fn()
      const singlePageMeta: PaginationMeta = {
        total: 10,
        page: 1,
        per_page: 20,
        last_page: 1,
      }
      const { container } = render(<Pagination meta={singlePageMeta} onPageChange={onPageChange} />)

      expect(container.firstChild).toBeNull()
    })
  })

  /**
   * ページ遷移のコールバック
   */
  describe('ページ遷移', () => {
    it('ページ番号クリックでonPageChangeが呼ばれる', async () => {
      const user = userEvent.setup()
      const onPageChange = vi.fn()
      render(<Pagination meta={defaultMeta} onPageChange={onPageChange} />)

      await user.click(screen.getByText('2'))
      expect(onPageChange).toHaveBeenCalledWith(2)
    })

    it('「次へ」クリックでonPageChangeが呼ばれる', async () => {
      const user = userEvent.setup()
      const onPageChange = vi.fn()
      render(<Pagination meta={defaultMeta} onPageChange={onPageChange} />)

      await user.click(screen.getByLabelText('次のページ'))
      expect(onPageChange).toHaveBeenCalledWith(2)
    })

    it('「前へ」クリックでonPageChangeが呼ばれる', async () => {
      const user = userEvent.setup()
      const onPageChange = vi.fn()
      const page2Meta: PaginationMeta = { ...defaultMeta, page: 2 }
      render(<Pagination meta={page2Meta} onPageChange={onPageChange} />)

      await user.click(screen.getByLabelText('前のページ'))
      expect(onPageChange).toHaveBeenCalledWith(1)
    })
  })

  /**
   * 省略記号の表示
   */
  describe('省略記号', () => {
    it('多ページ時に省略記号が表示される', () => {
      const onPageChange = vi.fn()
      const manyPagesMeta: PaginationMeta = {
        total: 200,
        page: 5,
        per_page: 20,
        last_page: 10,
      }
      render(<Pagination meta={manyPagesMeta} onPageChange={onPageChange} />)

      const ellipsis = screen.getAllByText('...')
      expect(ellipsis.length).toBeGreaterThan(0)
    })
  })

  /**
   * 中間ページの表示
   */
  describe('中間ページ', () => {
    it('中間ページでは「前へ」「次へ」両方が有効', () => {
      const onPageChange = vi.fn()
      const middlePageMeta: PaginationMeta = { ...defaultMeta, page: 3 }
      render(<Pagination meta={middlePageMeta} onPageChange={onPageChange} />)

      const prevButton = screen.getByLabelText('前のページ')
      const nextButton = screen.getByLabelText('次のページ')

      expect(prevButton).not.toBeDisabled()
      expect(nextButton).not.toBeDisabled()
    })
  })
})
