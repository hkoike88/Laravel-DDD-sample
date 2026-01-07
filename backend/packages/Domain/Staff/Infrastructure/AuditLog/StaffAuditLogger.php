<?php

declare(strict_types=1);

namespace Packages\Domain\Staff\Infrastructure\AuditLog;

use Illuminate\Support\Facades\Log;

/**
 * 職員監査ログサービス
 *
 * 職員アカウントに関する操作を監査ログに記録する。
 * audit チャンネルを使用して業務操作のログを出力する。
 *
 * @feature EPIC-003-staff-account-create
 */
class StaffAuditLogger
{
    /**
     * 職員アカウント作成をログに記録
     *
     * @param  string  $operatorId  操作を行った管理者のID
     * @param  string  $targetStaffId  作成された職員のID
     * @param  string  $timestamp  ISO 8601 形式のタイムスタンプ
     */
    public function logStaffCreated(
        string $operatorId,
        string $targetStaffId,
        string $timestamp
    ): void {
        Log::channel('audit')->info('職員アカウント作成', [
            'operator_id' => $operatorId,
            'target_staff_id' => $targetStaffId,
            'operation' => 'staff_created',
            'timestamp' => $timestamp,
        ]);
    }

    /**
     * 職員アカウント更新をログに記録
     *
     * @param  string  $operatorId  操作を行った管理者のID
     * @param  string  $targetStaffId  更新された職員のID
     * @param  array<string, array{before: mixed, after: mixed}>  $changes  変更内容（フィールド名 => [before, after]）
     * @param  string  $timestamp  ISO 8601 形式のタイムスタンプ
     *
     * @feature EPIC-004-staff-account-edit
     */
    public function logStaffUpdated(
        string $operatorId,
        string $targetStaffId,
        array $changes,
        string $timestamp
    ): void {
        Log::channel('audit')->info('職員アカウント更新', [
            'operator_id' => $operatorId,
            'target_staff_id' => $targetStaffId,
            'operation' => 'staff_updated',
            'changes' => $changes,
            'timestamp' => $timestamp,
        ]);
    }

    /**
     * パスワードリセットをログに記録
     *
     * セキュリティ上の理由から、パスワードの値自体は記録しない。
     *
     * @param  string  $operatorId  操作を行った管理者のID
     * @param  string  $targetStaffId  パスワードがリセットされた職員のID
     * @param  string  $timestamp  ISO 8601 形式のタイムスタンプ
     *
     * @feature EPIC-004-staff-account-edit
     */
    public function logPasswordReset(
        string $operatorId,
        string $targetStaffId,
        string $timestamp
    ): void {
        Log::channel('audit')->info('パスワードリセット', [
            'operator_id' => $operatorId,
            'target_staff_id' => $targetStaffId,
            'operation' => 'password_reset',
            'timestamp' => $timestamp,
        ]);
    }
}
