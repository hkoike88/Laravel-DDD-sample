/**
 * 認証ガードコンポーネント（認証必須ルート用）
 *
 * 認証されていないユーザーがアクセスした場合、ログイン画面にリダイレクト。
 * 認証確認中はローディング表示。
 */

import { Navigate } from 'react-router-dom'
import { useAuthStore } from '../stores/authStore'
import type { ReactNode } from 'react'

interface ProtectedRouteProps {
  /** 保護対象のコンテンツ */
  children: ReactNode
}

/**
 * ProtectedRoute コンポーネント
 *
 * 認証済みユーザーのみアクセスを許可するルートガード。
 *
 * @example
 * <Route
 *   path="/dashboard"
 *   element={
 *     <ProtectedRoute>
 *       <DashboardPage />
 *     </ProtectedRoute>
 *   }
 * />
 */
export function ProtectedRoute({ children }: ProtectedRouteProps) {
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

  // 未認証の場合はログイン画面へリダイレクト
  if (!isAuthenticated) {
    return <Navigate to="/login" replace />
  }

  return <>{children}</>
}
