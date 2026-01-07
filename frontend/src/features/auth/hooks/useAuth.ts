/**
 * 認証状態アクセスフック
 *
 * authStore のラッパーフック。
 * 認証状態と関連アクションを一括で提供し、コンポーネントからのアクセスを簡素化。
 *
 * @example
 * function MyComponent() {
 *   const { isAuthenticated, currentUser, isLoading } = useAuth()
 *
 *   if (isLoading) return <Loading />
 *   if (!isAuthenticated) return <Login />
 *   return <div>Welcome, {currentUser?.name}</div>
 * }
 */

import { useAuthStore } from '../stores/authStore'

/**
 * 認証状態と関連アクションを提供するフック
 *
 * @returns 認証状態（isAuthenticated, currentUser, isLoading）と
 *          アクション（setAuthenticated, clearAuthentication, setLoading）
 */
export function useAuth() {
  // 状態の取得（セレクターパターンで個別に取得することで不要な再レンダリングを防止）
  const isAuthenticated = useAuthStore((state) => state.isAuthenticated)
  const currentUser = useAuthStore((state) => state.currentUser)
  const isLoading = useAuthStore((state) => state.isLoading)

  // アクションの取得
  const setAuthenticated = useAuthStore((state) => state.setAuthenticated)
  const clearAuthentication = useAuthStore((state) => state.clearAuthentication)
  const setLoading = useAuthStore((state) => state.setLoading)

  return {
    // 状態
    isAuthenticated,
    currentUser,
    isLoading,
    // アクション
    setAuthenticated,
    clearAuthentication,
    setLoading,
  }
}
