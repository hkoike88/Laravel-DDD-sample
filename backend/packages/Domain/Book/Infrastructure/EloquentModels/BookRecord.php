<?php

declare(strict_types=1);

namespace Packages\Domain\Book\Infrastructure\EloquentModels;

use Database\Factories\BookFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 蔵書 Eloquent モデル
 *
 * データベーステーブルとのマッピングに専念。
 * ビジネスロジックは持たない。
 *
 * @property string $id ULID形式の蔵書ID
 * @property string $title 書籍タイトル
 * @property string|null $author 著者名
 * @property string|null $isbn ISBN（ハイフンなし正規化）
 * @property string|null $publisher 出版社名
 * @property int|null $published_year 出版年
 * @property string|null $genre ジャンル
 * @property string $status 貸出状態（available/borrowed/reserved）
 * @property string|null $registered_by 登録者ID（職員ULID）
 * @property \Carbon\Carbon|null $registered_at 登録日時
 * @property \Carbon\Carbon|null $created_at 作成日時
 * @property \Carbon\Carbon|null $updated_at 更新日時
 */
class BookRecord extends Model
{
    /** @use HasFactory<BookFactory> */
    use HasFactory;

    /**
     * テーブル名
     */
    protected $table = 'books';

    /**
     * 主キーの型
     */
    protected $keyType = 'string';

    /**
     * 主キーは自動インクリメントではない
     */
    public $incrementing = false;

    /**
     * 一括代入可能な属性
     *
     * @var list<string>
     */
    protected $fillable = [
        'id',
        'title',
        'author',
        'isbn',
        'publisher',
        'published_year',
        'genre',
        'status',
        'registered_by',
        'registered_at',
    ];

    /**
     * キャスト定義
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'published_year' => 'integer',
            'registered_at' => 'datetime',
        ];
    }

    /**
     * モデルに対応するファクトリを取得
     */
    protected static function newFactory(): BookFactory
    {
        return BookFactory::new();
    }
}
