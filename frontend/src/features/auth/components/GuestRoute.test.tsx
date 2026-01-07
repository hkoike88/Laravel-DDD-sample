/**
 * GuestRoute コンポーネントの単体テスト
 *
 * ゲストルートの動作を検証：
 * - 未認証状態で children を表示
 * - 認証済み状態でダッシュボードにリダイレクト
 * - ローディング中はローディング表示
 */

import { describe, test, expect, beforeEach } from 'vitest'
import { render, screen } from '@testing-library/react'
import { MemoryRouter, Routes, Route } from 'react-router-dom'
import { GuestRoute } from './GuestRoute'
import { useAuthStore } from '../stores/authStore'

/**
 * テスト用のラッパーコンポーネント
 * MemoryRouter でルーティングをシミュレート
 */
function renderWithRouter(ui: React.ReactElement, { initialEntries = ['/login'] } = {}) {
  return render(
    <MemoryRouter initialEntries={initialEntries}>
      <Routes>
        <Route path="/login" element={ui} />
        <Route path="/dashboard" element={<div>ダッシュボード</div>} />
      </Routes>
    </MemoryRouter>
  )
}

describe('GuestRoute', () => {
  beforeEach(() => {
    // 各テスト前に認証状態をリセット
    useAuthStore.setState({
      isAuthenticated: false,
      currentUser: null,
      isLoading: true,
    })
  })

  describe('ローディング状態', () => {
    test('認証確認中はローディング表示を表示する', () => {
      useAuthStore.setState({ isLoading: true })

      renderWithRouter(
        <GuestRoute>
          <div>ログイン画面コンテンツ</div>
        </GuestRoute>
      )

      expect(screen.getByText('認証確認中...')).toBeInTheDocument()
      expect(screen.queryByText('ログイン画面コンテンツ')).not.toBeInTheDocument()
    })
  })

  describe('未認証状態', () => {
    test('未認証の場合は children を表示する', () => {
      useAuthStore.setState({
        isAuthenticated: false,
        isLoading: false,
      })

      renderWithRouter(
        <GuestRoute>
          <div>ログイン画面コンテンツ</div>
        </GuestRoute>
      )

      expect(screen.getByText('ログイン画面コンテンツ')).toBeInTheDocument()
      expect(screen.queryByText('認証確認中...')).not.toBeInTheDocument()
      expect(screen.queryByText('ダッシュボード')).not.toBeInTheDocument()
    })
  })

  describe('認証済み状態', () => {
    test('認証済みの場合はダッシュボードにリダイレクトする', () => {
      useAuthStore.setState({
        isAuthenticated: true,
        currentUser: {
          id: '01HXYZ1234567890123456789A',
          name: 'テスト職員',
          email: 'staff@example.com',
          is_admin: false,
        },
        isLoading: false,
      })

      renderWithRouter(
        <GuestRoute>
          <div>ログイン画面コンテンツ</div>
        </GuestRoute>
      )

      // リダイレクト先のダッシュボードが表示される
      expect(screen.getByText('ダッシュボード')).toBeInTheDocument()
      expect(screen.queryByText('ログイン画面コンテンツ')).not.toBeInTheDocument()
    })
  })

  describe('状態遷移', () => {
    test('ローディング完了後に未認証なら children を表示する', () => {
      // 初期状態: ローディング中
      useAuthStore.setState({
        isAuthenticated: false,
        isLoading: true,
      })

      const { rerender } = renderWithRouter(
        <GuestRoute>
          <div>ログイン画面コンテンツ</div>
        </GuestRoute>
      )

      expect(screen.getByText('認証確認中...')).toBeInTheDocument()

      // ローディング完了、未認証
      useAuthStore.setState({
        isAuthenticated: false,
        currentUser: null,
        isLoading: false,
      })

      rerender(
        <MemoryRouter initialEntries={['/login']}>
          <Routes>
            <Route
              path="/login"
              element={
                <GuestRoute>
                  <div>ログイン画面コンテンツ</div>
                </GuestRoute>
              }
            />
            <Route path="/dashboard" element={<div>ダッシュボード</div>} />
          </Routes>
        </MemoryRouter>
      )

      expect(screen.getByText('ログイン画面コンテンツ')).toBeInTheDocument()
    })
  })
})
