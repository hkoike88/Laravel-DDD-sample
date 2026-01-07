# ST-004: 職員一覧 UI の実装

最終更新: 2025-12-26

---

## ストーリー

**管理者として**、職員一覧画面で職員を確認・管理したい。
**なぜなら**、システムに登録されている職員を把握し、必要に応じて編集・無効化を行いたいからだ。

---

## 関連情報

| 項目 | 値 |
|------|-----|
| Epic | [EPIC-003: 職員アカウント作成機能](../epic.md) |
| ポイント | 2 |
| 優先度 | Must |
| ステータス | Planned |
| ワイヤーフレーム | [職員アカウント一覧](../../../../../01_vision/initiatives/INI-001/ui/wireframes/staff-accounts-list.md) |

---

## 受け入れ条件

1. [ ] 職員一覧がテーブル形式で表示されること
2. [ ] 氏名、メールアドレス、権限、ステータス、作成日が表示されること
3. [ ] 氏名またはメールアドレスで検索できること
4. [ ] 新規作成ボタンで作成画面に遷移できること
5. [ ] 各行の編集ボタンで編集画面に遷移できること
6. [ ] ページネーションが動作すること
7. [ ] データ取得中はローディング表示されること

---

## 画面仕様

### UI 要素

| 要素ID | 種類 | 要素名 | 説明 |
|--------|------|--------|------|
| search-input | input[text] | 検索 | 氏名/メールで検索 |
| btn-search | button | 検索ボタン | 検索実行 |
| btn-add | button | 新規作成 | 作成画面へ遷移 |
| table-staff | table | 職員一覧 | 職員情報テーブル |
| btn-edit | button | 編集 | 編集画面へ遷移 |
| pagination | nav | ページネーション | ページ切り替え |

### テーブルカラム

| カラム | 説明 | ソート |
|--------|------|--------|
| 氏名 | 職員の氏名 | ○ |
| メールアドレス | ログイン用メールアドレス | ○ |
| 権限 | 一般職員 / 管理者 | - |
| ステータス | 有効 / 無効 | - |
| 作成日 | アカウント作成日 | ○ |
| 操作 | 編集ボタン | - |

---

## 技術仕様

### コンポーネント構成

```
StaffAccountsListPage
├── PageHeader
│   └── AddButton
├── SearchForm
│   ├── SearchInput
│   └── SearchButton
├── StaffTable
│   ├── TableHeader
│   ├── TableBody
│   │   └── StaffRow (xN)
│   └── TableFooter
└── Pagination
```

### useStaffList Hook

```typescript
// useStaffList.ts
export const useStaffList = (params: StaffListParams) => {
  return useQuery({
    queryKey: ['staff', 'accounts', params],
    queryFn: () => staffApi.getList(params),
    placeholderData: keepPreviousData,
  });
};
```

### StaffAccountsListPage コンポーネント

```tsx
// StaffAccountsListPage.tsx
export const StaffAccountsListPage: React.FC = () => {
  const [search, setSearch] = useState('');
  const [page, setPage] = useState(1);
  const { data, isLoading } = useStaffList({ search, page });
  const navigate = useNavigate();

  if (isLoading) {
    return <LoadingSpinner />;
  }

  return (
    <div className="staff-list">
      <PageHeader>
        <Button onClick={() => navigate('/staff/accounts/new')}>
          新規作成
        </Button>
      </PageHeader>

      <SearchForm value={search} onChange={setSearch} />

      <StaffTable data={data.data} />

      <Pagination
        currentPage={data.meta.currentPage}
        lastPage={data.meta.lastPage}
        onChange={setPage}
      />
    </div>
  );
};
```

### StaffTable コンポーネント

```tsx
// StaffTable.tsx
export const StaffTable: React.FC<{ data: Staff[] }> = ({ data }) => {
  const navigate = useNavigate();

  return (
    <table className="staff-table">
      <thead>
        <tr>
          <th>氏名</th>
          <th>メールアドレス</th>
          <th>権限</th>
          <th>ステータス</th>
          <th>作成日</th>
          <th>操作</th>
        </tr>
      </thead>
      <tbody>
        {data.map((staff) => (
          <tr key={staff.id}>
            <td>{staff.name}</td>
            <td>{staff.email}</td>
            <td>{staff.role === 'admin' ? '管理者' : '一般職員'}</td>
            <td>
              <Badge variant={staff.isActive ? 'success' : 'secondary'}>
                {staff.isActive ? '有効' : '無効'}
              </Badge>
            </td>
            <td>{formatDate(staff.createdAt)}</td>
            <td>
              <Button
                size="sm"
                onClick={() => navigate(`/staff/accounts/${staff.id}/edit`)}
              >
                編集
              </Button>
            </td>
          </tr>
        ))}
      </tbody>
    </table>
  );
};
```

---

## 成果物

| 成果物 | 配置場所 |
|--------|---------|
| StaffAccountsListPage | frontend/src/pages/staff/StaffAccountsListPage.tsx |
| StaffTable | frontend/src/features/staff/components/StaffTable.tsx |
| SearchForm | frontend/src/features/staff/components/SearchForm.tsx |
| useStaffList | frontend/src/features/staff/hooks/useStaffList.ts |
| staffApi（更新） | frontend/src/features/staff/api/staffApi.ts |
| テスト | frontend/src/features/staff/\_\_tests\_\_/ |

---

## タスク

### Design Tasks（外部設計）

- [ ] ワイヤーフレームの確認
- [ ] テーブルデザインの確定
- [ ] 検索UIの確定

### Spec Tasks（詳細設計）

- [ ] staffApi に getList メソッド追加
- [ ] useStaffList フック実装
- [ ] SearchForm コンポーネント実装
- [ ] StaffTable コンポーネント実装
- [ ] Pagination コンポーネント実装
- [ ] StaffAccountsListPage 実装
- [ ] ルーティング設定
- [ ] コンポーネントテスト作成

---

## 変更履歴

| 日付 | 変更内容 |
|------|---------|
| 2025-12-26 | 初版作成 |
