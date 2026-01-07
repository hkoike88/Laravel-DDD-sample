/**
 * メインレイアウトコンポーネント
 *
 * @feature 004-dashboard-ui
 */

import type { ReactNode } from 'react'
import { useAuthStore } from '@/features/auth/stores/authStore'
import { useLogout } from '@/features/auth/hooks/useLogout'
import { Header } from './Header'
import { Footer } from './Footer'

/**
 * メインレイアウトのプロパティ
 */
interface MainLayoutProps {
  /** 子要素 */
  children: ReactNode
}

/**
 * メインレイアウトコンポーネント
 *
 * 認証済みページ共通のレイアウト。
 * Header + children + Footer の構成。
 */
export function MainLayout({ children }: MainLayoutProps) {
  const currentUser = useAuthStore((state) => state.currentUser)
  const { logout, isLoggingOut } = useLogout()

  return (
    <div className="flex min-h-screen flex-col">
      <Header userName={currentUser?.name ?? ''} onLogout={logout} isLoggingOut={isLoggingOut} />
      <main className="flex-1">{children}</main>
      <Footer />
    </div>
  )
}
