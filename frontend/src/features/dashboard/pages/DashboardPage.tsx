/**
 * ダッシュボードページ
 *
 * @feature 004-dashboard-ui
 * @feature 003-role-based-menu
 */

import { useAuthStore } from '@/features/auth/stores/authStore'
import { MainLayout } from '@/components/layout/MainLayout'
import { MenuGrid } from '../components/MenuGrid'
import { WelcomeMessage } from '../components/WelcomeMessage'
import { AdminMenuSection } from '../components/AdminMenuSection'
import { menuItems } from '../constants/menuItems'

/**
 * ダッシュボードページコンポーネント
 *
 * ログイン後のランディングページ。
 * ログイン中の職員へのウェルカムメッセージと
 * 業務メニューをグリッド表示し、各業務画面への遷移を提供。
 * 管理者権限を持つ職員には管理メニューも表示する。
 */
export function DashboardPage() {
  const currentUser = useAuthStore((state) => state.currentUser)

  return (
    <MainLayout>
      <div className="bg-gray-100 py-8">
        <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
          {currentUser && (
            <div className="mb-8">
              <WelcomeMessage userName={currentUser.name} />
            </div>
          )}
          <div className="mb-6">
            <h2 className="text-xl font-bold text-gray-900">業務メニュー</h2>
            <p className="mt-1 text-sm text-gray-600">操作を選択してください</p>
          </div>
          <MenuGrid items={menuItems} />

          {/* 管理者のみ管理メニューを表示 */}
          {currentUser?.is_admin && <AdminMenuSection />}
        </div>
      </div>
    </MainLayout>
  )
}
