/**
 * ログインフック
 *
 * TanStack Query の useMutation を使用したログイン処理。
 * 成功時は認証ストアを更新し、ダッシュボードへリダイレクト。
 */

import { useMutation } from '@tanstack/react-query'
import { useNavigate } from 'react-router-dom'
import { login as loginApi } from '../api/authApi'
import { useAuthStore } from '../stores/authStore'
import type { LoginRequest, ApiError } from '../types/auth'

/**
 * ログインフック
 *
 * @returns ログイン処理に必要な関数と状態
 *
 * @example
 * const { login, isPending, error, reset } = useLogin()
 *
 * const handleSubmit = (data: LoginFormData) => {
 *   login(data)
 * }
 */
export function useLogin() {
  const navigate = useNavigate()
  const setAuthenticated = useAuthStore((state) => state.setAuthenticated)

  const mutation = useMutation<void, ApiError, LoginRequest>({
    /**
     * ログイン API を呼び出し
     */
    mutationFn: async (credentials: LoginRequest) => {
      const user = await loginApi(credentials)
      // 認証ストアを更新
      setAuthenticated(user)
    },
    /**
     * ログイン成功時にダッシュボードへリダイレクト
     */
    onSuccess: () => {
      navigate('/dashboard', { replace: true })
    },
  })

  return {
    /**
     * ログイン実行関数
     */
    login: mutation.mutate,
    /**
     * ログイン処理中フラグ
     */
    isPending: mutation.isPending,
    /**
     * ログイン成功フラグ
     */
    isSuccess: mutation.isSuccess,
    /**
     * ログインエラーフラグ
     */
    isError: mutation.isError,
    /**
     * エラー情報
     */
    error: mutation.error,
    /**
     * エラー状態をリセット
     */
    reset: mutation.reset,
  }
}
