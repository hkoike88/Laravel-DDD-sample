/**
 * 職員一覧取得フック
 *
 * TanStack Query の useQuery を使用した職員一覧取得処理。
 * ページネーション対応。
 *
 * @feature EPIC-003-staff-account-create
 */

import { useQuery, keepPreviousData } from '@tanstack/react-query'
import { getStaffList } from '../api/staffAccountsApi'
import type { StaffListResponse, StaffAccountApiError } from '../types/staffAccount'

/**
 * 職員一覧取得フック
 *
 * @param page - ページ番号（1始まり）
 * @returns 職員一覧取得に必要な関数と状態
 *
 * @example
 * const { data, isLoading, error, refetch } = useStaffList(1)
 *
 * if (data) {
 *   console.log(data.data) // 職員一覧
 *   console.log(data.meta.total) // 総件数
 * }
 */
export function useStaffList(page: number = 1) {
  return useQuery<StaffListResponse, StaffAccountApiError>({
    queryKey: ['staffList', page],
    queryFn: () => getStaffList(page),
    placeholderData: keepPreviousData,
    staleTime: 1000 * 60 * 5, // 5分間キャッシュを新鮮とみなす
  })
}
