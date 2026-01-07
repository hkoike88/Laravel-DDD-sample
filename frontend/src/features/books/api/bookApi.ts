import { apiClient } from '@/lib/axios'
import type {
  Book,
  BookSearchParams,
  BookSearchResponse,
  CreateBookInput,
  CreateBookResponse,
  IsbnCheckResponse,
} from '../types/book'

/**
 * 蔵書APIクライアント
 */
export const bookApi = {
  /**
   * 蔵書を検索する
   * @param params - 検索パラメータ
   * @returns 検索結果
   */
  async search(params: BookSearchParams): Promise<BookSearchResponse> {
    const { data } = await apiClient.get<BookSearchResponse>('/books', {
      params,
    })
    return data
  },

  /**
   * 蔵書を登録する
   * @param input - 登録データ
   * @returns 登録された蔵書
   */
  async create(input: CreateBookInput): Promise<Book> {
    const { data } = await apiClient.post<CreateBookResponse>('/books', input)
    return data.data
  },

  /**
   * 蔵書を取得する
   * @param id - 蔵書ID（ULID）
   * @returns 蔵書データ
   */
  async getById(id: string): Promise<Book> {
    const { data } = await apiClient.get<{ data: Book }>(`/books/${id}`)
    return data.data
  },

  /**
   * ISBN重複をチェックする
   * @param isbn - チェック対象のISBN
   * @returns チェック結果
   */
  async checkIsbn(isbn: string): Promise<IsbnCheckResponse> {
    const { data } = await apiClient.get<IsbnCheckResponse>('/books/check-isbn', {
      params: { isbn },
    })
    return data
  },
}
