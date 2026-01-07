/**
 * 認証 API クライアント
 *
 * Laravel Sanctum SPA 認証 API との通信を担当。
 * CSRF トークン取得、ログイン、ログアウト、認証ユーザー取得の機能を提供。
 */

import { authClient, getCsrfToken } from '@/lib/axios'
import type { LoginRequest, Staff, StaffResponse, ApiError } from '../types/auth'
import { AxiosError } from 'axios'

/**
 * API エラーを ApiError 型に変換
 *
 * @param error - Axios エラー
 * @returns 正規化された ApiError
 */
function handleApiError(error: unknown): ApiError {
  if (error instanceof AxiosError) {
    const status = error.response?.status
    const data = error.response?.data as { message?: string; errors?: Record<string, string[]> }

    switch (status) {
      case 401:
        return {
          type: 'authentication',
          message: data?.message || 'メールアドレスまたはパスワードが正しくありません',
        }
      case 422:
        return {
          type: 'validation',
          message: data?.message || '入力内容に誤りがあります',
          errors: data?.errors,
        }
      case 423:
        return {
          type: 'locked',
          message: data?.message || 'アカウントがロックされています',
        }
      case 429: {
        const retryAfter = parseInt(error.response?.headers['retry-after'] || '60', 10)
        return {
          type: 'rate_limit',
          message:
            data?.message ||
            'ログイン試行回数が上限に達しました。しばらくしてから再試行してください',
          retryAfter,
        }
      }
      case 500:
      default:
        if (error.code === 'ERR_NETWORK') {
          return {
            type: 'network',
            message: '通信エラーが発生しました。ネットワーク接続を確認してください',
          }
        }
        return {
          type: 'server',
          message: 'サーバーエラーが発生しました。しばらくしてから再試行してください',
        }
    }
  }

  return {
    type: 'network',
    message: '通信エラーが発生しました。ネットワーク接続を確認してください',
  }
}

/**
 * ログイン API
 *
 * CSRF トークンを取得後、ログインリクエストを送信。
 *
 * @param credentials - ログイン認証情報
 * @returns 認証された職員情報
 * @throws ApiError - 認証失敗時
 */
export async function login(credentials: LoginRequest): Promise<Staff> {
  try {
    // CSRF トークンを取得
    await getCsrfToken()

    // ログインリクエスト
    const response = await authClient.post<StaffResponse>('/api/auth/login', credentials)
    return response.data.data
  } catch (error) {
    throw handleApiError(error)
  }
}

/**
 * ログアウト API
 *
 * @throws ApiError - ログアウト失敗時
 */
export async function logout(): Promise<void> {
  try {
    await authClient.post('/api/auth/logout')
  } catch (error) {
    throw handleApiError(error)
  }
}

/**
 * 認証ユーザー取得 API
 *
 * 現在のセッションで認証されているユーザー情報を取得。
 *
 * @returns 認証済みの場合は職員情報、未認証の場合は null
 */
export async function getCurrentUser(): Promise<Staff | null> {
  try {
    const response = await authClient.get<StaffResponse>('/api/auth/user')
    return response.data.data
  } catch (error) {
    if (error instanceof AxiosError && error.response?.status === 401) {
      return null
    }
    throw handleApiError(error)
  }
}
