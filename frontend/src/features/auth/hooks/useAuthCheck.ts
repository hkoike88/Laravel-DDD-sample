/**
 * 認証状態確認フック
 *
 * アプリケーション初期化時に現在の認証状態を確認。
 * セッションが有効な場合はユーザー情報を取得して認証ストアを更新。
 */

import { useEffect } from 'react'
import { useQuery } from '@tanstack/react-query'
import { getCurrentUser } from '../api/authApi'
import { useAuthStore } from '../stores/authStore'

/**
 * 認証状態確認フック
 *
 * アプリケーション起動時に一度呼び出し、セッションの有効性を確認。
 * 認証済みの場合はユーザー情報を認証ストアに保存。
 *
 * @example
 * function App() {
 *   useAuthCheck()
 *   return <AppRouter />
 * }
 */
export function useAuthCheck() {
  const setAuthenticated = useAuthStore((state) => state.setAuthenticated)
  const clearAuthentication = useAuthStore((state) => state.clearAuthentication)
  const setLoading = useAuthStore((state) => state.setLoading)

  const {
    data: user,
    isLoading,
    isError,
    isSuccess,
  } = useQuery({
    queryKey: ['auth', 'user'],
    queryFn: getCurrentUser,
    retry: false,
    staleTime: 1000 * 60 * 5, // 5分間キャッシュ
  })

  useEffect(() => {
    if (isLoading) {
      setLoading(true)
      return
    }

    if (isSuccess && user) {
      setAuthenticated(user)
    } else if (isError || (isSuccess && !user)) {
      clearAuthentication()
    }
  }, [user, isLoading, isError, isSuccess, setAuthenticated, clearAuthentication, setLoading])

  return { isLoading }
}
