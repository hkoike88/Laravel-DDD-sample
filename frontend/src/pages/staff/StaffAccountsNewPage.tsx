/**
 * 職員作成画面
 *
 * 管理者が新規職員アカウントを作成するための画面。
 * 作成成功時は結果画面にリダイレクト。
 *
 * @feature EPIC-003-staff-account-create
 */

import { useNavigate } from 'react-router-dom'
import { StaffCreateForm } from '@/features/staff-accounts/components/StaffCreateForm'
import { useCreateStaff } from '@/features/staff-accounts/hooks/useCreateStaff'
import type { CreateStaffFormValues } from '@/features/staff-accounts/schemas/createStaffSchema'

/**
 * 職員作成画面コンポーネント
 *
 * @returns 職員作成画面
 */
export function StaffAccountsNewPage() {
  const navigate = useNavigate()
  const { createStaff, isPending, isError, error, data, isSuccess } = useCreateStaff()

  /**
   * フォーム送信ハンドラー
   */
  const handleSubmit = (formData: CreateStaffFormValues) => {
    createStaff(formData)
  }

  /**
   * 作成成功時は結果画面にリダイレクト
   */
  if (isSuccess && data) {
    // state で結果データを渡す
    navigate('/staff/accounts/result', {
      state: {
        staff: data.staff,
        temporaryPassword: data.temporaryPassword,
      },
      replace: true,
    })
  }

  /**
   * サーバーサイドバリデーションエラーをフィールド別に変換
   */
  const fieldErrors: Record<string, string> = {}
  if (error?.details) {
    for (const detail of error.details) {
      fieldErrors[detail.field] = detail.message
    }
  }

  return (
    <div className="mx-auto max-w-2xl px-4 py-8">
      {/* ヘッダー */}
      <div className="mb-8">
        <h1 className="text-2xl font-bold text-gray-900">新規職員作成</h1>
        <p className="mt-2 text-sm text-gray-600">
          新しい職員アカウントを作成します。作成後に初期パスワードが表示されます。
        </p>
      </div>

      {/* フォーム */}
      <div className="rounded-lg bg-white p-6 shadow">
        <StaffCreateForm
          onSubmit={handleSubmit}
          isLoading={isPending}
          apiError={isError && !error?.details ? error?.message : null}
          fieldErrors={fieldErrors}
        />
      </div>

      {/* 戻るリンク */}
      <div className="mt-6">
        <button
          type="button"
          onClick={() => navigate('/staff/accounts')}
          className="text-sm text-blue-600 hover:text-blue-800 hover:underline"
        >
          ← 職員一覧に戻る
        </button>
      </div>
    </div>
  )
}
