/**
 * 職員編集画面
 *
 * 管理者が職員アカウントを編集するための画面。
 * 職員詳細取得、更新、パスワードリセット機能を提供。
 *
 * @feature EPIC-004-staff-account-edit
 */

import { useState } from 'react'
import { useNavigate, useParams } from 'react-router-dom'
import { StaffEditForm } from '@/features/staff-accounts/components/StaffEditForm'
import { PasswordResetDialog } from '@/features/staff-accounts/components/PasswordResetDialog'
import { useStaffDetail } from '@/features/staff-accounts/hooks/useStaffDetail'
import { useUpdateStaff } from '@/features/staff-accounts/hooks/useUpdateStaff'
import { useResetPassword } from '@/features/staff-accounts/hooks/useResetPassword'
import type { UpdateStaffFormValues } from '@/features/staff-accounts/schemas/updateStaffSchema'

/**
 * 職員編集画面コンポーネント
 *
 * @returns 職員編集画面
 */
export function StaffAccountsEditPage() {
  const navigate = useNavigate()
  const { id } = useParams<{ id: string }>()
  const [showResetDialog, setShowResetDialog] = useState(false)

  // 職員詳細取得
  const {
    data: staff,
    isLoading: isDetailLoading,
    isError: isDetailError,
    error: detailError,
    refetch,
  } = useStaffDetail(id ?? '')

  // 職員更新
  const {
    updateStaff,
    isPending: isUpdatePending,
    isSuccess: isUpdateSuccess,
    isError: isUpdateError,
    error: updateError,
    reset: resetUpdateState,
  } = useUpdateStaff()

  // パスワードリセット
  const {
    resetPassword,
    isPending: isResetPending,
    isSuccess: isResetSuccess,
    data: resetData,
    reset: resetResetState,
  } = useResetPassword()

  /**
   * フォーム送信ハンドラー
   */
  const handleSubmit = (formData: UpdateStaffFormValues) => {
    if (!staff || !id) return

    // 更新状態をリセット
    resetUpdateState()

    updateStaff({
      id,
      data: {
        ...formData,
        updatedAt: staff.updatedAt,
      },
    })
  }

  /**
   * キャンセルハンドラー
   */
  const handleCancel = () => {
    navigate('/staff/accounts')
  }

  /**
   * パスワードリセットダイアログを開く
   */
  const handleOpenResetDialog = () => {
    resetResetState()
    setShowResetDialog(true)
  }

  /**
   * パスワードリセット実行
   */
  const handleResetPassword = () => {
    if (!id) return
    resetPassword(id)
  }

  /**
   * パスワードリセットダイアログを閉じる
   */
  const handleCloseResetDialog = () => {
    setShowResetDialog(false)
    resetResetState()
  }

  /**
   * 最新情報を再取得
   */
  const handleRefresh = () => {
    resetUpdateState()
    refetch()
  }

  /**
   * 更新成功時は一覧画面に戻る
   */
  if (isUpdateSuccess) {
    navigate('/staff/accounts', { replace: true })
  }

  /**
   * 競合エラー判定
   */
  const isConflictError = updateError?.code === 'STAFF_UPDATE_CONFLICT'

  /**
   * サーバーサイドバリデーションエラーをフィールド別に変換
   */
  const fieldErrors: Record<string, string> = {}
  if (updateError?.details) {
    for (const detail of updateError.details) {
      fieldErrors[detail.field] = detail.message
    }
  }

  /**
   * ID がない場合
   */
  if (!id) {
    return (
      <div className="mx-auto max-w-2xl px-4 py-8">
        <div className="rounded-md bg-red-50 p-4 text-sm text-red-700">
          職員IDが指定されていません。
        </div>
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

  /**
   * ローディング中
   */
  if (isDetailLoading) {
    return (
      <div className="mx-auto max-w-2xl px-4 py-8">
        <div className="flex items-center justify-center py-12">
          <div className="h-8 w-8 animate-spin rounded-full border-4 border-blue-600 border-t-transparent" />
          <span className="ml-3 text-gray-600">読み込み中...</span>
        </div>
      </div>
    )
  }

  /**
   * エラー発生時
   */
  if (isDetailError || !staff) {
    const errorMessage =
      detailError?.code === 'STAFF_NOT_FOUND'
        ? '指定された職員が見つかりません。'
        : (detailError?.message ?? 'データの取得に失敗しました。')

    return (
      <div className="mx-auto max-w-2xl px-4 py-8">
        <div className="rounded-md bg-red-50 p-4 text-sm text-red-700">{errorMessage}</div>
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

  return (
    <div className="mx-auto max-w-2xl px-4 py-8">
      {/* ヘッダー */}
      <div className="mb-8">
        <h1 className="text-2xl font-bold text-gray-900">職員情報編集</h1>
        <p className="mt-2 text-sm text-gray-600">
          職員の氏名、メールアドレス、権限を編集できます。
        </p>
      </div>

      {/* フォーム */}
      <div className="rounded-lg bg-white p-6 shadow">
        <StaffEditForm
          staff={staff}
          onSubmit={handleSubmit}
          onCancel={handleCancel}
          onResetPassword={handleOpenResetDialog}
          isLoading={isUpdatePending}
          apiError={
            isUpdateError && !isConflictError && !updateError?.details ? updateError?.message : null
          }
          isConflictError={isConflictError}
          onRefresh={handleRefresh}
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

      {/* パスワードリセットダイアログ */}
      <PasswordResetDialog
        isOpen={showResetDialog}
        staffName={staff.name}
        onConfirm={handleResetPassword}
        onClose={handleCloseResetDialog}
        isPending={isResetPending}
        isSuccess={isResetSuccess}
        temporaryPassword={resetData?.temporaryPassword}
      />
    </div>
  )
}
