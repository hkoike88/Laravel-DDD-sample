/**
 * 認証ストア
 *
 * Zustand を使用した認証状態管理。
 * 認証済みフラグ、現在のユーザー情報、ローディング状態を管理。
 */

import { create } from 'zustand'
import type { AuthStore, Staff } from '../types/auth'

/**
 * 認証ストア
 *
 * グローバルな認証状態を管理する Zustand ストア。
 */
export const useAuthStore = create<AuthStore>((set) => ({
  // 初期状態
  isAuthenticated: false,
  currentUser: null,
  isLoading: true, // 初回認証確認中は true

  // アクション
  /**
   * 認証成功時の処理
   * @param user - 認証されたユーザー情報
   */
  setAuthenticated: (user: Staff) =>
    set({
      isAuthenticated: true,
      currentUser: user,
      isLoading: false,
    }),

  /**
   * 認証クリア（ログアウト時）
   */
  clearAuthentication: () =>
    set({
      isAuthenticated: false,
      currentUser: null,
      isLoading: false,
    }),

  /**
   * ローディング状態設定
   * @param loading - ローディング状態
   */
  setLoading: (loading: boolean) =>
    set({
      isLoading: loading,
    }),
}))
