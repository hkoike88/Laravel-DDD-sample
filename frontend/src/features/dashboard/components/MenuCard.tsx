/**
 * メニューカードコンポーネント
 *
 * @feature 004-dashboard-ui
 */

import { Link } from 'react-router-dom'
import type { MenuCardProps } from '../types/menu'

/**
 * 業務メニューカードコンポーネント
 *
 * ダッシュボードに表示される業務機能へのリンクカード。
 * 有効なメニューはクリックで遷移、無効なメニューは「準備中」表示。
 */
export function MenuCard({ item, onDisabledClick }: MenuCardProps) {
  const baseClasses =
    'flex flex-col items-center justify-center rounded-lg border p-6 text-center transition-all duration-200'

  if (!item.enabled) {
    return (
      <button
        type="button"
        onClick={() => onDisabledClick?.(item)}
        className={`${baseClasses} cursor-not-allowed border-gray-200 bg-gray-50 text-gray-400`}
        aria-disabled="true"
        aria-label={`${item.label}（準備中）`}
      >
        <div className="mb-3 text-4xl opacity-50">{item.icon}</div>
        <h3 className="text-lg font-medium">{item.label}</h3>
        <span className="mt-2 text-sm text-gray-400">準備中</span>
      </button>
    )
  }

  return (
    <Link
      to={item.path}
      className={`${baseClasses} border-gray-200 bg-white hover:border-blue-300 hover:bg-blue-50 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2`}
      aria-label={item.label}
    >
      <div className="mb-3 text-4xl text-blue-600">{item.icon}</div>
      <h3 className="text-lg font-medium text-gray-900">{item.label}</h3>
      {item.description && <p className="mt-2 text-sm text-gray-500">{item.description}</p>}
    </Link>
  )
}
