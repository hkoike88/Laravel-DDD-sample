import { useState, useCallback } from 'react'
import { useForm, Controller } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import {
  createBookSchema,
  toCreateBookInput,
  type CreateBookFormInput,
} from '../schemas/bookRegistration'
import { useBookRegistration } from '../hooks/useBookRegistration'
import { useIsbnCheck } from '../hooks/useIsbnCheck'
import { IsbnDuplicateWarning } from './IsbnDuplicateWarning'

/**
 * 蔵書登録フォームコンポーネント
 *
 * React Hook Form + Zod によるバリデーションを提供。
 * 仕様 FR-003〜FR-006, FR-011〜FR-013 に準拠。
 * ISBN重複チェック機能を含む。
 */
export function BookRegistrationForm() {
  const { register: registerBook, isLoading, error } = useBookRegistration()
  const {
    result: isbnCheckResult,
    isChecking: isIsbnChecking,
    checkIsbn,
    reset: resetIsbnCheck,
  } = useIsbnCheck()
  const [isbnDuplicateAcknowledged, setIsbnDuplicateAcknowledged] = useState(false)

  const {
    register,
    control,
    handleSubmit,
    formState: { errors },
  } = useForm<CreateBookFormInput>({
    resolver: zodResolver(createBookSchema),
    defaultValues: {
      title: '',
      author: '',
      isbn: '',
      publisher: '',
      published_year: null,
      genre: '',
    },
  })

  const onSubmit = (data: CreateBookFormInput) => {
    // ISBN重複があり、まだ確認されていない場合は送信をブロック
    if (isbnCheckResult?.exists && !isbnDuplicateAcknowledged) {
      return
    }

    const input = toCreateBookInput(data)
    registerBook(input)
  }

  // ISBNフィールドのblurハンドラ
  const handleIsbnBlur = useCallback(
    (e: React.FocusEvent<HTMLInputElement>) => {
      const isbn = e.target.value
      if (isbn) {
        setIsbnDuplicateAcknowledged(false) // ISBNが変更されたらリセット
        checkIsbn(isbn)
      } else {
        resetIsbnCheck()
      }
    },
    [checkIsbn, resetIsbnCheck]
  )

  // 重複警告で続行を選択
  const handleDuplicateContinue = useCallback(() => {
    setIsbnDuplicateAcknowledged(true)
  }, [])

  // 重複警告で中止を選択（ISBNフィールドをクリア）
  const handleDuplicateCancel = useCallback(() => {
    resetIsbnCheck()
    setIsbnDuplicateAcknowledged(false)
  }, [resetIsbnCheck])

  return (
    <form onSubmit={handleSubmit(onSubmit)} className="space-y-6">
      {/* APIエラーメッセージ */}
      {error && (
        <div className="p-4 bg-red-50 border border-red-200 rounded-md">
          <p className="text-sm text-red-800">登録に失敗しました: {error.message}</p>
        </div>
      )}

      {/* タイトル（必須） */}
      <div>
        <label htmlFor="title" className="block text-sm font-medium text-gray-700 mb-1">
          タイトル <span className="text-red-500">*</span>
        </label>
        <input
          id="title"
          type="text"
          {...register('title')}
          className={`w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 ${
            errors.title ? 'border-red-500' : 'border-gray-300'
          }`}
          placeholder="書籍のタイトルを入力"
          aria-invalid={errors.title ? 'true' : 'false'}
          aria-describedby={errors.title ? 'title-error' : undefined}
        />
        {errors.title && (
          <p id="title-error" className="mt-1 text-sm text-red-600" role="alert">
            {errors.title.message}
          </p>
        )}
      </div>

      {/* 著者名 */}
      <div>
        <label htmlFor="author" className="block text-sm font-medium text-gray-700 mb-1">
          著者名
        </label>
        <input
          id="author"
          type="text"
          {...register('author')}
          className={`w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 ${
            errors.author ? 'border-red-500' : 'border-gray-300'
          }`}
          placeholder="著者名を入力"
        />
        {errors.author && (
          <p className="mt-1 text-sm text-red-600" role="alert">
            {errors.author.message}
          </p>
        )}
      </div>

      {/* ISBN */}
      <div>
        <label htmlFor="isbn" className="block text-sm font-medium text-gray-700 mb-1">
          ISBN
          {isIsbnChecking && <span className="ml-2 text-xs text-gray-500">チェック中...</span>}
        </label>
        <input
          id="isbn"
          type="text"
          {...register('isbn', { onBlur: handleIsbnBlur })}
          className={`w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 ${
            errors.isbn ? 'border-red-500' : 'border-gray-300'
          }`}
          placeholder="ISBNを入力（ハイフンなし）"
        />
        {errors.isbn && (
          <p className="mt-1 text-sm text-red-600" role="alert">
            {errors.isbn.message}
          </p>
        )}
        <p className="mt-1 text-xs text-gray-500">
          ISBN-10（10桁）または ISBN-13（13桁）をハイフンなしで入力
        </p>

        {/* ISBN重複警告 */}
        {isbnCheckResult?.exists && !isbnDuplicateAcknowledged && (
          <div className="mt-3">
            <IsbnDuplicateWarning
              count={isbnCheckResult.count}
              onContinue={handleDuplicateContinue}
              onCancel={handleDuplicateCancel}
            />
          </div>
        )}
        {/* 重複確認済み表示 */}
        {isbnCheckResult?.exists && isbnDuplicateAcknowledged && (
          <p className="mt-2 text-sm text-green-600">
            複本として登録する準備ができました（既存: {isbnCheckResult.count}冊）
          </p>
        )}
      </div>

      {/* 出版社 */}
      <div>
        <label htmlFor="publisher" className="block text-sm font-medium text-gray-700 mb-1">
          出版社
        </label>
        <input
          id="publisher"
          type="text"
          {...register('publisher')}
          className={`w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 ${
            errors.publisher ? 'border-red-500' : 'border-gray-300'
          }`}
          placeholder="出版社名を入力"
        />
        {errors.publisher && (
          <p className="mt-1 text-sm text-red-600" role="alert">
            {errors.publisher.message}
          </p>
        )}
      </div>

      {/* 出版年 */}
      <div>
        <label htmlFor="published_year" className="block text-sm font-medium text-gray-700 mb-1">
          出版年
        </label>
        <Controller
          name="published_year"
          control={control}
          render={({ field }) => (
            <input
              id="published_year"
              type="number"
              {...field}
              value={field.value ?? ''}
              onChange={(e) => {
                const value = e.target.value
                field.onChange(value === '' ? null : parseInt(value, 10))
              }}
              className={`w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 ${
                errors.published_year ? 'border-red-500' : 'border-gray-300'
              }`}
              placeholder="出版年（西暦4桁）"
              min={1000}
              max={new Date().getFullYear() + 1}
            />
          )}
        />
        {errors.published_year && (
          <p className="mt-1 text-sm text-red-600" role="alert">
            {errors.published_year.message}
          </p>
        )}
      </div>

      {/* ジャンル */}
      <div>
        <label htmlFor="genre" className="block text-sm font-medium text-gray-700 mb-1">
          ジャンル
        </label>
        <input
          id="genre"
          type="text"
          {...register('genre')}
          className={`w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 ${
            errors.genre ? 'border-red-500' : 'border-gray-300'
          }`}
          placeholder="ジャンルを入力"
        />
        {errors.genre && (
          <p className="mt-1 text-sm text-red-600" role="alert">
            {errors.genre.message}
          </p>
        )}
      </div>

      {/* 送信ボタン */}
      <div className="flex justify-end space-x-4">
        <button
          type="submit"
          disabled={
            isLoading || isIsbnChecking || (isbnCheckResult?.exists && !isbnDuplicateAcknowledged)
          }
          className="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          {isLoading ? '登録中...' : '登録'}
        </button>
      </div>
    </form>
  )
}
