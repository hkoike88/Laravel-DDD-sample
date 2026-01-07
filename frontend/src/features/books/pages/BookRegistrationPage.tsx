import { Link } from 'react-router-dom'
import { MainLayout } from '@/components/layout/MainLayout'
import { BookRegistrationForm } from '../components/BookRegistrationForm'

/**
 * 蔵書登録ページ
 *
 * 職員が新規蔵書を登録するためのページ。
 * 認証済みユーザーのみアクセス可能。
 */
export function BookRegistrationPage() {
  return (
    <MainLayout>
      <div className="max-w-2xl mx-auto">
        {/* パンくずリスト */}
        <nav className="mb-4 text-sm text-gray-500">
          <Link to="/dashboard" className="hover:text-blue-600">
            ダッシュボード
          </Link>
          <span className="mx-2">/</span>
          <span className="text-gray-700">蔵書登録</span>
        </nav>

        {/* ページヘッダー */}
        <div className="mb-6">
          <h1 className="text-2xl font-bold text-gray-900">蔵書登録</h1>
          <p className="mt-1 text-sm text-gray-500">新しい蔵書を図書館システムに登録します</p>
        </div>

        {/* 登録フォーム */}
        <div className="bg-white p-6 rounded-lg shadow">
          <BookRegistrationForm />
        </div>
      </div>
    </MainLayout>
  )
}
