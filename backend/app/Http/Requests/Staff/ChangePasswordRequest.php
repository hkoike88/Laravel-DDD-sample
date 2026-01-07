<?php

declare(strict_types=1);

namespace App\Http\Requests\Staff;

use App\Rules\PasswordNotCompromisedRule;
use App\Rules\PasswordNotReusedRule;
use App\Rules\PasswordPolicyRule;
use Illuminate\Foundation\Http\FormRequest;
use Packages\Domain\Staff\Domain\Repositories\PasswordHistoryRepositoryInterface;
use Packages\Domain\Staff\Domain\ValueObjects\StaffId;

/**
 * パスワード変更リクエスト
 *
 * パスワード変更APIのリクエストバリデーションを行う。
 *
 * @feature 001-security-preparation
 */
final class ChangePasswordRequest extends FormRequest
{
    /**
     * リクエストが認可されているか判定
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * バリデーションルールを取得
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var \Packages\Domain\Staff\Infrastructure\EloquentModels\StaffRecord $user */
        $user = $this->user();
        $staffId = StaffId::fromString($user->id);

        /** @var PasswordHistoryRepositoryInterface $repository */
        $repository = app(PasswordHistoryRepositoryInterface::class);

        return [
            'current_password' => ['required', 'string', 'current_password:web'],
            'new_password' => [
                'required',
                'string',
                'confirmed',
                new PasswordPolicyRule,
                new PasswordNotCompromisedRule,
                new PasswordNotReusedRule($staffId, $repository),
            ],
        ];
    }

    /**
     * バリデーション属性名を取得
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'current_password' => __('validation.attributes.current_password'),
            'new_password' => __('validation.attributes.new_password'),
        ];
    }

    /**
     * カスタムエラーメッセージを取得
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'current_password.current_password' => __('validation.password.current_incorrect'),
        ];
    }
}
