<?php

declare(strict_types=1);

namespace Packages\Domain\Book\Presentation\HTTP\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Packages\Domain\Book\Application\DTO\BookCollection;

/**
 * 蔵書コレクションAPIリソース
 *
 * 蔵書コレクション（検索結果）をJSON形式に変換するリソースクラス。
 * ページネーション情報を含む。
 *
 * @mixin BookCollection
 */
class BookCollectionResource extends JsonResource
{
    /**
     * リソースを配列に変換
     *
     * @param  Request  $request  リクエスト
     * @return array<string, mixed> JSON変換用配列
     */
    public function toArray(Request $request): array
    {
        /** @var BookCollection $collection */
        $collection = $this->resource;

        return [
            'data' => BookResource::collection($collection->items),
            'meta' => [
                'total' => $collection->totalCount,
                'page' => $collection->currentPage,
                'per_page' => $collection->pageSize,
                'last_page' => $collection->totalPages,
            ],
        ];
    }
}
