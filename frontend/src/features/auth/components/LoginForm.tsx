/**
 * ログインフォームコンポーネント
 *
 * メールアドレスとパスワードの入力フォーム。
 * React Hook Form と Zod によるバリデーションを使用。
 */

import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { loginSchema, type LoginFormValues } from '../schemas/loginSchema'

/**
 * ログインフォームの Props
 */
interface LoginFormProps {
  /** フォーム送信時のコールバック */
  onSubmit: (data: LoginFormValues) => void
  /** ローディング状態 */
  isLoading?: boolean
  /** API エラーメッセージ */
  apiError?: string | null
}

/**
 * ログインフォームコンポーネント
 *
 * @param props - コンポーネントのプロパティ
 *
 * @example
 * <LoginForm
 *   onSubmit={(data) => login(data)}
 *   isLoading={isPending}
 *   apiError={error?.message}
 * />
 */
export function LoginForm({ onSubmit, isLoading = false, apiError }: LoginFormProps) {
  const {
    register,
    handleSubmit,
    formState: { errors },
  } = useForm<LoginFormValues>({
    resolver: zodResolver(loginSchema),
    defaultValues: {
      email: '',
      password: '',
    },
  })

  return (
    <form onSubmit={handleSubmit(onSubmit)} className="space-y-6" noValidate>
      {/* API エラー表示 */}
      {apiError && (
        <div role="alert" className="rounded-md bg-red-50 p-4 text-sm text-red-700">
          {apiError}
        </div>
      )}

      {/* メールアドレス入力 */}
      <div>
        <label htmlFor="email" className="block text-sm font-medium text-gray-700">
          メールアドレス
        </label>
        <input
          id="email"
          type="email"
          autoComplete="email"
          disabled={isLoading}
          aria-invalid={errors.email ? 'true' : 'false'}
          aria-describedby={errors.email ? 'email-error' : undefined}
          className={`mt-1 block w-full rounded-md border px-3 py-2 shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 ${
            errors.email
              ? 'border-red-500 focus:border-red-500 focus:ring-red-500'
              : 'border-gray-300 focus:border-blue-500'
          } disabled:bg-gray-100 disabled:text-gray-500`}
          {...register('email')}
        />
        {errors.email && (
          <p id="email-error" className="mt-1 text-sm text-red-600">
            {errors.email.message}
          </p>
        )}
      </div>

      {/* パスワード入力 */}
      <div>
        <label htmlFor="password" className="block text-sm font-medium text-gray-700">
          パスワード
        </label>
        <input
          id="password"
          type="password"
          autoComplete="current-password"
          disabled={isLoading}
          aria-invalid={errors.password ? 'true' : 'false'}
          aria-describedby={errors.password ? 'password-error' : undefined}
          aria-label="パスワード"
          className={`mt-1 block w-full rounded-md border px-3 py-2 shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 ${
            errors.password
              ? 'border-red-500 focus:border-red-500 focus:ring-red-500'
              : 'border-gray-300 focus:border-blue-500'
          } disabled:bg-gray-100 disabled:text-gray-500`}
          {...register('password')}
        />
        {errors.password && (
          <p id="password-error" className="mt-1 text-sm text-red-600">
            {errors.password.message}
          </p>
        )}
      </div>

      {/* 送信ボタン */}
      <button
        type="submit"
        disabled={isLoading}
        className="w-full rounded-md bg-blue-600 px-4 py-2 text-white font-medium hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:bg-blue-400 disabled:cursor-not-allowed"
      >
        {isLoading ? 'ログイン中...' : 'ログイン'}
      </button>
    </form>
  )
}
