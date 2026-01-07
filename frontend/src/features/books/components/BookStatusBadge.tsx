import type { BookStatus } from '../types/book'

interface BookStatusBadgeProps {
  /** 蔵書の貸出状態 */
  status: BookStatus
}

/**
 * 状態に対応するバッジ設定
 */
const statusConfig: Record<BookStatus, { label: string; className: string }> = {
  available: {
    label: '貸出可',
    className: 'bg-green-100 text-green-800',
  },
  borrowed: {
    label: '貸出中',
    className: 'bg-red-100 text-red-800',
  },
  reserved: {
    label: '予約あり',
    className: 'bg-yellow-100 text-yellow-800',
  },
}

/**
 * 蔵書状態バッジコンポーネント
 * 貸出状態を色分けして視覚的に表示
 */
export function BookStatusBadge({ status }: BookStatusBadgeProps) {
  const config = statusConfig[status]

  return (
    <span
      className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${config.className}`}
    >
      {config.label}
    </span>
  )
}
