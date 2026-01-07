import type { Book } from '../types/book'
import { BookStatusBadge } from './BookStatusBadge'

interface BookSearchResultsProps {
  /** 検索結果の蔵書リスト */
  books: Book[]
}

/**
 * 蔵書検索結果テーブルコンポーネント
 * 検索結果をテーブル形式で表示
 */
export function BookSearchResults({ books }: BookSearchResultsProps) {
  if (books.length === 0) {
    return (
      <div className="bg-white rounded-lg shadow p-8 text-center">
        <div className="text-gray-500 mb-4">
          <svg
            className="mx-auto h-12 w-12 text-gray-400"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
            aria-hidden="true"
          >
            <path
              strokeLinecap="round"
              strokeLinejoin="round"
              strokeWidth={2}
              d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
            />
          </svg>
        </div>
        <h3 className="text-lg font-medium text-gray-900 mb-2">
          検索条件に一致する蔵書が見つかりませんでした
        </h3>
        <p className="text-sm text-gray-500">
          検索条件を変更して再度お試しください。
          <br />
          タイトルや著者名の一部を入力するか、ISBNで検索してみてください。
        </p>
      </div>
    )
  }

  return (
    <div className="bg-white rounded-lg shadow overflow-hidden">
      <table className="min-w-full divide-y divide-gray-200">
        <thead className="bg-gray-50">
          <tr>
            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              タイトル
            </th>
            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              著者
            </th>
            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              出版社
            </th>
            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              状態
            </th>
          </tr>
        </thead>
        <tbody className="bg-white divide-y divide-gray-200">
          {books.map((book) => (
            <tr key={book.id} className="hover:bg-gray-50">
              <td className="px-6 py-4 whitespace-nowrap">
                <div className="text-sm font-medium text-gray-900">{book.title}</div>
                {book.isbn && <div className="text-sm text-gray-500">ISBN: {book.isbn}</div>}
              </td>
              <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {book.author || '-'}
              </td>
              <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {book.publisher || '-'}
              </td>
              <td className="px-6 py-4 whitespace-nowrap">
                <BookStatusBadge status={book.status} />
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  )
}
