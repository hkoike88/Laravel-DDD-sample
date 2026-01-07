<?php

declare(strict_types=1);

namespace Packages\Domain\Book\Application\UseCases\Commands\CreateBook;

/**
 * 蔵書登録コマンド
 *
 * 蔵書登録に必要な入力データを保持するイミュータブルなDTO。
 * タイトルと登録者IDは必須、その他の項目はオプション。
 */
final readonly class CreateBookCommand
{
    /**
     * コンストラクタ
     *
     * @param  string  $title  書籍タイトル（必須）
     * @param  string  $staffId  登録者ID（職員ULID、必須）
     * @param  string|null  $author  著者名
     * @param  string|null  $isbn  ISBN（ISBN-10またはISBN-13形式）
     * @param  string|null  $publisher  出版社名
     * @param  int|null  $publishedYear  出版年
     * @param  string|null  $genre  ジャンル
     */
    public function __construct(
        public string $title,
        public string $staffId,
        public ?string $author = null,
        public ?string $isbn = null,
        public ?string $publisher = null,
        public ?int $publishedYear = null,
        public ?string $genre = null,
    ) {}
}
