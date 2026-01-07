/**
 * パスワードリセットフック
 *
 * TanStack Query の useMutation を使用したパスワードリセット処理。
 *
 * @feature EPIC-004-staff-account-edit
 */

import { useMutation } from '@tanstack/react-query'
import { resetPassword as resetPasswordApi } from '../api/staffAccountsApi'
import type { ResetPasswordResponse, StaffAccountApiError } from '../types/staffAccount'

/**
 * パスワードリセットフック
 *
 * @returns パスワードリセット処理に必要な関数と状態
 *
 * @example
 * const { resetPassword, isPending, data, reset } = useResetPassword()
 *
 * const handleReset = () => {
 *   resetPassword(staffId)
 * }
 *
 * // 成功時
 * if (data) {
 *   console.log(data.temporaryPassword)
 * }
 */
export function useResetPassword() {
  const mutation = useMutation<ResetPasswordResponse, StaffAccountApiError, string>({
    /**
     * パスワードリセット API を呼び出し
     */
    mutationFn: async (id: string) => {
      return await resetPasswordApi(id)
    },
  })

  return {
    /**
     * パスワードリセット実行関数
     */
    resetPassword: mutation.mutate,
    /**
     * パスワードリセット処理中フラグ
     */
    isPending: mutation.isPending,
    /**
     * パスワードリセット成功フラグ
     */
    isSuccess: mutation.isSuccess,
    /**
     * パスワードリセットエラーフラグ
     */
    isError: mutation.isError,
    /**
     * エラー情報
     */
    error: mutation.error,
    /**
     * リセット結果（一時パスワードを含む）
     */
    data: mutation.data,
    /**
     * 状態をリセット
     */
    reset: mutation.reset,
  }
}
