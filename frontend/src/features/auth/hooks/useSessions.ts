import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import {
  getActiveSessions,
  terminateOtherSessions,
  terminateSession,
  type Session,
} from '../services/sessionApi'

/**
 * セッション一覧クエリキー
 */
const SESSIONS_QUERY_KEY = ['sessions'] as const

/**
 * セッション管理フック
 *
 * 認証済み職員のセッション一覧取得と管理機能を提供する。
 *
 * @feature 001-security-preparation
 */
export function useSessions() {
  const queryClient = useQueryClient()

  /**
   * アクティブなセッション一覧を取得
   */
  const sessionsQuery = useQuery({
    queryKey: SESSIONS_QUERY_KEY,
    queryFn: getActiveSessions,
    staleTime: 30 * 1000, // 30秒間はキャッシュを使用
  })

  /**
   * 指定したセッションを終了
   */
  const terminateMutation = useMutation({
    mutationFn: terminateSession,
    onSuccess: () => {
      // セッション一覧を再取得
      queryClient.invalidateQueries({ queryKey: SESSIONS_QUERY_KEY })
    },
  })

  /**
   * 他のセッションを全て終了
   */
  const terminateOthersMutation = useMutation({
    mutationFn: terminateOtherSessions,
    onSuccess: () => {
      // セッション一覧を再取得
      queryClient.invalidateQueries({ queryKey: SESSIONS_QUERY_KEY })
    },
  })

  return {
    /** セッション一覧 */
    sessions: sessionsQuery.data ?? [],
    /** ローディング状態 */
    isLoading: sessionsQuery.isLoading,
    /** エラー状態 */
    isError: sessionsQuery.isError,
    /** エラー内容 */
    error: sessionsQuery.error,
    /** セッションを終了 */
    terminateSession: terminateMutation.mutate,
    /** セッション終了中 */
    isTerminating: terminateMutation.isPending,
    /** 他のセッションを全て終了 */
    terminateOthers: terminateOthersMutation.mutate,
    /** 他のセッション終了中 */
    isTerminatingOthers: terminateOthersMutation.isPending,
    /** セッション一覧を再取得 */
    refetch: sessionsQuery.refetch,
  }
}

export type { Session }
