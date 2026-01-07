<?php

declare(strict_types=1);

namespace Packages\Domain\Staff\Application\DTO\StaffAccount;

/**
 * ページネーションリンク
 *
 * @feature EPIC-003-staff-account-create
 */
final readonly class PaginationLinks
{
    /**
     * コンストラクタ
     *
     * @param  string|null  $first  最初のページへのURL
     * @param  string|null  $last  最後のページへのURL
     * @param  string|null  $prev  前のページへのURL
     * @param  string|null  $next  次のページへのURL
     */
    public function __construct(
        public ?string $first,
        public ?string $last,
        public ?string $prev,
        public ?string $next,
    ) {}

    /**
     * 配列に変換
     *
     * @return array{first: string|null, last: string|null, prev: string|null, next: string|null}
     */
    public function toArray(): array
    {
        return [
            'first' => $this->first,
            'last' => $this->last,
            'prev' => $this->prev,
            'next' => $this->next,
        ];
    }
}
