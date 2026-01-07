/**
 * メニューグリッドコンポーネント
 *
 * @feature 004-dashboard-ui
 */

import { useState, useCallback } from 'react'
import type { MenuItem, MenuGridProps } from '../types/menu'
import { MenuCard } from './MenuCard'

/**
 * 業務メニューグリッドコンポーネント
 *
 * メニューカードをレスポンシブなグリッドレイアウトで表示。
 * モバイル: 1列、タブレット: 2列、デスクトップ: 3列
 */
export function MenuGrid({ items }: MenuGridProps) {
  const [disabledMessage, setDisabledMessage] = useState<string | null>(null)

  const handleDisabledClick = useCallback((item: MenuItem) => {
    setDisabledMessage(`「${item.label}」は現在準備中です。`)
    // 3秒後にメッセージを消す
    setTimeout(() => {
      setDisabledMessage(null)
    }, 3000)
  }, [])

  return (
    <div>
      {disabledMessage && (
        <div
          className="mb-4 rounded-lg bg-yellow-50 p-4 text-sm text-yellow-800"
          role="alert"
          aria-live="polite"
        >
          {disabledMessage}
        </div>
      )}
      <div
        className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3"
        role="navigation"
        aria-label="業務メニュー"
      >
        {items.map((item) => (
          <MenuCard key={item.id} item={item} onDisabledClick={handleDisabledClick} />
        ))}
      </div>
    </div>
  )
}
