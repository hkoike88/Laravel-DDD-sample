/**
 * 職員アカウント API クライアント
 *
 * 職員アカウントの作成・一覧取得 API との通信を担当。
 *
 * @feature EPIC-003-staff-account-create
 */

import { authClient } from '@/lib/axios'
import type {
  CreateStaffRequest,
  CreateStaffResponse,
  StaffListResponse,
  StaffAccountApiError,
  ErrorResponse,
} from '../types/staffAccount'
import { AxiosError } from 'axios'

/**
 * API エラーを StaffAccountApiError 型に変換
 *
 * @param error - Axios エラー
 * @returns 正規化された StaffAccountApiError
 */
/**
 * 編集系エラーレスポンス型（バックエンドが返す形式）
 */
interface EditErrorResponse {
  message?: string
  code?: string
}

function handleApiError(error: unknown): StaffAccountApiError {
  if (error instanceof AxiosError) {
    const status = error.response?.status
    const data = error.response?.data as ErrorResponse | EditErrorResponse | undefined

    switch (status) {
      case 401:
        return {
          type: 'authentication',
          code: (data as ErrorResponse)?.error?.code || 'AUTH_UNAUTHENTICATED',
          message: (data as ErrorResponse)?.error?.message || '認証が必要です',
        }
      case 403:
        return {
          type: 'permission',
          code: (data as ErrorResponse)?.error?.code || 'AUTHZ_PERMISSION_DENIED',
          message: (data as ErrorResponse)?.error?.message || 'この操作を行う権限がありません',
        }
      case 404:
        return {
          type: 'notFound',
          code: 'STAFF_NOT_FOUND',
          message: (data as EditErrorResponse)?.message || '指定された職員が見つかりません',
        }
      case 409:
        return {
          type: 'conflict',
          code: 'STAFF_UPDATE_CONFLICT',
          message: (data as EditErrorResponse)?.message || '他のユーザーによって更新されています',
        }
      case 422: {
        // 編集系エラー（code フィールドあり）の場合
        const editData = data as EditErrorResponse
        if (editData?.code) {
          return {
            type: 'businessRule',
            code: editData.code,
            message: editData.message || '操作を実行できません',
          }
        }
        // 従来のバリデーションエラー形式
        const errorData = data as ErrorResponse
        return {
          type: 'validation',
          code: errorData?.error?.code || 'VALIDATION_ERROR',
          message: errorData?.error?.message || '入力内容に誤りがあります',
          details: errorData?.error?.details,
        }
      }
      case 500:
      default:
        if (error.code === 'ERR_NETWORK') {
          return {
            type: 'network',
            code: 'NETWORK_ERROR',
            message: '通信エラーが発生しました。ネットワーク接続を確認してください',
          }
        }
        return {
          type: 'server',
          code: (data as ErrorResponse)?.error?.code || 'SYSTEM_INTERNAL_ERROR',
          message:
            (data as ErrorResponse)?.error?.message ||
            'サーバーエラーが発生しました。しばらくしてから再試行してください',
        }
    }
  }

  return {
    type: 'network',
    code: 'NETWORK_ERROR',
    message: '通信エラーが発生しました。ネットワーク接続を確認してください',
  }
}

/**
 * 職員一覧取得 API
 *
 * @param page - ページ番号（1始まり、デフォルト: 1）
 * @returns 職員一覧レスポンス
 * @throws StaffAccountApiError - 取得失敗時
 */
export async function getStaffList(page: number = 1): Promise<StaffListResponse> {
  try {
    const response = await authClient.get<StaffListResponse>('/api/staff/accounts', {
      params: { page },
    })
    return response.data
  } catch (error) {
    throw handleApiError(error)
  }
}

/**
 * 職員作成 API
 *
 * @param data - 職員作成リクエストデータ
 * @returns 職員作成レスポンス（初期パスワードを含む）
 * @throws StaffAccountApiError - 作成失敗時
 */
export async function createStaff(data: CreateStaffRequest): Promise<CreateStaffResponse> {
  try {
    const response = await authClient.post<CreateStaffResponse>('/api/staff/accounts', data)
    return response.data
  } catch (error) {
    throw handleApiError(error)
  }
}

// =============================================================================
// EPIC-004: 職員アカウント編集機能
// =============================================================================

import type {
  StaffDetailResponse,
  UpdateStaffRequest,
  UpdateStaffResponse,
  ResetPasswordResponse,
} from '../types/staffAccount'

/**
 * 職員詳細取得 API
 *
 * @param id - 職員ID
 * @returns 職員詳細レスポンス
 * @throws StaffAccountApiError - 取得失敗時
 *
 * @feature EPIC-004-staff-account-edit
 */
export async function getStaff(id: string): Promise<StaffDetailResponse> {
  try {
    const response = await authClient.get<StaffDetailResponse>(`/api/staff/accounts/${id}`)
    return response.data
  } catch (error) {
    throw handleApiError(error)
  }
}

/**
 * 職員更新 API
 *
 * @param id - 職員ID
 * @param data - 職員更新リクエストデータ
 * @returns 職員更新レスポンス
 * @throws StaffAccountApiError - 更新失敗時
 *
 * @feature EPIC-004-staff-account-edit
 */
export async function updateStaff(
  id: string,
  data: UpdateStaffRequest
): Promise<UpdateStaffResponse> {
  try {
    const response = await authClient.put<UpdateStaffResponse>(`/api/staff/accounts/${id}`, data)
    return response.data
  } catch (error) {
    throw handleApiError(error)
  }
}

/**
 * パスワードリセット API
 *
 * @param id - 職員ID
 * @returns パスワードリセットレスポンス（一時パスワードを含む）
 * @throws StaffAccountApiError - リセット失敗時
 *
 * @feature EPIC-004-staff-account-edit
 */
export async function resetPassword(id: string): Promise<ResetPasswordResponse> {
  try {
    const response = await authClient.post<ResetPasswordResponse>(
      `/api/staff/accounts/${id}/reset-password`
    )
    return response.data
  } catch (error) {
    throw handleApiError(error)
  }
}
