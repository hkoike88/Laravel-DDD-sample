/**
 * テストユーティリティ
 * 共通のレンダリング関数やヘルパーを提供
 */
import React, { ReactElement } from 'react'
import { render, RenderOptions } from '@testing-library/react'
import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { BrowserRouter } from 'react-router-dom'

/**
 * テスト用QueryClientを作成
 * 各テストで独立したインスタンスを使用
 */
function createTestQueryClient() {
  return new QueryClient({
    defaultOptions: {
      queries: {
        retry: false, // テストではリトライしない
        gcTime: 0, // キャッシュを無効化
        staleTime: 0,
      },
    },
  })
}

/**
 * プロバイダーラッパー
 * QueryClientProviderとBrowserRouterを提供
 */
interface AllTheProvidersProps {
  children: React.ReactNode
}

function AllTheProviders({ children }: AllTheProvidersProps) {
  const queryClient = createTestQueryClient()

  return (
    <QueryClientProvider client={queryClient}>
      <BrowserRouter>{children}</BrowserRouter>
    </QueryClientProvider>
  )
}

/**
 * カスタムレンダー関数
 * 全てのプロバイダーでラップしてレンダリング
 */
function customRender(ui: ReactElement, options?: Omit<RenderOptions, 'wrapper'>) {
  return render(ui, { wrapper: AllTheProviders, ...options })
}

/**
 * QueryClientProviderのみでラップするレンダー関数
 * ルーティングが不要なコンポーネントテスト用
 */
function renderWithQuery(ui: ReactElement, options?: Omit<RenderOptions, 'wrapper'>) {
  const queryClient = createTestQueryClient()

  function QueryWrapper({ children }: { children: React.ReactNode }) {
    return <QueryClientProvider client={queryClient}>{children}</QueryClientProvider>
  }

  return render(ui, { wrapper: QueryWrapper, ...options })
}

/**
 * ラッパーなしでレンダーする関数
 * 純粋なコンポーネント単体テスト用
 */
function renderWithoutWrapper(ui: ReactElement, options?: Omit<RenderOptions, 'wrapper'>) {
  return render(ui, options)
}

// re-export everything
export * from '@testing-library/react'
export { userEvent } from '@testing-library/user-event'

// override render method
export { customRender as render, renderWithQuery, renderWithoutWrapper }
