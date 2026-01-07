/**
 * LoginPage コンポーネントのユニットテスト
 *
 * ログインページの表示、ログアウトメッセージの表示/非表示をテスト
 *
 * @feature 001-staff-logout
 */
import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { render, screen, act } from '@testing-library/react'
import { MemoryRouter } from 'react-router-dom'
import { LoginPage } from './LoginPage'

// useLogin フックのモック
vi.mock('../hooks/useLogin', () => ({
  useLogin: () => ({
    login: vi.fn(),
    isPending: false,
    error: null,
  }),
}))

describe('LoginPage', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    // window.history.replaceState をモック
    vi.spyOn(window.history, 'replaceState').mockImplementation(() => {})
  })

  afterEach(() => {
    vi.restoreAllMocks()
  })

  /**
   * 基本表示テスト
   */
  describe('基本表示', () => {
    it('ログインページのタイトルが表示される', () => {
      render(
        <MemoryRouter initialEntries={['/login']}>
          <LoginPage />
        </MemoryRouter>
      )

      expect(screen.getByRole('heading', { name: 'ログイン' })).toBeInTheDocument()
    })

    it('ログインフォームが表示される', () => {
      render(
        <MemoryRouter initialEntries={['/login']}>
          <LoginPage />
        </MemoryRouter>
      )

      expect(screen.getByLabelText('メールアドレス')).toBeInTheDocument()
      expect(screen.getByLabelText('パスワード')).toBeInTheDocument()
    })
  })

  /**
   * ログアウトメッセージ表示テスト（T006）
   */
  describe('ログアウトメッセージ表示', () => {
    it('ログアウト後のリダイレクト時にメッセージが表示される', () => {
      render(
        <MemoryRouter initialEntries={[{ pathname: '/login', state: { loggedOut: true } }]}>
          <LoginPage />
        </MemoryRouter>
      )

      expect(screen.getByText('ログアウトしました')).toBeInTheDocument()
    })

    it('ログアウトメッセージが role=alert で表示される', () => {
      render(
        <MemoryRouter initialEntries={[{ pathname: '/login', state: { loggedOut: true } }]}>
          <LoginPage />
        </MemoryRouter>
      )

      expect(screen.getByRole('alert')).toHaveTextContent('ログアウトしました')
    })
  })

  /**
   * 通常遷移時のテスト（T008）
   */
  describe('通常遷移時', () => {
    it('state なしの場合はメッセージが表示されない', () => {
      render(
        <MemoryRouter initialEntries={['/login']}>
          <LoginPage />
        </MemoryRouter>
      )

      expect(screen.queryByText('ログアウトしました')).not.toBeInTheDocument()
    })

    it('state.loggedOut が false の場合はメッセージが表示されない', () => {
      render(
        <MemoryRouter initialEntries={[{ pathname: '/login', state: { loggedOut: false } }]}>
          <LoginPage />
        </MemoryRouter>
      )

      expect(screen.queryByText('ログアウトしました')).not.toBeInTheDocument()
    })

    it('state が null の場合はメッセージが表示されない', () => {
      render(
        <MemoryRouter initialEntries={[{ pathname: '/login', state: null }]}>
          <LoginPage />
        </MemoryRouter>
      )

      expect(screen.queryByText('ログアウトしました')).not.toBeInTheDocument()
    })
  })

  /**
   * メッセージ自動非表示テスト（T007）
   */
  describe('メッセージ自動非表示', () => {
    beforeEach(() => {
      vi.useFakeTimers()
    })

    afterEach(() => {
      vi.useRealTimers()
    })

    it('5秒後にログアウトメッセージが非表示になる', () => {
      render(
        <MemoryRouter initialEntries={[{ pathname: '/login', state: { loggedOut: true } }]}>
          <LoginPage />
        </MemoryRouter>
      )

      // メッセージが表示されていることを確認
      expect(screen.getByText('ログアウトしました')).toBeInTheDocument()

      // 5秒経過
      act(() => {
        vi.advanceTimersByTime(5000)
      })

      // メッセージが非表示になることを確認
      expect(screen.queryByText('ログアウトしました')).not.toBeInTheDocument()
    })

    it('4秒後にはまだメッセージが表示されている', () => {
      render(
        <MemoryRouter initialEntries={[{ pathname: '/login', state: { loggedOut: true } }]}>
          <LoginPage />
        </MemoryRouter>
      )

      // メッセージが表示されていることを確認
      expect(screen.getByText('ログアウトしました')).toBeInTheDocument()

      // 4秒経過
      act(() => {
        vi.advanceTimersByTime(4000)
      })

      // まだメッセージが表示されていることを確認
      expect(screen.getByText('ログアウトしました')).toBeInTheDocument()
    })
  })

  /**
   * ブラウザ履歴 state クリアテスト
   */
  describe('ブラウザ履歴 state クリア', () => {
    it('ログアウト後に history.replaceState が呼ばれる', () => {
      render(
        <MemoryRouter initialEntries={[{ pathname: '/login', state: { loggedOut: true } }]}>
          <LoginPage />
        </MemoryRouter>
      )

      expect(window.history.replaceState).toHaveBeenCalledWith({}, document.title)
    })

    it('state なしの場合は history.replaceState が呼ばれない', () => {
      render(
        <MemoryRouter initialEntries={['/login']}>
          <LoginPage />
        </MemoryRouter>
      )

      expect(window.history.replaceState).not.toHaveBeenCalled()
    })
  })
})
