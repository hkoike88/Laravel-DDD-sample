/**
 * 職員一覧テーブルコンポーネント
 *
 * 職員一覧をテーブル形式で表示する。
 * 編集リンク付き。
 *
 * @feature EPIC-003-staff-account-create
 * @feature EPIC-004-staff-account-edit
 */

import { Link } from 'react-router-dom'
import type { StaffListItem } from '../types/staffAccount'

/**
 * StaffListTable コンポーネントの Props
 */
interface StaffListTableProps {
  /** 職員一覧データ */
  staffList: StaffListItem[]
  /** ローディング状態 */
  isLoading?: boolean
}

/**
 * 職員一覧テーブルコンポーネント
 *
 * @param props - コンポーネントプロパティ
 * @returns 職員一覧テーブル
 *
 * @example
 * <StaffListTable staffList={data.data} isLoading={isLoading} />
 */
export function StaffListTable({ staffList, isLoading = false }: StaffListTableProps) {
  /**
   * 権限の表示ラベルを取得
   */
  const getRoleLabel = (role: string): string => {
    return role === 'admin' ? '管理者' : '一般職員'
  }

  /**
   * 権限のバッジスタイルを取得
   */
  const getRoleBadgeStyle = (role: string): string => {
    return role === 'admin' ? 'bg-purple-100 text-purple-800' : 'bg-gray-100 text-gray-800'
  }

  /**
   * 日時をフォーマット
   */
  const formatDate = (dateString: string): string => {
    const date = new Date(dateString)
    return date.toLocaleString('ja-JP', {
      year: 'numeric',
      month: '2-digit',
      day: '2-digit',
      hour: '2-digit',
      minute: '2-digit',
    })
  }

  if (isLoading) {
    return (
      <div className="flex justify-center items-center py-12">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600" />
        <span className="ml-3 text-gray-600">読み込み中...</span>
      </div>
    )
  }

  if (staffList.length === 0) {
    return (
      <div className="text-center py-12">
        <p className="text-gray-500">職員が登録されていません</p>
      </div>
    )
  }

  return (
    <div className="overflow-x-auto">
      <table className="min-w-full divide-y divide-gray-200">
        <thead className="bg-gray-50">
          <tr>
            <th
              scope="col"
              className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
            >
              氏名
            </th>
            <th
              scope="col"
              className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
            >
              メールアドレス
            </th>
            <th
              scope="col"
              className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
            >
              権限
            </th>
            <th
              scope="col"
              className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
            >
              作成日時
            </th>
            <th
              scope="col"
              className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider"
            >
              操作
            </th>
          </tr>
        </thead>
        <tbody className="bg-white divide-y divide-gray-200">
          {staffList.map((staff) => (
            <tr key={staff.id} className="hover:bg-gray-50">
              <td className="px-6 py-4 whitespace-nowrap">
                <div className="text-sm font-medium text-gray-900">{staff.name}</div>
              </td>
              <td className="px-6 py-4 whitespace-nowrap">
                <div className="text-sm text-gray-900">{staff.email}</div>
              </td>
              <td className="px-6 py-4 whitespace-nowrap">
                <span
                  className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${getRoleBadgeStyle(staff.role)}`}
                >
                  {getRoleLabel(staff.role)}
                </span>
              </td>
              <td className="px-6 py-4 whitespace-nowrap">
                <div className="text-sm text-gray-500">{formatDate(staff.createdAt)}</div>
              </td>
              <td className="px-6 py-4 whitespace-nowrap text-right">
                <Link
                  to={`/staff/accounts/${staff.id}/edit`}
                  className="text-sm text-blue-600 hover:text-blue-800 hover:underline"
                >
                  編集
                </Link>
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  )
}
