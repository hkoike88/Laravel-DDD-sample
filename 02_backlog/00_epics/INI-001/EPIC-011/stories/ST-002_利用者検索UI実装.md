# ST-002: 利用者検索 UI の実装

最終更新: 2025-12-26

---

## ストーリー

**図書館職員として**、利用者を検索したい。
**なぜなら**、貸出・返却処理や情報編集のために利用者を素早く特定したいからだ。

---

## 関連情報

| 項目 | 値 |
|------|-----|
| Epic | [EPIC-011: 利用者検索機能](../epic.md) |
| ポイント | 3 |
| 優先度 | Must |
| ステータス | Planned |

---

## 受け入れ条件

1. [ ] 利用者管理画面に検索フォームが表示されること
2. [ ] 利用者番号、氏名、ふりがな、電話番号で検索できること
3. [ ] ステータス（有効/無効/すべて）でフィルタできること
4. [ ] 検索ボタンクリックで検索が実行されること
5. [ ] クリアボタンで検索条件がリセットされること
6. [ ] 検索結果が0件の場合にメッセージが表示されること
7. [ ] 検索中はローディング表示されること
8. [ ] URLパラメータで検索条件が保持されること

---

## 画面仕様

### 検索フォーム

```
┌─────────────────────────────────────────────────────────────┐
│  利用者管理                                    [新規登録]   │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  検索条件                                                   │
│  ─────────────────────────────────────────────────────────  │
│                                                             │
│  ┌──────────────┐ ┌──────────────┐ ┌──────────────┐        │
│  │利用者番号    │ │氏名          │ │ふりがな      │        │
│  │              │ │              │ │              │        │
│  └──────────────┘ └──────────────┘ └──────────────┘        │
│                                                             │
│  ┌──────────────┐ ┌──────────────┐                         │
│  │電話番号      │ │ステータス    │                         │
│  │              │ │有効       ▼ │                         │
│  └──────────────┘ └──────────────┘                         │
│                                                             │
│                              [クリア]  [検索]               │
│                                                             │
│  ─────────────────────────────────────────────────────────  │
│                                                             │
│  検索結果: 45件                                             │
│                                                             │
│  ┌─────────────────────────────────────────────────────┐   │
│  │ 利用者番号   │ 氏名      │ ふりがな    │ ステータス │   │
│  ├─────────────────────────────────────────────────────┤   │
│  │ P2025000001  │ 山田 太郎 │ やまだ たろう │ 有効     │   │
│  │ P2025000015  │ 山田 花子 │ やまだ はなこ │ 有効     │   │
│  └─────────────────────────────────────────────────────┘   │
│                                                             │
│                    [<] 1 2 3 ... 10 [>]                     │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

### 検索結果0件

```
│  検索結果: 0件                                              │
│                                                             │
│  ┌─────────────────────────────────────────────────────┐   │
│  │                                                      │   │
│  │     該当する利用者が見つかりませんでした             │   │
│  │                                                      │   │
│  │     検索条件を変更してお試しください                 │   │
│  │                                                      │   │
│  └─────────────────────────────────────────────────────┘   │
```

---

## 技術仕様

### コンポーネント構成

```
PatronManagementPage
├── PageHeader
│   └── NewPatronButton
├── PatronSearchForm
│   ├── PatronNumberInput
│   ├── NameInput
│   ├── NameKanaInput
│   ├── PhoneNumberInput
│   ├── StatusSelect
│   └── SearchActions
│       ├── ClearButton
│       └── SearchButton
└── PatronSearchResults
    ├── ResultCount
    ├── PatronList
    └── Pagination
```

### 検索フォームスキーマ（Zod）

```typescript
// patronSearchSchema.ts
export const patronSearchSchema = z.object({
  patronNumber: z.string().optional(),
  name: z.string().optional(),
  nameKana: z.string().optional(),
  phoneNumber: z.string().optional(),
  status: z.enum(['active', 'inactive', 'all']).default('active'),
});

export type PatronSearchParams = z.infer<typeof patronSearchSchema>;
```

### usePatronSearch Hook

```typescript
// usePatronSearch.ts
export const usePatronSearch = () => {
  const [searchParams, setSearchParams] = useSearchParams();

  const params: PatronSearchParams = {
    patronNumber: searchParams.get('patron_number') || undefined,
    name: searchParams.get('name') || undefined,
    nameKana: searchParams.get('name_kana') || undefined,
    phoneNumber: searchParams.get('phone_number') || undefined,
    status: (searchParams.get('status') as PatronStatus) || 'active',
    page: parseInt(searchParams.get('page') || '1'),
  };

  const query = useQuery({
    queryKey: ['patrons', params],
    queryFn: () => patronApi.search(params),
    keepPreviousData: true,
  });

  const search = (newParams: PatronSearchParams) => {
    const urlParams = new URLSearchParams();
    Object.entries(newParams).forEach(([key, value]) => {
      if (value) urlParams.set(key, String(value));
    });
    setSearchParams(urlParams);
  };

  const clear = () => {
    setSearchParams({ status: 'active' });
  };

  return {
    ...query,
    params,
    search,
    clear,
  };
};
```

### PatronSearchForm コンポーネント

```tsx
// PatronSearchForm.tsx
export const PatronSearchForm: React.FC<PatronSearchFormProps> = ({
  defaultValues,
  onSearch,
  onClear,
  isLoading,
}) => {
  const form = useForm<PatronSearchParams>({
    resolver: zodResolver(patronSearchSchema),
    defaultValues,
  });

  return (
    <form onSubmit={form.handleSubmit(onSearch)} className="search-form">
      <div className="search-fields">
        <TextField
          label="利用者番号"
          {...form.register('patronNumber')}
          placeholder="P2025000001"
        />
        <TextField
          label="氏名"
          {...form.register('name')}
          placeholder="山田"
        />
        <TextField
          label="ふりがな"
          {...form.register('nameKana')}
          placeholder="やまだ"
        />
        <TextField
          label="電話番号"
          {...form.register('phoneNumber')}
          placeholder="090"
        />
        <Select
          label="ステータス"
          {...form.register('status')}
          options={[
            { value: 'active', label: '有効' },
            { value: 'inactive', label: '無効' },
            { value: 'all', label: 'すべて' },
          ]}
        />
      </div>
      <div className="search-actions">
        <Button type="button" variant="secondary" onClick={onClear}>
          クリア
        </Button>
        <Button type="submit" loading={isLoading}>
          検索
        </Button>
      </div>
    </form>
  );
};
```

---

## 成果物

| 成果物 | 配置場所 |
|--------|---------|
| PatronManagementPage | frontend/src/pages/staff/patrons/PatronManagementPage.tsx |
| PatronSearchForm | frontend/src/features/patron/components/PatronSearchForm.tsx |
| patronSearchSchema | frontend/src/features/patron/schemas/patronSearchSchema.ts |
| usePatronSearch | frontend/src/features/patron/hooks/usePatronSearch.ts |
| patronApi（更新） | frontend/src/features/patron/api/patronApi.ts |
| テスト | frontend/src/features/patron/__tests__/ |

---

## タスク

### Design Tasks（外部設計）

- [ ] 検索フォームレイアウトの確定
- [ ] 検索項目の配置確認
- [ ] 0件時メッセージの確定

### Spec Tasks（詳細設計）

- [ ] patronSearchSchema 実装
- [ ] patronApi に search メソッド追加
- [ ] usePatronSearch フック実装
- [ ] PatronSearchForm コンポーネント実装
- [ ] PatronManagementPage 実装
- [ ] URLパラメータ連携実装
- [ ] ルーティング設定
- [ ] コンポーネントテスト作成

---

## 変更履歴

| 日付 | 変更内容 |
|------|---------|
| 2025-12-26 | 初版作成 |
