<?php

declare(strict_types=1);

namespace Packages\Domain\Staff\Domain\Repositories;

use Packages\Domain\Staff\Domain\Model\Staff;
use Packages\Domain\Staff\Domain\ValueObjects\Email;
use Packages\Domain\Staff\Domain\ValueObjects\StaffId;

/**
 * 職員リポジトリインターフェース
 *
 * 職員の永続化と取得を抽象化するインターフェース。
 * ドメイン層とインフラストラクチャ層を分離し、データアクセスを抽象化する。
 */
interface StaffRepositoryInterface
{
    /**
     * ID で職員を取得
     *
     * @param  StaffId  $id  職員ID
     * @return Staff 職員エンティティ
     *
     * @throws \Packages\Domain\Staff\Domain\Exceptions\StaffNotFoundException 職員が存在しない場合
     */
    public function find(StaffId $id): Staff;

    /**
     * ID で職員を取得（存在しない場合は null）
     *
     * @param  StaffId  $id  職員ID
     * @return Staff|null 職員エンティティまたは null
     */
    public function findOrNull(StaffId $id): ?Staff;

    /**
     * メールアドレスで職員を検索
     *
     * @param  Email  $email  メールアドレス
     * @return Staff|null 職員エンティティまたは null
     */
    public function findByEmail(Email $email): ?Staff;

    /**
     * メールアドレスの存在確認
     *
     * @param  Email  $email  メールアドレス
     * @return bool 存在する場合 true
     */
    public function existsByEmail(Email $email): bool;

    /**
     * 職員を保存（新規作成または更新）
     *
     * @param  Staff  $staff  職員エンティティ
     */
    public function save(Staff $staff): void;

    /**
     * 職員を削除
     *
     * @param  StaffId  $id  職員ID
     */
    public function delete(StaffId $id): void;

    /**
     * ページネーション付きで全職員を取得
     *
     * @param  int  $page  ページ番号（1始まり）
     * @param  int  $perPage  1ページあたりの件数
     * @return array{
     *   data: Staff[],
     *   currentPage: int,
     *   lastPage: int,
     *   perPage: int,
     *   total: int,
     *   from: int|null,
     *   to: int|null
     * }
     *
     * @feature EPIC-003-staff-account-create
     */
    public function findAllPaginated(int $page = 1, int $perPage = 20): array;

    /**
     * 管理者の人数をカウント
     *
     * @return int 管理者数
     *
     * @feature EPIC-004-staff-account-edit
     */
    public function countAdmins(): int;

    /**
     * 指定した職員以外でメールアドレスが存在するか確認
     *
     * @param  Email  $email  確認するメールアドレス
     * @param  StaffId  $excludeId  除外する職員ID
     * @return bool 他の職員が同じメールアドレスを使用している場合 true
     *
     * @feature EPIC-004-staff-account-edit
     */
    public function existsByEmailExcludingId(Email $email, StaffId $excludeId): bool;

    /**
     * 管理者の人数をカウント（排他ロック付き）
     *
     * トランザクション内で使用し、最後の管理者保護などの
     * 競合状態を防ぐための排他ロック（SELECT ... FOR UPDATE）を取得する。
     *
     * @return int 管理者数
     *
     * @feature EPIC-004-staff-account-edit
     */
    public function countAdminsForUpdate(): int;
}
