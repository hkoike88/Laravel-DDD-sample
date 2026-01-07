# ST-001: 利用者アカウント登録 API の実装

最終更新: 2025-12-26

---

## ストーリー

**フロントエンド開発者として**、利用者アカウント登録 API を利用したい。
**なぜなら**、フロントエンドから新規利用者を登録したいからだ。

---

## 関連情報

| 項目 | 値 |
|------|-----|
| Epic | [EPIC-008: 利用者アカウント登録機能](../epic.md) |
| ポイント | 5 |
| 優先度 | Must |
| ステータス | Planned |

---

## 受け入れ条件

1. [ ] `POST /api/patrons` で利用者を登録できること
2. [ ] 必須項目のバリデーションが動作すること
3. [ ] 利用者番号が自動採番されること
4. [ ] 有効期限が登録日から1年間で設定されること
5. [ ] 未成年者（児童区分）は保護者情報が必須であること
6. [ ] 個人情報が暗号化されて保存されること
7. [ ] 職員以外がアクセスすると 403 が返ること
8. [ ] 登録操作が監査ログに記録されること

---

## 技術仕様

### API エンドポイント

| メソッド | エンドポイント | 説明 | 認証 | 権限 |
|----------|---------------|------|------|------|
| POST | `/api/patrons` | 利用者登録 | 必須 | 職員 |

### リクエスト/レスポンス

#### POST /api/patrons

**リクエスト:**
```json
{
  "name": "山田 太郎",
  "nameKana": "やまだ たろう",
  "birthDate": "1990-05-15",
  "address": "〒123-4567 東京都○○区...",
  "phoneNumber": "090-1234-5678",
  "patronType": "general",
  "notes": "",
  "guardian": null
}
```

**児童の場合（保護者情報必須）:**
```json
{
  "name": "山田 花子",
  "nameKana": "やまだ はなこ",
  "birthDate": "2018-03-20",
  "address": "〒123-4567 東京都○○区...",
  "phoneNumber": "090-1234-5678",
  "patronType": "child",
  "notes": "",
  "guardian": {
    "name": "山田 太郎",
    "phoneNumber": "090-1234-5678",
    "relationship": "父"
  }
}
```

**成功レスポンス (201):**
```json
{
  "message": "利用者を登録しました",
  "patron": {
    "id": "01HV...",
    "patronNumber": "P2025000001",
    "name": "山田 太郎",
    "nameKana": "やまだ たろう",
    "birthDate": "1990-05-15",
    "patronType": "general",
    "expiresAt": "2026-12-26",
    "isActive": true,
    "createdAt": "2025-12-26T10:00:00+09:00"
  }
}
```

**バリデーションエラーレスポンス (422):**
```json
{
  "message": "入力内容に誤りがあります",
  "errors": {
    "name": ["氏名を入力してください"],
    "guardian.name": ["保護者氏名を入力してください"]
  }
}
```

### バリデーション

```php
// CreatePatronRequest
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

public function messages(): array
{
    return [
        'name.required' => '氏名を入力してください',
        'nameKana.required' => 'ふりがなを入力してください',
        'nameKana.regex' => 'ふりがなはひらがなで入力してください',
        'birthDate.required' => '生年月日を入力してください',
        'address.required' => '住所を入力してください',
        'phoneNumber.required' => '電話番号を入力してください',
        'patronType.required' => '利用者区分を選択してください',
        'guardian.required_if' => '児童の場合は保護者情報が必要です',
    ];
}
```

### UseCase 設計

```php
// CreatePatronHandler
class CreatePatronHandler
{
    public function handle(CreatePatronCommand $command): Patron
    {
        // 利用者番号を採番
        $patronNumber = $this->patronNumberGenerator->generate();

        // 有効期限を設定（1年間）
        $expiresAt = Carbon::now()->addYear();

        // 利用者エンティティを作成
        $patron = Patron::create(
            id: new PatronId(),
            patronNumber: $patronNumber,
            name: $command->name,
            nameKana: $command->nameKana,
            birthDate: $command->birthDate,
            address: $this->encryptor->encrypt($command->address),
            phoneNumber: $this->encryptor->encrypt($command->phoneNumber),
            patronType: PatronType::from($command->patronType),
            notes: $command->notes,
            guardian: $command->guardian,
            expiresAt: $expiresAt,
        );

        $this->patronRepository->save($patron);

        // 監査ログ
        $this->logger->channel('audit')->info('利用者を登録しました', [
            'patron_id' => $patron->id()->value(),
            'patron_number' => $patronNumber->value(),
            'registered_by' => $command->staffId,
        ]);

        return $patron;
    }
}
```

### Controller 実装

```php
// PatronController
public function store(CreatePatronRequest $request): JsonResponse
{
    $patron = $this->createPatronHandler->handle(
        new CreatePatronCommand(
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
        'message' => '利用者を登録しました',
        'patron' => new PatronResource($patron),
    ], 201);
}
```

---

## 成果物

| 成果物 | 配置場所 |
|--------|---------|
| Patron エンティティ | backend/packages/Domain/Patron/Patron.php |
| PatronId | backend/packages/Domain/Patron/PatronId.php |
| PatronNumber | backend/packages/Domain/Patron/PatronNumber.php |
| PatronType | backend/packages/Domain/Patron/PatronType.php |
| Guardian 値オブジェクト | backend/packages/Domain/Patron/Guardian.php |
| PatronRepository | backend/packages/Domain/Patron/PatronRepository.php |
| CreatePatronCommand | backend/packages/Domain/Patron/Application/UseCases/Commands/CreatePatron/CreatePatronCommand.php |
| CreatePatronHandler | backend/packages/Domain/Patron/Application/UseCases/Commands/CreatePatron/CreatePatronHandler.php |
| CreatePatronRequest | backend/app/Http/Requests/Patron/CreatePatronRequest.php |
| PatronController | backend/app/Http/Controllers/Patron/PatronController.php |
| PatronResource | backend/app/Http/Resources/PatronResource.php |
| Feature テスト | backend/tests/Feature/Patron/CreatePatronTest.php |

---

## タスク

### Design Tasks（外部設計）

- [ ] API レスポンス形式の確定
- [ ] バリデーションメッセージの確定
- [ ] 利用者区分の定義確認

### Spec Tasks（詳細設計）

- [ ] Patron エンティティ実装
- [ ] PatronId, PatronNumber, PatronType 値オブジェクト実装
- [ ] Guardian 値オブジェクト実装
- [ ] PatronRepository インターフェース定義
- [ ] EloquentPatronRepository 実装
- [ ] CreatePatronCommand 実装
- [ ] CreatePatronHandler 実装
- [ ] CreatePatronRequest 実装
- [ ] PatronController 実装
- [ ] PatronResource 実装
- [ ] ルーティング設定
- [ ] マイグレーション作成（patrons テーブル）
- [ ] 個人情報暗号化処理実装
- [ ] 監査ログ出力実装
- [ ] Feature テスト作成

---

## 変更履歴

| 日付 | 変更内容 |
|------|---------|
| 2025-12-26 | 初版作成 |
