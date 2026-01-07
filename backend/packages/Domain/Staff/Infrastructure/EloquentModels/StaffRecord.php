<?php

declare(strict_types=1);

namespace Packages\Domain\Staff\Infrastructure\EloquentModels;

use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * 職員 Eloquent モデル
 *
 * staffs テーブルとのマッピングを担当する。
 * ビジネスロジックは含まず、データアクセスのみを担当。
 * Laravel Sanctum SPA 認証のために Authenticatable を継承。
 *
 * @property string $id
 * @property string $email
 * @property string $password
 * @property string $name
 * @property bool $is_admin
 * @property bool $is_locked
 * @property int $failed_login_attempts
 * @property \Carbon\Carbon|null $locked_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class StaffRecord extends Authenticatable
{
    /**
     * テーブル名
     */
    protected $table = 'staffs';

    /**
     * 主キーのカラム名
     */
    protected $primaryKey = 'id';

    /**
     * 主キーの型
     */
    protected $keyType = 'string';

    /**
     * 自動インクリメント無効
     */
    public $incrementing = false;

    /**
     * 複数代入可能な属性
     */
    protected $fillable = [
        'id',
        'email',
        'password',
        'name',
        'is_admin',
        'is_locked',
        'failed_login_attempts',
        'locked_at',
    ];

    /**
     * 属性のキャスト
     */
    protected $casts = [
        'is_admin' => 'boolean',
        'is_locked' => 'boolean',
        'failed_login_attempts' => 'integer',
        'locked_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * 非表示属性（シリアライズ時に除外）
     */
    protected $hidden = [
        'password',
    ];
}
