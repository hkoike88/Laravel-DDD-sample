/**
 * MSW テスト用サーバー設定
 * Node.js環境（Vitest）でAPIモックを提供
 */
import { setupServer } from 'msw/node'
import { handlers } from './handlers'

/**
 * MSWサーバーインスタンス
 * テストセットアップで起動される
 */
export const server = setupServer(...handlers)
