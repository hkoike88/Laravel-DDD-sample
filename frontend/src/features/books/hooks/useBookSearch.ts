import { useQuery } from '@tanstack/react-query'
import { bookApi } from '../api/bookApi'
import type { BookSearchParams } from '../types/book'

/**
 * 蔵書検索カスタムフック
 * TanStack Queryを使用してサーバー状態を管理
 * @param params - 検索パラメータ
 * @returns クエリ結果
 */
export function useBookSearch(params: BookSearchParams) {
  return useQuery({
    queryKey: ['books', params],
    queryFn: () => bookApi.search(params),
    staleTime: 1000 * 60, // 1分間キャッシュ
  })
}
