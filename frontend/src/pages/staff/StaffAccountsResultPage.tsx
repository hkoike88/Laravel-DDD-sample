/**
 * 職員作成結果画面
 *
 * 職員作成成功後に初期パスワードを表示する画面。
 * この画面を離れると初期パスワードは再表示できない。
 *
 * @feature EPIC-003-staff-account-create
 */

import { useLocation, useNavigate, Navigate } from 'react-router-dom'
import { PasswordDisplay } from '@/features/staff-accounts/components/PasswordDisplay'
import type { StaffListItem } from '@/features/staff-accounts/types/staffAccount'

/**
 * ルートステートの型
 */
interface ResultState {
  staff: StaffListItem
  temporaryPassword: string
}

/**
 * 職員作成結果画面コンポーネント
 *
 * @returns 職員作成結果画面
 */
export function StaffAccountsResultPage() {
  const location = useLocation()
  const navigate = useNavigate()
  const state = location.state as ResultState | null

  // state がない場合は職員一覧にリダイレクト
  if (!state?.staff || !state?.temporaryPassword) {
    return <Navigate to="/staff/accounts" replace />
  }

  const { staff, temporaryPassword } = state

  /**
   * 権限の表示ラベルを取得
   */
  const getRoleLabel = (role: string): string => {
    return role === 'admin' ? '管理者' : '一般職員'
  }

  return (
    <div className="mx-auto max-w-2xl px-4 py-8">
      {/* 成功メッセージ */}
      <div className="mb-8 rounded-lg bg-green-50 p-4">
        <div className="flex items-center">
          <svg
            className="h-6 w-6 text-green-600"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              strokeLinecap="round"
              strokeLinejoin="round"
              strokeWidth={2}
              d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
            />
          </svg>
          <h1 className="ml-2 text-lg font-semibold text-green-800">
            職員アカウントを作成しました
          </h1>
        </div>
      </div>

      {/* 作成された職員情報 */}
      <div className="mb-8 rounded-lg bg-white p-6 shadow">
        <h2 className="mb-4 text-lg font-semibold text-gray-900">作成された職員情報</h2>
        <dl className="space-y-3">
          <div className="flex">
            <dt className="w-32 flex-shrink-0 text-sm font-medium text-gray-500">氏名</dt>
            <dd className="text-sm text-gray-900">{staff.name}</dd>
          </div>
          <div className="flex">
            <dt className="w-32 flex-shrink-0 text-sm font-medium text-gray-500">メールアドレス</dt>
            <dd className="text-sm text-gray-900">{staff.email}</dd>
          </div>
          <div className="flex">
            <dt className="w-32 flex-shrink-0 text-sm font-medium text-gray-500">権限</dt>
            <dd className="text-sm text-gray-900">{getRoleLabel(staff.role)}</dd>
          </div>
        </dl>
      </div>

      {/* 初期パスワード */}
      <div className="mb-8 rounded-lg border-2 border-amber-300 bg-amber-50 p-6">
        <h2 className="mb-4 text-lg font-semibold text-amber-800">初期パスワード</h2>
        <p className="mb-4 text-sm text-amber-700">
          以下の初期パスワードを職員に安全な方法で伝達してください。
        </p>
        <PasswordDisplay password={temporaryPassword} />
      </div>

      {/* アクションボタン */}
      <div className="flex justify-center gap-4">
        <button
          type="button"
          onClick={() => navigate('/staff/accounts/new')}
          className="rounded-md border border-blue-600 bg-white px-6 py-2 text-blue-600 font-medium hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
        >
          続けて作成
        </button>
        <button
          type="button"
          onClick={() => navigate('/staff/accounts')}
          className="rounded-md bg-blue-600 px-6 py-2 text-white font-medium hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
        >
          一覧へ戻る
        </button>
      </div>
    </div>
  )
}
