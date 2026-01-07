<?php

declare(strict_types=1);

namespace Packages\Domain\Book\Domain\Exceptions;

use Packages\Domain\Book\Domain\ValueObjects\BookId;
use RuntimeException;

/**
 * 蔵書が見つからない例外
 *
 * リポジトリの find メソッドで蔵書が存在しない場合にスロー。
 */
final class BookNotFoundException extends RuntimeException
{
    /**
     * 見つからなかった蔵書ID
     */
    private readonly BookId $bookId;

    /**
     * コンストラクタ
     *
     * @param  BookId  $bookId  見つからなかった蔵書ID
     */
    public function __construct(BookId $bookId)
    {
        $this->bookId = $bookId;

        parent::__construct(
            sprintf('蔵書が見つかりません: %s', $bookId->value())
        );
    }

    /**
     * 見つからなかった蔵書IDを取得
     */
    public function getBookId(): BookId
    {
        return $this->bookId;
    }
}
