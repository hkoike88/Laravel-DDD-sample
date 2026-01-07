/**
 * 認証プロバイダーコンポーネント
 *
 * アプリケーション起動時に認証状態を確認し、
 * 認証コンテキストを子コンポーネントに提供。
 */

import type { ReactNode } from 'react'
import { useAuthCheck } from '../hooks/useAuthCheck'

interface AuthProviderProps {
  /** 子コンポーネント */
  children: ReactNode
}

/**
 * AuthProvider コンポーネント
 *
 * アプリケーションのルートに配置し、認証状態の初期化を行う。
 *
 * @example
 * <AuthProvider>
 *   <Routes>
 *     <Route path="/login" element={<LoginPage />} />
 *   </Routes>
 * </AuthProvider>
 */
export function AuthProvider({ children }: AuthProviderProps) {
  // 認証状態を確認（初回マウント時に実行）
  useAuthCheck()

  return <>{children}</>
}
