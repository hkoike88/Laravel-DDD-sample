<?php

declare(strict_types=1);

namespace Packages\Domain\Book\Domain\Model;

use DateTimeImmutable;
use Packages\Domain\Book\Domain\Exceptions\EmptyBookTitleException;
use Packages\Domain\Book\Domain\ValueObjects\BookId;
use Packages\Domain\Book\Domain\ValueObjects\BookStatus;
use Packages\Domain\Book\Domain\ValueObjects\ISBN;

/**
 * 蔵書エンティティ
 *
 * 図書館が所蔵する書籍を表すエンティティ（集約ルート）。
 * 蔵書の基本情報と貸出状態を管理する。
 */
final class Book
{
    /**
     * コンストラクタ
     *
     * @param  BookId  $id  蔵書ID
     * @param  string  $title  書籍タイトル
     * @param  string|null  $author  著者名
     * @param  ISBN|null  $isbn  ISBN
     * @param  string|null  $publisher  出版社名
     * @param  int|null  $publishedYear  出版年
     * @param  string|null  $genre  ジャンル
     * @param  BookStatus  $status  貸出状態
     * @param  string|null  $registeredBy  登録者ID（職員ULID）
     * @param  DateTimeImmutable|null  $registeredAt  登録日時
     */
    private function __construct(
        private readonly BookId $id,
        private readonly string $title,
        private readonly ?string $author,
        private readonly ?ISBN $isbn,
        private readonly ?string $publisher,
        private readonly ?int $publishedYear,
        private readonly ?string $genre,
        private BookStatus $status,
        private readonly ?string $registeredBy = null,
        private readonly ?DateTimeImmutable $registeredAt = null,
    ) {}

    /**
     * 新規蔵書を作成
     *
     * @param  BookId  $id  蔵書ID
     * @param  string  $title  書籍タイトル
     * @param  string|null  $author  著者名
     * @param  ISBN|null  $isbn  ISBN
     * @param  string|null  $publisher  出版社名
     * @param  int|null  $publishedYear  出版年
     * @param  string|null  $genre  ジャンル
     * @param  string|null  $registeredBy  登録者ID（職員ULID）
     * @param  DateTimeImmutable|null  $registeredAt  登録日時
     *
     * @throws EmptyBookTitleException タイトルが空の場合
     */
    public static function create(
        BookId $id,
        string $title,
        ?string $author = null,
        ?ISBN $isbn = null,
        ?string $publisher = null,
        ?int $publishedYear = null,
        ?string $genre = null,
        ?string $registeredBy = null,
        ?DateTimeImmutable $registeredAt = null,
    ): self {
        self::validateTitle($title);

        return new self(
            id: $id,
            title: $title,
            author: $author,
            isbn: $isbn,
            publisher: $publisher,
            publishedYear: $publishedYear,
            genre: $genre,
            status: BookStatus::available(),
            registeredBy: $registeredBy,
            registeredAt: $registeredAt,
        );
    }

    /**
     * 永続化データから蔵書を復元
     *
     * @param  BookId  $id  蔵書ID
     * @param  string  $title  書籍タイトル
     * @param  string|null  $author  著者名
     * @param  ISBN|null  $isbn  ISBN
     * @param  string|null  $publisher  出版社名
     * @param  int|null  $publishedYear  出版年
     * @param  string|null  $genre  ジャンル
     * @param  BookStatus  $status  貸出状態
     * @param  string|null  $registeredBy  登録者ID（職員ULID）
     * @param  DateTimeImmutable|null  $registeredAt  登録日時
     */
    public static function reconstruct(
        BookId $id,
        string $title,
        ?string $author,
        ?ISBN $isbn,
        ?string $publisher,
        ?int $publishedYear,
        ?string $genre,
        BookStatus $status,
        ?string $registeredBy = null,
        ?DateTimeImmutable $registeredAt = null,
    ): self {
        return new self(
            id: $id,
            title: $title,
            author: $author,
            isbn: $isbn,
            publisher: $publisher,
            publishedYear: $publishedYear,
            genre: $genre,
            status: $status,
            registeredBy: $registeredBy,
            registeredAt: $registeredAt,
        );
    }

    /**
     * タイトルのバリデーション
     *
     * @param  string  $title  タイトル
     *
     * @throws EmptyBookTitleException タイトルが空または空白のみの場合
     */
    private static function validateTitle(string $title): void
    {
        if (trim($title) === '') {
            throw new EmptyBookTitleException;
        }
    }

    /**
     * 蔵書IDを取得
     */
    public function id(): BookId
    {
        return $this->id;
    }

    /**
     * タイトルを取得
     */
    public function title(): string
    {
        return $this->title;
    }

    /**
     * 著者名を取得
     */
    public function author(): ?string
    {
        return $this->author;
    }

    /**
     * ISBN を取得
     */
    public function isbn(): ?ISBN
    {
        return $this->isbn;
    }

    /**
     * 出版社名を取得
     */
    public function publisher(): ?string
    {
        return $this->publisher;
    }

    /**
     * 出版年を取得
     */
    public function publishedYear(): ?int
    {
        return $this->publishedYear;
    }

    /**
     * ジャンルを取得
     */
    public function genre(): ?string
    {
        return $this->genre;
    }

    /**
     * 貸出状態を取得
     */
    public function status(): BookStatus
    {
        return $this->status;
    }

    /**
     * 登録者IDを取得
     */
    public function registeredBy(): ?string
    {
        return $this->registeredBy;
    }

    /**
     * 登録日時を取得
     */
    public function registeredAt(): ?DateTimeImmutable
    {
        return $this->registeredAt;
    }

    /**
     * 利用可能か判定
     */
    public function isAvailable(): bool
    {
        return $this->status->isAvailable();
    }

    /**
     * 貸出処理
     *
     * @throws \Packages\Domain\Book\Domain\Exceptions\InvalidBookStatusTransitionException 貸出不可の場合
     */
    public function borrow(): void
    {
        $this->status = $this->status->toBorrowed();
    }

    /**
     * 返却処理
     *
     * @throws \Packages\Domain\Book\Domain\Exceptions\InvalidBookStatusTransitionException 返却不可の場合
     */
    public function return(): void
    {
        $this->status = $this->status->toAvailableByReturn();
    }

    /**
     * 予約処理
     *
     * @throws \Packages\Domain\Book\Domain\Exceptions\InvalidBookStatusTransitionException 予約不可の場合
     */
    public function reserve(): void
    {
        $this->status = $this->status->toReserved();
    }

    /**
     * 予約者への貸出処理
     *
     * @throws \Packages\Domain\Book\Domain\Exceptions\InvalidBookStatusTransitionException 貸出不可の場合
     */
    public function lendToReserver(): void
    {
        $this->status = $this->status->toBorrowedFromReserved();
    }

    /**
     * 予約キャンセル処理
     *
     * @throws \Packages\Domain\Book\Domain\Exceptions\InvalidBookStatusTransitionException キャンセル不可の場合
     */
    public function cancelReservation(): void
    {
        $this->status = $this->status->toAvailableByCancellation();
    }
}
