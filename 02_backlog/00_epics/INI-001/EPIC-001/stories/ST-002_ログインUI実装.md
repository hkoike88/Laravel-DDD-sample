# ST-002: ログイン UI の実装

最終更新: 2025-12-26

---

## ストーリー

**図書館職員として**、ログイン画面からシステムにログインしたい。
**なぜなら**、職員向け機能（蔵書管理、貸出・返却、利用者管理）を利用したいからだ。

---

## 関連情報

| 項目 | 値 |
|------|-----|
| Epic | [EPIC-001: 職員ログイン機能](../epic.md) |
| ポイント | 3 |
| 優先度 | Must |
| ステータス | Planned |
| ワイヤーフレーム | [職員ログイン](../../../../../01_vision/initiatives/INI-001/ui/wireframes/staff-login.md) |

---

## 受け入れ条件

1. [ ] メールアドレス入力フィールドが表示されること
2. [ ] パスワード入力フィールド（マスク表示）が表示されること
3. [ ] パスワード表示/非表示の切り替えができること
4. [ ] ログインボタンをクリックするとログイン処理が実行されること
5. [ ] ログイン成功時にダッシュボード画面にリダイレクトされること
6. [ ] 認証エラー時にエラーメッセージが表示されること
7. [ ] バリデーションエラー時にフィールドごとにエラーが表示されること
8. [ ] ログイン処理中はローディング状態が表示されること
9. [ ] Enter キーでログインを実行できること
10. [ ] 入力中はボタンがフォーカス可能であること

---

## 画面仕様

### UI 要素

| 要素ID | 種類 | 要素名 | 必須 | 説明 |
|--------|------|--------|------|------|
| email | input[email] | メールアドレス | ○ | 職員のメールアドレス |
| password | input[password] | パスワード | ○ | ログインパスワード |
| toggle-password | button | 表示切替 | - | パスワードの表示/非表示 |
| btn-login | button | ログイン | - | ログイン実行ボタン |
| error-message | div | エラーメッセージ | - | 認証エラー表示 |

### バリデーション

| フィールド | ルール | エラーメッセージ |
|-----------|--------|-----------------|
| メールアドレス | 必須 | メールアドレスを入力してください |
| メールアドレス | メール形式 | 正しいメールアドレス形式で入力してください |
| パスワード | 必須 | パスワードを入力してください |

### 状態遷移

| 状態 | 説明 |
|------|------|
| 初期状態 | フォーム入力可能、ボタン有効 |
| 入力中 | リアルタイムバリデーション |
| 送信中 | ボタン無効、ローディング表示 |
| エラー | エラーメッセージ表示、フォーム入力可能 |
| 成功 | ダッシュボードへリダイレクト |

---

## 技術仕様

### コンポーネント構成

```
LoginPage
├── LoginForm
│   ├── EmailField
│   ├── PasswordField
│   └── SubmitButton
└── ErrorMessage
```

### React Hook Form + Zod スキーマ

```typescript
// loginSchema.ts
import { z } from 'zod';

export const loginSchema = z.object({
  email: z
    .string()
    .min(1, 'メールアドレスを入力してください')
    .email('正しいメールアドレス形式で入力してください'),
  password: z
    .string()
    .min(1, 'パスワードを入力してください'),
});

export type LoginFormData = z.infer<typeof loginSchema>;
```

### useLogin Hook

```typescript
// useLogin.ts
import { useMutation } from '@tanstack/react-query';
import { useNavigate } from 'react-router-dom';
import { useAuthStore } from '@/stores/authStore';
import { authApi } from '@/features/auth/api';

export const useLogin = () => {
  const navigate = useNavigate();
  const setUser = useAuthStore((state) => state.setUser);

  return useMutation({
    mutationFn: authApi.login,
    onSuccess: (data) => {
      setUser(data.user);
      navigate('/staff/dashboard');
    },
  });
};
```

### LoginPage コンポーネント

```tsx
// LoginPage.tsx
export const LoginPage: React.FC = () => {
  const { register, handleSubmit, formState: { errors } } = useForm<LoginFormData>({
    resolver: zodResolver(loginSchema),
  });
  const { mutate: login, isPending, error } = useLogin();

  const onSubmit = (data: LoginFormData) => {
    login(data);
  };

  return (
    <div className="login-container">
      <form onSubmit={handleSubmit(onSubmit)}>
        <TextField
          label="メールアドレス"
          type="email"
          {...register('email')}
          error={errors.email?.message}
        />
        <TextField
          label="パスワード"
          type="password"
          {...register('password')}
          error={errors.password?.message}
        />
        {error && <ErrorMessage message={error.message} />}
        <Button type="submit" loading={isPending}>
          ログイン
        </Button>
      </form>
    </div>
  );
};
```

---

## 成果物

| 成果物 | 配置場所 |
|--------|---------|
| LoginPage | frontend/src/pages/staff/LoginPage.tsx |
| LoginForm | frontend/src/features/auth/components/LoginForm.tsx |
| loginSchema | frontend/src/features/auth/schemas/loginSchema.ts |
| useLogin | frontend/src/features/auth/hooks/useLogin.ts |
| authApi | frontend/src/features/auth/api/authApi.ts |
| テスト | frontend/src/features/auth/\_\_tests\_\_/LoginForm.test.tsx |

---

## タスク

### Design Tasks（外部設計）

- [ ] ワイヤーフレームの確認
- [ ] エラーメッセージの確定
- [ ] アクセシビリティ要件の確認

### Spec Tasks（詳細設計）

- [ ] loginSchema 実装
- [ ] authApi 実装（login, getCsrfCookie）
- [ ] useLogin フック実装
- [ ] LoginForm コンポーネント実装
- [ ] LoginPage 実装
- [ ] ルーティング設定
- [ ] コンポーネントテスト作成

---

## 変更履歴

| 日付 | 変更内容 |
|------|---------|
| 2025-12-26 | 初版作成 |
