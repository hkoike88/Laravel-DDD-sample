/**
 * useLogout フックのユニットテスト
 *
 * ログアウト処理、状態クリア、リダイレクトをテスト
 *
 * @feature 001-staff-logout
 */
import { describe, it, expect, vi, beforeEach } from 'vitest'
import { renderHook, waitFor, act } from '@testing-library/react'
import { MemoryRouter } from 'react-router-dom'
import type { ReactNode } from 'react'
import { useLogout } from './useLogout'
import * as authApi from '../api/authApi'
import { useAuthStore } from '../stores/authStore'

// authApi のモック
vi.mock('../api/authApi')
const mockedAuthApi = vi.mocked(authApi)

// react-router-dom のナビゲーションをモック
const mockNavigate = vi.fn()
vi.mock('react-router-dom', async () => {
  const actual = await vi.importActual('react-router-dom')
  return {
    ...actual,
    useNavigate: () => mockNavigate,
  }
})

describe('useLogout', () => {
  /**
   * テスト用ラッパー
   */
  const createWrapper = () => {
    return ({ children }: { children: ReactNode }) => <MemoryRouter>{children}</MemoryRouter>
  }

  beforeEach(() => {
    vi.clearAllMocks()
    // ストアをリセット
    useAuthStore.getState().clearAuthentication()
    // コンソールエラーをモック
    vi.spyOn(console, 'error').mockImplementation(() => {})
  })

  /**
   * ログアウト成功テスト
   */
  describe('ログアウト成功', () => {
    it('ログアウト成功時にログイン画面へリダイレクトする', async () => {
      mockedAuthApi.logout.mockResolvedValueOnce(undefined)

      const { result } = renderHook(() => useLogout(), {
        wrapper: createWrapper(),
      })

      await act(async () => {
        await result.current.logout()
      })

      expect(mockNavigate).toHaveBeenCalledWith('/login', {
        replace: true,
        state: { loggedOut: true },
      })
    })

    it('ログアウト成功時に認証状態がクリアされる', async () => {
      // 事前に認証状態を設定
      useAuthStore.getState().setAuthenticated({
        id: '01HXYZ1234567890123456789A',
        name: 'テスト職員',
        email: 'test@example.com',
        is_admin: false,
      })
      expect(useAuthStore.getState().isAuthenticated).toBe(true)

      mockedAuthApi.logout.mockResolvedValueOnce(undefined)

      const { result } = renderHook(() => useLogout(), {
        wrapper: createWrapper(),
      })

      await act(async () => {
        await result.current.logout()
      })

      expect(useAuthStore.getState().isAuthenticated).toBe(false)
      expect(useAuthStore.getState().currentUser).toBeNull()
    })

    it('ログアウト処理中は isLoggingOut が true になる', async () => {
      let resolveLogout: () => void
      const logoutPromise = new Promise<void>((resolve) => {
        resolveLogout = resolve
      })
      mockedAuthApi.logout.mockReturnValueOnce(logoutPromise)

      const { result } = renderHook(() => useLogout(), {
        wrapper: createWrapper(),
      })

      expect(result.current.isLoggingOut).toBe(false)

      act(() => {
        result.current.logout()
      })

      await waitFor(() => {
        expect(result.current.isLoggingOut).toBe(true)
      })

      await act(async () => {
        resolveLogout!()
      })

      await waitFor(() => {
        expect(result.current.isLoggingOut).toBe(false)
      })
    })
  })

  /**
   * ログアウト失敗テスト（エラー時もログイン画面へ遷移）
   */
  describe('ログアウト失敗', () => {
    it('API エラー時でもログイン画面へリダイレクトする', async () => {
      mockedAuthApi.logout.mockRejectedValueOnce(new Error('Network error'))

      const { result } = renderHook(() => useLogout(), {
        wrapper: createWrapper(),
      })

      await act(async () => {
        await result.current.logout()
      })

      // エラー時でも navigate が呼ばれる
      expect(mockNavigate).toHaveBeenCalledWith('/login', {
        replace: true,
        state: { loggedOut: true },
      })
    })

    it('API エラー時でも認証状態がクリアされる', async () => {
      // 事前に認証状態を設定
      useAuthStore.getState().setAuthenticated({
        id: '01HXYZ1234567890123456789A',
        name: 'テスト職員',
        email: 'test@example.com',
        is_admin: false,
      })
      expect(useAuthStore.getState().isAuthenticated).toBe(true)

      mockedAuthApi.logout.mockRejectedValueOnce(new Error('Server error'))

      const { result } = renderHook(() => useLogout(), {
        wrapper: createWrapper(),
      })

      await act(async () => {
        await result.current.logout()
      })

      // エラー時でも認証状態がクリアされる
      expect(useAuthStore.getState().isAuthenticated).toBe(false)
      expect(useAuthStore.getState().currentUser).toBeNull()
    })

    it('API エラー時はコンソールにエラーログが出力される', async () => {
      const mockError = new Error('API error')
      mockedAuthApi.logout.mockRejectedValueOnce(mockError)

      const { result } = renderHook(() => useLogout(), {
        wrapper: createWrapper(),
      })

      await act(async () => {
        await result.current.logout()
      })

      expect(console.error).toHaveBeenCalledWith('Logout API error:', mockError)
    })
  })

  /**
   * state パラメータのテスト
   */
  describe('navigate state', () => {
    it('ログイン画面へのリダイレクト時に state: { loggedOut: true } が渡される', async () => {
      mockedAuthApi.logout.mockResolvedValueOnce(undefined)

      const { result } = renderHook(() => useLogout(), {
        wrapper: createWrapper(),
      })

      await act(async () => {
        await result.current.logout()
      })

      expect(mockNavigate).toHaveBeenCalledTimes(1)
      expect(mockNavigate).toHaveBeenCalledWith('/login', {
        replace: true,
        state: { loggedOut: true },
      })
    })
  })
})
