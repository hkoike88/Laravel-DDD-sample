import type { PaginationMeta } from '../types/book'

interface PaginationProps {
  /** ページネーション情報 */
  meta: PaginationMeta
  /** ページ変更時のコールバック */
  onPageChange: (page: number) => void
}

/**
 * ページネーションコンポーネント
 * 検索結果のページ移動機能を提供
 */
export function Pagination({ meta, onPageChange }: PaginationProps) {
  const { page, last_page } = meta

  if (last_page <= 1) {
    return null
  }

  const pages = generatePageNumbers(page, last_page)

  return (
    <nav className="flex items-center justify-center space-x-1 mt-6">
      <button
        onClick={() => onPageChange(page - 1)}
        disabled={page <= 1}
        className="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
        aria-label="前のページ"
      >
        &lt;
      </button>

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
              p === page
                ? 'bg-blue-600 text-white'
                : 'text-gray-700 bg-white border border-gray-300 hover:bg-gray-50'
            }`}
            aria-current={p === page ? 'page' : undefined}
          >
            {p}
          </button>
        )
      )}

      <button
        onClick={() => onPageChange(page + 1)}
        disabled={page >= last_page}
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
