/**
 * 蔵書の貸出状態
 */
export type BookStatus = 'available' | 'borrowed' | 'reserved'

/**
 * 蔵書エンティティ
 */
export interface Book {
  /** ULID形式の蔵書ID */
  id: string
  /** 書籍タイトル */
  title: string
  /** 著者名（nullable） */
  author: string | null
  /** ISBN（nullable、ハイフンなし） */
  isbn: string | null
  /** 出版社名（nullable） */
  publisher: string | null
  /** 出版年（nullable） */
  published_year: number | null
  /** ジャンル（nullable） */
  genre: string | null
  /** 貸出状態 */
  status: BookStatus
  /** 登録者ID（職員ULID、nullable） */
  registered_by: string | null
  /** 登録日時（ISO 8601形式、nullable） */
  registered_at: string | null
}

/**
 * 蔵書検索パラメータ
 */
export interface BookSearchParams {
  /** タイトル検索キーワード（部分一致） */
  title?: string
  /** 著者名検索キーワード（部分一致） */
  author?: string
  /** ISBN（完全一致） */
  isbn?: string
  /** ページ番号（1始まり） */
  page?: number
  /** 1ページあたりの件数 */
  per_page?: number
}

/**
 * ページネーションメタ情報
 */
export interface PaginationMeta {
  /** 総件数 */
  total: number
  /** 現在のページ番号 */
  page: number
  /** 1ページあたりの件数 */
  per_page: number
  /** 総ページ数 */
  last_page: number
}

/**
 * 蔵書検索APIレスポンス
 */
export interface BookSearchResponse {
  /** 検索結果の蔵書リスト */
  data: Book[]
  /** ページネーション情報 */
  meta: PaginationMeta
}

/**
 * 蔵書登録入力データ
 */
export interface CreateBookInput {
  /** 書籍タイトル（必須、1〜200文字） */
  title: string
  /** 著者名（任意、最大100文字） */
  author?: string
  /** ISBN（任意、ハイフンなし ISBN-10 または ISBN-13） */
  isbn?: string
  /** 出版社名（任意、最大100文字） */
  publisher?: string
  /** 出版年（任意、1000〜現在年+1） */
  published_year?: number
  /** ジャンル（任意、最大100文字） */
  genre?: string
}

/**
 * 蔵書登録APIレスポンス
 */
export interface CreateBookResponse {
  /** 登録された蔵書データ */
  data: Book
}

/**
 * ISBN重複チェックAPIレスポンス
 */
export interface IsbnCheckResponse {
  /** 同一ISBNの蔵書が存在するか */
  exists: boolean
  /** 同一ISBNの蔵書数 */
  count: number
}
