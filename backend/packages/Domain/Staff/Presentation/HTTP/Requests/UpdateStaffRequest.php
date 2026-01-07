<?php

declare(strict_types=1);

namespace Packages\Domain\Staff\Presentation\HTTP\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * 職員更新リクエスト
 *
 * 職員更新 API のバリデーションルールを定義。
 * エラーレスポンスは標準形式（error.code, error.message, error.details）で返す。
 *
 * @feature EPIC-004-staff-account-edit
 */
class UpdateStaffRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255'],
            'role' => ['required', 'in:staff,admin'],
            'updatedAt' => ['required', 'date'],
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
            'name.max' => '氏名は100文字以内で入力してください',
            'email.required' => 'メールアドレスは必須です',
            'email.email' => '有効なメールアドレスを入力してください',
            'role.required' => '権限を選択してください',
            'role.in' => '権限は staff または admin を選択してください',
            'updatedAt.required' => '更新日時は必須です',
            'updatedAt.date' => '更新日時の形式が不正です',
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
