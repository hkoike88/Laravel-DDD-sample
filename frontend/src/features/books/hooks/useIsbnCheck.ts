import { useState, useEffect, useCallback, useRef } from 'react'
import { bookApi } from '../api/bookApi'
import type { IsbnCheckResponse } from '../types/book'

/**
 * ISBN形式の正規表現パターン
 */
const ISBN_PATTERN = /^(97[89]\d{10}|\d{9}[\dX])$/

/**
 * ISBN重複チェックフックの戻り値型
 */
interface UseIsbnCheckReturn {
  /** 重複チェック結果 */
  result: IsbnCheckResponse | null
  /** チェック中かどうか */
  isChecking: boolean
  /** エラーオブジェクト */
  error: Error | null
  /** ISBN重複チェックを実行 */
  checkIsbn: (isbn: string) => void
  /** 結果をリセット */
  reset: () => void
}

/**
 * ISBN重複チェックフック
 *
 * debounce付きでISBN重複チェックを実行する。
 * フォーカス移動時（onBlur）にチェックを実行する想定。
 *
 * @param debounceMs - debounce時間（ミリ秒、デフォルト: 300ms）
 */
export function useIsbnCheck(debounceMs = 300): UseIsbnCheckReturn {
  const [result, setResult] = useState<IsbnCheckResponse | null>(null)
  const [isChecking, setIsChecking] = useState(false)
  const [error, setError] = useState<Error | null>(null)
  const timeoutRef = useRef<ReturnType<typeof setTimeout> | null>(null)
  const abortControllerRef = useRef<AbortController | null>(null)

  // クリーンアップ
  useEffect(() => {
    return () => {
      if (timeoutRef.current) {
        clearTimeout(timeoutRef.current)
      }
      if (abortControllerRef.current) {
        abortControllerRef.current.abort()
      }
    }
  }, [])

  const checkIsbn = useCallback(
    (isbn: string) => {
      // 既存のタイマーをキャンセル
      if (timeoutRef.current) {
        clearTimeout(timeoutRef.current)
      }

      // 既存のリクエストをキャンセル
      if (abortControllerRef.current) {
        abortControllerRef.current.abort()
      }

      // 空またはISBN形式でない場合はスキップ
      if (!isbn || !ISBN_PATTERN.test(isbn)) {
        setResult(null)
        setError(null)
        return
      }

      // debounce付きでチェックを実行
      timeoutRef.current = setTimeout(async () => {
        setIsChecking(true)
        setError(null)

        const controller = new AbortController()
        abortControllerRef.current = controller

        try {
          const response = await bookApi.checkIsbn(isbn)
          if (!controller.signal.aborted) {
            setResult(response)
          }
        } catch (err) {
          if (!controller.signal.aborted) {
            setError(err instanceof Error ? err : new Error('ISBN重複チェックに失敗しました'))
            setResult(null)
          }
        } finally {
          if (!controller.signal.aborted) {
            setIsChecking(false)
          }
        }
      }, debounceMs)
    },
    [debounceMs]
  )

  const reset = useCallback(() => {
    if (timeoutRef.current) {
      clearTimeout(timeoutRef.current)
    }
    if (abortControllerRef.current) {
      abortControllerRef.current.abort()
    }
    setResult(null)
    setError(null)
    setIsChecking(false)
  }, [])

  return {
    result,
    isChecking,
    error,
    checkIsbn,
    reset,
  }
}
