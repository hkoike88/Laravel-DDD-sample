/**
 * 職員作成フック
 *
 * TanStack Query の useMutation を使用した職員作成処理。
 * 成功時は作成結果（初期パスワードを含む）を返す。
 *
 * @feature EPIC-003-staff-account-create
 */

import { useMutation, useQueryClient } from '@tanstack/react-query'
import { createStaff as createStaffApi } from '../api/staffAccountsApi'
import type {
  CreateStaffRequest,
  CreateStaffResponse,
  StaffAccountApiError,
} from '../types/staffAccount'

/**
 * 職員作成フック
 *
 * @returns 職員作成処理に必要な関数と状態
 *
 * @example
 * const { createStaff, isPending, error, data, reset } = useCreateStaff()
 *
 * const handleSubmit = (formData: CreateStaffFormData) => {
 *   createStaff(formData)
 * }
 *
 * // 成功時
 * if (data) {
 *   console.log(data.temporaryPassword)
 * }
 */
export function useCreateStaff() {
  const queryClient = useQueryClient()

  const mutation = useMutation<CreateStaffResponse, StaffAccountApiError, CreateStaffRequest>({
    /**
     * 職員作成 API を呼び出し
     */
    mutationFn: async (data: CreateStaffRequest) => {
      return await createStaffApi(data)
    },
    /**
     * 職員作成成功時に職員一覧キャッシュを無効化
     */
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['staffList'] })
    },
  })

  return {
    /**
     * 職員作成実行関数
     */
    createStaff: mutation.mutate,
    /**
     * 職員作成処理中フラグ
     */
    isPending: mutation.isPending,
    /**
     * 職員作成成功フラグ
     */
    isSuccess: mutation.isSuccess,
    /**
     * 職員作成エラーフラグ
     */
    isError: mutation.isError,
    /**
     * エラー情報
     */
    error: mutation.error,
    /**
     * 作成結果（初期パスワードを含む）
     */
    data: mutation.data,
    /**
     * 状態をリセット
     */
    reset: mutation.reset,
  }
}
