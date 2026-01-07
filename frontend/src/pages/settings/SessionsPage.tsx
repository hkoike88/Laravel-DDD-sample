import { MainLayout } from '@/components/layout/MainLayout'
import { SessionList } from '@/features/auth/components/SessionList'
import { PasswordChangeForm } from '@/features/settings/components/PasswordChangeForm'

/**
 * セキュリティ設定ページ
 *
 * 認証済み職員のセキュリティ関連設定を管理するページ。
 * - パスワード変更
 * - セッション管理
 *
 * @feature 001-security-preparation
 */
export function SessionsPage() {
  return (
    <MainLayout>
      <div className="bg-gray-100 min-h-screen py-8">
        <div className="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
          <div className="mb-6">
            <h1 className="text-2xl font-bold text-gray-900">セキュリティ設定</h1>
            <p className="mt-1 text-sm text-gray-600">
              パスワードの変更やセッションの管理を行います。
            </p>
          </div>

          {/* パスワード変更 */}
          <div className="mb-8">
            <PasswordChangeForm />
          </div>

          {/* セッション管理 */}
          <div className="mb-8">
            <SessionList />
          </div>

          <div className="p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
            <h3 className="text-sm font-medium text-yellow-800">セキュリティのヒント</h3>
            <ul className="mt-2 text-sm text-yellow-700 list-disc list-inside">
              <li>パスワードは定期的に変更することをお勧めします</li>
              <li>見覚えのないデバイスやIPアドレスからのセッションは終了してください</li>
              <li>共有PCを使用した後は、必ずログアウトしてください</li>
            </ul>
          </div>
        </div>
      </div>
    </MainLayout>
  )
}
