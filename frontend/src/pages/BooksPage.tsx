/**
 * 蔵書管理ページ（プレースホルダー）
 *
 * @feature 004-dashboard-ui
 */

import { MainLayout } from '@/components/layout/MainLayout'

/**
 * 蔵書管理ページコンポーネント
 *
 * 蔵書の検索・登録・編集機能を提供するページ。
 * 現在はプレースホルダーとして実装。
 */
export function BooksPage() {
  return (
    <MainLayout>
      <div className="bg-gray-100 py-8">
        <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
          <div className="rounded-lg bg-white p-6 shadow">
            <h1 className="text-2xl font-bold text-gray-900">蔵書管理</h1>
            <p className="mt-4 text-gray-600">蔵書の検索・登録・編集機能は準備中です。</p>
          </div>
        </div>
      </div>
    </MainLayout>
  )
}
