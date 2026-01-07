<?php

declare(strict_types=1);

namespace Packages\Domain\Book\Presentation\HTTP\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * ISBN重複チェックリクエスト
 *
 * ISBN重複チェックAPIのリクエストバリデーションを行うFormRequest。
 * 認証済み職員のみアクセス可能。
 */
class CheckIsbnRequest extends FormRequest
{
    /**
     * リクエストの認可判定
     *
     * ISBN重複チェックは認証済み職員のみ実行可能。
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
     * @return array<string, array<int, string>> バリデーションルール
     */
    public function rules(): array
    {
        return [
            'isbn' => ['required', 'string', 'regex:/^(97[89]\d{10}|\d{9}[\dX])$/'],
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
            'isbn.required' => 'ISBNは必須です',
            'isbn.regex' => 'ISBNの形式が正しくありません（ハイフンなしで入力してください）',
        ];
    }

    /**
     * ISBNを取得
     *
     * @return string ISBN
     */
    public function isbn(): string
    {
        /** @var string $isbn */
        $isbn = $this->input('isbn');

        return $isbn;
    }
}
