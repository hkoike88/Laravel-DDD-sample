/**
 * 管理者専用ルートガード
 *
 * 管理者権限を持つ職員のみがアクセス可能なルートを保護するコンポーネント。
 * - 未認証: ログインページへリダイレクト
 * - 一般職員: 403 エラーページを表示
 * - 管理者: children をレンダリング
 *
 * @feature 003-role-based-menu
 */

import type { ReactNode } from 'react'
import { Navigate } from 'react-router-dom'
import { useAuthStore } from '@/features/auth/stores/authStore'
import { ForbiddenPage } from '@/pages/errors/ForbiddenPage'

/**
 * AdminGuard コンポーネントのプロパティ
 */
interface AdminGuardProps {
  /** 保護対象の子コンポーネント */
  children: ReactNode
}

/**
 * 管理者専用ルートガードコンポーネント
 *
 * 管理者権限を持つ職員のみがアクセス可能なルートを保護する。
 */
export function AdminGuard({ children }: AdminGuardProps) {
  const { isAuthenticated, currentUser, isLoading } = useAuthStore()

  // 認証状態確認中はローディング表示
  if (isLoading) {
    return (
      <div className="flex min-h-screen items-center justify-center">
        <div className="text-gray-600">読み込み中...</div>
      </div>
    )
  }

  // 未認証の場合はログインページへリダイレクト
  if (!isAuthenticated || !currentUser) {
    return <Navigate to="/login" replace />
  }

  // 管理者権限がない場合は 403 ページを表示
  if (!currentUser.is_admin) {
    return <ForbiddenPage />
  }

  // 管理者の場合は children をレンダリング
  return <>{children}</>
}
