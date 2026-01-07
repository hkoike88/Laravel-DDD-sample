import axios, { AxiosError } from 'axios'
import type { AxiosResponse } from 'axios'

/**
 * API ベース URL
 * 環境変数から取得、未設定時はデフォルト値を使用
 */
const API_BASE_URL = import.meta.env.VITE_API_URL || 'http://localhost'

/**
 * セッションタイムアウトエラーかどうかを判定
 *
 * @param error - Axios エラー
 * @returns セッションタイムアウトの場合 true
 */
function isSessionTimeoutError(error: AxiosError): boolean {
  if (error.response?.status !== 401) {
    return false
  }

  const data = error.response.data as { message?: string } | undefined
  if (!data?.message) {
    return false
  }

  // メッセージに「セッション」または「タイムアウト」が含まれる場合
  return data.message.includes('セッション') || data.message.includes('timeout')
}

/**
 * セッションタイムアウト時の処理
 *
 * 認証状態をクリアしてログイン画面にリダイレクトする。
 * 動的インポートで循環参照を回避。
 */
async function handleSessionTimeout(): Promise<void> {
  // 動的インポートで循環参照を回避
  const { useAuthStore } = await import('@/features/auth/stores/authStore')
  const { clearAuthentication } = useAuthStore.getState()

  // 認証状態をクリア
  clearAuthentication()

  // ログイン画面にリダイレクト（タイムアウトメッセージを含める）
  const currentPath = window.location.pathname
  if (currentPath !== '/login') {
    window.location.href = '/login?reason=session_timeout'
  }
}

/**
 * APIクライアントのAxiosインスタンス
 * 蔵書検索APIとの通信に使用
 *
 * Laravel Sanctum SPA 認証に対応した設定:
 * - withCredentials: クッキー（セッション、CSRF トークン）の送受信を有効化
 * - withXSRFToken: XSRF-TOKEN クッキーから X-XSRF-TOKEN ヘッダーを自動設定
 */
export const apiClient = axios.create({
  baseURL: `${API_BASE_URL}/api`,
  headers: {
    Accept: 'application/json',
    'Content-Type': 'application/json',
  },
  withCredentials: true,
  withXSRFToken: true,
})

/**
 * 認証用 Axios インスタンス
 *
 * Laravel Sanctum SPA 認証に対応した設定:
 * - withCredentials: クッキー（セッション、CSRF トークン）の送受信を有効化
 * - withXSRFToken: XSRF-TOKEN クッキーから X-XSRF-TOKEN ヘッダーを自動設定
 */
export const authClient = axios.create({
  baseURL: API_BASE_URL,
  headers: {
    Accept: 'application/json',
    'Content-Type': 'application/json',
  },
  withCredentials: true,
  withXSRFToken: true,
})

/**
 * CSRF トークンを取得
 *
 * Laravel Sanctum SPA 認証では、ログイン前に CSRF トークンを取得する必要がある。
 * このリクエストにより、XSRF-TOKEN クッキーが設定される。
 */
export async function getCsrfToken(): Promise<void> {
  await authClient.get('/sanctum/csrf-cookie')
}

/**
 * レスポンスインターセプター
 *
 * セッションタイムアウトを検知し、適切に処理する。
 */
authClient.interceptors.response.use(
  (response: AxiosResponse) => response,
  async (error: AxiosError) => {
    if (isSessionTimeoutError(error)) {
      await handleSessionTimeout()
    }
    return Promise.reject(error)
  }
)
