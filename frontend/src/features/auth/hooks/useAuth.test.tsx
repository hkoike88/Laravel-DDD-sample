/**
 * useAuth フックの単体テスト
 *
 * 認証状態アクセスフックの動作を検証：
 * - 初期状態を正しく返す
 * - setAuthenticated でユーザー情報を設定
 * - clearAuthentication で認証クリア
 * - setLoading でローディング状態を設定
 */

import { describe, test, expect, beforeEach } from 'vitest'
import { renderHook, act } from '@testing-library/react'
import { useAuth } from './useAuth'
import { useAuthStore } from '../stores/authStore'

describe('useAuth', () => {
  beforeEach(() => {
    // 各テスト前に認証状態をリセット
    useAuthStore.setState({
      isAuthenticated: false,
      currentUser: null,
      isLoading: true,
    })
  })

  describe('初期状態', () => {
    test('初期状態を正しく返す', () => {
      const { result } = renderHook(() => useAuth())

      expect(result.current.isAuthenticated).toBe(false)
      expect(result.current.currentUser).toBeNull()
      expect(result.current.isLoading).toBe(true)
    })

    test('全てのアクションが関数として提供される', () => {
      const { result } = renderHook(() => useAuth())

      expect(typeof result.current.setAuthenticated).toBe('function')
      expect(typeof result.current.clearAuthentication).toBe('function')
      expect(typeof result.current.setLoading).toBe('function')
    })
  })

  describe('setAuthenticated', () => {
    test('ユーザー情報を設定すると認証済み状態になる', () => {
      const { result } = renderHook(() => useAuth())
      const mockUser = {
        id: '01HXYZ1234567890123456789A',
        name: 'テスト職員',
        email: 'staff@example.com',
        is_admin: false,
      }

      act(() => {
        result.current.setAuthenticated(mockUser)
      })

      expect(result.current.isAuthenticated).toBe(true)
      expect(result.current.currentUser).toEqual(mockUser)
      expect(result.current.isLoading).toBe(false)
    })
  })

  describe('clearAuthentication', () => {
    test('認証クリアすると未認証状態になる', () => {
      const { result } = renderHook(() => useAuth())

      // 先に認証状態にする
      act(() => {
        result.current.setAuthenticated({
          id: '01HXYZ1234567890123456789A',
          name: 'テスト職員',
          email: 'staff@example.com',
          is_admin: false,
        })
      })

      expect(result.current.isAuthenticated).toBe(true)

      // 認証クリア
      act(() => {
        result.current.clearAuthentication()
      })

      expect(result.current.isAuthenticated).toBe(false)
      expect(result.current.currentUser).toBeNull()
      expect(result.current.isLoading).toBe(false)
    })
  })

  describe('setLoading', () => {
    test('ローディング状態を true に設定できる', () => {
      const { result } = renderHook(() => useAuth())

      // 初期状態を false に変更
      act(() => {
        result.current.setLoading(false)
      })

      expect(result.current.isLoading).toBe(false)

      // true に変更
      act(() => {
        result.current.setLoading(true)
      })

      expect(result.current.isLoading).toBe(true)
    })

    test('ローディング状態を false に設定できる', () => {
      const { result } = renderHook(() => useAuth())

      expect(result.current.isLoading).toBe(true) // 初期値

      act(() => {
        result.current.setLoading(false)
      })

      expect(result.current.isLoading).toBe(false)
    })
  })

  describe('複数のフック間での状態共有', () => {
    test('異なるコンポーネントで同じ状態を共有する', () => {
      const { result: result1 } = renderHook(() => useAuth())
      const { result: result2 } = renderHook(() => useAuth())

      const mockUser = {
        id: '01HXYZ1234567890123456789A',
        name: 'テスト職員',
        email: 'staff@example.com',
        is_admin: false,
      }

      // result1 で認証状態を設定
      act(() => {
        result1.current.setAuthenticated(mockUser)
      })

      // result2 でも同じ状態が反映される
      expect(result2.current.isAuthenticated).toBe(true)
      expect(result2.current.currentUser).toEqual(mockUser)
    })
  })
})
