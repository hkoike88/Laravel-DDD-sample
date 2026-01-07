/**
 * ダッシュボード メニュー型定義
 *
 * @feature 004-dashboard-ui
 */

import type { ReactNode } from 'react'

/**
 * 業務メニュー項目
 */
export interface MenuItem {
  /** メニュー識別子 */
  id: string
  /** 表示ラベル */
  label: string
  /** メニューアイコン */
  icon: ReactNode
  /** 遷移先パス */
  path: string
  /** 有効/無効状態 */
  enabled: boolean
  /** メニューの説明（オプション） */
  description?: string
}

/**
 * メニューカードのプロパティ
 */
export interface MenuCardProps {
  /** メニュー項目 */
  item: MenuItem
  /** 無効メニュークリック時のコールバック */
  onDisabledClick?: (item: MenuItem) => void
}

/**
 * メニューグリッドのプロパティ
 */
export interface MenuGridProps {
  /** メニュー項目の配列 */
  items: MenuItem[]
}

/**
 * ウェルカムメッセージのプロパティ
 */
export interface WelcomeMessageProps {
  /** 職員名 */
  userName: string
  /** 表示日付（オプション） */
  date?: Date
}
