/**
 * 職員詳細取得フック
 *
 * TanStack Query の useQuery を使用した職員詳細取得処理。
 *
 * @feature EPIC-004-staff-account-edit
 */

import { useQuery } from '@tanstack/react-query'
import { getStaff } from '../api/staffAccountsApi'
import type { StaffDetail, StaffAccountApiError } from '../types/staffAccount'

/**
 * 職員詳細取得フック
 *
 * @param id - 職員ID
 * @returns 職員詳細取得処理に必要な関数と状態
 *
 * @example
 * const { data, isLoading, error, refetch } = useStaffDetail('01HV8J3N5Y6X7Z8A9B0C1D2E3F')
 *
 * if (isLoading) {
 *   return <Loading />
 * }
 *
 * if (data) {
 *   console.log(data.name, data.email)
 * }
 */
export function useStaffDetail(id: string) {
  const query = useQuery<StaffDetail, StaffAccountApiError>({
    /**
     * キャッシュキー（職員IDを含む）
     */
    queryKey: ['staffDetail', id],
    /**
     * 職員詳細取得 API を呼び出し
     */
    queryFn: async () => {
      const response = await getStaff(id)
      return response.data
    },
    /**
     * IDが指定されている場合のみ実行
     */
    enabled: !!id,
    /**
     * 5分間キャッシュを保持
     */
    staleTime: 5 * 60 * 1000,
  })

  return {
    /**
     * 職員詳細データ
     */
    data: query.data,
    /**
     * 読み込み中フラグ
     */
    isLoading: query.isLoading,
    /**
     * エラー発生フラグ
     */
    isError: query.isError,
    /**
     * エラー情報
     */
    error: query.error,
    /**
     * 再取得関数
     */
    refetch: query.refetch,
  }
}
