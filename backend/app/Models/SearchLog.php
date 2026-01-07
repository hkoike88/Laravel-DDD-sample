<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * 検索統計ログモデル
 *
 * 蔵書検索の統計情報を匿名で記録するEloquentモデル。
 * ビジネスロジックを持たない統計データのため、Domain層ではなくapp/Modelsに配置。
 *
 * @property string $id ULID形式のログID
 * @property string $keyword 結合された検索キーワード
 * @property string|null $title_keyword タイトル検索語
 * @property string|null $author_keyword 著者検索語
 * @property string|null $isbn_keyword ISBN検索語
 * @property int $result_count 検索結果件数
 * @property Carbon $searched_at 検索実行日時
 */
class SearchLog extends Model
{
    use HasUlids;

    /**
     * タイムスタンプを無効化（searched_atのみ使用）
     */
    public $timestamps = false;

    /**
     * マスアサインメント可能な属性
     *
     * @var list<string>
     */
    protected $fillable = [
        'keyword',
        'title_keyword',
        'author_keyword',
        'isbn_keyword',
        'result_count',
        'searched_at',
    ];

    /**
     * 属性のキャスト定義
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'result_count' => 'integer',
            'searched_at' => 'datetime',
        ];
    }

    /**
     * 検索ログを記録
     *
     * @param  string|null  $title  タイトル検索語
     * @param  string|null  $author  著者検索語
     * @param  string|null  $isbn  ISBN検索語
     * @param  int  $resultCount  検索結果件数
     * @return self 作成されたログインスタンス
     */
    public static function record(
        ?string $title,
        ?string $author,
        ?string $isbn,
        int $resultCount,
    ): self {
        // 検索キーワードを結合（分析用）
        $keywords = array_filter([$title, $author, $isbn]);
        $keyword = implode(' ', $keywords) ?: '(empty)';

        return self::create([
            'keyword' => $keyword,
            'title_keyword' => $title,
            'author_keyword' => $author,
            'isbn_keyword' => $isbn,
            'result_count' => $resultCount,
            'searched_at' => now(),
        ]);
    }
}
