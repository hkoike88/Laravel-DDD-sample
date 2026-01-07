import { useMutation } from '@tanstack/react-query'
import { authClient } from '@/lib/axios'
import { AxiosError } from 'axios'

/**
 * パスワード変更リクエスト
 */
export interface PasswordChangeRequest {
  /** 現在のパスワード */
  current_password: string
  /** 新しいパスワード */
  new_password: string
  /** 新しいパスワード（確認） */
  new_password_confirmation: string
}

/**
 * パスワード変更レスポンス
 */
interface PasswordChangeResponse {
  message: string
}

/**
 * API エラーレスポンス
 */
interface ApiErrorResponse {
  message: string
  errors?: Record<string, string[]>
}

/**
 * パスワード変更API呼び出し
 *
 * @param data パスワード変更リクエスト
 * @returns パスワード変更レスポンス
 */
async function changePassword(data: PasswordChangeRequest): Promise<PasswordChangeResponse> {
  const response = await authClient.put<PasswordChangeResponse>('/api/staff/password', data)
  return response.data
}

/**
 * パスワード変更フック
 *
 * パスワード変更機能を提供する。
 *
 * @feature 001-security-preparation
 */
export function usePasswordChange() {
  const mutation = useMutation({
    mutationFn: changePassword,
  })

  /**
   * エラーメッセージを取得
   */
  const getErrorMessage = (): string | null => {
    if (!mutation.error) return null

    const error = mutation.error as AxiosError<ApiErrorResponse>
    if (error.response?.data?.message) {
      return error.response.data.message
    }
    return 'パスワードの変更に失敗しました'
  }

  /**
   * フィールドエラーを取得
   */
  const getFieldErrors = (): Record<string, string[]> => {
    if (!mutation.error) return {}

    const error = mutation.error as AxiosError<ApiErrorResponse>
    return error.response?.data?.errors ?? {}
  }

  return {
    /** パスワードを変更 */
    changePassword: mutation.mutate,
    /** 変更中 */
    isLoading: mutation.isPending,
    /** 成功 */
    isSuccess: mutation.isSuccess,
    /** エラー */
    isError: mutation.isError,
    /** エラーメッセージ */
    errorMessage: getErrorMessage(),
    /** フィールドエラー */
    fieldErrors: getFieldErrors(),
    /** リセット */
    reset: mutation.reset,
  }
}
