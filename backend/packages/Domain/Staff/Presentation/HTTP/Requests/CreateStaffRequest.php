<?php

declare(strict_types=1);

namespace Packages\Domain\Staff\Presentation\HTTP\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * 職員作成リクエスト
 *
 * 職員作成 API のバリデーションルールを定義。
 * エラーレスポンスは標準形式（error.code, error.message, error.details）で返す。
 *
 * @feature EPIC-003-staff-account-create
 */
class CreateStaffRequest extends FormRequest
{
    /**
     * リクエストの認可
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * バリデーションルール
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:255', 'unique:staffs,email'],
            'role' => ['required', 'in:staff,admin'],
        ];
    }

    /**
     * カスタムエラーメッセージ
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => '氏名は必須です',
            'name.max' => '氏名は50文字以内で入力してください',
            'email.required' => 'メールアドレスは必須です',
            'email.email' => '有効なメールアドレスを入力してください',
            'email.unique' => 'このメールアドレスは既に登録されています',
            'role.required' => '権限を選択してください',
            'role.in' => '権限は staff または admin を選択してください',
        ];
    }

    /**
     * バリデーション失敗時のレスポンス
     *
     * 標準エラーレスポンス形式で返す。
     *
     * @throws HttpResponseException
     */
    protected function failedValidation(Validator $validator): void
    {
        $errors = $validator->errors()->toArray();
        $details = [];

        foreach ($errors as $field => $messages) {
            foreach ($messages as $message) {
                $details[] = [
                    'field' => $field,
                    'code' => 'VALIDATION_ERROR',
                    'message' => $message,
                ];
            }
        }

        throw new HttpResponseException(
            response()->json([
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => '入力内容に誤りがあります',
                    'details' => $details,
                ],
            ], 422)
        );
    }
}
