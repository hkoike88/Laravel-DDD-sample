import { authClient } from '@/lib/axios'

/**
 * セッション情報
 *
 * @feature 001-security-preparation
 */
export interface Session {
  /** セッションID */
  id: string
  /** IPアドレス */
  ip_address: string | null
  /** ユーザーエージェント */
  user_agent: string | null
  /** 最終アクティビティ（UNIXタイムスタンプ） */
  last_activity: number
  /** 現在のセッションかどうか */
  is_current: boolean
}

/**
 * セッション終了結果
 */
export interface TerminateResult {
  message: string
  count?: number
}

/**
 * アクティブなセッション一覧を取得
 *
 * @returns セッション一覧
 */
export async function getActiveSessions(): Promise<Session[]> {
  const response = await authClient.get<{ data: Session[] }>('/api/staff/sessions')
  return response.data.data
}

/**
 * 指定したセッションを終了
 *
 * @param sessionId 終了するセッションID
 * @returns 終了結果
 */
export async function terminateSession(sessionId: string): Promise<TerminateResult> {
  const response = await authClient.delete<TerminateResult>(`/api/staff/sessions/${sessionId}`)
  return response.data
}

/**
 * 現在のセッション以外を全て終了
 *
 * @returns 終了結果（終了したセッション数を含む）
 */
export async function terminateOtherSessions(): Promise<TerminateResult> {
  const response = await authClient.delete<TerminateResult>('/api/staff/sessions/others')
  return response.data
}
