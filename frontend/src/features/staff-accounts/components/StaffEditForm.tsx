/**
 * 職員編集フォームコンポーネント
 *
 * 職員の氏名、メールアドレス、権限の編集フォーム。
 * React Hook Form と Zod によるバリデーションを使用。
 * 自己権限変更防止、競合エラーハンドリングを含む。
 *
 * @feature EPIC-004-staff-account-edit
 */

import { useEffect } from 'react'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { updateStaffSchema, type UpdateStaffFormValues } from '../schemas/updateStaffSchema'
import type { StaffDetail } from '../types/staffAccount'

/**
 * 職員編集フォームの Props
 */
interface StaffEditFormProps {
  /** 編集対象の職員データ */
  staff: StaffDetail
  /** フォーム送信時のコールバック */
  onSubmit: (data: UpdateStaffFormValues) => void
  /** キャンセル時のコールバック */
  onCancel: () => void
  /** パスワードリセット時のコールバック */
  onResetPassword: () => void
  /** ローディング状態 */
  isLoading?: boolean
  /** API エラーメッセージ */
  apiError?: string | null
  /** 競合エラー発生フラグ */
  isConflictError?: boolean
  /** 最新データ再取得コールバック */
  onRefresh?: () => void
  /** フィールド別エラー（サーバーサイドバリデーション） */
  fieldErrors?: Record<string, string>
}

/**
 * 職員編集フォームコンポーネント
 *
 * @param props - コンポーネントのプロパティ
 *
 * @example
 * <StaffEditForm
 *   staff={staffData}
 *   onSubmit={(data) => updateStaff(data)}
 *   onCancel={() => navigate('/staff/accounts')}
 *   onResetPassword={() => setShowResetDialog(true)}
 *   isLoading={isPending}
 *   apiError={error?.message}
 * />
 */
export function StaffEditForm({
  staff,
  onSubmit,
  onCancel,
  onResetPassword,
  isLoading = false,
  apiError,
  isConflictError = false,
  onRefresh,
  fieldErrors,
}: StaffEditFormProps) {
  const {
    register,
    handleSubmit,
    formState: { errors },
    reset,
  } = useForm<UpdateStaffFormValues>({
    resolver: zodResolver(updateStaffSchema),
    defaultValues: {
      name: staff.name,
      email: staff.email,
      role: staff.role,
    },
  })

  // 職員データが変更されたらフォームをリセット
  useEffect(() => {
    reset({
      name: staff.name,
      email: staff.email,
      role: staff.role,
    })
  }, [staff, reset])

  /**
   * フィールドエラーを取得（クライアント側バリデーション優先）
   */
  const getFieldError = (field: keyof UpdateStaffFormValues): string | undefined => {
    return errors[field]?.message || fieldErrors?.[field]
  }

  /**
   * 権限フィールドを無効化するかどうか
   * 自分自身の場合は権限変更不可
   */
  const isRoleDisabled = staff.isCurrentUser || isLoading

  return (
    <form onSubmit={handleSubmit(onSubmit)} className="space-y-6" noValidate>
      {/* API エラー表示 */}
      {apiError && !isConflictError && (
        <div role="alert" className="rounded-md bg-red-50 p-4 text-sm text-red-700">
          {apiError}
        </div>
      )}

      {/* 競合エラー表示 */}
      {isConflictError && (
        <div role="alert" className="rounded-md bg-yellow-50 p-4">
          <div className="flex">
            <div className="flex-shrink-0">
              <svg className="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                <path
                  fillRule="evenodd"
                  d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z"
                  clipRule="evenodd"
                />
              </svg>
            </div>
            <div className="ml-3">
              <h3 className="text-sm font-medium text-yellow-800">
                他のユーザーによって更新されています
              </h3>
              <div className="mt-2 text-sm text-yellow-700">
                <p>最新の情報を取得してから再度編集してください。</p>
              </div>
              {onRefresh && (
                <div className="mt-4">
                  <button
                    type="button"
                    onClick={onRefresh}
                    className="rounded-md bg-yellow-50 px-3 py-2 text-sm font-medium text-yellow-800 hover:bg-yellow-100 focus:outline-none focus:ring-2 focus:ring-yellow-600 focus:ring-offset-2"
                  >
                    最新情報を取得
                  </button>
                </div>
              )}
            </div>
          </div>
        </div>
      )}

      {/* 自己編集時の権限変更不可の注意 */}
      {staff.isCurrentUser && (
        <div role="alert" className="rounded-md bg-blue-50 p-4 text-sm text-blue-700">
          自分自身のアカウントを編集しています。権限は変更できません。
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
          disabled={isRoleDisabled}
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
        {staff.isCurrentUser && (
          <p className="mt-1 text-sm text-gray-500">自分自身の権限は変更できません</p>
        )}
      </div>

      {/* 作成日時・更新日時表示 */}
      <div className="grid grid-cols-2 gap-4 text-sm text-gray-500">
        <div>
          <span className="font-medium">作成日時:</span>{' '}
          {new Date(staff.createdAt).toLocaleString('ja-JP')}
        </div>
        <div>
          <span className="font-medium">更新日時:</span>{' '}
          {new Date(staff.updatedAt).toLocaleString('ja-JP')}
        </div>
      </div>

      {/* ボタングループ */}
      <div className="flex justify-between pt-4 border-t border-gray-200">
        {/* パスワードリセットボタン */}
        <button
          type="button"
          onClick={onResetPassword}
          disabled={isLoading}
          className="rounded-md bg-yellow-600 px-4 py-2 text-white font-medium hover:bg-yellow-700 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-offset-2 disabled:bg-yellow-400 disabled:cursor-not-allowed"
        >
          パスワードリセット
        </button>

        {/* キャンセル・保存ボタン */}
        <div className="flex gap-3">
          <button
            type="button"
            onClick={onCancel}
            disabled={isLoading}
            className="rounded-md bg-gray-100 px-6 py-2 text-gray-700 font-medium hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 disabled:bg-gray-50 disabled:cursor-not-allowed"
          >
            キャンセル
          </button>
          <button
            type="submit"
            disabled={isLoading}
            className="rounded-md bg-blue-600 px-6 py-2 text-white font-medium hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:bg-blue-400 disabled:cursor-not-allowed"
          >
            {isLoading ? '保存中...' : '保存'}
          </button>
        </div>
      </div>
    </form>
  )
}
