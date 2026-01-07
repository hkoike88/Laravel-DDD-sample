/**
 * MSW (Mock Service Worker) ハンドラー定義
 * APIリクエストをモックするためのハンドラー
 */
import { http, HttpResponse, delay } from 'msw'
import type { BookSearchResponse, Book } from '@/features/books/types/book'

/**
 * モック用蔵書データ
 */
export const mockBooks: Book[] = [
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
  {
    id: '01HQXYZ123456789ABCDEFI',
    title: '羅生門',
    author: '芥川龍之介',
    isbn: '9784003107010',
    publisher: '岩波書店',
    published_year: 1915,
    genre: '文学',
    status: 'reserved',
    registered_by: '01HQXYZ000000000STAFF02',
    registered_at: '2024-01-16T09:00:00Z',
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
    registered_by: '01HQXYZ000000000STAFF02',
    registered_at: '2024-01-16T10:00:00Z',
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
    registered_by: '01HQXYZ000000000STAFF01',
    registered_at: '2024-01-17T14:30:00Z',
  },
]

/**
 * 蔵書検索APIハンドラー
 */
const bookSearchHandler = http.get('*/api/books', async ({ request }) => {
  const url = new URL(request.url)
  const title = url.searchParams.get('title')
  const author = url.searchParams.get('author')
  const isbn = url.searchParams.get('isbn')
  const page = parseInt(url.searchParams.get('page') || '1', 10)
  const perPage = parseInt(url.searchParams.get('per_page') || '20', 10)

  // フィルタリング
  let filteredBooks = [...mockBooks]

  if (title) {
    filteredBooks = filteredBooks.filter((book) =>
      book.title.toLowerCase().includes(title.toLowerCase())
    )
  }

  if (author) {
    filteredBooks = filteredBooks.filter((book) =>
      book.author?.toLowerCase().includes(author.toLowerCase())
    )
  }

  if (isbn) {
    filteredBooks = filteredBooks.filter((book) => book.isbn === isbn.replace(/-/g, ''))
  }

  // ページネーション
  const total = filteredBooks.length
  const lastPage = Math.ceil(total / perPage)
  const startIndex = (page - 1) * perPage
  const paginatedBooks = filteredBooks.slice(startIndex, startIndex + perPage)

  const response: BookSearchResponse = {
    data: paginatedBooks,
    meta: {
      total,
      page,
      per_page: perPage,
      last_page: lastPage || 1,
    },
  }

  // 少し遅延を入れてローディング状態をテスト可能に
  await delay(100)

  return HttpResponse.json(response)
})

/**
 * エクスポートするハンドラー一覧
 */
export const handlers = [bookSearchHandler]

/**
 * エラーレスポンス用ハンドラーファクトリ
 */
export const errorHandlers = {
  /**
   * 500 Internal Server Error
   */
  serverError: http.get('*/api/books', () => {
    return HttpResponse.json({ message: 'Internal Server Error' }, { status: 500 })
  }),

  /**
   * 503 Service Unavailable
   */
  serviceUnavailable: http.get('*/api/books', () => {
    return HttpResponse.json({ message: 'Service Unavailable' }, { status: 503 })
  }),

  /**
   * 422 Validation Error
   */
  validationError: http.get('*/api/books', () => {
    return HttpResponse.json(
      {
        message: 'The given data was invalid.',
        errors: {
          per_page: ['1ページあたりの件数は100以下で入力してください'],
        },
      },
      { status: 422 }
    )
  }),

  /**
   * 401 Unauthorized
   */
  unauthorized: http.get('*/api/books', () => {
    return HttpResponse.json({ message: 'Unauthenticated.' }, { status: 401 })
  }),

  /**
   * 403 Forbidden
   */
  forbidden: http.get('*/api/books', () => {
    return HttpResponse.json({ message: 'Forbidden.' }, { status: 403 })
  }),

  /**
   * Network Error
   */
  networkError: http.get('*/api/books', () => {
    return HttpResponse.error()
  }),

  /**
   * Timeout (5秒遅延)
   */
  timeout: http.get('*/api/books', async () => {
    await delay(6000)
    return HttpResponse.json({ data: [], meta: { total: 0, page: 1, per_page: 20, last_page: 1 } })
  }),

  /**
   * 不正なJSONレスポンス
   */
  invalidJson: http.get('*/api/books', () => {
    return new HttpResponse('invalid json {{{', {
      headers: { 'Content-Type': 'application/json' },
    })
  }),

  /**
   * 必須フィールド欠落
   */
  missingFields: http.get('*/api/books', () => {
    return HttpResponse.json({ unexpected: 'response' })
  }),
}
