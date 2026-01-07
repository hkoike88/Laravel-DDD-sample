/**
 * 職員一覧画面
 *
 * 管理者が登録済みの職員一覧を確認するための画面。
 * ページネーション対応。
 *
 * @feature EPIC-003-staff-account-create
 */

import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { MainLayout } from '@/components/layout/MainLayout'
import { StaffListTable } from '@/features/staff-accounts/components/StaffListTable'
import { Pagination } from '@/features/staff-accounts/components/Pagination'
import { useStaffList } from '@/features/staff-accounts/hooks/useStaffList'

/**
 * 職員一覧画面コンポーネント
 *
 * 管理者専用ページ。職員アカウントの一覧を表示する。
 */
export function StaffAccountsPage() {
  const navigate = useNavigate()
  const [page, setPage] = useState(1)
  const { data, isLoading, isError, error } = useStaffList(page)

  /**
   * ページ変更ハンドラー
   */
  const handlePageChange = (newPage: number) => {
    setPage(newPage)
    // ページトップにスクロール
    window.scrollTo({ top: 0, behavior: 'smooth' })
  }

  /**
   * 新規作成ボタンクリックハンドラー
   */
  const handleCreate = () => {
    navigate('/staff/accounts/new')
  }

  return (
    <MainLayout>
      <div className="bg-gray-100 min-h-screen py-8">
        <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
          {/* ヘッダー */}
          <div className="mb-8 flex items-center justify-between">
            <div>
              <h1 className="text-2xl font-bold text-gray-900">職員管理</h1>
              <p className="mt-2 text-gray-600">
                {data ? `登録済み職員: ${data.meta.total}名` : '職員アカウントの一覧'}
              </p>
            </div>
            <button
              type="button"
              onClick={handleCreate}
              className="rounded-md bg-blue-600 px-4 py-2 text-white font-medium hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
            >
              新規作成
            </button>
          </div>

          {/* エラー表示 */}
          {isError && (
            <div className="mb-6 rounded-md bg-red-50 p-4 text-sm text-red-700" role="alert">
              {error?.message || 'データの取得に失敗しました'}
            </div>
          )}

          {/* 職員一覧テーブル */}
          <div className="rounded-lg bg-white shadow">
            <StaffListTable staffList={data?.data || []} isLoading={isLoading} />
          </div>

          {/* ページネーション */}
          {data && data.meta.total > 0 && (
            <div className="mt-6">
              <Pagination meta={data.meta} onPageChange={handlePageChange} />
              <p className="mt-3 text-center text-sm text-gray-500">
                {data.meta.from}〜{data.meta.to}件 / 全{data.meta.total}件
              </p>
            </div>
          )}
        </div>
      </div>
    </MainLayout>
  )
}
