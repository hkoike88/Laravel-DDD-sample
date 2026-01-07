/**
 * ダッシュボード画面 フロントエンド型定義
 *
 * @feature 004-dashboard-ui
 * @description ダッシュボードで使用するメニュー項目とナビゲーションの型定義
 */

import type { ReactNode } from 'react'

/**
 * 業務メニュー項目
 *
 * ダッシュボードのカードメニューに表示される各業務機能へのリンク
 */
export interface MenuItem {
  /** メニュー識別子（例: 'books', 'loans'） */
  id: string

  /** 表示ラベル（例: '蔵書管理'） */
  label: string

  /** メニューアイコン（React コンポーネント） */
  icon: ReactNode

  /** 遷移先パス（例: '/books'） */
  path: string

  /** 有効/無効状態（false の場合は「準備中」表示） */
  enabled: boolean

  /** メニューの説明（オプション） */
  description?: string
}

/**
 * ヘッダーナビゲーション項目
 *
 * ヘッダーに表示される主要機能へのリンク
 */
export interface NavigationItem {
  /** ナビ識別子 */
  id: string

  /** 表示ラベル */
  label: string

  /** 遷移先パス */
  path: string
}

/**
 * ユーザーメニュー項目
 *
 * ヘッダーのユーザードロップダウンに表示されるアクション
 */
export interface UserMenuItem {
  /** メニュー識別子 */
  id: string

  /** 表示ラベル */
  label: string

  /** クリック時のアクション */
  action: () => void

  /** アイコン（オプション） */
  icon?: ReactNode

  /** 危険なアクション（赤色表示） */
  danger?: boolean
}

/**
 * メニューカードのプロパティ
 */
export interface MenuCardProps {
  /** メニュー項目 */
  item: MenuItem

  /** クリック時のコールバック（オプション、無効メニュー用） */
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

  /** 表示日付（オプション、デフォルトは現在日付） */
  date?: Date
}

/**
 * ヘッダーのプロパティ
 */
export interface HeaderProps {
  /** ナビゲーション項目 */
  navigationItems: NavigationItem[]

  /** 職員名 */
  userName: string

  /** ログアウトハンドラ */
  onLogout: () => void

  /** ログアウト処理中フラグ */
  isLoggingOut?: boolean
}

/**
 * フッターのプロパティ
 */
export interface FooterProps {
  /** 著作権表示年（オプション、デフォルトは現在年） */
  year?: number

  /** 組織名（オプション） */
  organizationName?: string
}

/**
 * メインレイアウトのプロパティ
 */
export interface MainLayoutProps {
  /** 子要素 */
  children: ReactNode
}
