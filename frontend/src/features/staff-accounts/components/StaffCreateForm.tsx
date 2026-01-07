/**
 * 職員作成フォームコンポーネント
 *
 * 職員の氏名、メールアドレス、権限の入力フォーム。
 * React Hook Form と Zod によるバリデーションを使用。
 *
 * @feature EPIC-003-staff-account-create
 */

import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { createStaffSchema, type CreateStaffFormValues } from '../schemas/createStaffSchema'

/**
 * 職員作成フォームの Props
 */
interface StaffCreateFormProps {
  /** フォーム送信時のコールバック */
  onSubmit: (data: CreateStaffFormValues) => void
  /** ローディング状態 */
  isLoading?: boolean
  /** API エラーメッセージ */
  apiError?: string | null
  /** フィールド別エラー（サーバーサイドバリデーション） */
  fieldErrors?: Record<string, string>
}

/**
 * 職員作成フォームコンポーネント
 *
 * @param props - コンポーネントのプロパティ
 *
 * @example
 * <StaffCreateForm
 *   onSubmit={(data) => createStaff(data)}
 *   isLoading={isPending}
 *   apiError={error?.message}
 * />
 */
export function StaffCreateForm({
  onSubmit,
  isLoading = false,
  apiError,
  fieldErrors,
}: StaffCreateFormProps) {
  const {
    register,
    handleSubmit,
    formState: { errors },
  } = useForm<CreateStaffFormValues>({
    resolver: zodResolver(createStaffSchema),
    defaultValues: {
      name: '',
      email: '',
      role: 'staff',
    },
  })

  /**
   * フィールドエラーを取得（クライアント側バリデーション優先）
   */
  const getFieldError = (field: keyof CreateStaffFormValues): string | undefined => {
    return errors[field]?.message || fieldErrors?.[field]
  }

  return (
    <form onSubmit={handleSubmit(onSubmit)} className="space-y-6" noValidate>
      {/* API エラー表示 */}
      {apiError && (
        <div role="alert" className="rounded-md bg-red-50 p-4 text-sm text-red-700">
          {apiError}
        </div>
      )}

      {/* 氏名入力 */}
      <div>
        <label htmlFor="name" className="block text-sm font-medium text-gray-700">
          氏名 <span className="text-red-500">*</span>
        </label>
        <input
          id="name"
          type="text"
          autoComplete="name"
          disabled={isLoading}
          aria-invalid={getFieldError('name') ? 'true' : 'false'}
          aria-describedby={getFieldError('name') ? 'name-error' : undefined}
          className={`mt-1 block w-full rounded-md border px-3 py-2 shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 ${
            getFieldError('name')
              ? 'border-red-500 focus:border-red-500 focus:ring-red-500'
              : 'border-gray-300 focus:border-blue-500'
          } disabled:bg-gray-100 disabled:text-gray-500`}
          placeholder="山田 太郎"
          {...register('name')}
        />
        {getFieldError('name') && (
          <p id="name-error" className="mt-1 text-sm text-red-600">
            {getFieldError('name')}
          </p>
        )}
      </div>

      {/* メールアドレス入力 */}
      <div>
        <label htmlFor="email" className="block text-sm font-medium text-gray-700">
          メールアドレス <span className="text-red-500">*</span>
        </label>
        <input
          id="email"
          type="email"
          autoComplete="email"
          disabled={isLoading}
          aria-invalid={getFieldError('email') ? 'true' : 'false'}
          aria-describedby={getFieldError('email') ? 'email-error' : undefined}
          className={`mt-1 block w-full rounded-md border px-3 py-2 shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 ${
            getFieldError('email')
              ? 'border-red-500 focus:border-red-500 focus:ring-red-500'
              : 'border-gray-300 focus:border-blue-500'
          } disabled:bg-gray-100 disabled:text-gray-500`}
          placeholder="example@example.com"
          {...register('email')}
        />
        {getFieldError('email') && (
          <p id="email-error" className="mt-1 text-sm text-red-600">
            {getFieldError('email')}
          </p>
        )}
      </div>

      {/* 権限選択 */}
      <div>
        <label htmlFor="role" className="block text-sm font-medium text-gray-700">
          権限 <span className="text-red-500">*</span>
        </label>
        <select
          id="role"
          disabled={isLoading}
          aria-invalid={getFieldError('role') ? 'true' : 'false'}
          aria-describedby={getFieldError('role') ? 'role-error' : undefined}
          className={`mt-1 block w-full rounded-md border px-3 py-2 shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 ${
            getFieldError('role')
              ? 'border-red-500 focus:border-red-500 focus:ring-red-500'
              : 'border-gray-300 focus:border-blue-500'
          } disabled:bg-gray-100 disabled:text-gray-500`}
          {...register('role')}
        >
          <option value="staff">一般職員</option>
          <option value="admin">管理者</option>
        </select>
        {getFieldError('role') && (
          <p id="role-error" className="mt-1 text-sm text-red-600">
            {getFieldError('role')}
          </p>
        )}
      </div>

      {/* 送信ボタン */}
      <div className="flex justify-end gap-3">
        <button
          type="submit"
          disabled={isLoading}
          className="rounded-md bg-blue-600 px-6 py-2 text-white font-medium hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:bg-blue-400 disabled:cursor-not-allowed"
        >
          {isLoading ? '作成中...' : '作成'}
        </button>
      </div>
    </form>
  )
}
