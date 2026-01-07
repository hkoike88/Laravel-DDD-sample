# ST-001: 利用者アカウント無効化 API の実装

最終更新: 2025-12-26

---

## ストーリー

**フロントエンド開発者として**、利用者アカウント無効化 API を利用したい。
**なぜなら**、フロントエンドから利用者アカウントを無効化したいからだ。

---

## 関連情報

| 項目 | 値 |
|------|-----|
| Epic | [EPIC-010: 利用者アカウント無効化機能](../epic.md) |
| ポイント | 3 |
| 優先度 | Must |
| ステータス | Planned |

---

## 受け入れ条件

1. [ ] `DELETE /api/patrons/{id}` で利用者アカウントを無効化できること
2. [ ] 無効化理由が必須であること
3. [ ] 未返却図書がある場合は警告が返却されること
4. [ ] 既に無効化済みのアカウントを無効化しようとすると 422 が返ること
5. [ ] 職員以外がアクセスすると 403 が返ること
6. [ ] 無効化操作が監査ログに記録されること（理由含む）

---

## 技術仕様

### API エンドポイント

| メソッド | エンドポイント | 説明 | 認証 | 権限 |
|----------|---------------|------|------|------|
| DELETE | `/api/patrons/{id}` | 利用者無効化 | 必須 | 職員 |

### リクエスト/レスポンス

#### DELETE /api/patrons/{id}

**リクエスト:**
```json
{
  "reason": "relocation",
  "notes": "転出届確認済み"
}
```

**無効化理由コード:**
| コード | 理由 |
|--------|------|
| relocation | 転出 |
| request | 本人希望 |
| expired | 有効期限切れ |
| violation | 規約違反 |
| other | その他 |

**成功レスポンス (200):**
```json
{
  "message": "利用者アカウントを無効化しました"
}
```

**未返却警告付きレスポンス (200):**
```json
{
  "message": "利用者アカウントを無効化しました",
  "warning": "未返却図書が2冊あります",
  "unreturned_books": [
    { "id": "01HV...", "title": "プログラミング入門" },
    { "id": "01HV...", "title": "データベース設計" }
  ]
}
```

**バリデーションエラーレスポンス (422):**
```json
{
  "message": "入力内容に誤りがあります",
  "errors": {
    "reason": ["無効化理由を選択してください"]
  }
}
```

**既に無効化済みレスポンス (422):**
```json
{
  "message": "このアカウントは既に無効化されています"
}
```

### バリデーション

```php
// DeactivatePatronRequest
public function rules(): array
{
    return [
        'reason' => ['required', Rule::in([
            'relocation', 'request', 'expired', 'violation', 'other'
        ])],
        'notes' => ['required_if:reason,other', 'nullable', 'string', 'max:500'],
    ];
}

public function messages(): array
{
    return [
        'reason.required' => '無効化理由を選択してください',
        'reason.in' => '無効な理由コードです',
        'notes.required_if' => 'その他を選択した場合は備考を入力してください',
    ];
}
```

### UseCase 設計

```php
// DeactivatePatronHandler
class DeactivatePatronHandler
{
    public function handle(DeactivatePatronCommand $command): DeactivatePatronResult
    {
        $patron = $this->patronRepository->findById(new PatronId($command->patronId));

        if ($patron === null) {
            throw new PatronNotFoundException('利用者が見つかりません');
        }

        // 既に無効化済みチェック
        if (!$patron->isActive()) {
            throw new AlreadyDeactivatedException(
                'このアカウントは既に無効化されています'
            );
        }

        // 未返却図書の確認
        $unreturnedBooks = $this->lendingRepository->findUnreturnedByPatronId($patron->id());

        // 無効化
        $patron->deactivate(
            reason: DeactivationReason::from($command->reason),
            notes: $command->notes,
        );
        $this->patronRepository->save($patron);

        // 無効化ログを記録
        $this->deactivationLogRepository->save(
            PatronDeactivationLog::create(
                patronId: $patron->id(),
                reason: $command->reason,
                notes: $command->notes,
                deactivatedBy: $command->staffId,
            )
        );

        // 監査ログ
        $this->logger->channel('audit')->info('利用者アカウントを無効化しました', [
            'patron_id' => $patron->id()->value(),
            'reason' => $command->reason,
            'deactivated_by' => $command->staffId,
        ]);

        return new DeactivatePatronResult(
            success: true,
            unreturnedBooks: $unreturnedBooks,
        );
    }
}
```

### Controller 実装

```php
// PatronController
public function destroy(DeactivatePatronRequest $request, string $id): JsonResponse
{
    $result = $this->deactivatePatronHandler->handle(
        new DeactivatePatronCommand(
            patronId: $id,
            reason: $request->reason,
            notes: $request->notes,
            staffId: auth()->id(),
        )
    );

    $response = ['message' => '利用者アカウントを無効化しました'];

    if (count($result->unreturnedBooks) > 0) {
        $response['warning'] = sprintf('未返却図書が%d冊あります', count($result->unreturnedBooks));
        $response['unreturned_books'] = BookResource::collection($result->unreturnedBooks);
    }

    return response()->json($response);
}
```

---

## 成果物

| 成果物 | 配置場所 |
|--------|---------|
| DeactivatePatronCommand | backend/packages/Domain/Patron/Application/UseCases/Commands/DeactivatePatron/DeactivatePatronCommand.php |
| DeactivatePatronHandler | backend/packages/Domain/Patron/Application/UseCases/Commands/DeactivatePatron/DeactivatePatronHandler.php |
| DeactivatePatronResult | backend/packages/Domain/Patron/Application/UseCases/Commands/DeactivatePatron/DeactivatePatronResult.php |
| DeactivatePatronRequest | backend/app/Http/Requests/Patron/DeactivatePatronRequest.php |
| DeactivationReason | backend/packages/Domain/Patron/DeactivationReason.php |
| PatronDeactivationLog | backend/packages/Domain/Patron/PatronDeactivationLog.php |
| AlreadyDeactivatedException | backend/packages/Domain/Patron/Exceptions/AlreadyDeactivatedException.php |
| Feature テスト | backend/tests/Feature/Patron/DeactivatePatronTest.php |

---

## タスク

### Design Tasks（外部設計）

- [ ] API レスポンス形式の確定
- [ ] 無効化理由コードの確定
- [ ] エラーメッセージの確定

### Spec Tasks（詳細設計）

- [ ] DeactivationReason Enum 実装
- [ ] DeactivatePatronCommand 実装
- [ ] DeactivatePatronHandler 実装
- [ ] DeactivatePatronResult 実装
- [ ] PatronDeactivationLog エンティティ実装
- [ ] DeactivatePatronRequest 実装
- [ ] AlreadyDeactivatedException 実装
- [ ] Patron エンティティに deactivate メソッド追加
- [ ] PatronController に destroy メソッド追加
- [ ] マイグレーション作成（patron_deactivation_logs）
- [ ] ルーティング設定
- [ ] 監査ログ出力実装
- [ ] Feature テスト作成

---

## 変更履歴

| 日付 | 変更内容 |
|------|---------|
| 2025-12-26 | 初版作成 |
