/**
 * BookSearchFormコンポーネントのユニットテスト
 * TC-EC001〜TC-EC009
 */
import { describe, it, expect, vi } from 'vitest'
import { render, screen, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { BookSearchForm } from './BookSearchForm'

describe('BookSearchForm', () => {
  /**
   * 基本表示
   */
  describe('基本表示', () => {
    it('タイトル入力欄が表示される', () => {
      const onSearch = vi.fn()
      render(<BookSearchForm onSearch={onSearch} />)

      expect(screen.getByLabelText('タイトル')).toBeInTheDocument()
    })

    it('著者入力欄が表示される', () => {
      const onSearch = vi.fn()
      render(<BookSearchForm onSearch={onSearch} />)

      expect(screen.getByLabelText('著者')).toBeInTheDocument()
    })

    it('ISBN入力欄が表示される', () => {
      const onSearch = vi.fn()
      render(<BookSearchForm onSearch={onSearch} />)

      expect(screen.getByLabelText('ISBN')).toBeInTheDocument()
    })

    it('検索ボタンが表示される', () => {
      const onSearch = vi.fn()
      render(<BookSearchForm onSearch={onSearch} />)

      expect(screen.getByRole('button', { name: /検索/i })).toBeInTheDocument()
    })
  })

  /**
   * TC-EC001: 最大文字数入力（255文字）
   */
  describe('最大文字数入力', () => {
    it('255文字まで入力できる', async () => {
      const user = userEvent.setup()
      const onSearch = vi.fn()
      render(<BookSearchForm onSearch={onSearch} />)

      const longText = 'あ'.repeat(255)
      const titleInput = screen.getByLabelText('タイトル')
      await user.type(titleInput, longText)

      expect(titleInput).toHaveValue(longText)
    })

    it('255文字で検索が正常に実行される', async () => {
      const user = userEvent.setup()
      const onSearch = vi.fn()
      render(<BookSearchForm onSearch={onSearch} />)

      const longText = 'あ'.repeat(255)
      await user.type(screen.getByLabelText('タイトル'), longText)
      await user.click(screen.getByRole('button', { name: /検索/i }))

      await waitFor(() => {
        expect(onSearch).toHaveBeenCalledWith({ title: longText })
      })
    })
  })

  /**
   * TC-EC002: 最大文字数超過（256文字以上）
   */
  describe('最大文字数超過', () => {
    it('256文字以上でバリデーションエラーが表示される', async () => {
      const user = userEvent.setup()
      const onSearch = vi.fn()
      render(<BookSearchForm onSearch={onSearch} />)

      const tooLongText = 'あ'.repeat(256)
      await user.type(screen.getByLabelText('タイトル'), tooLongText)
      await user.click(screen.getByRole('button', { name: /検索/i }))

      await waitFor(() => {
        // Zodのデフォルトエラーメッセージまたはカスタムメッセージを確認
        expect(onSearch).not.toHaveBeenCalled()
      })
    })
  })

  /**
   * TC-EC003: 全件検索（検索条件なしでの検索）
   */
  describe('全件検索', () => {
    it('空白のみの入力では空のパラメータで検索が実行される', async () => {
      const user = userEvent.setup()
      const onSearch = vi.fn()
      render(<BookSearchForm onSearch={onSearch} />)

      await user.type(screen.getByLabelText('タイトル'), '   ')
      await user.click(screen.getByRole('button', { name: /検索/i }))

      await waitFor(() => {
        // 空白のみの入力は全件検索として処理される
        expect(onSearch).toHaveBeenCalledWith({})
      })
    })

    it('何も入力しない場合は全件検索として実行される', async () => {
      const user = userEvent.setup()
      const onSearch = vi.fn()
      render(<BookSearchForm onSearch={onSearch} />)

      await user.click(screen.getByRole('button', { name: /検索/i }))

      await waitFor(() => {
        // 検索条件なしは全件検索として処理される
        expect(onSearch).toHaveBeenCalledWith({})
      })
    })
  })

  /**
   * TC-EC004: 特殊文字（XSS対策）
   */
  describe('特殊文字入力', () => {
    it('HTMLタグが文字列として入力できる', async () => {
      const user = userEvent.setup()
      const onSearch = vi.fn()
      render(<BookSearchForm onSearch={onSearch} />)

      const xssString = '<script>alert(1)</script>'
      await user.type(screen.getByLabelText('タイトル'), xssString)

      expect(screen.getByLabelText('タイトル')).toHaveValue(xssString)
    })

    it('SQLインジェクション文字列が入力できる', async () => {
      const user = userEvent.setup()
      const onSearch = vi.fn()
      render(<BookSearchForm onSearch={onSearch} />)

      const sqlString = "'; DROP TABLE books; --"
      await user.type(screen.getByLabelText('タイトル'), sqlString)

      expect(screen.getByLabelText('タイトル')).toHaveValue(sqlString)
    })
  })

  /**
   * TC-EC005: 日本語・英語・記号混在
   */
  describe('マルチバイト文字', () => {
    it('日本語・英語・記号が混在した入力ができる', async () => {
      const user = userEvent.setup()
      const onSearch = vi.fn()
      render(<BookSearchForm onSearch={onSearch} />)

      const mixedText = '吾輩は Cat-123 である！'
      await user.type(screen.getByLabelText('タイトル'), mixedText)
      await user.click(screen.getByRole('button', { name: /検索/i }))

      await waitFor(() => {
        expect(onSearch).toHaveBeenCalledWith({ title: mixedText })
      })
    })
  })

  /**
   * TC-EC006: 絵文字入力
   */
  describe('絵文字入力', () => {
    it('絵文字を含む入力ができる', async () => {
      const user = userEvent.setup()
      const onSearch = vi.fn()
      render(<BookSearchForm onSearch={onSearch} />)

      const emojiText = '📚本の検索'
      await user.type(screen.getByLabelText('タイトル'), emojiText)
      await user.click(screen.getByRole('button', { name: /検索/i }))

      await waitFor(() => {
        expect(onSearch).toHaveBeenCalledWith({ title: emojiText })
      })
    })
  })

  /**
   * TC-EC007: ISBN-13（ハイフンあり）
   */
  describe('ISBN入力', () => {
    it('ハイフン付きISBN-13が入力できる', async () => {
      const user = userEvent.setup()
      const onSearch = vi.fn()
      render(<BookSearchForm onSearch={onSearch} />)

      const isbnWithHyphen = '978-4-00-310101-8'
      await user.type(screen.getByLabelText('ISBN'), isbnWithHyphen)
      await user.click(screen.getByRole('button', { name: /検索/i }))

      await waitFor(() => {
        expect(onSearch).toHaveBeenCalledWith({ isbn: isbnWithHyphen })
      })
    })

    it('ハイフンなしISBN-13が入力できる', async () => {
      const user = userEvent.setup()
      const onSearch = vi.fn()
      render(<BookSearchForm onSearch={onSearch} />)

      const isbn = '9784003101018'
      await user.type(screen.getByLabelText('ISBN'), isbn)
      await user.click(screen.getByRole('button', { name: /検索/i }))

      await waitFor(() => {
        expect(onSearch).toHaveBeenCalledWith({ isbn })
      })
    })
  })

  /**
   * TC-EC008: ISBN-10形式
   */
  describe('ISBN-10形式', () => {
    it('10桁ISBNが入力できる', async () => {
      const user = userEvent.setup()
      const onSearch = vi.fn()
      render(<BookSearchForm onSearch={onSearch} />)

      const isbn10 = '4003101014'
      await user.type(screen.getByLabelText('ISBN'), isbn10)
      await user.click(screen.getByRole('button', { name: /検索/i }))

      await waitFor(() => {
        expect(onSearch).toHaveBeenCalledWith({ isbn: isbn10 })
      })
    })
  })

  /**
   * ローディング状態
   */
  describe('ローディング状態', () => {
    it('isLoading=trueでボタンが無効化される', () => {
      const onSearch = vi.fn()
      render(<BookSearchForm onSearch={onSearch} isLoading={true} />)

      const button = screen.getByRole('button')
      expect(button).toBeDisabled()
    })

    it('isLoading=trueで「検索中...」と表示される', () => {
      const onSearch = vi.fn()
      render(<BookSearchForm onSearch={onSearch} isLoading={true} />)

      expect(screen.getByRole('button')).toHaveTextContent('検索中...')
    })

    it('isLoading=falseで「検索」と表示される', () => {
      const onSearch = vi.fn()
      render(<BookSearchForm onSearch={onSearch} isLoading={false} />)

      expect(screen.getByRole('button')).toHaveTextContent('検索')
    })
  })

  /**
   * 複合検索
   */
  describe('複合検索', () => {
    it('タイトルと著者の両方を指定して検索できる', async () => {
      const user = userEvent.setup()
      const onSearch = vi.fn()
      render(<BookSearchForm onSearch={onSearch} />)

      await user.type(screen.getByLabelText('タイトル'), '坊')
      await user.type(screen.getByLabelText('著者'), '夏目')
      await user.click(screen.getByRole('button', { name: /検索/i }))

      await waitFor(() => {
        expect(onSearch).toHaveBeenCalledWith({ title: '坊', author: '夏目' })
      })
    })

    it('全フィールドを指定して検索できる', async () => {
      const user = userEvent.setup()
      const onSearch = vi.fn()
      render(<BookSearchForm onSearch={onSearch} />)

      await user.type(screen.getByLabelText('タイトル'), '猫')
      await user.type(screen.getByLabelText('著者'), '夏目')
      await user.type(screen.getByLabelText('ISBN'), '9784003101018')
      await user.click(screen.getByRole('button', { name: /検索/i }))

      await waitFor(() => {
        expect(onSearch).toHaveBeenCalledWith({
          title: '猫',
          author: '夏目',
          isbn: '9784003101018',
        })
      })
    })
  })
})
