/**
 * useLogin フックのユニットテスト
 *
 * ログイン処理、エラーハンドリング、状態管理をテスト
 */
import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { renderHook, waitFor, act } from '@testing-library/react'
import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { MemoryRouter } from 'react-router-dom'
import type { ReactNode } from 'react'
import { useLogin } from './useLogin'
import * as authApi from '../api/authApi'
import { useAuthStore } from '../stores/authStore'
import type { Staff, ApiError } from '../types/auth'

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

describe('useLogin', () => {
  let queryClient: QueryClient

  /**
   * テスト用ラッパー
   */
  const createWrapper = () => {
    queryClient = new QueryClient({
      defaultOptions: {
        queries: { retry: false },
        mutations: { retry: false },
      },
    })

    return ({ children }: { children: ReactNode }) => (
      <QueryClientProvider client={queryClient}>
        <MemoryRouter>{children}</MemoryRouter>
      </QueryClientProvider>
    )
  }

  beforeEach(() => {
    vi.clearAllMocks()
    // ストアをリセット
    useAuthStore.getState().clearAuthentication()
  })

  afterEach(() => {
    queryClient?.clear()
  })

  /**
   * ログイン成功テスト
   */
  describe('ログイン成功', () => {
    const mockStaff: Staff = {
      id: '01HXYZ1234567890123456789A',
      name: 'テスト職員',
      email: 'test@example.com',
      is_admin: false,
    }

    it('ログイン成功時にユーザー情報が認証ストアに保存される', async () => {
      mockedAuthApi.login.mockResolvedValueOnce(mockStaff)

      const { result } = renderHook(() => useLogin(), {
        wrapper: createWrapper(),
      })

      await act(async () => {
        result.current.login({
          email: 'test@example.com',
          password: 'password123',
        })
      })

      await waitFor(() => {
        expect(result.current.isSuccess).toBe(true)
      })

      // 認証ストアの状態を確認
      const authState = useAuthStore.getState()
      expect(authState.isAuthenticated).toBe(true)
      expect(authState.currentUser).toEqual(mockStaff)
    })

    it('ログイン成功時にダッシュボードへリダイレクトする', async () => {
      mockedAuthApi.login.mockResolvedValueOnce(mockStaff)

      const { result } = renderHook(() => useLogin(), {
        wrapper: createWrapper(),
      })

      await act(async () => {
        result.current.login({
          email: 'test@example.com',
          password: 'password123',
        })
      })

      await waitFor(() => {
        expect(result.current.isSuccess).toBe(true)
      })

      expect(mockNavigate).toHaveBeenCalledWith('/dashboard', { replace: true })
    })

    it('ログイン処理中は isLoading が true になる', async () => {
      let resolveLogin: (value: Staff) => void
      const loginPromise = new Promise<Staff>((resolve) => {
        resolveLogin = resolve
      })
      mockedAuthApi.login.mockReturnValueOnce(loginPromise)

      const { result } = renderHook(() => useLogin(), {
        wrapper: createWrapper(),
      })

      act(() => {
        result.current.login({
          email: 'test@example.com',
          password: 'password123',
        })
      })

      // 非同期の開始を待つ
      await waitFor(() => {
        expect(result.current.isPending).toBe(true)
      })

      await act(async () => {
        resolveLogin!(mockStaff)
      })

      await waitFor(() => {
        expect(result.current.isPending).toBe(false)
      })
    })
  })

  /**
   * ログイン失敗テスト
   */
  describe('ログイン失敗', () => {
    it('認証エラー（401）で適切なエラーメッセージが返される', async () => {
      const apiError: ApiError = {
        type: 'authentication',
        message: 'メールアドレスまたはパスワードが正しくありません',
      }
      mockedAuthApi.login.mockRejectedValueOnce(apiError)

      const { result } = renderHook(() => useLogin(), {
        wrapper: createWrapper(),
      })

      await act(async () => {
        result.current.login({
          email: 'wrong@example.com',
          password: 'wrongpassword',
        })
      })

      await waitFor(() => {
        expect(result.current.isError).toBe(true)
      })

      expect(result.current.error).toEqual(apiError)
      expect(mockNavigate).not.toHaveBeenCalled()
    })

    it('アカウントロック（423）で適切なエラーメッセージが返される', async () => {
      const apiError: ApiError = {
        type: 'locked',
        message: 'アカウントがロックされています',
      }
      mockedAuthApi.login.mockRejectedValueOnce(apiError)

      const { result } = renderHook(() => useLogin(), {
        wrapper: createWrapper(),
      })

      await act(async () => {
        result.current.login({
          email: 'locked@example.com',
          password: 'password123',
        })
      })

      await waitFor(() => {
        expect(result.current.isError).toBe(true)
      })

      expect(result.current.error?.type).toBe('locked')
    })

    it('レート制限（429）で適切なエラーメッセージが返される', async () => {
      const apiError: ApiError = {
        type: 'rate_limit',
        message: 'ログイン試行回数が上限に達しました。しばらくしてから再試行してください',
        retryAfter: 60,
      }
      mockedAuthApi.login.mockRejectedValueOnce(apiError)

      const { result } = renderHook(() => useLogin(), {
        wrapper: createWrapper(),
      })

      await act(async () => {
        result.current.login({
          email: 'test@example.com',
          password: 'password123',
        })
      })

      await waitFor(() => {
        expect(result.current.isError).toBe(true)
      })

      expect(result.current.error?.type).toBe('rate_limit')
      expect(result.current.error?.retryAfter).toBe(60)
    })

    it('ネットワークエラーで適切なエラーメッセージが返される', async () => {
      const apiError: ApiError = {
        type: 'network',
        message: '通信エラーが発生しました。ネットワーク接続を確認してください',
      }
      mockedAuthApi.login.mockRejectedValueOnce(apiError)

      const { result } = renderHook(() => useLogin(), {
        wrapper: createWrapper(),
      })

      await act(async () => {
        result.current.login({
          email: 'test@example.com',
          password: 'password123',
        })
      })

      await waitFor(() => {
        expect(result.current.isError).toBe(true)
      })

      expect(result.current.error?.type).toBe('network')
    })

    it('サーバーエラー（500）で適切なエラーメッセージが返される', async () => {
      const apiError: ApiError = {
        type: 'server',
        message: 'サーバーエラーが発生しました。しばらくしてから再試行してください',
      }
      mockedAuthApi.login.mockRejectedValueOnce(apiError)

      const { result } = renderHook(() => useLogin(), {
        wrapper: createWrapper(),
      })

      await act(async () => {
        result.current.login({
          email: 'test@example.com',
          password: 'password123',
        })
      })

      await waitFor(() => {
        expect(result.current.isError).toBe(true)
      })

      expect(result.current.error?.type).toBe('server')
    })
  })

  /**
   * エラーリセットテスト
   */
  describe('エラーリセット', () => {
    it('reset() でエラー状態がクリアされる', async () => {
      const apiError: ApiError = {
        type: 'authentication',
        message: 'メールアドレスまたはパスワードが正しくありません',
      }
      mockedAuthApi.login.mockRejectedValueOnce(apiError)

      const { result } = renderHook(() => useLogin(), {
        wrapper: createWrapper(),
      })

      await act(async () => {
        result.current.login({
          email: 'wrong@example.com',
          password: 'wrongpassword',
        })
      })

      await waitFor(() => {
        expect(result.current.isError).toBe(true)
      })

      await act(async () => {
        result.current.reset()
      })

      await waitFor(() => {
        expect(result.current.isError).toBe(false)
        expect(result.current.error).toBeNull()
      })
    })
  })
})
