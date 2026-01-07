import { defineConfig } from 'vitest/config'
import react from '@vitejs/plugin-react'
import { resolve } from 'path'

/**
 * Vitest設定ファイル
 * ユニットテスト・統合テスト用
 */
export default defineConfig({
  plugins: [react()],
  test: {
    // テスト環境
    environment: 'jsdom',
    // グローバル関数（describe, it, expect等）を自動インポート
    globals: true,
    // セットアップファイル
    setupFiles: ['./src/test/setup.ts'],
    // テストファイルのパターン
    include: ['src/**/*.test.{ts,tsx}'],
    // 除外パターン
    exclude: ['node_modules', 'dist', 'tests/e2e'],
    // カバレッジ設定
    coverage: {
      provider: 'v8',
      reporter: ['text', 'json', 'html'],
      exclude: [
        'node_modules/',
        'src/test/',
        'src/mocks/',
        '**/*.d.ts',
        '**/*.test.{ts,tsx}',
        'src/main.tsx',
        'src/vite-env.d.ts',
      ],
      // カバレッジ閾値
      thresholds: {
        statements: 80,
        branches: 80,
        functions: 80,
        lines: 80,
      },
    },
    // テストタイムアウト
    testTimeout: 10000,
    // フック タイムアウト
    hookTimeout: 10000,
  },
  resolve: {
    alias: {
      '@': resolve(__dirname, './src'),
    },
  },
})
