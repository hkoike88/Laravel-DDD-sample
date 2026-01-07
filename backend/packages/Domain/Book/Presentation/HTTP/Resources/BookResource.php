<?php

declare(strict_types=1);

namespace Packages\Domain\Book\Presentation\HTTP\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Packages\Domain\Book\Domain\Model\Book;

/**
 * 蔵書APIリソース
 *
 * 蔵書エンティティをJSON形式に変換するリソースクラス。
 *
 * @mixin Book
 */
class BookResource extends JsonResource
{
    /**
     * リソースを配列に変換
     *
     * @param  Request  $request  リクエスト
     * @return array<string, mixed> JSON変換用配列
     */
    public function toArray(Request $request): array
    {
        /** @var Book $book */
        $book = $this->resource;

        return [
            'id' => $book->id()->value(),
            'title' => $book->title(),
            'author' => $book->author(),
            'isbn' => $book->isbn()?->value(),
            'publisher' => $book->publisher(),
            'published_year' => $book->publishedYear(),
            'genre' => $book->genre(),
            'status' => $book->status()->value(),
            'registered_by' => $book->registeredBy(),
            'registered_at' => $book->registeredAt()?->format(\DateTimeInterface::ATOM),
        ];
    }
}
