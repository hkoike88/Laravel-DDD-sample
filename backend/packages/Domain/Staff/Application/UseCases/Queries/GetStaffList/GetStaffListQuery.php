<?php

declare(strict_types=1);

namespace Packages\Domain\Staff\Application\UseCases\Queries\GetStaffList;

/**
 * 職員一覧取得クエリ
 *
 * 職員一覧取得ユースケースへの入力を表すクエリオブジェクト。
 *
 * @feature EPIC-003-staff-account-create
 */
final readonly class GetStaffListQuery
{
    /**
     * コンストラクタ
     *
     * @param  int  $page  ページ番号（1始まり）
     * @param  int  $perPage  1ページあたりの件数
     * @param  string  $baseUrl  ページネーションリンクのベースURL
     */
    public function __construct(
        public int $page = 1,
        public int $perPage = 20,
        public string $baseUrl = '',
    ) {}
}
