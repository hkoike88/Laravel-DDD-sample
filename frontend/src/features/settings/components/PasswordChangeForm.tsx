import { useState, useEffect, useRef } from 'react'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import { usePasswordChange, type PasswordChangeRequest } from '../hooks/usePasswordChange'

/**
 * パスワード変更フォームバリデーションスキーマ
 */
const passwordChangeSchema = z
  .object({
    current_password: z.string().min(1, '現在のパスワードを入力してください'),
    new_password: z
      .string()
      .min(12, 'パスワードは12文字以上で入力してください')
      .regex(/[A-Z]/, 'パスワードには大文字を含めてください')
      .regex(/[a-z]/, 'パスワードには小文字を含めてください')
      .regex(/[0-9]/, 'パスワードには数字を含めてください')
      .regex(/[!@#$%^&*(),.?":{}|<>]/, 'パスワードには記号を含めてください'),
    new_password_confirmation: z.string().min(1, '確認用パスワードを入力してください'),
  })
  .refine((data) => data.new_password === data.new_password_confirmation, {
    message: 'パスワードが一致しません',
    path: ['new_password_confirmation'],
  })

type PasswordChangeFormData = z.infer<typeof passwordChangeSchema>

/**
 * パスワード変更フォームコンポーネント
 *
 * 認証済み職員のパスワード変更を行うフォーム。
 * パスワードポリシーに準拠したバリデーションを実装。
 *
 * @feature 001-security-preparation
 */
export function PasswordChangeForm() {
  const { changePassword, isLoading, isSuccess, isError, errorMessage, fieldErrors, reset } =
    usePasswordChange()

  const [showSuccess, setShowSuccess] = useState(false)

  const {
    register,
    handleSubmit,
    formState: { errors },
    setError,
    reset: resetForm,
  } = useForm<PasswordChangeFormData>({
    resolver: zodResolver(passwordChangeSchema),
  })

  // サーバーエラーをフォームに反映
  useEffect(() => {
    if (fieldErrors.current_password) {
      setError('current_password', { message: fieldErrors.current_password[0] })
    }
    if (fieldErrors.new_password) {
      setError('new_password', { message: fieldErrors.new_password[0] })
    }
  }, [fieldErrors, setError])

  // 前回の成功状態を追跡
  const prevIsSuccessRef = useRef(false)

  // 成功時の処理（成功状態の変化を検出）
  useEffect(() => {
    // false → true への変化時のみ処理
    if (isSuccess && !prevIsSuccessRef.current) {
      // eslint-disable-next-line react-hooks/set-state-in-effect -- 成功通知表示のための適切な使用
      setShowSuccess(true)
      resetForm()
      // 3秒後に成功メッセージを非表示
      const timer = setTimeout(() => {
        setShowSuccess(false)
        reset()
      }, 3000)
      prevIsSuccessRef.current = isSuccess
      return () => clearTimeout(timer)
    }
    prevIsSuccessRef.current = isSuccess
  }, [isSuccess, resetForm, reset])

  const onSubmit = (data: PasswordChangeFormData) => {
    changePassword(data as PasswordChangeRequest)
  }

  return (
    <div className="bg-white shadow rounded-lg overflow-hidden">
      <div className="px-4 py-3 border-b border-gray-200">
        <h3 className="text-lg font-medium text-gray-900">パスワード変更</h3>
      </div>

      <form onSubmit={handleSubmit(onSubmit)} className="p-4 space-y-4">
        {/* 成功メッセージ */}
        {showSuccess && (
          <div className="p-3 bg-green-50 border border-green-200 rounded text-green-800 text-sm">
            パスワードを変更しました
          </div>
        )}

        {/* エラーメッセージ */}
        {isError && errorMessage && (
          <div className="p-3 bg-red-50 border border-red-200 rounded text-red-800 text-sm">
            {errorMessage}
          </div>
        )}

        {/* 現在のパスワード */}
        <div>
          <label htmlFor="current_password" className="block text-sm font-medium text-gray-700">
            現在のパスワード
          </label>
          <input
            type="password"
            id="current_password"
            autoComplete="current-password"
            {...register('current_password')}
            className={`mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm ${
              errors.current_password ? 'border-red-500' : ''
            }`}
          />
          {errors.current_password && (
            <p className="mt-1 text-sm text-red-600">{errors.current_password.message}</p>
          )}
        </div>

        {/* 新しいパスワード */}
        <div>
          <label htmlFor="new_password" className="block text-sm font-medium text-gray-700">
            新しいパスワード
          </label>
          <input
            type="password"
            id="new_password"
            autoComplete="new-password"
            {...register('new_password')}
            className={`mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm ${
              errors.new_password ? 'border-red-500' : ''
            }`}
          />
          {errors.new_password && (
            <p className="mt-1 text-sm text-red-600">{errors.new_password.message}</p>
          )}
          <p className="mt-1 text-xs text-gray-500">12文字以上、大文字・小文字・数字・記号を含む</p>
        </div>

        {/* 新しいパスワード（確認） */}
        <div>
          <label
            htmlFor="new_password_confirmation"
            className="block text-sm font-medium text-gray-700"
          >
            新しいパスワード（確認）
          </label>
          <input
            type="password"
            id="new_password_confirmation"
            autoComplete="new-password"
            {...register('new_password_confirmation')}
            className={`mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm ${
              errors.new_password_confirmation ? 'border-red-500' : ''
            }`}
          />
          {errors.new_password_confirmation && (
            <p className="mt-1 text-sm text-red-600">{errors.new_password_confirmation.message}</p>
          )}
        </div>

        {/* 送信ボタン */}
        <div className="pt-2">
          <button
            type="submit"
            disabled={isLoading}
            className="w-full px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            {isLoading ? '変更中...' : 'パスワードを変更'}
          </button>
        </div>
      </form>
    </div>
  )
}
