<?php

declare(strict_types=1);

namespace Packages\Domain\Book\Presentation\HTTP\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 蔵書登録リクエスト
 *
 * 蔵書登録APIのリクエストバリデーションを行うFormRequest。
 * タイトルは必須、その他の項目はオプション。
 */
class CreateBookRequest extends FormRequest
{
    /**
     * リクエストの認可判定
     *
     * 蔵書登録は認証済み職員のみ実行可能。
     *
     * @return bool 認証済みの場合true
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * バリデーションルールを取得
     *
     * 仕様 FR-003〜FR-006, FR-011〜FR-013 に準拠したバリデーションルール。
     *
     * @return array<string, array<int, string>> バリデーションルール
     */
    public function rules(): array
    {
        $currentYear = (int) date('Y');
        $maxYear = $currentYear + 1;

        return [
            'title' => ['required', 'string', 'min:1', 'max:200'],
            'author' => ['nullable', 'string', 'max:100'],
            'isbn' => ['nullable', 'string', 'regex:/^(97[89]\d{10}|\d{9}[\dX])$/'],
            'publisher' => ['nullable', 'string', 'max:100'],
            'published_year' => ['nullable', 'integer', 'min:1000', "max:{$maxYear}"],
            'genre' => ['nullable', 'string', 'max:100'],
        ];
    }

    /**
     * バリデーションエラーメッセージを取得
     *
     * @return array<string, string> エラーメッセージ
     */
    public function messages(): array
    {
        $currentYear = (int) date('Y');
        $maxYear = $currentYear + 1;

        return [
            'title.required' => 'タイトルは必須です',
            'title.min' => 'タイトルは1文字以上で入力してください',
            'title.max' => 'タイトルは200文字以内で入力してください',
            'author.max' => '著者名は100文字以内で入力してください',
            'isbn.regex' => 'ISBNの形式が正しくありません（ハイフンなしで入力してください）',
            'publisher.max' => '出版社名は100文字以内で入力してください',
            'published_year.integer' => '出版年は整数で入力してください',
            'published_year.min' => '出版年は1000以上で入力してください',
            'published_year.max' => "出版年は{$maxYear}以下で入力してください",
            'genre.max' => 'ジャンルは100文字以内で入力してください',
        ];
    }

    /**
     * バリデーション前のデータ準備
     *
     * 空白のみのタイトルをトリム
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('title') && is_string($this->input('title'))) {
            $this->merge([
                'title' => trim($this->input('title')),
            ]);
        }
    }

    /**
     * タイトルを取得
     *
     * @return string タイトル
     */
    public function title(): string
    {
        /** @var string $title */
        $title = $this->input('title');

        return $title;
    }

    /**
     * 著者名を取得
     *
     * @return string|null 著者名
     */
    public function author(): ?string
    {
        /** @var string|null $author */
        $author = $this->input('author');

        return $author;
    }

    /**
     * ISBNを取得
     *
     * @return string|null ISBN
     */
    public function isbn(): ?string
    {
        /** @var string|null $isbn */
        $isbn = $this->input('isbn');

        return $isbn;
    }

    /**
     * 出版社名を取得
     *
     * @return string|null 出版社名
     */
    public function publisher(): ?string
    {
        /** @var string|null $publisher */
        $publisher = $this->input('publisher');

        return $publisher;
    }

    /**
     * 出版年を取得
     *
     * @return int|null 出版年
     */
    public function publishedYear(): ?int
    {
        /** @var int|string|null $publishedYear */
        $publishedYear = $this->input('published_year');

        return $publishedYear !== null ? (int) $publishedYear : null;
    }

    /**
     * ジャンルを取得
     *
     * @return string|null ジャンル
     */
    public function genre(): ?string
    {
        /** @var string|null $genre */
        $genre = $this->input('genre');

        return $genre;
    }
}
