# ST-001: 利用者アカウント編集 API の実装

最終更新: 2025-12-26

---

## ストーリー

**フロントエンド開発者として**、利用者アカウント編集 API を利用したい。
**なぜなら**、フロントエンドから利用者情報を更新したいからだ。

---

## 関連情報

| 項目 | 値 |
|------|-----|
| Epic | [EPIC-009: 利用者アカウント編集機能](../epic.md) |
| ポイント | 3 |
| 優先度 | Must |
| ステータス | Planned |

---

## 受け入れ条件

1. [ ] `PUT /api/patrons/{id}` で利用者情報を更新できること
2. [ ] 利用者番号は変更不可であること
3. [ ] 必須項目のバリデーションが動作すること
4. [ ] 存在しない利用者IDの場合 404 が返ること
5. [ ] 職員以外がアクセスすると 403 が返ること
6. [ ] 更新操作が監査ログに記録されること
7. [ ] 変更内容が履歴として記録されること

---

## 技術仕様

### API エンドポイント

| メソッド | エンドポイント | 説明 | 認証 | 権限 |
|----------|---------------|------|------|------|
| GET | `/api/patrons/{id}` | 利用者詳細取得 | 必須 | 職員 |
| PUT | `/api/patrons/{id}` | 利用者情報更新 | 必須 | 職員 |

### リクエスト/レスポンス

#### PUT /api/patrons/{id}

**リクエスト:**
```json
{
  "name": "山田 太郎",
  "nameKana": "やまだ たろう",
  "birthDate": "1990-05-15",
  "address": "〒123-4567 東京都○○区...",
  "phoneNumber": "090-1234-5678",
  "patronType": "general",
  "notes": "住所変更（2025/12/26）",
  "guardian": null
}
```

**成功レスポンス (200):**
```json
{
  "message": "利用者情報を更新しました",
  "patron": {
    "id": "01HV...",
    "patronNumber": "P2025000001",
    "name": "山田 太郎",
    "nameKana": "やまだ たろう",
    "birthDate": "1990-05-15",
    "patronType": "general",
    "expiresAt": "2026-12-26",
    "isActive": true,
    "updatedAt": "2025-12-26T10:00:00+09:00"
  }
}
```

**バリデーションエラーレスポンス (422):**
```json
{
  "message": "入力内容に誤りがあります",
  "errors": {
    "name": ["氏名を入力してください"]
  }
}
```

**Not Found レスポンス (404):**
```json
{
  "message": "利用者が見つかりません"
}
```

### バリデーション

```php
// UpdatePatronRequest
public function rules(): array
{
    return [
        'name' => ['required', 'string', 'max:50'],
        'nameKana' => ['required', 'string', 'max:50', 'regex:/^[ぁ-んー\s]+$/u'],
        'birthDate' => ['required', 'date', 'before:today'],
        'address' => ['required', 'string', 'max:200'],
        'phoneNumber' => ['required', 'string', 'regex:/^[0-9\-]+$/'],
        'patronType' => ['required', Rule::in(['general', 'student', 'child'])],
        'notes' => ['nullable', 'string', 'max:500'],
        'guardian' => ['required_if:patronType,child', 'nullable', 'array'],
        'guardian.name' => ['required_with:guardian', 'string', 'max:50'],
        'guardian.phoneNumber' => ['required_with:guardian', 'string'],
        'guardian.relationship' => ['required_with:guardian', 'string', 'max:20'],
    ];
}
```

### UseCase 設計

```php
// UpdatePatronHandler
class UpdatePatronHandler
{
    public function handle(UpdatePatronCommand $command): Patron
    {
        $patron = $this->patronRepository->findById(new PatronId($command->patronId));

        if ($patron === null) {
            throw new PatronNotFoundException('利用者が見つかりません');
        }

        // 変更前の状態を保存（履歴用）
        $before = $patron->toArray();

        // 更新
        $patron->update(
            name: $command->name,
            nameKana: $command->nameKana,
            birthDate: $command->birthDate,
            address: $this->encryptor->encrypt($command->address),
            phoneNumber: $this->encryptor->encrypt($command->phoneNumber),
            patronType: PatronType::from($command->patronType),
            notes: $command->notes,
            guardian: $command->guardian,
        );

        $this->patronRepository->save($patron);

        // 変更履歴を記録
        $this->patronHistoryRepository->save(
            PatronHistory::create(
                patronId: $patron->id(),
                before: $before,
                after: $patron->toArray(),
                changedBy: $command->staffId,
            )
        );

        // 監査ログ
        $this->logger->channel('audit')->info('利用者情報を更新しました', [
            'patron_id' => $patron->id()->value(),
            'updated_by' => $command->staffId,
        ]);

        return $patron;
    }
}
```

### Controller 実装

```php
// PatronController
public function update(UpdatePatronRequest $request, string $id): JsonResponse
{
    $patron = $this->updatePatronHandler->handle(
        new UpdatePatronCommand(
            patronId: $id,
            name: $request->name,
            nameKana: $request->nameKana,
            birthDate: $request->birthDate,
            address: $request->address,
            phoneNumber: $request->phoneNumber,
            patronType: $request->patronType,
            notes: $request->notes,
            guardian: $request->guardian,
            staffId: auth()->id(),
        )
    );

    return response()->json([
        'message' => '利用者情報を更新しました',
        'patron' => new PatronResource($patron),
    ]);
}
```

---

## 成果物

| 成果物 | 配置場所 |
|--------|---------|
| UpdatePatronCommand | backend/packages/Domain/Patron/Application/UseCases/Commands/UpdatePatron/UpdatePatronCommand.php |
| UpdatePatronHandler | backend/packages/Domain/Patron/Application/UseCases/Commands/UpdatePatron/UpdatePatronHandler.php |
| UpdatePatronRequest | backend/app/Http/Requests/Patron/UpdatePatronRequest.php |
| PatronNotFoundException | backend/packages/Domain/Patron/Exceptions/PatronNotFoundException.php |
| PatronController（更新） | backend/app/Http/Controllers/Patron/PatronController.php |
| Feature テスト | backend/tests/Feature/Patron/UpdatePatronTest.php |

---

## タスク

### Design Tasks（外部設計）

- [ ] API レスポンス形式の確定
- [ ] バリデーションメッセージの確定

### Spec Tasks（詳細設計）

- [ ] UpdatePatronCommand 実装
- [ ] UpdatePatronHandler 実装
- [ ] UpdatePatronRequest 実装
- [ ] PatronNotFoundException 実装
- [ ] Patron エンティティに update メソッド追加
- [ ] PatronController に update メソッド追加
- [ ] ルーティング設定
- [ ] 監査ログ出力実装
- [ ] Feature テスト作成

---

## 変更履歴

| 日付 | 変更内容 |
|------|---------|
| 2025-12-26 | 初版作成 |
