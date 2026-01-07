/**
 * ProtectedRoute コンポーネントの単体テスト
 *
 * 認証ガードの動作を検証：
 * - 認証済み状態で children を表示
 * - 未認証状態でログイン画面にリダイレクト
 * - ローディング中はローディング表示
 */

import { describe, test, expect, beforeEach } from 'vitest'
import { render, screen } from '@testing-library/react'
import { MemoryRouter, Routes, Route } from 'react-router-dom'
import { ProtectedRoute } from './ProtectedRoute'
import { useAuthStore } from '../stores/authStore'

/**
 * テスト用のラッパーコンポーネント
 * MemoryRouter でルーティングをシミュレート
 */
function renderWithRouter(ui: React.ReactElement, { initialEntries = ['/protected'] } = {}) {
  return render(
    <MemoryRouter initialEntries={initialEntries}>
      <Routes>
        <Route path="/protected" element={ui} />
        <Route path="/login" element={<div>ログイン画面</div>} />
      </Routes>
    </MemoryRouter>
  )
}

describe('ProtectedRoute', () => {
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
        <ProtectedRoute>
          <div>保護されたコンテンツ</div>
        </ProtectedRoute>
      )

      expect(screen.getByText('認証確認中...')).toBeInTheDocument()
      expect(screen.queryByText('保護されたコンテンツ')).not.toBeInTheDocument()
    })
  })

  describe('未認証状態', () => {
    test('未認証の場合はログイン画面にリダイレクトする', () => {
      useAuthStore.setState({
        isAuthenticated: false,
        isLoading: false,
      })

      renderWithRouter(
        <ProtectedRoute>
          <div>保護されたコンテンツ</div>
        </ProtectedRoute>
      )

      // リダイレクト先のログイン画面が表示される
      expect(screen.getByText('ログイン画面')).toBeInTheDocument()
      expect(screen.queryByText('保護されたコンテンツ')).not.toBeInTheDocument()
    })
  })

  describe('認証済み状態', () => {
    test('認証済みの場合は children を表示する', () => {
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
        <ProtectedRoute>
          <div>保護されたコンテンツ</div>
        </ProtectedRoute>
      )

      expect(screen.getByText('保護されたコンテンツ')).toBeInTheDocument()
      expect(screen.queryByText('認証確認中...')).not.toBeInTheDocument()
      expect(screen.queryByText('ログイン画面')).not.toBeInTheDocument()
    })
  })

  describe('状態遷移', () => {
    test('ローディング完了後に認証済みなら children を表示する', () => {
      // 初期状態: ローディング中
      useAuthStore.setState({
        isAuthenticated: false,
        isLoading: true,
      })

      const { rerender } = renderWithRouter(
        <ProtectedRoute>
          <div>保護されたコンテンツ</div>
        </ProtectedRoute>
      )

      expect(screen.getByText('認証確認中...')).toBeInTheDocument()

      // ローディング完了、認証済み
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

      rerender(
        <MemoryRouter initialEntries={['/protected']}>
          <Routes>
            <Route
              path="/protected"
              element={
                <ProtectedRoute>
                  <div>保護されたコンテンツ</div>
                </ProtectedRoute>
              }
            />
            <Route path="/login" element={<div>ログイン画面</div>} />
          </Routes>
        </MemoryRouter>
      )

      expect(screen.getByText('保護されたコンテンツ')).toBeInTheDocument()
    })
  })
})
