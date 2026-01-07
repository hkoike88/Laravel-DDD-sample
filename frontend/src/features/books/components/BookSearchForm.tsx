import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import type { BookSearchParams } from '../types/book'

/**
 * 検索フォームのバリデーションスキーマ
 */
const bookSearchSchema = z.object({
  title: z.string().max(255).optional(),
  author: z.string().max(255).optional(),
  isbn: z.string().max(17).optional(),
})

type BookSearchFormData = z.infer<typeof bookSearchSchema>

interface BookSearchFormProps {
  /** 検索実行時のコールバック */
  onSearch: (params: BookSearchParams) => void
  /** ローディング中フラグ */
  isLoading?: boolean
}

/**
 * 蔵書検索フォームコンポーネント
 * タイトルと著者名での検索機能を提供
 */
export function BookSearchForm({ onSearch, isLoading }: BookSearchFormProps) {
  const {
    register,
    handleSubmit,
    formState: { errors },
  } = useForm<BookSearchFormData>({
    resolver: zodResolver(bookSearchSchema),
    defaultValues: {
      title: '',
      author: '',
      isbn: '',
    },
  })

  const onSubmit = (data: BookSearchFormData) => {
    const params: BookSearchParams = {}
    if (data.title?.trim()) params.title = data.title.trim()
    if (data.author?.trim()) params.author = data.author.trim()
    if (data.isbn?.trim()) params.isbn = data.isbn.trim()
    // 検索条件なしの場合は全件検索として処理
    onSearch(params)
  }

  return (
    <form onSubmit={handleSubmit(onSubmit)} className="bg-white p-6 rounded-lg shadow">
      <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
          <label htmlFor="title" className="block text-sm font-medium text-gray-700 mb-1">
            タイトル
          </label>
          <input
            id="title"
            type="text"
            {...register('title')}
            className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            placeholder="タイトルで検索..."
          />
          {errors.title && <p className="mt-1 text-sm text-red-600">{errors.title.message}</p>}
        </div>

        <div>
          <label htmlFor="author" className="block text-sm font-medium text-gray-700 mb-1">
            著者
          </label>
          <input
            id="author"
            type="text"
            {...register('author')}
            className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            placeholder="著者名で検索..."
          />
          {errors.author && <p className="mt-1 text-sm text-red-600">{errors.author.message}</p>}
        </div>

        <div>
          <label htmlFor="isbn" className="block text-sm font-medium text-gray-700 mb-1">
            ISBN
          </label>
          <input
            id="isbn"
            type="text"
            {...register('isbn')}
            className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            placeholder="ISBN（13桁または10桁）"
          />
          {errors.isbn && <p className="mt-1 text-sm text-red-600">{errors.isbn.message}</p>}
        </div>

        <div className="flex items-end">
          <button
            type="submit"
            disabled={isLoading}
            className="w-full px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            {isLoading ? '検索中...' : '🔍 検索'}
          </button>
        </div>
      </div>
    </form>
  )
}
