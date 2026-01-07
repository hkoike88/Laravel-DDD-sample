/**
 * 403 Forbidden エラーページ
 *
 * 権限がないリソースへのアクセス時に表示されるエラーページ。
 * ダッシュボードへの戻りリンクを提供する。
 *
 * @feature 003-role-based-menu
 */

import { Link } from 'react-router-dom'
import { MainLayout } from '@/components/layout/MainLayout'

/**
 * 403 エラーページコンポーネント
 *
 * 管理者専用ページに一般職員がアクセスした場合などに表示する。
 */
export function ForbiddenPage() {
  return (
    <MainLayout>
      <div className="flex min-h-[calc(100vh-200px)] items-center justify-center">
        <div className="text-center">
          <h1 className="text-6xl font-bold text-red-600">403</h1>
          <h2 className="mt-4 text-2xl font-semibold text-gray-900">アクセス権限がありません</h2>
          <p className="mt-2 text-gray-600">この操作を行う権限がありません</p>
          <Link
            to="/dashboard"
            className="mt-6 inline-block rounded-md bg-blue-600 px-4 py-2 text-white hover:bg-blue-700"
          >
            ダッシュボードに戻る
          </Link>
        </div>
      </div>
    </MainLayout>
  )
}
