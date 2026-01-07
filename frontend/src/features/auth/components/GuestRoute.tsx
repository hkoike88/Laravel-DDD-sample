/**
 * ゲストルートコンポーネント（未認証ユーザー用）
 *
 * 認証済みユーザーがアクセスした場合、ダッシュボードにリダイレクト。
 * 認証確認中はローディング表示。
 */

import { Navigate } from 'react-router-dom'
import { useAuthStore } from '../stores/authStore'
import type { ReactNode } from 'react'

interface GuestRouteProps {
  /** ゲスト向けコンテンツ */
  children: ReactNode
}

/**
 * GuestRoute コンポーネント
 *
 * 未認証ユーザーのみアクセスを許可するルートガード。
 * ログイン画面などで使用。
 *
 * @example
 * <Route
 *   path="/login"
 *   element={
 *     <GuestRoute>
 *       <LoginPage />
 *     </GuestRoute>
 *   }
 * />
 */
export function GuestRoute({ children }: GuestRouteProps) {
  const isAuthenticated = useAuthStore((state) => state.isAuthenticated)
  const isLoading = useAuthStore((state) => state.isLoading)

  // 認証確認中はローディング表示
  if (isLoading) {
    return (
      <div className="flex min-h-screen items-center justify-center">
        <div className="text-gray-500">認証確認中...</div>
      </div>
    )
  }

  // 認証済みの場合はダッシュボードへリダイレクト
  if (isAuthenticated) {
    return <Navigate to="/dashboard" replace />
  }

  return <>{children}</>
}
