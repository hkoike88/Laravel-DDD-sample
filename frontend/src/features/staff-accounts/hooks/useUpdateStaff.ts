/**
 * 職員更新フック
 *
 * TanStack Query の useMutation を使用した職員更新処理。
 * 楽観的ロック競合、自己権限変更、最後の管理者保護などのエラーをハンドリング。
 *
 * @feature EPIC-004-staff-account-edit
 */

import { useMutation, useQueryClient } from '@tanstack/react-query'
import { updateStaff as updateStaffApi } from '../api/staffAccountsApi'
import type {
  UpdateStaffRequest,
  UpdateStaffResponse,
  StaffAccountApiError,
} from '../types/staffAccount'

/**
 * 更新パラメータ
 */
interface UpdateStaffParams {
  /** 職員ID */
  id: string
  /** 更新データ */
  data: UpdateStaffRequest
}

/**
 * 職員更新フック
 *
 * @returns 職員更新処理に必要な関数と状態
 *
 * @example
 * const { updateStaff, isPending, isSuccess, error, reset } = useUpdateStaff()
 *
 * const handleSubmit = (formData: UpdateStaffFormData, updatedAt: string) => {
 *   updateStaff({
 *     id: staffId,
 *     data: { ...formData, updatedAt }
 *   })
 * }
 */
export function useUpdateStaff() {
  const queryClient = useQueryClient()

  const mutation = useMutation<UpdateStaffResponse, StaffAccountApiError, UpdateStaffParams>({
    /**
     * 職員更新 API を呼び出し
     */
    mutationFn: async ({ id, data }: UpdateStaffParams) => {
      return await updateStaffApi(id, data)
    },
    /**
     * 職員更新成功時にキャッシュを無効化
     */
    onSuccess: (_data, variables) => {
      // 職員一覧キャッシュを無効化
      queryClient.invalidateQueries({ queryKey: ['staffList'] })
      // 職員詳細キャッシュを無効化
      queryClient.invalidateQueries({ queryKey: ['staffDetail', variables.id] })
    },
  })

  return {
    /**
     * 職員更新実行関数
     */
    updateStaff: mutation.mutate,
    /**
     * 職員更新処理中フラグ
     */
    isPending: mutation.isPending,
    /**
     * 職員更新成功フラグ
     */
    isSuccess: mutation.isSuccess,
    /**
     * 職員更新エラーフラグ
     */
    isError: mutation.isError,
    /**
     * エラー情報
     */
    error: mutation.error,
    /**
     * 更新結果
     */
    data: mutation.data,
    /**
     * 状態をリセット
     */
    reset: mutation.reset,
  }
}
