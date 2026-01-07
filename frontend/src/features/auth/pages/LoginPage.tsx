/**
 * ログインページ
 *
 * 職員がシステムにログインするためのページ。
 * ログインフォームと認証処理を統合。
 * ログアウト後のリダイレクト時にはメッセージを表示。
 *
 * @feature 001-staff-logout
 */

import { useState, useEffect } from 'react'
import { useLocation } from 'react-router-dom'
import { LoginForm } from '../components/LoginForm'
import { useLogin } from '../hooks/useLogin'

/**
 * ログインページの location state 型定義
 */
interface LocationState {
  /** ログアウト後のリダイレクト時に true */
  loggedOut?: boolean
}

/**
 * ログインページコンポーネント
 *
 * ログインフォームを表示し、認証処理を実行。
 * ログイン成功時はダッシュボードへリダイレクト。
 * ログアウト後のリダイレクト時はメッセージを表示。
 */
export function LoginPage() {
  const { login, isPending, error } = useLogin()
  const location = useLocation()
  const state = location.state as LocationState | null

  // ログアウトメッセージの表示状態
  const [showLogoutMessage, setShowLogoutMessage] = useState(state?.loggedOut ?? false)

  // 5秒後にメッセージを自動非表示
  useEffect(() => {
    if (showLogoutMessage) {
      const timer = setTimeout(() => {
        setShowLogoutMessage(false)
      }, 5000)
      return () => clearTimeout(timer)
    }
  }, [showLogoutMessage])

  // ブラウザ履歴から state をクリア（リロード時の再表示防止）
  useEffect(() => {
    if (state?.loggedOut) {
      window.history.replaceState({}, document.title)
    }
  }, [state])

  return (
    <div className="flex min-h-screen items-center justify-center bg-gray-100 px-4 py-12 sm:px-6 lg:px-8">
      <div className="w-full max-w-md space-y-8">
        {/* ヘッダー */}
        <div className="text-center">
          <h1 className="text-3xl font-bold tracking-tight text-gray-900">ログイン</h1>
          <p className="mt-2 text-sm text-gray-600">メールアドレスとパスワードを入力してください</p>
        </div>

        {/* ログアウト完了メッセージ */}
        {showLogoutMessage && (
          <div
            role="alert"
            className="rounded-md bg-green-50 p-4 text-green-800 border border-green-200"
          >
            ログアウトしました
          </div>
        )}

        {/* ログインフォーム */}
        <div className="rounded-lg bg-white px-6 py-8 shadow sm:px-10">
          <LoginForm onSubmit={login} isLoading={isPending} apiError={error?.message} />
        </div>
      </div>
    </div>
  )
}
