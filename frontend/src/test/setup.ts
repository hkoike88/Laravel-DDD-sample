/**
 * Vitestテストセットアップファイル
 * 全テストファイルの実行前に読み込まれる
 */
import '@testing-library/jest-dom'
import { cleanup } from '@testing-library/react'
import { afterEach, beforeAll, afterAll } from 'vitest'
import { server } from '@/mocks/server'

/**
 * テスト開始前: MSWサーバーを起動
 */
beforeAll(() => {
  server.listen({ onUnhandledRequest: 'error' })
})

/**
 * 各テスト後: クリーンアップとMSWハンドラーリセット
 */
afterEach(() => {
  cleanup()
  server.resetHandlers()
})

/**
 * 全テスト終了後: MSWサーバーを停止
 */
afterAll(() => {
  server.close()
})
