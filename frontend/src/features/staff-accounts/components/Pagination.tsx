/**
 * ページネーションコンポーネント
 *
 * 職員一覧のページ移動機能を提供する。
 *
 * @feature EPIC-003-staff-account-create
 */

import type { PaginationMeta } from '../types/staffAccount'

/**
 * Pagination コンポーネントの Props
 */
interface PaginationProps {
  /** ページネーション情報 */
  meta: PaginationMeta
  /** ページ変更時のコールバック */
  onPageChange: (page: number) => void
}

/**
 * ページネーションコンポーネント
 *
 * @param props - コンポーネントプロパティ
 * @returns ページネーションUI
 *
 * @example
 * <Pagination
 *   meta={{ currentPage: 1, lastPage: 5, perPage: 20, total: 100, from: 1, to: 20 }}
 *   onPageChange={(page) => setPage(page)}
 * />
 */
export function Pagination({ meta, onPageChange }: PaginationProps) {
  const { currentPage, lastPage } = meta

  if (lastPage <= 1) {
    return null
  }

  const pages = generatePageNumbers(currentPage, lastPage)

  return (
    <nav className="flex items-center justify-center space-x-1 mt-6" aria-label="ページネーション">
      {/* 前へボタン */}
      <button
        onClick={() => onPageChange(currentPage - 1)}
        disabled={currentPage <= 1}
        className="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
        aria-label="前のページ"
      >
        &lt;
      </button>

      {/* ページ番号 */}
      {pages.map((p, index) =>
        p === '...' ? (
          <span key={`ellipsis-${index}`} className="px-3 py-2 text-sm text-gray-500">
            ...
          </span>
        ) : (
          <button
            key={p}
            onClick={() => onPageChange(p as number)}
            className={`px-3 py-2 text-sm font-medium rounded-md ${
              p === currentPage
                ? 'bg-blue-600 text-white'
                : 'text-gray-700 bg-white border border-gray-300 hover:bg-gray-50'
            }`}
            aria-current={p === currentPage ? 'page' : undefined}
          >
            {p}
          </button>
        )
      )}

      {/* 次へボタン */}
      <button
        onClick={() => onPageChange(currentPage + 1)}
        disabled={currentPage >= lastPage}
        className="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
        aria-label="次のページ"
      >
        &gt;
      </button>
    </nav>
  )
}

/**
 * 表示するページ番号を生成
 * 現在ページの前後を表示し、離れた部分は省略記号で表示
 *
 * @param current - 現在のページ
 * @param total - 総ページ数
 * @returns ページ番号の配列（省略記号を含む）
 */
function generatePageNumbers(current: number, total: number): (number | '...')[] {
  const delta = 2
  const range: number[] = []

  for (let i = 1; i <= total; i++) {
    if (i === 1 || i === total || (i >= current - delta && i <= current + delta)) {
      range.push(i)
    }
  }

  const result: (number | '...')[] = []
  let prev = 0

  for (const i of range) {
    if (prev && i - prev > 1) {
      result.push('...')
    }
    result.push(i)
    prev = i
  }

  return result
}
