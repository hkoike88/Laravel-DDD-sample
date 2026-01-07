# ST-002: ログアウト UI の実装

最終更新: 2025-12-24

---

## ストーリー

**職員として**、画面上のログアウトボタンをクリックしてログアウトしたい。
**なぜなら**、業務終了時や離席時にセッションを安全に終了させたいからだ。

---

## 関連情報

| 項目 | 値 |
|------|-----|
| Epic | [EPIC-006: 職員ログアウト機能](../epic.md) |
| ポイント | 2 |
| 優先度 | Must |
| ステータス | Planned |

---

## 受け入れ条件

1. [ ] ヘッダーにログアウトボタンがあること
2. [ ] ログアウトボタンをクリックするとログアウト処理が実行されること
3. [ ] ログアウト処理中はローディング表示されること
4. [ ] ログアウト成功後、ログイン画面にリダイレクトされること
5. [ ] 認証状態がクリアされること

---

## 技術仕様

### コンポーネント構成

```
frontend/src/
├── components/
│   └── layout/
│       ├── Header.tsx          # ヘッダー（UserMenu を含む）
│       └── UserMenu.tsx        # ユーザーメニュー（ログアウトボタン）
├── features/
│   └── auth/
│       ├── hooks/
│       │   └── useLogout.ts    # ログアウトフック
│       └── services/
│           └── authService.ts  # API 呼び出し（更新）
```

### UserMenu 実装

```tsx
export const UserMenu: FC = () => {
  const { staff } = useAuth();
  const { logout, isLoading } = useLogout();
  const [isOpen, setIsOpen] = useState(false);

  const handleLogout = async () => {
    await logout();
  };

  return (
    <div className="relative">
      <button
        onClick={() => setIsOpen(!isOpen)}
        className="flex items-center gap-2 text-gray-700 hover:text-gray-900"
      >
        <span>{staff?.name}</span>
        <ChevronDownIcon className="h-4 w-4" />
      </button>

      {isOpen && (
        <div className="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg">
          <button
            onClick={handleLogout}
            disabled={isLoading}
            className="w-full px-4 py-2 text-left text-gray-700 hover:bg-gray-100"
          >
            {isLoading ? 'ログアウト中...' : 'ログアウト'}
          </button>
        </div>
      )}
    </div>
  );
};
```

### useLogout フック実装

```tsx
export const useLogout = () => {
  const navigate = useNavigate();
  const { clearAuth } = useAuth();
  const queryClient = useQueryClient();

  const mutation = useMutation({
    mutationFn: authService.logout,
    onSuccess: () => {
      // 認証状態をクリア
      clearAuth();

      // キャッシュをクリア
      queryClient.clear();

      // ログイン画面にリダイレクト
      navigate('/login', {
        replace: true,
        state: { message: 'ログアウトしました' }
      });
    },
    onError: (error) => {
      console.error('Logout failed:', error);
      // エラーでも認証状態をクリアしてログイン画面へ
      clearAuth();
      navigate('/login', { replace: true });
    },
  });

  return {
    logout: mutation.mutate,
    isLoading: mutation.isPending,
    error: mutation.error,
  };
};
```

### authService 更新

```tsx
// authService.ts
export const authService = {
  // ... 既存のメソッド

  logout: async (): Promise<void> => {
    await axios.post('/api/auth/logout');
  },
};
```

### Header にユーザーメニューを配置

```tsx
export const Header: FC = () => {
  return (
    <header className="bg-white shadow">
      <div className="max-w-7xl mx-auto px-4 py-4 flex justify-between items-center">
        <div className="flex items-center gap-8">
          <Logo />
          <Navigation />
        </div>
        <UserMenu />
      </div>
    </header>
  );
};
```

---

## 成果物

| 成果物 | 配置場所 |
|--------|---------|
| UserMenu | frontend/src/components/layout/UserMenu.tsx |
| useLogout | frontend/src/features/auth/hooks/useLogout.ts |
| authService（更新） | frontend/src/features/auth/services/authService.ts |
| Header（更新） | frontend/src/components/layout/Header.tsx |

---

## タスク

### Design Tasks（外部設計）

- [ ] ユーザーメニューのデザイン確定
- [ ] ログアウトボタンの配置確定

### Spec Tasks（詳細設計）

- [ ] UserMenu コンポーネント実装
- [ ] useLogout フック実装
- [ ] authService に logout メソッド追加
- [ ] Header に UserMenu を配置
- [ ] コンポーネントテスト作成

---

## 変更履歴

| 日付 | 変更内容 |
|------|---------|
| 2025-12-24 | 初版作成 |
