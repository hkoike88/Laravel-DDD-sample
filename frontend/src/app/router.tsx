import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom'
import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { BookSearchPage } from '@/features/books/pages/BookSearchPage'
import { BookRegistrationPage } from '@/features/books/pages/BookRegistrationPage'
import { BookCompletePage } from '@/features/books/pages/BookCompletePage'
import { LoginPage } from '@/features/auth/pages/LoginPage'
import { DashboardPage } from '@/features/dashboard/pages/DashboardPage'
import { StaffAccountsPage } from '@/features/staff/pages/StaffAccountsPage'
import { StaffAccountsNewPage } from '@/pages/staff/StaffAccountsNewPage'
import { StaffAccountsEditPage } from '@/pages/staff/StaffAccountsEditPage'
import { StaffAccountsResultPage } from '@/pages/staff/StaffAccountsResultPage'
import { SessionsPage } from '@/pages/settings/SessionsPage'
import { BooksPage } from '@/pages/BooksPage'
import { LendingPage } from '@/pages/LendingPage'
import { ReturnPage } from '@/pages/ReturnPage'
import { UsersPage } from '@/pages/UsersPage'
import { ReservationsPage } from '@/pages/ReservationsPage'
import { GuestRoute } from '@/features/auth/components/GuestRoute'
import { ProtectedRoute } from '@/features/auth/components/ProtectedRoute'
import { AdminGuard } from '@/components/guards/AdminGuard'
import { AuthProvider } from '@/features/auth/components/AuthProvider'

/**
 * TanStack Query クライアント
 * API リクエストのキャッシュと状態管理を提供
 */
const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      staleTime: 1000 * 60 * 5, // 5分間キャッシュを新鮮とみなす
      retry: 1,
    },
  },
})

/**
 * アプリケーションのルーター設定
 * React Router を使用したクライアントサイドルーティング
 */
export function AppRouter() {
  return (
    <QueryClientProvider client={queryClient}>
      <BrowserRouter>
        <AuthProvider>
          <Routes>
            <Route path="/" element={<Navigate to="/books" replace />} />
            <Route path="/books" element={<BookSearchPage />} />
            {/* 蔵書登録（認証必須） */}
            <Route
              path="/books/new"
              element={
                <ProtectedRoute>
                  <BookRegistrationPage />
                </ProtectedRoute>
              }
            />
            {/* 蔵書登録完了確認（認証必須） */}
            <Route
              path="/books/:id/complete"
              element={
                <ProtectedRoute>
                  <BookCompletePage />
                </ProtectedRoute>
              }
            />
            <Route
              path="/login"
              element={
                <GuestRoute>
                  <LoginPage />
                </GuestRoute>
              }
            />
            <Route
              path="/dashboard"
              element={
                <ProtectedRoute>
                  <DashboardPage />
                </ProtectedRoute>
              }
            />
            <Route
              path="/books/manage"
              element={
                <ProtectedRoute>
                  <BooksPage />
                </ProtectedRoute>
              }
            />
            <Route
              path="/loans/checkout"
              element={
                <ProtectedRoute>
                  <LendingPage />
                </ProtectedRoute>
              }
            />
            <Route
              path="/loans/return"
              element={
                <ProtectedRoute>
                  <ReturnPage />
                </ProtectedRoute>
              }
            />
            <Route
              path="/users"
              element={
                <ProtectedRoute>
                  <UsersPage />
                </ProtectedRoute>
              }
            />
            <Route
              path="/reservations"
              element={
                <ProtectedRoute>
                  <ReservationsPage />
                </ProtectedRoute>
              }
            />
            {/* 管理者専用ルート */}
            <Route
              path="/staff/accounts"
              element={
                <AdminGuard>
                  <StaffAccountsPage />
                </AdminGuard>
              }
            />
            <Route
              path="/staff/accounts/new"
              element={
                <AdminGuard>
                  <StaffAccountsNewPage />
                </AdminGuard>
              }
            />
            <Route
              path="/staff/accounts/result"
              element={
                <AdminGuard>
                  <StaffAccountsResultPage />
                </AdminGuard>
              }
            />
            <Route
              path="/staff/accounts/:id/edit"
              element={
                <AdminGuard>
                  <StaffAccountsEditPage />
                </AdminGuard>
              }
            />
            {/* セッション管理（全認証済みユーザー）@feature 001-security-preparation */}
            <Route
              path="/settings/sessions"
              element={
                <ProtectedRoute>
                  <SessionsPage />
                </ProtectedRoute>
              }
            />
          </Routes>
        </AuthProvider>
      </BrowserRouter>
    </QueryClientProvider>
  )
}
