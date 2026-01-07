/**
 * BookSearchResultsコンポーネントのユニットテスト
 * TC-N016, TC-EC022, TC-EC023
 */
import { describe, it, expect } from 'vitest'
import { render, screen } from '@testing-library/react'
import { BookSearchResults } from './BookSearchResults'
import type { Book } from '../types/book'

describe('BookSearchResults', () => {
  const mockBooks: Book[] = [
    {
      id: '01HQXYZ123456789ABCDEFG',
      title: '吾輩は猫である',
      author: '夏目漱石',
      isbn: '9784003101018',
      publisher: '岩波書店',
      published_year: 1905,
      genre: '文学',
      status: 'available',
      registered_by: '01HQXYZ000000000STAFF01',
      registered_at: '2024-01-15T10:00:00Z',
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
      registered_by: '01HQXYZ000000000STAFF01',
      registered_at: '2024-01-15T11:00:00Z',
    },
  ]

  /**
   * 基本表示
   */
  describe('基本表示', () => {
    it('テーブルヘッダーが表示される', () => {
      render(<BookSearchResults books={mockBooks} />)

      expect(screen.getByText('タイトル')).toBeInTheDocument()
      expect(screen.getByText('著者')).toBeInTheDocument()
      expect(screen.getByText('出版社')).toBeInTheDocument()
      expect(screen.getByText('状態')).toBeInTheDocument()
    })

    it('蔵書のタイトルが表示される', () => {
      render(<BookSearchResults books={mockBooks} />)

      expect(screen.getByText('吾輩は猫である')).toBeInTheDocument()
      expect(screen.getByText('坊っちゃん')).toBeInTheDocument()
    })

    it('蔵書の著者が表示される', () => {
      render(<BookSearchResults books={mockBooks} />)

      expect(screen.getAllByText('夏目漱石')).toHaveLength(2)
    })

    it('蔵書の出版社が表示される', () => {
      render(<BookSearchResults books={mockBooks} />)

      expect(screen.getByText('岩波書店')).toBeInTheDocument()
      expect(screen.getByText('新潮社')).toBeInTheDocument()
    })

    it('ISBNが表示される', () => {
      render(<BookSearchResults books={mockBooks} />)

      expect(screen.getByText('ISBN: 9784003101018')).toBeInTheDocument()
      expect(screen.getByText('ISBN: 9784101010014')).toBeInTheDocument()
    })

    it('状態バッジが表示される', () => {
      render(<BookSearchResults books={mockBooks} />)

      expect(screen.getByText('貸出可')).toBeInTheDocument()
      expect(screen.getByText('貸出中')).toBeInTheDocument()
    })
  })

  /**
   * TC-N016: 0件時のメッセージ表示
   */
  describe('0件時の表示', () => {
    it('メッセージが表示される', () => {
      render(<BookSearchResults books={[]} />)

      expect(screen.getByText('検索条件に一致する蔵書が見つかりませんでした')).toBeInTheDocument()
    })

    it('検索ヒントが表示される', () => {
      render(<BookSearchResults books={[]} />)

      expect(screen.getByText(/検索条件を変更して再度お試しください/)).toBeInTheDocument()
      expect(
        screen.getByText(/タイトルや著者名の一部を入力するか、ISBNで検索してみてください/)
      ).toBeInTheDocument()
    })

    it('テーブルが表示されない', () => {
      render(<BookSearchResults books={[]} />)

      expect(screen.queryByRole('table')).not.toBeInTheDocument()
    })

    it('アイコンが表示される', () => {
      const { container } = render(<BookSearchResults books={[]} />)

      const icon = container.querySelector('svg')
      expect(icon).toBeInTheDocument()
    })
  })

  /**
   * TC-EC022: 長いタイトルの表示
   */
  describe('長いタイトルの表示', () => {
    it('長いタイトルが正しく表示される', () => {
      const longTitleBook: Book = {
        id: '01HQXYZ123456789ABCDEFL',
        title:
          'これはとても長いタイトルの本です。サブタイトルも含めるとさらに長くなります。出版社の意向でこのように長いタイトルになりました。',
        author: '著者名',
        isbn: '9784000000000',
        publisher: '出版社',
        published_year: 2024,
        genre: '文学',
        status: 'available',
        registered_by: '01HQXYZ000000000STAFF01',
        registered_at: '2024-01-15T10:00:00Z',
      }

      render(<BookSearchResults books={[longTitleBook]} />)

      expect(screen.getByText(longTitleBook.title)).toBeInTheDocument()
    })

    it('長いタイトルにwhitespace-nowrapクラスが適用される', () => {
      const longTitleBook: Book = {
        id: '01HQXYZ123456789ABCDEFL',
        title: 'これはとても長いタイトルの本です。サブタイトルも含めるとさらに長くなります。',
        author: '著者名',
        isbn: '9784000000000',
        publisher: '出版社',
        published_year: 2024,
        genre: '文学',
        status: 'available',
        registered_by: '01HQXYZ000000000STAFF01',
        registered_at: '2024-01-15T10:00:00Z',
      }

      render(<BookSearchResults books={[longTitleBook]} />)

      const titleCell = screen.getByText(longTitleBook.title).closest('td')
      expect(titleCell).toHaveClass('whitespace-nowrap')
    })
  })

  /**
   * TC-EC023: 長い著者名の表示
   */
  describe('長い著者名の表示', () => {
    it('長い著者名が正しく表示される', () => {
      const longAuthorBook: Book = {
        id: '01HQXYZ123456789ABCDEFM',
        title: 'タイトル',
        author:
          'ジョン・ロナルド・ロウエル・トールキン / クリストファー・ジョン・ロウエル・トールキン',
        isbn: '9784000000001',
        publisher: '出版社',
        published_year: 2024,
        genre: '文学',
        status: 'available',
        registered_by: '01HQXYZ000000000STAFF01',
        registered_at: '2024-01-15T10:00:00Z',
      }

      render(<BookSearchResults books={[longAuthorBook]} />)

      expect(screen.getByText(longAuthorBook.author!)).toBeInTheDocument()
    })
  })

  /**
   * nullableフィールドの表示
   */
  describe('nullableフィールド', () => {
    it('著者がnullの場合「-」が表示される', () => {
      const bookWithoutAuthor: Book = {
        id: '01HQXYZ123456789ABCDEFN',
        title: 'タイトル',
        author: null,
        isbn: '9784000000002',
        publisher: '出版社',
        published_year: 2024,
        genre: '文学',
        status: 'available',
        registered_by: '01HQXYZ000000000STAFF01',
        registered_at: '2024-01-15T10:00:00Z',
      }

      render(<BookSearchResults books={[bookWithoutAuthor]} />)

      const cells = screen.getAllByText('-')
      expect(cells.length).toBeGreaterThanOrEqual(1)
    })

    it('出版社がnullの場合「-」が表示される', () => {
      const bookWithoutPublisher: Book = {
        id: '01HQXYZ123456789ABCDEFO',
        title: 'タイトル',
        author: '著者',
        isbn: '9784000000003',
        publisher: null,
        published_year: 2024,
        genre: '文学',
        status: 'available',
        registered_by: '01HQXYZ000000000STAFF01',
        registered_at: '2024-01-15T10:00:00Z',
      }

      render(<BookSearchResults books={[bookWithoutPublisher]} />)

      const cells = screen.getAllByText('-')
      expect(cells.length).toBeGreaterThanOrEqual(1)
    })

    it('ISBNがnullの場合はISBN行が表示されない', () => {
      const bookWithoutIsbn: Book = {
        id: '01HQXYZ123456789ABCDEFP',
        title: 'タイトル',
        author: '著者',
        isbn: null,
        publisher: '出版社',
        published_year: 2024,
        genre: '文学',
        status: 'available',
        registered_by: '01HQXYZ000000000STAFF01',
        registered_at: '2024-01-15T10:00:00Z',
      }

      render(<BookSearchResults books={[bookWithoutIsbn]} />)

      expect(screen.queryByText(/ISBN:/)).not.toBeInTheDocument()
    })
  })

  /**
   * テーブル構造
   */
  describe('テーブル構造', () => {
    it('テーブル要素が正しく構成される', () => {
      render(<BookSearchResults books={mockBooks} />)

      expect(screen.getByRole('table')).toBeInTheDocument()
    })

    it('行数が蔵書数と一致する', () => {
      render(<BookSearchResults books={mockBooks} />)

      // tbody内のtrを取得
      const rows = screen.getAllByRole('row')
      // ヘッダー行 + データ行
      expect(rows).toHaveLength(mockBooks.length + 1)
    })
  })
})
