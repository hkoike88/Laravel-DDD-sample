import { useState } from 'react'
import { useSessions, type Session } from '../hooks/useSessions'

/**
 * ユーザーエージェントからデバイス名を抽出
 *
 * @param userAgent ユーザーエージェント文字列
 * @returns デバイス名
 */
function parseUserAgent(userAgent: string | null): string {
  if (!userAgent) return '不明なデバイス'

  // 簡易的なパース
  if (userAgent.includes('Windows')) return 'Windows PC'
  if (userAgent.includes('Macintosh')) return 'Mac'
  if (userAgent.includes('iPhone')) return 'iPhone'
  if (userAgent.includes('iPad')) return 'iPad'
  if (userAgent.includes('Android')) return 'Android'
  if (userAgent.includes('Linux')) return 'Linux'

  return 'その他のデバイス'
}

/**
 * タイムスタンプを相対時間に変換
 *
 * @param timestamp UNIXタイムスタンプ（秒）
 * @returns 相対時間文字列
 */
function formatLastActivity(timestamp: number): string {
  const now = Math.floor(Date.now() / 1000)
  const diff = now - timestamp

  if (diff < 60) return '今'
  if (diff < 3600) return `${Math.floor(diff / 60)}分前`
  if (diff < 86400) return `${Math.floor(diff / 3600)}時間前`
  return `${Math.floor(diff / 86400)}日前`
}

/**
 * セッション行コンポーネント
 */
function SessionRow({
  session,
  onTerminate,
  isTerminating,
}: {
  session: Session
  onTerminate: (id: string) => void
  isTerminating: boolean
}) {
  return (
    <tr className={session.is_current ? 'bg-blue-50' : ''}>
      <td className="px-4 py-3 text-sm">
        <div className="flex items-center gap-2">
          {parseUserAgent(session.user_agent)}
          {session.is_current && (
            <span className="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
              現在のセッション
            </span>
          )}
        </div>
      </td>
      <td className="px-4 py-3 text-sm text-gray-500">{session.ip_address || '不明'}</td>
      <td className="px-4 py-3 text-sm text-gray-500">
        {formatLastActivity(session.last_activity)}
      </td>
      <td className="px-4 py-3 text-sm">
        {!session.is_current && (
          <button
            type="button"
            onClick={() => onTerminate(session.id)}
            disabled={isTerminating}
            className="text-red-600 hover:text-red-800 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            終了
          </button>
        )}
      </td>
    </tr>
  )
}

/**
 * セッション一覧コンポーネント
 *
 * 認証済み職員のアクティブなセッション一覧を表示し、
 * 個別のセッション終了や一括終了を行う。
 *
 * @feature 001-security-preparation
 */
export function SessionList() {
  const {
    sessions,
    isLoading,
    isError,
    terminateSession,
    isTerminating,
    terminateOthers,
    isTerminatingOthers,
    refetch,
  } = useSessions()

  const [confirmTerminateAll, setConfirmTerminateAll] = useState(false)

  const handleTerminate = (sessionId: string) => {
    terminateSession(sessionId)
  }

  const handleTerminateAll = () => {
    if (confirmTerminateAll) {
      terminateOthers()
      setConfirmTerminateAll(false)
    } else {
      setConfirmTerminateAll(true)
    }
  }

  // 他のセッション数
  const otherSessionsCount = sessions.filter((s) => !s.is_current).length

  if (isLoading) {
    return <div className="p-4 text-center text-gray-500">セッション情報を読み込み中...</div>
  }

  if (isError) {
    return (
      <div className="p-4 text-center text-red-500">
        <p>セッション情報の取得に失敗しました</p>
        <button
          type="button"
          onClick={() => refetch()}
          className="mt-2 text-blue-600 hover:text-blue-800"
        >
          再試行
        </button>
      </div>
    )
  }

  return (
    <div className="bg-white shadow rounded-lg overflow-hidden">
      <div className="px-4 py-3 border-b border-gray-200 flex justify-between items-center">
        <h3 className="text-lg font-medium text-gray-900">
          アクティブなセッション ({sessions.length})
        </h3>
        {otherSessionsCount > 0 && (
          <button
            type="button"
            onClick={handleTerminateAll}
            disabled={isTerminatingOthers}
            className={`px-3 py-1.5 text-sm font-medium rounded ${
              confirmTerminateAll
                ? 'bg-red-600 text-white hover:bg-red-700'
                : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
            } disabled:opacity-50 disabled:cursor-not-allowed`}
          >
            {isTerminatingOthers
              ? '処理中...'
              : confirmTerminateAll
                ? `本当に${otherSessionsCount}件を終了しますか？`
                : '他のセッションを全て終了'}
          </button>
        )}
      </div>

      {sessions.length === 0 ? (
        <div className="p-4 text-center text-gray-500">アクティブなセッションはありません</div>
      ) : (
        <table className="min-w-full divide-y divide-gray-200">
          <thead className="bg-gray-50">
            <tr>
              <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                デバイス
              </th>
              <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                IPアドレス
              </th>
              <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                最終アクティビティ
              </th>
              <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                操作
              </th>
            </tr>
          </thead>
          <tbody className="bg-white divide-y divide-gray-200">
            {sessions.map((session) => (
              <SessionRow
                key={session.id}
                session={session}
                onTerminate={handleTerminate}
                isTerminating={isTerminating}
              />
            ))}
          </tbody>
        </table>
      )}
    </div>
  )
}
