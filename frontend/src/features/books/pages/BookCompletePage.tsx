import { Link, useParams, useNavigate } from 'react-router-dom'
import { useQuery } from '@tanstack/react-query'
import { MainLayout } from '@/components/layout/MainLayout'
import { bookApi } from '../api/bookApi'
import { BookStatusBadge } from '../components/BookStatusBadge'

/**
 * 蔵書登録完了確認ページ
 *
 * 蔵書登録完了後に表示される確認画面。
 * 登録された蔵書の詳細と、続けて登録するためのリンクを提供。
 */
export function BookCompletePage() {
  const { id } = useParams<{ id: string }>()
  const navigate = useNavigate()

  const {
    data: book,
    isLoading,
    error,
  } = useQuery({
    queryKey: ['book', id],
    queryFn: () => bookApi.getById(id!),
    enabled: !!id,
  })

  if (isLoading) {
    return (
      <MainLayout>
        <div className="max-w-2xl mx-auto">
          <div className="bg-white p-6 rounded-lg shadow">
            <p className="text-gray-500">読み込み中...</p>
          </div>
        </div>
      </MainLayout>
    )
  }

  if (error || !book) {
    return (
      <MainLayout>
        <div className="max-w-2xl mx-auto">
          <div className="bg-red-50 p-6 rounded-lg shadow">
            <p className="text-red-600">蔵書情報の取得に失敗しました</p>
            <Link to="/books/new" className="mt-4 inline-block text-blue-600 hover:underline">
              新規登録に戻る
            </Link>
          </div>
        </div>
      </MainLayout>
    )
  }

  // 登録日時のフォーマット
  const formattedDate = book.registered_at
    ? new Date(book.registered_at).toLocaleString('ja-JP', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
      })
    : '-'

  return (
    <MainLayout>
      <div className="max-w-2xl mx-auto">
        {/* 成功メッセージ */}
        <div className="mb-6 p-4 bg-green-50 border border-green-200 rounded-md">
          <div className="flex items-center">
            <svg
              className="h-5 w-5 text-green-400 mr-2"
              xmlns="http://www.w3.org/2000/svg"
              viewBox="0 0 20 20"
              fill="currentColor"
            >
              <path
                fillRule="evenodd"
                d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z"
                clipRule="evenodd"
              />
            </svg>
            <p className="text-green-800 font-medium">蔵書を登録しました</p>
          </div>
        </div>

        {/* パンくずリスト */}
        <nav className="mb-4 text-sm text-gray-500">
          <Link to="/dashboard" className="hover:text-blue-600">
            ダッシュボード
          </Link>
          <span className="mx-2">/</span>
          <Link to="/books/new" className="hover:text-blue-600">
            蔵書登録
          </Link>
          <span className="mx-2">/</span>
          <span className="text-gray-700">登録完了</span>
        </nav>

        {/* ページヘッダー */}
        <div className="mb-6">
          <h1 className="text-2xl font-bold text-gray-900">登録完了</h1>
          <p className="mt-1 text-sm text-gray-500">以下の蔵書が登録されました</p>
        </div>

        {/* 登録内容 */}
        <div className="bg-white p-6 rounded-lg shadow">
          <dl className="space-y-4">
            <div className="grid grid-cols-3 gap-4">
              <dt className="text-sm font-medium text-gray-500">蔵書ID</dt>
              <dd className="col-span-2 text-sm text-gray-900 font-mono">{book.id}</dd>
            </div>
            <div className="grid grid-cols-3 gap-4">
              <dt className="text-sm font-medium text-gray-500">タイトル</dt>
              <dd className="col-span-2 text-sm text-gray-900">{book.title}</dd>
            </div>
            {book.author && (
              <div className="grid grid-cols-3 gap-4">
                <dt className="text-sm font-medium text-gray-500">著者</dt>
                <dd className="col-span-2 text-sm text-gray-900">{book.author}</dd>
              </div>
            )}
            {book.isbn && (
              <div className="grid grid-cols-3 gap-4">
                <dt className="text-sm font-medium text-gray-500">ISBN</dt>
                <dd className="col-span-2 text-sm text-gray-900 font-mono">{book.isbn}</dd>
              </div>
            )}
            {book.publisher && (
              <div className="grid grid-cols-3 gap-4">
                <dt className="text-sm font-medium text-gray-500">出版社</dt>
                <dd className="col-span-2 text-sm text-gray-900">{book.publisher}</dd>
              </div>
            )}
            {book.published_year && (
              <div className="grid grid-cols-3 gap-4">
                <dt className="text-sm font-medium text-gray-500">出版年</dt>
                <dd className="col-span-2 text-sm text-gray-900">{book.published_year}年</dd>
              </div>
            )}
            {book.genre && (
              <div className="grid grid-cols-3 gap-4">
                <dt className="text-sm font-medium text-gray-500">ジャンル</dt>
                <dd className="col-span-2 text-sm text-gray-900">{book.genre}</dd>
              </div>
            )}
            <div className="grid grid-cols-3 gap-4">
              <dt className="text-sm font-medium text-gray-500">状態</dt>
              <dd className="col-span-2">
                <BookStatusBadge status={book.status} />
              </dd>
            </div>
            <div className="grid grid-cols-3 gap-4">
              <dt className="text-sm font-medium text-gray-500">登録日時</dt>
              <dd className="col-span-2 text-sm text-gray-900">{formattedDate}</dd>
            </div>
          </dl>
        </div>

        {/* アクションボタン */}
        <div className="mt-6 flex justify-between">
          <Link
            to="/books"
            className="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
          >
            蔵書一覧へ
          </Link>
          <button
            onClick={() => navigate('/books/new')}
            className="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700"
          >
            続けて登録
          </button>
        </div>
      </div>
    </MainLayout>
  )
}
