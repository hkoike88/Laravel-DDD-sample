<?php

declare(strict_types=1);

namespace Packages\Domain\Staff\Infrastructure\EloquentModels;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * パスワード履歴 Eloquent モデル
 *
 * password_histories テーブルとのマッピングを担当する。
 * ビジネスロジックは含まず、データアクセスのみを担当。
 *
 * @property string $id パスワード履歴ID（ULID）
 * @property string $staff_id 職員ID
 * @property string $password_hash ハッシュ化済みパスワード
 * @property \Carbon\Carbon $created_at 作成日時
 *
 * @feature 001-security-preparation
 */
class PasswordHistoryRecord extends Model
{
    /**
     * テーブル名
     */
    protected $table = 'password_histories';

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
     * タイムスタンプを無効化（created_at のみ使用）
     */
    public $timestamps = false;

    /**
     * 複数代入可能な属性
     */
    protected $fillable = [
        'id',
        'staff_id',
        'password_hash',
        'created_at',
    ];

    /**
     * 属性のキャスト
     */
    protected $casts = [
        'created_at' => 'datetime',
    ];

    /**
     * 非表示属性（シリアライズ時に除外）
     */
    protected $hidden = [
        'password_hash',
    ];

    /**
     * 職員リレーション
     *
     * @return BelongsTo<StaffRecord, $this>
     */
    public function staff(): BelongsTo
    {
        return $this->belongsTo(StaffRecord::class, 'staff_id', 'id');
    }
}
