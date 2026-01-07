/**
 * authStore の単体テスト
 *
 * Zustand 認証ストアの動作を検証：
 * - 初期状態
 * - setAuthenticated アクション
 * - clearAuthentication アクション
 * - setLoading アクション
 */

import { describe, test, expect, beforeEach } from 'vitest'
import { useAuthStore } from './authStore'

describe('authStore', () => {
  beforeEach(() => {
    // 各テスト前に状態をリセット
    useAuthStore.setState({
      isAuthenticated: false,
      currentUser: null,
      isLoading: true,
    })
  })

  describe('初期状態', () => {
    test('初期状態が正しく設定されている', () => {
      const state = useAuthStore.getState()

      expect(state.isAuthenticated).toBe(false)
      expect(state.currentUser).toBeNull()
      expect(state.isLoading).toBe(true)
    })
  })

  describe('setAuthenticated', () => {
    test('ユーザー情報を設定すると認証済み状態になる', () => {
      const mockUser = {
        id: '01HXYZ1234567890123456789A',
        name: 'テスト職員',
        email: 'staff@example.com',
        is_admin: false,
      }

      useAuthStore.getState().setAuthenticated(mockUser)
      const state = useAuthStore.getState()

      expect(state.isAuthenticated).toBe(true)
      expect(state.currentUser).toEqual(mockUser)
      expect(state.isLoading).toBe(false)
    })

    test('異なるユーザーで再設定できる', () => {
      const mockUser1 = {
        id: '01HXYZ1234567890123456789A',
        name: 'ユーザー1',
        email: 'user1@example.com',
        is_admin: false,
      }
      const mockUser2 = {
        id: '01HXYZ1234567890123456789B',
        name: 'ユーザー2',
        email: 'user2@example.com',
        is_admin: false,
      }

      useAuthStore.getState().setAuthenticated(mockUser1)
      expect(useAuthStore.getState().currentUser?.name).toBe('ユーザー1')

      useAuthStore.getState().setAuthenticated(mockUser2)
      expect(useAuthStore.getState().currentUser?.name).toBe('ユーザー2')
    })
  })

  describe('clearAuthentication', () => {
    test('認証クリアすると未認証状態になる', () => {
      // 先に認証状態にする
      useAuthStore.getState().setAuthenticated({
        id: '01HXYZ1234567890123456789A',
        name: 'テスト職員',
        email: 'staff@example.com',
        is_admin: false,
      })

      expect(useAuthStore.getState().isAuthenticated).toBe(true)

      // 認証クリア
      useAuthStore.getState().clearAuthentication()
      const state = useAuthStore.getState()

      expect(state.isAuthenticated).toBe(false)
      expect(state.currentUser).toBeNull()
      expect(state.isLoading).toBe(false)
    })

    test('未認証状態でクリアしてもエラーにならない', () => {
      // 初期状態（未認証）でクリア
      expect(() => {
        useAuthStore.getState().clearAuthentication()
      }).not.toThrow()

      const state = useAuthStore.getState()
      expect(state.isAuthenticated).toBe(false)
      expect(state.currentUser).toBeNull()
    })
  })

  describe('setLoading', () => {
    test('ローディング状態を true に設定できる', () => {
      useAuthStore.getState().setLoading(false)
      expect(useAuthStore.getState().isLoading).toBe(false)

      useAuthStore.getState().setLoading(true)
      expect(useAuthStore.getState().isLoading).toBe(true)
    })

    test('ローディング状態を false に設定できる', () => {
      expect(useAuthStore.getState().isLoading).toBe(true) // 初期値

      useAuthStore.getState().setLoading(false)
      expect(useAuthStore.getState().isLoading).toBe(false)
    })

    test('他の状態に影響しない', () => {
      const mockUser = {
        id: '01HXYZ1234567890123456789A',
        name: 'テスト職員',
        email: 'staff@example.com',
        is_admin: false,
      }

      useAuthStore.getState().setAuthenticated(mockUser)
      useAuthStore.getState().setLoading(true)

      const state = useAuthStore.getState()
      expect(state.isAuthenticated).toBe(true)
      expect(state.currentUser).toEqual(mockUser)
      expect(state.isLoading).toBe(true)
    })
  })

  describe('状態の購読', () => {
    test('状態変更をsubscribeで検知できる', () => {
      let callCount = 0
      const unsubscribe = useAuthStore.subscribe(() => {
        callCount++
      })

      useAuthStore.getState().setLoading(false)
      expect(callCount).toBe(1)

      useAuthStore.getState().setAuthenticated({
        id: '01HXYZ1234567890123456789A',
        name: 'テスト職員',
        email: 'staff@example.com',
        is_admin: false,
      })
      expect(callCount).toBe(2)

      unsubscribe()

      // unsubscribe 後は呼ばれない
      useAuthStore.getState().clearAuthentication()
      expect(callCount).toBe(2)
    })
  })
})
