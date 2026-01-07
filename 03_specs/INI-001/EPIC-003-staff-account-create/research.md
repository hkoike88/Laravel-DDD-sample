# Research: 職員アカウント作成機能

**Branch**: `007-staff-account-create` | **Date**: 2026-01-06

## 概要

職員アカウント作成機能の実装に必要な技術的調査結果をまとめる。

---

## 1. 初期パスワード生成

### Decision

PHP の `random_int()` を使用して、暗号学的に安全な16文字のランダムパスワードを生成する。

### Rationale

- `random_int()` は暗号学的に安全な乱数を生成する（`random_bytes()` をベースにしている）
- Laravel の `Str::random()` も内部で `random_bytes()` を使用しているが、より明示的にパスワード用の文字セットを制御するためカスタム実装を選択
- パスワードには英大文字・英小文字・数字・記号を含め、セキュリティ要件を満たす

### Alternatives Considered

| 方法 | 評価 | 却下理由 |
|------|------|----------|
| `Str::random(16)` | 使いやすい | 英数字のみで記号を含まない |
| `password_hash()` + ランダム文字列 | 安全 | ハッシュ化は保存時に別途行う |
| UUID/ULID の一部 | 一意性あり | 予測可能性が高く、パスワードには不適 |

### Implementation

```php
class PasswordGenerator
{
    private const LOWERCASE = 'abcdefghijklmnopqrstuvwxyz';
    private const UPPERCASE = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    private const DIGITS = '0123456789';
    private const SYMBOLS = '!@#$%^&*';

    public function generate(int $length = 16): string
    {
        $chars = self::LOWERCASE . self::UPPERCASE . self::DIGITS . self::SYMBOLS;
        $password = '';

        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[random_int(0, strlen($chars) - 1)];
        }

        return $password;
    }
}
```

---

## 2. 監査ログの実装パターン

### Decision

Laravel の Log ファサードを使用し、audit チャンネルに JSON 形式でログを出力する。

### Rationale

- 既存の Laravel ログ機能を活用し、追加のインフラ依存を避ける
- JSON 形式により、後続のログ分析ツールとの連携が容易
- audit チャンネルを分離することで、業務操作の監査ログ管理が容易
- security チャンネルは認証・認可イベント用として区別

### Alternatives Considered

| 方法 | 評価 | 却下理由 |
|------|------|----------|
| 専用テーブル | 構造化 | 本機能のスコープを超える |
| Eloquent イベント | 自動化 | 明示的な記録が必要なため不適 |
| 外部サービス | 高機能 | インフラ追加が必要 |
| security チャンネル | 既存 | 認証・認可イベント用として分離 |

### Implementation

```php
class StaffAuditLogger
{
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
}
```

---

## 3. 管理者権限チェック（Authorization）

### Decision

Laravel ミドルウェアで管理者権限をチェックし、Gate/Policy でも二重チェックを行う。

### Rationale

- ミドルウェアでルートレベルの保護を行い、不正アクセスを早期に遮断
- Gate/Policy でアクション単位の認可を行い、コントローラの責務を軽減
- 既存の認証基盤（Laravel Sanctum）と統合しやすい

### Alternatives Considered

| 方法 | 評価 | 却下理由 |
|------|------|----------|
| コントローラ内チェック | 簡単 | 重複コードが発生しやすい |
| ミドルウェアのみ | シンプル | 細かい認可制御が困難 |
| RBAC パッケージ | 高機能 | 現時点では過剰 |

### Implementation

```php
// ミドルウェア
class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->isAdmin()) {
            abort(403, 'この操作を行う権限がありません');
        }
        return $next($request);
    }
}

// ルート定義
Route::middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::get('/api/staff/accounts', [StaffAccountController::class, 'index']);
    Route::post('/api/staff/accounts', [StaffAccountController::class, 'store']);
});
```

---

## 4. ページネーション

### Decision

Laravel の `paginate()` メソッドを使用し、フロントエンドは TanStack Query でデータ取得・キャッシュを管理する。

### Rationale

- Laravel 標準のページネーションはレスポンス形式が統一されており、フロントエンドとの連携が容易
- TanStack Query のキャッシュ機能により、ページ遷移時のパフォーマンスが向上
- 20件/ページは一般的な一覧表示で適切なサイズ

### Implementation

```php
// Backend
public function index(Request $request): JsonResponse
{
    $staffs = Staff::query()
        ->orderBy('created_at', 'desc')
        ->paginate(20);

    return response()->json($staffs);
}

// Frontend (React Query)
const useStaffList = (page: number) => {
    return useQuery({
        queryKey: ['staffList', page],
        queryFn: () => fetchStaffList(page),
        keepPreviousData: true,
    });
};
```

---

## 5. パスワードマスク表示（フロントエンド）

### Decision

React コンポーネントでマスク表示/平文表示を切り替え、クリップボードコピー機能を提供する。

### Rationale

- ショルダーハッキング（背後からの覗き見）対策としてデフォルトはマスク表示
- `navigator.clipboard.writeText()` でコピー機能を実装し、安全にパスワードを伝達
- アクセシビリティを考慮し、ボタンに明確なラベルを付与

### Implementation

```tsx
const PasswordDisplay: React.FC<{ password: string }> = ({ password }) => {
    const [visible, setVisible] = useState(false);
    const [copied, setCopied] = useState(false);

    const handleCopy = async () => {
        await navigator.clipboard.writeText(password);
        setCopied(true);
        setTimeout(() => setCopied(false), 2000);
    };

    return (
        <div>
            <span>{visible ? password : '••••••••••••••••'}</span>
            <button onClick={() => setVisible(!visible)}>
                {visible ? '非表示' : '表示'}
            </button>
            <button onClick={handleCopy}>
                {copied ? 'コピーしました' : 'コピー'}
            </button>
        </div>
    );
};
```

---

## 6. メールアドレス一意性検証

### Decision

データベースの UNIQUE 制約と Laravel バリデーションルールの両方で一意性を保証する。

### Rationale

- データベースレベルの制約で最終的なデータ整合性を保証
- バリデーションルールで早期エラー検出とユーザーフレンドリーなメッセージを提供
- 同時登録の競合状態（Race Condition）はデータベース制約で防止

### Implementation

```php
// Form Request
class CreateStaffRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:255', 'unique:staffs,email'],
            'role' => ['required', 'in:staff,admin'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'このメールアドレスは既に登録されています',
        ];
    }
}
```

---

## 7. フロントエンドのフォームバリデーション

### Decision

Zod でスキーマ定義し、React Hook Form と連携してリアルタイムバリデーションを行う。

### Rationale

- Zod は TypeScript との親和性が高く、型安全なバリデーションが可能
- React Hook Form は再レンダリングを最小化し、パフォーマンスに優れる
- クライアント側バリデーションでユーザー体験を向上（サーバーへのリクエスト削減）

### Implementation

```typescript
// Zod スキーマ
const createStaffSchema = z.object({
    name: z.string()
        .min(1, '氏名は必須です')
        .max(50, '氏名は50文字以内で入力してください'),
    email: z.string()
        .min(1, 'メールアドレスは必須です')
        .email('有効なメールアドレスを入力してください')
        .max(255),
    role: z.enum(['staff', 'admin'], {
        errorMap: () => ({ message: '権限を選択してください' }),
    }),
});

// React Hook Form
const { register, handleSubmit, formState: { errors } } = useForm({
    resolver: zodResolver(createStaffSchema),
});
```

---

## 調査完了サマリー

| トピック | ステータス | 補足 |
|----------|------------|------|
| 初期パスワード生成 | 完了 | `random_int()` ベースのカスタム実装 |
| 監査ログ | 完了 | Laravel Log ファサード + security チャンネル |
| 管理者権限チェック | 完了 | ミドルウェア + Gate/Policy |
| ページネーション | 完了 | Laravel paginate + TanStack Query |
| パスワードマスク表示 | 完了 | React コンポーネント |
| メールアドレス一意性 | 完了 | DB 制約 + バリデーションルール |
| フォームバリデーション | 完了 | Zod + React Hook Form |

すべての技術的な調査が完了しました。Phase 1（Design & Contracts）に進む準備ができています。
