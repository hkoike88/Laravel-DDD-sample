/**
 * 返却処理ページ（プレースホルダー）
 *
 * @feature 004-dashboard-ui
 */

import { MainLayout } from '@/components/layout/MainLayout'

/**
 * 返却処理ページコンポーネント
 *
 * 図書の返却処理を行うページ。
 * 現在はプレースホルダーとして実装。
 */
export function ReturnPage() {
  return (
    <MainLayout>
      <div className="bg-gray-100 py-8">
        <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
          <div className="rounded-lg bg-white p-6 shadow">
            <h1 className="text-2xl font-bold text-gray-900">返却処理</h1>
            <p className="mt-4 text-gray-600">返却処理機能は準備中です。</p>
          </div>
        </div>
      </div>
    </MainLayout>
  )
}
