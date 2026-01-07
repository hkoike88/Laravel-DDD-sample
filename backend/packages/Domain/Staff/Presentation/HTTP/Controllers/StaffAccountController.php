<?php

declare(strict_types=1);

namespace Packages\Domain\Staff\Presentation\HTTP\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Packages\Domain\Staff\Application\DTO\StaffAccount\CreateStaffInput;
use Packages\Domain\Staff\Application\DTO\StaffAccount\UpdateStaffInput;
use Packages\Domain\Staff\Application\UseCases\Commands\CreateStaff\CreateStaffCommand;
use Packages\Domain\Staff\Application\UseCases\Commands\CreateStaff\CreateStaffHandler;
use Packages\Domain\Staff\Application\UseCases\Commands\ResetPassword\ResetPasswordCommand;
use Packages\Domain\Staff\Application\UseCases\Commands\ResetPassword\ResetPasswordHandler;
use Packages\Domain\Staff\Application\UseCases\Commands\UpdateStaff\UpdateStaffCommand;
use Packages\Domain\Staff\Application\UseCases\Commands\UpdateStaff\UpdateStaffHandler;
use Packages\Domain\Staff\Application\UseCases\Queries\GetStaffDetail\GetStaffDetailHandler;
use Packages\Domain\Staff\Application\UseCases\Queries\GetStaffDetail\GetStaffDetailQuery;
use Packages\Domain\Staff\Application\UseCases\Queries\GetStaffList\GetStaffListHandler;
use Packages\Domain\Staff\Application\UseCases\Queries\GetStaffList\GetStaffListQuery;
use Packages\Domain\Staff\Domain\Exceptions\DuplicateEmailException;
use Packages\Domain\Staff\Domain\Exceptions\LastAdminProtectionException;
use Packages\Domain\Staff\Domain\Exceptions\OptimisticLockException;
use Packages\Domain\Staff\Domain\Exceptions\SelfRoleChangeException;
use Packages\Domain\Staff\Domain\Exceptions\StaffNotFoundException;
use Packages\Domain\Staff\Presentation\HTTP\Requests\CreateStaffRequest;
use Packages\Domain\Staff\Presentation\HTTP\Requests\UpdateStaffRequest;

/**
 * 職員アカウントコントローラー
 *
 * 職員アカウントの作成・一覧取得・編集を処理する。
 *
 * @feature EPIC-003-staff-account-create
 * @feature EPIC-004-staff-account-edit
 */
class StaffAccountController extends Controller
{
    /**
     * コンストラクタ
     *
     * @param  CreateStaffHandler  $createStaffHandler  職員作成ハンドラー
     * @param  GetStaffListHandler  $getStaffListHandler  職員一覧取得ハンドラー
     * @param  GetStaffDetailHandler  $getStaffDetailHandler  職員詳細取得ハンドラー
     * @param  UpdateStaffHandler  $updateStaffHandler  職員更新ハンドラー
     * @param  ResetPasswordHandler  $resetPasswordHandler  パスワードリセットハンドラー
     */
    public function __construct(
        private readonly CreateStaffHandler $createStaffHandler,
        private readonly GetStaffListHandler $getStaffListHandler,
        private readonly GetStaffDetailHandler $getStaffDetailHandler,
        private readonly UpdateStaffHandler $updateStaffHandler,
        private readonly ResetPasswordHandler $resetPasswordHandler,
    ) {}

    /**
     * 職員一覧取得
     *
     * @param  Request  $request  リクエスト
     * @return JsonResponse 職員一覧レスポンス
     */
    public function index(Request $request): JsonResponse
    {
        $page = (int) $request->query('page', '1');
        $baseUrl = $request->url();

        $query = new GetStaffListQuery(
            page: $page,
            perPage: 20,
            baseUrl: $baseUrl,
        );

        $output = $this->getStaffListHandler->handle($query);

        return response()->json($output->toArray());
    }

    /**
     * 職員作成
     *
     * @param  CreateStaffRequest  $request  バリデーション済みリクエスト
     * @return JsonResponse 職員作成レスポンス
     */
    public function store(CreateStaffRequest $request): JsonResponse
    {
        try {
            /** @var array{name: string, email: string, role: string} $validated */
            $validated = $request->validated();

            /** @var \Packages\Domain\Staff\Infrastructure\EloquentModels\StaffRecord $user */
            $user = $request->user();

            $input = new CreateStaffInput(
                name: $validated['name'],
                email: $validated['email'],
                role: $validated['role'],
            );

            $command = new CreateStaffCommand(
                input: $input,
                operatorId: $user->id,
            );

            $output = $this->createStaffHandler->handle($command);

            return response()->json($output->toArray(), 201);
        } catch (DuplicateEmailException $e) {
            return response()->json([
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => '入力内容に誤りがあります',
                    'details' => [
                        [
                            'field' => 'email',
                            'code' => 'VALIDATION_ERROR',
                            'message' => 'このメールアドレスは既に登録されています',
                        ],
                    ],
                ],
            ], 422);
        }
    }

    /**
     * 職員詳細取得
     *
     * @param  Request  $request  リクエスト
     * @param  string  $id  職員ID
     * @return JsonResponse 職員詳細レスポンス
     *
     * @feature EPIC-004-staff-account-edit
     */
    public function show(Request $request, string $id): JsonResponse
    {
        try {
            /** @var \Packages\Domain\Staff\Infrastructure\EloquentModels\StaffRecord $user */
            $user = $request->user();

            $query = new GetStaffDetailQuery(
                staffId: $id,
                operatorId: $user->id,
            );

            $output = $this->getStaffDetailHandler->handle($query);

            return response()->json($output->toArray());
        } catch (StaffNotFoundException $e) {
            return response()->json([
                'message' => '指定された職員が見つかりません',
            ], 404);
        }
    }

    /**
     * 職員更新
     *
     * @param  UpdateStaffRequest  $request  バリデーション済みリクエスト
     * @param  string  $id  職員ID
     * @return JsonResponse 職員更新レスポンス
     *
     * @feature EPIC-004-staff-account-edit
     */
    public function update(UpdateStaffRequest $request, string $id): JsonResponse
    {
        try {
            /** @var array{name: string, email: string, role: string, updatedAt: string} $validated */
            $validated = $request->validated();

            /** @var \Packages\Domain\Staff\Infrastructure\EloquentModels\StaffRecord $user */
            $user = $request->user();

            $input = new UpdateStaffInput(
                name: $validated['name'],
                email: $validated['email'],
                role: $validated['role'],
                updatedAt: $validated['updatedAt'],
            );

            $command = new UpdateStaffCommand(
                staffId: $id,
                input: $input,
                operatorId: $user->id,
            );

            $output = $this->updateStaffHandler->handle($command);

            return response()->json($output->toArray());
        } catch (StaffNotFoundException $e) {
            return response()->json([
                'message' => '指定された職員が見つかりません',
            ], 404);
        } catch (OptimisticLockException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 409);
        } catch (SelfRoleChangeException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'code' => 'SELF_ROLE_CHANGE',
            ], 422);
        } catch (LastAdminProtectionException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'code' => 'LAST_ADMIN_PROTECTION',
            ], 422);
        } catch (DuplicateEmailException $e) {
            return response()->json([
                'message' => 'このメールアドレスは既に使用されています',
                'code' => 'DUPLICATE_EMAIL',
            ], 422);
        }
    }

    /**
     * パスワードリセット
     *
     * @param  Request  $request  リクエスト
     * @param  string  $id  職員ID
     * @return JsonResponse パスワードリセットレスポンス
     *
     * @feature EPIC-004-staff-account-edit
     */
    public function resetPassword(Request $request, string $id): JsonResponse
    {
        try {
            /** @var \Packages\Domain\Staff\Infrastructure\EloquentModels\StaffRecord $user */
            $user = $request->user();

            $command = new ResetPasswordCommand(
                staffId: $id,
                operatorId: $user->id,
            );

            $output = $this->resetPasswordHandler->handle($command);

            return response()->json($output->toArray());
        } catch (StaffNotFoundException $e) {
            return response()->json([
                'message' => '指定された職員が見つかりません',
            ], 404);
        }
    }
}
