/**
 * ウェルカムメッセージコンポーネント
 *
 * @feature 004-dashboard-ui
 */

import type { WelcomeMessageProps } from '../types/menu'

/**
 * 日付を日本語形式でフォーマット
 */
function formatDate(date: Date): string {
  return date.toLocaleDateString('ja-JP', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    weekday: 'long',
  })
}

/**
 * ウェルカムメッセージコンポーネント
 *
 * ダッシュボードに表示されるログイン中の職員へのウェルカムメッセージ。
 * 職員名と現在の日付を表示。
 */
export function WelcomeMessage({ userName, date = new Date() }: WelcomeMessageProps) {
  return (
    <div className="rounded-lg bg-white p-6 shadow">
      <p className="text-lg text-gray-600">
        ようこそ、<span className="font-semibold text-gray-900">{userName}</span> さん
      </p>
      <p className="mt-2 text-sm text-gray-500">{formatDate(date)}</p>
    </div>
  )
}
