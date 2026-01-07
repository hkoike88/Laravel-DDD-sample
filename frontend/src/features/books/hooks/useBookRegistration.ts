import { useMutation, useQueryClient } from '@tanstack/react-query'
import { useNavigate } from 'react-router-dom'
import { bookApi } from '../api/bookApi'
import type { CreateBookInput } from '../types/book'

/**
 * 蔵書登録フックの戻り値型
 */
interface UseBookRegistrationReturn {
  /** 蔵書を登録する */
  register: (input: CreateBookInput) => void
  /** 登録処理中かどうか */
  isLoading: boolean
  /** エラーオブジェクト */
  error: Error | null
  /** 登録成功したかどうか */
  isSuccess: boolean
  /** 登録処理をリセット */
  reset: () => void
}

/**
 * 蔵書登録フック
 *
 * TanStack Query を使用した蔵書登録処理を提供。
 * 登録成功時は確認画面へ遷移する。
 */
export function useBookRegistration(): UseBookRegistrationReturn {
  const queryClient = useQueryClient()
  const navigate = useNavigate()

  const mutation = useMutation({
    mutationFn: (input: CreateBookInput) => bookApi.create(input),
    onSuccess: (book) => {
      // 蔵書検索のキャッシュを無効化
      queryClient.invalidateQueries({ queryKey: ['books'] })
      // 登録完了確認画面へ遷移
      navigate(`/books/${book.id}/complete`)
    },
  })

  return {
    register: mutation.mutate,
    isLoading: mutation.isPending,
    error: mutation.error,
    isSuccess: mutation.isSuccess,
    reset: mutation.reset,
  }
}
