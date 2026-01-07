<?php

declare(strict_types=1);

namespace Packages\Domain\Book\Presentation\HTTP\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 蔵書検索リクエスト
 *
 * 蔵書検索APIのリクエストバリデーションを行うFormRequest。
 * クエリパラメータの型変換とバリデーションを担当。
 * 検索条件なしの場合は全件検索として処理する。
 */
class SearchBooksRequest extends FormRequest
{
    /**
     * リクエストの認可判定
     *
     * @return bool 常にtrue（認証不要のAPI）
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * バリデーションルールを取得
     *
     * @return array<string, array<int, string>> バリデーションルール
     */
    public function rules(): array
    {
        return [
            'title' => ['nullable', 'string', 'max:255'],
            'author' => ['nullable', 'string', 'max:255'],
            'isbn' => ['nullable', 'string', 'max:17'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    /**
     * バリデーションエラーメッセージを取得
     *
     * @return array<string, string> エラーメッセージ
     */
    public function messages(): array
    {
        return [
            'title.max' => 'タイトルは255文字以内で入力してください',
            'author.max' => '著者名は255文字以内で入力してください',
            'isbn.max' => 'ISBNは17文字以内で入力してください',
            'page.integer' => 'ページ番号は整数で入力してください',
            'page.min' => 'ページ番号は1以上で入力してください',
            'per_page.integer' => '1ページあたりの件数は整数で入力してください',
            'per_page.min' => '1ページあたりの件数は1以上で入力してください',
            'per_page.max' => '1ページあたりの件数は100以下で入力してください',
        ];
    }

    /**
     * タイトル検索キーワードを取得
     *
     * @return string|null タイトル検索キーワード
     */
    public function title(): ?string
    {
        /** @var string|null $title */
        $title = $this->input('title');

        return $title;
    }

    /**
     * 著者名検索キーワードを取得
     *
     * @return string|null 著者名検索キーワード
     */
    public function author(): ?string
    {
        /** @var string|null $author */
        $author = $this->input('author');

        return $author;
    }

    /**
     * ISBN検索キーワードを取得
     *
     * @return string|null ISBN検索キーワード
     */
    public function isbn(): ?string
    {
        /** @var string|null $isbn */
        $isbn = $this->input('isbn');

        return $isbn;
    }

    /**
     * ページ番号を取得
     *
     * @return int ページ番号（デフォルト: 1）
     */
    public function page(): int
    {
        /** @var int|string|null $page */
        $page = $this->input('page', 1);

        return (int) $page;
    }

    /**
     * 1ページあたりの件数を取得
     *
     * @return int 1ページあたりの件数（デフォルト: 20）
     */
    public function perPage(): int
    {
        /** @var int|string|null $perPage */
        $perPage = $this->input('per_page', 20);

        return (int) $perPage;
    }
}
