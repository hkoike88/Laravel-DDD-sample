/**
 * ログアウトフック
 *
 * @feature 001-staff-logout
 */

import { useState, useCallback } from 'react'
import { useNavigate } from 'react-router-dom'
import { logout as logoutApi } from '../api/authApi'
import { useAuthStore } from '../stores/authStore'

/**
 * useLogout フックの戻り値
 */
interface UseLogoutResult {
  /** ログアウト処理中フラグ */
  isLoggingOut: boolean
  /** エラーメッセージ */
  error: string | null
  /** ログアウト実行関数 */
  logout: () => Promise<void>
}

/**
 * ログアウト処理を管理するフック
 *
 * API 呼び出し、状態クリア、リダイレクトを一元管理。
 * エラー発生時もローカル状態をクリアしてログイン画面へ遷移。
 */
export function useLogout(): UseLogoutResult {
  const navigate = useNavigate()
  const clearAuthentication = useAuthStore((state) => state.clearAuthentication)
  const [isLoggingOut, setIsLoggingOut] = useState(false)
  const [error, setError] = useState<string | null>(null)

  const logout = useCallback(async () => {
    setIsLoggingOut(true)
    setError(null)

    try {
      await logoutApi()
    } catch (err) {
      // エラーをログに記録するが、ユーザーにはログアウトを完了させる
      console.error('Logout API error:', err)
    } finally {
      // API 成功/失敗に関わらず、ローカル状態をクリア
      clearAuthentication()
      setIsLoggingOut(false)
      navigate('/login', { replace: true, state: { loggedOut: true } })
    }
  }, [clearAuthentication, navigate])

  return {
    isLoggingOut,
    error,
    logout,
  }
}
