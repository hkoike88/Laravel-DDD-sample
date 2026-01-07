/**
 * 管理者専用メニューセクション
 *
 * 管理者権限を持つ職員のみに表示されるメニューセクション。
 * 職員管理などの管理者専用機能へのアクセスを提供する。
 *
 * @feature 003-role-based-menu
 */

import { MenuGrid } from './MenuGrid'
import { adminMenuItems } from '../constants/adminMenuItems'

/**
 * 管理者専用メニューセクションコンポーネント
 *
 * 管理者のみがアクセス可能な機能のメニューをグリッド表示する。
 * 表示制御は呼び出し元（DashboardPage）で行う。
 */
export function AdminMenuSection() {
  return (
    <section className="mt-8">
      <div className="mb-6">
        <h2 className="text-xl font-bold text-gray-900">管理メニュー</h2>
        <p className="mt-1 text-sm text-gray-600">管理者専用の機能</p>
      </div>
      <MenuGrid items={adminMenuItems} />
    </section>
  )
}
