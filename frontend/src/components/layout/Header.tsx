/**
 * 共通ヘッダーコンポーネント
 *
 * @feature 004-dashboard-ui
 */

import { useState, useCallback } from 'react'
import { Link, useLocation } from 'react-router-dom'

/**
 * ナビゲーション項目
 */
interface NavigationItem {
  /** ナビ識別子 */
  id: string
  /** 表示ラベル */
  label: string
  /** 遷移先パス */
  path: string
}

/**
 * ヘッダーのプロパティ
 */
interface HeaderProps {
  /** 職員名 */
  userName: string
  /** ログアウトハンドラ */
  onLogout: () => void
  /** ログアウト処理中フラグ */
  isLoggingOut?: boolean
}

/**
 * ナビゲーション項目定義
 */
const navigationItems: NavigationItem[] = [
  { id: 'books', label: '蔵書管理', path: '/books/manage' },
  { id: 'book-registration', label: '蔵書登録', path: '/books/new' },
  { id: 'lending', label: '貸出・返却', path: '/loans/checkout' },
  { id: 'users', label: '利用者管理', path: '/users' },
]

/**
 * アプリケーション共通ヘッダー
 *
 * ナビゲーション、ユーザーメニュー、ログアウトボタンを含む。
 */
export function Header({ userName, onLogout, isLoggingOut = false }: HeaderProps) {
  const location = useLocation()
  const [isUserMenuOpen, setIsUserMenuOpen] = useState(false)
  const [isMobileMenuOpen, setIsMobileMenuOpen] = useState(false)

  const toggleUserMenu = useCallback(() => {
    setIsUserMenuOpen((prev) => !prev)
  }, [])

  const closeUserMenu = useCallback(() => {
    setIsUserMenuOpen(false)
  }, [])

  const toggleMobileMenu = useCallback(() => {
    setIsMobileMenuOpen((prev) => !prev)
  }, [])

  const handleLogout = useCallback(() => {
    closeUserMenu()
    onLogout()
  }, [closeUserMenu, onLogout])

  const isCurrentPath = (path: string) => {
    return location.pathname === path || location.pathname.startsWith(path + '/')
  }

  return (
    <header className="bg-white shadow" role="banner">
      <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div className="flex h-16 items-center justify-between">
          {/* ロゴ */}
          <div className="flex items-center">
            <Link
              to="/dashboard"
              className="text-xl font-bold text-gray-900 hover:text-blue-600"
              aria-label="ダッシュボードへ戻る"
            >
              青空市立図書館
            </Link>
          </div>

          {/* デスクトップナビゲーション */}
          <nav
            className="hidden md:flex md:items-center md:space-x-4"
            role="navigation"
            aria-label="メインナビゲーション"
          >
            {navigationItems.map((item) => (
              <Link
                key={item.id}
                to={item.path}
                className={`rounded-md px-3 py-2 text-sm font-medium transition-colors ${
                  isCurrentPath(item.path)
                    ? 'bg-blue-100 text-blue-700'
                    : 'text-gray-700 hover:bg-gray-100 hover:text-gray-900'
                }`}
                aria-current={isCurrentPath(item.path) ? 'page' : undefined}
              >
                {item.label}
              </Link>
            ))}
          </nav>

          {/* ユーザーメニュー */}
          <div className="flex items-center">
            {/* モバイルメニューボタン */}
            <button
              type="button"
              onClick={toggleMobileMenu}
              className="mr-2 rounded-md p-2 text-gray-700 hover:bg-gray-100 md:hidden"
              aria-expanded={isMobileMenuOpen}
              aria-label="メニューを開く"
            >
              <svg
                className="h-6 w-6"
                fill="none"
                viewBox="0 0 24 24"
                strokeWidth={1.5}
                stroke="currentColor"
                aria-hidden="true"
              >
                {isMobileMenuOpen ? (
                  <path strokeLinecap="round" strokeLinejoin="round" d="M6 18 18 6M6 6l12 12" />
                ) : (
                  <path
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"
                  />
                )}
              </svg>
            </button>

            {/* ユーザードロップダウン */}
            <div className="relative">
              <button
                type="button"
                onClick={toggleUserMenu}
                className="flex items-center rounded-md px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100"
                aria-expanded={isUserMenuOpen}
                aria-haspopup="true"
              >
                <span className="mr-2">{userName}</span>
                <svg
                  className={`h-4 w-4 transition-transform ${isUserMenuOpen ? 'rotate-180' : ''}`}
                  fill="none"
                  viewBox="0 0 24 24"
                  strokeWidth={1.5}
                  stroke="currentColor"
                  aria-hidden="true"
                >
                  <path
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    d="m19.5 8.25-7.5 7.5-7.5-7.5"
                  />
                </svg>
              </button>

              {/* ドロップダウンメニュー */}
              {isUserMenuOpen && (
                <div
                  className="absolute right-0 z-10 mt-2 w-48 origin-top-right rounded-md bg-white py-1 shadow-lg ring-1 ring-black ring-opacity-5"
                  role="menu"
                  aria-orientation="vertical"
                >
                  <button
                    type="button"
                    onClick={handleLogout}
                    disabled={isLoggingOut}
                    className="block w-full px-4 py-2 text-left text-sm text-red-600 hover:bg-gray-100 disabled:opacity-50"
                    role="menuitem"
                  >
                    {isLoggingOut ? 'ログアウト中...' : 'ログアウト'}
                  </button>
                </div>
              )}
            </div>
          </div>
        </div>

        {/* モバイルナビゲーション */}
        {isMobileMenuOpen && (
          <nav
            className="border-t border-gray-200 pb-4 pt-2 md:hidden"
            role="navigation"
            aria-label="モバイルナビゲーション"
          >
            {navigationItems.map((item) => (
              <Link
                key={item.id}
                to={item.path}
                onClick={() => setIsMobileMenuOpen(false)}
                className={`block rounded-md px-3 py-2 text-base font-medium ${
                  isCurrentPath(item.path)
                    ? 'bg-blue-100 text-blue-700'
                    : 'text-gray-700 hover:bg-gray-100 hover:text-gray-900'
                }`}
                aria-current={isCurrentPath(item.path) ? 'page' : undefined}
              >
                {item.label}
              </Link>
            ))}
          </nav>
        )}
      </div>
    </header>
  )
}
