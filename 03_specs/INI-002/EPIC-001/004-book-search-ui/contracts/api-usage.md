# API Usage: 蔵書検索画面

**Feature**: 004-book-search-ui
**Date**: 2025-12-24

## 利用するAPI

本機能では、003-book-search-api で実装済みの蔵書検索APIを利用する。

### GET /api/books - 蔵書検索

**Base URL**: `http://localhost/api`（開発環境）

#### リクエスト

```http
GET /api/books?title=猫&author=夏目&page=1&per_page=20
```

**Query Parameters**:

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| title | string | No | タイトル検索キーワード（部分一致） |
| author | string | No | 著者名検索キーワード（部分一致） |
| isbn | string | No | ISBN（完全一致） |
| page | integer | No | ページ番号（デフォルト: 1） |
| per_page | integer | No | 1ページあたりの件数（デフォルト: 20、最大: 100） |

#### レスポンス

**成功時（200 OK）**:

```json
{
  "data": [
    {
      "id": "01HQXYZ123456789ABCDEFG",
      "title": "吾輩は猫である",
      "author": "夏目漱石",
      "isbn": "9784003101018",
      "publisher": "岩波書店",
      "published_year": 1905,
      "genre": "文学",
      "status": "available"
    }
  ],
  "meta": {
    "total": 100,
    "page": 1,
    "per_page": 20,
    "last_page": 5
  }
}
```

**バリデーションエラー時（422 Unprocessable Entity）**:

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "per_page": ["1ページあたりの件数は100以下で入力してください"]
  }
}
```

## Axiosクライアント設定

```typescript
// frontend/src/lib/axios.ts
import axios from 'axios';

export const apiClient = axios.create({
  baseURL: import.meta.env.VITE_API_URL || 'http://localhost/api',
  headers: {
    'Accept': 'application/json',
    'Content-Type': 'application/json',
  },
});
```

## TanStack Query 使用例

```typescript
// frontend/src/features/books/hooks/useBookSearch.ts
import { useQuery } from '@tanstack/react-query';
import { apiClient } from '@/lib/axios';
import type { BookSearchParams, BookSearchResponse } from '../types/book';

export function useBookSearch(params: BookSearchParams) {
  return useQuery({
    queryKey: ['books', params],
    queryFn: async () => {
      const { data } = await apiClient.get<BookSearchResponse>('/books', {
        params,
      });
      return data;
    },
    staleTime: 1000 * 60, // 1分間キャッシュ
  });
}
```
