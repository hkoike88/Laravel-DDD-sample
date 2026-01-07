<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Packages\Domain\Book\Infrastructure\EloquentModels\BookRecord;
use Symfony\Component\Uid\Ulid;

/**
 * 蔵書ファクトリ
 *
 * テスト用およびシード用の蔵書データを生成する。
 * Faker日本語ローカライズを使用してリアルな日本語データを生成する。
 *
 * @extends Factory<BookRecord>
 */
class BookFactory extends Factory
{
    /**
     * 対象モデル
     *
     * @var class-string<BookRecord>
     */
    protected $model = BookRecord::class;

    /**
     * 蔵書状態の定数
     */
    public const STATUS_AVAILABLE = 'available';

    public const STATUS_BORROWED = 'borrowed';

    public const STATUS_RESERVED = 'reserved';

    /**
     * ジャンル一覧
     *
     * @var array<string>
     */
    private const GENRES = [
        '文学',
        '歴史',
        '科学',
        '芸術',
        '哲学',
        '経済',
        '技術',
        '教育',
        '社会',
        '医学',
    ];

    /**
     * 書籍タイトルの接頭辞
     *
     * @var array<string>
     */
    private const TITLE_PREFIXES = [
        '新版',
        '完全版',
        '入門',
        '実践',
        '図解',
        '詳説',
        '現代',
        '古典',
        '日本の',
        '世界の',
    ];

    /**
     * 書籍タイトルの主題
     *
     * @var array<string>
     */
    private const TITLE_SUBJECTS = [
        '物語',
        '研究',
        '概論',
        '入門書',
        '全集',
        '選集',
        '論集',
        '紀行',
        '随想',
        '評伝',
        '歴史',
        '文学',
        '思想',
        '芸術',
        '科学',
    ];

    /**
     * 日本の出版社一覧
     *
     * @var array<string>
     */
    private const PUBLISHERS = [
        '岩波書店',
        '新潮社',
        '講談社',
        '文藝春秋',
        '角川書店',
        '集英社',
        '小学館',
        '中央公論新社',
        '筑摩書房',
        '光文社',
        '河出書房新社',
        '早川書房',
        'PHP研究所',
        '日本経済新聞出版',
        'ダイヤモンド社',
    ];

    /**
     * モデルのデフォルト状態を定義
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // 日本語ローカライズを使用
        $faker = \Faker\Factory::create('ja_JP');

        return [
            'id' => (string) new Ulid,
            'title' => $this->generateJapaneseTitle($faker),
            'author' => $faker->name(),
            'isbn' => $this->generateIsbn13(),
            'publisher' => $faker->randomElement(self::PUBLISHERS),
            'published_year' => $faker->numberBetween(1900, (int) date('Y')),
            'genre' => $faker->randomElement(self::GENRES),
            'status' => $faker->randomElement([
                self::STATUS_AVAILABLE,
                self::STATUS_BORROWED,
                self::STATUS_RESERVED,
            ]),
        ];
    }

    /**
     * 日本語の書籍タイトルを生成
     *
     * @param  \Faker\Generator  $faker  Fakerインスタンス
     * @return string 生成されたタイトル
     */
    private function generateJapaneseTitle(\Faker\Generator $faker): string
    {
        // 50%の確率で接頭辞を付ける
        /** @var string $prefix */
        $prefix = $faker->boolean(50)
            ? $faker->randomElement(self::TITLE_PREFIXES)
            : '';

        /** @var string $subject */
        $subject = $faker->randomElement(self::TITLE_SUBJECTS);
        /** @var string $genre */
        $genre = $faker->randomElement(self::GENRES);
        /** @var string $lastName */
        $lastName = $faker->lastName();

        // タイトルパターンをランダムに選択
        $patterns = [
            $prefix.$genre.$subject,
            $prefix.$subject,
            $genre.'の'.$subject,
            $lastName.'の'.$subject,
            $prefix.$lastName.$subject,
        ];

        /** @var string $title */
        $title = $faker->randomElement($patterns);

        return $title;
    }

    /**
     * 利用可能状態を指定
     */
    public function available(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => self::STATUS_AVAILABLE,
        ]);
    }

    /**
     * 貸出中状態を指定
     */
    public function borrowed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => self::STATUS_BORROWED,
        ]);
    }

    /**
     * 予約中状態を指定
     */
    public function reserved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => self::STATUS_RESERVED,
        ]);
    }

    /**
     * ISBN-13を生成（チェックディジット付き）
     *
     * @return string 13桁のISBN（ハイフンなし）
     */
    private function generateIsbn13(): string
    {
        // 日本のISBN接頭辞: 978-4（日本）
        $prefix = '9784';

        // 8桁のランダム数字を生成
        $body = str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT);

        // 12桁の数列
        $isbn12 = $prefix.$body;

        // チェックディジットを計算
        $checkDigit = $this->calculateIsbn13CheckDigit($isbn12);

        return $isbn12.$checkDigit;
    }

    /**
     * ISBN-13のチェックディジットを計算
     *
     * @param  string  $isbn12  12桁のISBN（チェックディジットなし）
     * @return string チェックディジット（0-9）
     */
    private function calculateIsbn13CheckDigit(string $isbn12): string
    {
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $digit = (int) $isbn12[$i];
            // 奇数位置（1, 3, 5...）は×1、偶数位置（2, 4, 6...）は×3
            $weight = ($i % 2 === 0) ? 1 : 3;
            $sum += $digit * $weight;
        }

        $checkDigit = (10 - ($sum % 10)) % 10;

        return (string) $checkDigit;
    }
}
