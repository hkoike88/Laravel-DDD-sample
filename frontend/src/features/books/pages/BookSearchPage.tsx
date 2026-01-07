import { useState } from 'react'
import { useBookSearch } from '../hooks/useBookSearch'
import { BookSearchForm } from '../components/BookSearchForm'
import { BookSearchResults } from '../components/BookSearchResults'
import { Pagination } from '../components/Pagination'
import type { BookSearchParams } from '../types/book'

/**
 * 蔵書検索ページコンポーネント
 * 検索フォーム、ローディング表示、検索結果を統合
 */
export function BookSearchPage() {
  const [searchParams, setSearchParams] = useState<BookSearchParams>({})
  const { data, isLoading, isFetching, isError, error, refetch } = useBookSearch(searchParams)

  const handleSearch = (params: BookSearchParams) => {
    setSearchParams({ ...params, page: 1 })
  }

  const handlePageChange = (page: number) => {
    setSearchParams((prev) => ({ ...prev, page }))
  }

  return (
    <div className="min-h-screen bg-gray-100">
      <div className="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        <h1 className="text-3xl font-bold text-gray-900 mb-6">蔵書検索</h1>

        <div className="mb-6">
          <BookSearchForm onSearch={handleSearch} isLoading={isLoading || isFetching} />
        </div>

        {/* エラー表示 */}
        {isError && !isLoading && !isFetching && (
          <div className="bg-red-50 border border-red-200 rounded-lg p-6 mb-6">
            <div className="flex items-center mb-4">
              <svg
                className="h-6 w-6 text-red-600 mr-2"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
              >
                <path
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  strokeWidth={2}
                  d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                />
              </svg>
              <h3 className="text-lg font-medium text-red-800">検索中にエラーが発生しました</h3>
            </div>
            <p className="text-sm text-red-700 mb-4">
              {error instanceof Error ? error.message : 'ネットワークエラーが発生しました。'}
            </p>
            <button
              onClick={() => refetch()}
              className="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500"
            >
              再試行
            </button>
          </div>
        )}

        {/* ローディング表示 */}
        {(isLoading || isFetching) && (
          <div className="flex justify-center items-center py-12">
            <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
            <span className="ml-3 text-gray-600">検索中...</span>
          </div>
        )}

        {/* 検索結果件数表示 */}
        {data && !isLoading && !isFetching && (
          <div className="mb-4">
            <p className="text-sm text-gray-600">
              {data.meta.total > 0 ? (
                <>
                  <span className="font-semibold">
                    {(data.meta.page - 1) * data.meta.per_page + 1}〜
                    {Math.min(data.meta.page * data.meta.per_page, data.meta.total)}件目
                  </span>
                  {' / '}
                  <span className="font-semibold">{data.meta.total}</span>件中
                </>
              ) : (
                <>
                  検索結果: <span className="font-semibold">0</span>件
                </>
              )}
            </p>
          </div>
        )}

        {/* 検索結果テーブル */}
        {data && !isLoading && !isFetching && (
          <>
            <BookSearchResults books={data.data} />
            {data.data.length > 0 && (
              <Pagination meta={data.meta} onPageChange={handlePageChange} />
            )}
          </>
        )}
      </div>
    </div>
  )
}
