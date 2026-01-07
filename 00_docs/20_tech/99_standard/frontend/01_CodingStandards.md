# フロントエンドコーディング規約（React + TypeScript）

本ドキュメントは、React + TypeScript をベースにした SPA / フロントエンド実装におけるコーディング規約です。
状態管理や API 通信は、以下の構成を前提とします。

* 言語: TypeScript
* ライブラリ: React
* 状態管理: TanStack Query（サーバ状態） + Zustand（UI/クライアント状態）
* HTTP クライアント: axios などの共通 apiClient

---

## 1. 開発原則

本プロジェクトでは以下の開発原則を重視します。コードレビューや設計判断の際には、これらの原則に立ち返って検討してください。

### 1.1 SOLID原則（React/TypeScript向け）

#### S: Single Responsibility Principle（単一責任の原則）

* 1コンポーネント・1カスタムフックは1つの責務のみを持つ。
* データ取得、状態管理、UI表示を1コンポーネントに詰め込まない。
* Container（ロジック）と Presentational（UI）の分離を意識する。

**例:**
```typescript
// 悪い例: データ取得とUI表示が混在
function UserProfile() {
  const [user, setUser] = useState<User | null>(null);
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    setLoading(true);
    fetchUser().then(setUser).finally(() => setLoading(false));
  }, []);

  return (
    <div>
      {loading ? <Spinner /> : <UserCard user={user} />}
      <Button onClick={() => sendEmail(user)}>メール送信</Button>
    </div>
  );
}

// 良い例: カスタムフックでロジック分離
function useUser(userId: string) {
  const { data, isLoading } = useQuery(['user', userId], () => fetchUser(userId));
  return { user: data, isLoading };
}

function UserProfile({ userId }: Props) {
  const { user, isLoading } = useUser(userId);

  if (isLoading) return <Spinner />;
  return <UserCard user={user} />;
}
```

#### O: Open/Closed Principle（開放/閉鎖の原則）

* 新しいバリエーションを追加する際、既存コンポーネントを変更せず、Props や children で拡張可能にする。
* 共通コンポーネントは汎用的に設計し、特定の用途に依存しない。

**例:**
```typescript
// 悪い例: 新しいボタンタイプを追加するたびに修正が必要
function Button({ type }: { type: 'primary' | 'secondary' | 'danger' }) {
  if (type === 'primary') return <button className="btn-primary">...</button>;
  if (type === 'secondary') return <button className="btn-secondary">...</button>;
  if (type === 'danger') return <button className="btn-danger">...</button>;
}

// 良い例: className を Props で受け取り、拡張可能に
function Button({ className, children, ...props }: ButtonProps) {
  return <button className={`btn ${className}`} {...props}>{children}</button>;
}

// 使用例
<Button className="btn-primary">送信</Button>
<Button className="btn-danger">削除</Button>
```

#### L: Liskov Substitution Principle（リスコフの置換原則）

* 親コンポーネントの Props インターフェースを継承する場合、親の契約を守る。
* 共通コンポーネントを拡張する際、元の使い方を壊さない。

#### I: Interface Segregation Principle（インターフェース分離の原則）

* Props が肥大化したら分割する。
* 使わない Props を強制しない（optional にする、またはコンポーネント分割）。

**例:**
```typescript
// 悪い例: 全ての Props を1つのインターフェースに
interface UserFormProps {
  user: User;
  onSubmit: (user: User) => void;
  onCancel: () => void;
  showDeleteButton: boolean;
  onDelete?: () => void;
  showExportButton: boolean;
  onExport?: () => void;
  // ... 増え続ける
}

// 良い例: 責務ごとに分離
interface UserFormProps {
  user: User;
  onSubmit: (user: User) => void;
  onCancel: () => void;
}

interface UserFormActionsProps {
  onDelete?: () => void;
  onExport?: () => void;
}

// さらに分割
function UserForm({ user, onSubmit, onCancel }: UserFormProps) { ... }
function UserFormActions({ onDelete, onExport }: UserFormActionsProps) { ... }
```

#### D: Dependency Inversion Principle（依存性逆転の原則）

* コンポーネントは具体的な実装ではなく、Props インターフェースに依存する。
* カスタムフックは API クライアントの実装詳細ではなく、抽象化されたインターフェースに依存する。

**例:**
```typescript
// 悪い例: 具体的な実装に依存
function UserList() {
  const users = useContext(UserContext); // 具体的な Context に依存
  return <ul>{users.map(user => <li>{user.name}</li>)}</ul>;
}

// 良い例: Props インターフェースに依存
interface UserListProps {
  users: User[];
}

function UserList({ users }: UserListProps) {
  return <ul>{users.map(user => <li key={user.id}>{user.name}</li>)}</ul>;
}

// データ取得は親コンポーネントやカスタムフックで
function UserListContainer() {
  const { data: users } = useUsersQuery();
  return <UserList users={users ?? []} />;
}
```

### 1.2 KISS（Keep It Simple, Stupid）

* **シンプルさを保つ**。複雑な実装は避ける。
* 過度な抽象化・HOC の多重ラップ・複雑な状態管理を避ける。
* 読みやすさ・理解しやすさを優先する。

**原則:**
* 必要最小限の実装にとどめる。
* 不要な Context・カスタムフック・コンポーネントを作らない。
* 一度で理解できるコードを書く。

**例:**
```typescript
// 悪い例: 過度に複雑
const withAuth = (Component) => (props) => {
  const { user } = useAuth();
  if (!user) return <Redirect to="/login" />;
  return <Component {...props} user={user} />;
};

const withLoading = (Component) => (props) => {
  const { isLoading } = useLoading();
  if (isLoading) return <Spinner />;
  return <Component {...props} />;
};

export default withAuth(withLoading(UserProfile));

// 良い例: シンプルに
function UserProfile() {
  const { user, isLoading } = useAuth();

  if (isLoading) return <Spinner />;
  if (!user) return <Navigate to="/login" />;

  return <div>...</div>;
}
```

### 1.3 YAGNI（You Aren't Gonna Need It）

* **必要になるまで実装しない**。
* 将来必要になるかもしれない機能を先回りして実装しない。
* 現在の要件に集中する。

**原則:**
* 「将来使うかも」のコンポーネント・カスタムフック・Props は実装しない。
* 仕様が明確になってから実装する。
* リファクタリングで後から追加できることを信頼する。

**例:**
```typescript
// 悪い例: 使わない機能を先回り実装
interface UserCardProps {
  user: User;
  showAvatar?: boolean;
  showBio?: boolean;
  showFollowers?: boolean;  // まだ要件にない
  showActivity?: boolean;   // まだ要件にない
  onExport?: () => void;    // まだ要件にない
}

function UserCard({ user, showAvatar, showBio, showFollowers, showActivity, onExport }: UserCardProps) {
  // ... 使われない機能が大量に実装されている
}

// 良い例: 現在必要な機能のみ
interface UserCardProps {
  user: User;
  showAvatar?: boolean;
  showBio?: boolean;
}

function UserCard({ user, showAvatar, showBio }: UserCardProps) {
  // 現在の要件のみ実装
}
```

### 1.4 DRY（Don't Repeat Yourself）

* **重複を避ける**。同じロジックを複数箇所に書かない。
* 共通処理はカスタムフック・共通コンポーネント・ユーティリティ関数に抽出する。
* ただし、偶発的な重複（coincidental duplication）には注意。

**原則:**
* 同じコードが3回出てきたら抽出を検討（Three Strikes Rule）。
* ビジネスロジックの重複は特に避ける。
* 見た目が似ているだけの重複は無理に共通化しない。

**例:**
```typescript
// 悪い例: ロジックの重複
function UserProfile() {
  const [user, setUser] = useState<User | null>(null);
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    setLoading(true);
    fetchUser().then(setUser).finally(() => setLoading(false));
  }, []);

  // ...
}

function UserSettings() {
  const [user, setUser] = useState<User | null>(null);
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    setLoading(true);
    fetchUser().then(setUser).finally(() => setLoading(false));
  }, []);

  // ...
}

// 良い例: カスタムフックに抽出
function useUser() {
  const { data: user, isLoading } = useQuery(['user'], fetchUser);
  return { user, isLoading };
}

function UserProfile() {
  const { user, isLoading } = useUser();
  // ...
}

function UserSettings() {
  const { user, isLoading } = useUser();
  // ...
}
```

**注意: 偶発的な重複には抽出しない**

見た目が似ているだけで、異なる理由・異なる変更タイミングで変わるコードは無理に共通化しない。

```typescript
// これらは見た目は似ているが、別々の理由で変更される可能性がある
function LoginButton() {
  return <Button variant="primary">ログイン</Button>;
}

function SubmitButton() {
  return <Button variant="primary">送信</Button>;
}
// → 無理に共通化せず、それぞれ独立させておく
```

### 1.5 コンポーネント/関数の長さ

* **1コンポーネントは100-200行以内を理想とする**。
* 200行を超えてきたら分割を検討する。
* 1関数は20-30行以内を理想とする（最大50行）。

**分割の基準:**
* **複数の責務**: Container と Presentational に分離する。
* **条件分岐が多い**: 条件ごとにサブコンポーネント化する。
* **繰り返し処理**: リストアイテムを別コンポーネントにする。
* **深いネスト**: 早期 return、コンポーネント分割でネストを浅くする（最大3-4レベル）。

**例:**
```typescript
// 悪い例: 1コンポーネントに詰め込む（300行超）
function UserDashboard() {
  // 状態管理（30行）
  const [user, setUser] = useState<User | null>(null);
  const [posts, setPosts] = useState<Post[]>([]);
  // ...

  // データ取得（50行）
  useEffect(() => { ... }, []);

  // イベントハンドラ（100行）
  const handleSubmit = () => { ... };
  const handleDelete = () => { ... };
  // ...

  // UI レンダリング（120行）
  return (
    <div>
      {/* 複雑なネスト構造 */}
    </div>
  );
}

// 良い例: 責務ごとに分割
function useUserDashboard() {
  const { data: user } = useQuery(['user'], fetchUser);
  const { data: posts } = useQuery(['posts'], fetchPosts);
  return { user, posts };
}

function UserDashboard() {
  const { user, posts } = useUserDashboard();

  return (
    <div>
      <UserHeader user={user} />
      <UserStats user={user} />
      <PostList posts={posts} />
    </div>
  );
}

function UserHeader({ user }: { user: User }) { /* 30行 */ }
function UserStats({ user }: { user: User }) { /* 40行 */ }
function PostList({ posts }: { posts: Post[] }) { /* 50行 */ }
```

---

## 2. ディレクトリ構成 / レイヤ責務

```text
src/
├── features/           # ドメイン単位の機能群（Feature-based）
│   ├── auth/
│   │   ├── components/
│   │   ├── hooks/
│   │   ├── pages/
│   │   ├── types.ts
│   │   └── schema.ts   # バリデーションスキーマなど（必要に応じて）
│   ├── questions/
│   ├── practice/
│   └── ...
├── services/           # apiClient, tokenService, queryClient など
├── stores/             # グローバル UI 状態（Zustand ストア）
├── components/         # 共通 UI コンポーネント（Button, Modal 等）
├── layouts/            # 画面レイアウト
├── routes/             # ルーティング設定
├── App.tsx
└── main.tsx
```

### 2.1 Feature 単位設計

* 1 Feature = 1 ドメイン的な機能（例: auth, questions, practice, bookmarks）。
* Feature 配下には原則として次を用意する:

  * `components/`: Feature 専用の UI コンポーネント
  * `hooks/`: Feature 専用のカスタムフック（TanStack Query, Zustand など）
  * `pages/`: ルーティング単位のページコンポーネント
  * `types.ts`: Feature に閉じた型定義
  * `schema.ts` / `validation.ts`: バリデーションスキーマ（Zod など）

### 2.2 共通レイヤ

* `components/`: プロジェクト全体で再利用する UI コンポーネント
* `services/`: API クライアント、認証トークン管理、設定など
* `stores/`: 画面横断の UI 状態管理（Zustand）

---

## 3. 命名規約

### 3.1 ファイル / コンポーネント

* React コンポーネント: PascalCase（`LoginForm.tsx`, `QuestionList.tsx`）。
* ページコンポーネント: ` XxxPage.tsx` 接尾辞を推奨（`LoginPage.tsx`, `PracticePage.tsx`）。
* Hook ファイル: `useXxx.ts`（`useLogin.ts`, `useQuestions.ts`）。
* Zustand ストア: `xxxStore.ts` または `useXxxStore.ts`。

### 3.2 変数 / 関数

* lowerCamelCase。
* boolean 変数/関数は `is`, `has`, `can`, `should` から始める。

  * `isLoading`, `hasError`, `canSubmit`, `shouldRefetch`。

### 3.3 型定義

* 型エイリアス / インターフェースは PascalCase。

  * `type LoginFormValues = {...}`
  * `interface Question`。
* API Request/Response 型は `XxxRequest`, `XxxResponse`。

---

## 4. TypeScript ルール

### 4.1 any の禁止

* `any` の使用は禁止。
* やむを得ず使用する場合は `// FIXME` コメントで理由と除去方針を書く。

### 4.2 型の明示

* 公開関数の戻り値型は基本的に明示する。
* カスタムフックの戻り値はオブジェクト型で定義し、構造を固定する。

### 4.3 Nullable / Optional

* `null` と `undefined` を混在させない（どちらを使うか決めておく）。
* Optional プロパティは `foo?: string`、存在しない可能性がある値は `string | null` など。

### 4.4 リテラル型 / enum

* マジック文字列/数値には literal 型 or enum or `as const` を使用する。

  * 例) ステータス `"idle" | "loading" | "error" | "success"`。

---

## 5. React コンポーネント設計

### 5.1 責務の分離

* Container（状態・データ取得）と Presentational（表示）の分離を意識する。
* 1 コンポーネントあたりの責務を小さく保ち、100-200 行以内を理想とする。

### 5.2 Props 設計

* Props は必要最小限にする。
* Props が増えすぎる場合はオブジェクト 1 つにまとめる、またはコンポーネント分割を検討。
* コールバック関数は `onXxx` 命名（`onClick`, `onSubmit`, `onChangeFilter` など）。

### 5.3 再レンダリング配慮

* 子コンポーネントに渡すコールバックは `useCallback` でメモ化を検討。
* 高コストな計算処理がある場合は `useMemo` を利用して最適化。

### 5.4 リストレンダリング

* `key` には index ではなく安定した ID を使用することを基本とする。
* 並び替え・削除が発生しない静的リストのみ index 許容。

---

## 6. 状態管理（TanStack Query / Zustand）

### 6.1 原則

* サーバ由来のデータ（API レスポンス）は **TanStack Query** で管理する。
* UI 状態・一時的なクライアント専用状態は **Zustand** またはローカル state で管理する。

### 6.2 TanStack Query

* Query Key は `[feature, resource, params]` 形式の配列に統一する。

  * 例: `['questions', 'list', { topicId }]`。
* `useQuery`, `useMutation` は直接コンポーネントで使わず、Feature の `hooks/` でラップしたカスタムフックにまとめる。

  * 例: `useQuestionsQuery`, `useCreateBookmarkMutation`。
* エラー処理・loading 状態・リトライポリシーはカスタムフック側で基本方針を定める。

### 6.3 Zustand

* グローバル UI 状態（モーダルの開閉、選択中のタブ、フィルタ条件など）に使用。
* ストアの作成関数名は `useXxxStore` に統一。
* Zustand ストアは `stores/` または Feature 配下の `stores/` に配置。

---

## 7. API クライアント / 通信

### 7.1 apiClient

* HTTP 通信は `services/apiClient.ts`（axios インスタンス）経由に統一。
* apiClient に実装するもの:

  * baseURL 設定
  * 認証ヘッダ付与（Authorization: Bearer ...）
  * 共通エラーハンドリング
  * レスポンスの型のラップ（必要であれば）

### 7.2 Feature 別 API 関数

* 各 Feature 配下に `api.ts` を作成し、apiClient を用いて thin wrapper 関数を定義。

  * 例: `login`, `fetchQuestions`, `submitAnswer` など。
* コンポーネントから直接 axios を呼び出すことを禁止。

---

## 8. フォーム / バリデーション

### 8.1 ライブラリ

* フォームは原則 React Hook Form + Zod（または Yup）を使用。
* バリデーションスキーマは `schema.ts` / `validation.ts` に定義。

### 8.2 エラーメッセージ

* エラーメッセージの文言は可能な限り共通化（定数管理）する。
* UI としてのエラー表示コンポーネント（例: `<FormError />`）を共通コンポーネントとして用意することを検討。

---

## 9. スタイル / UI

### 9.1 コンポーネントライブラリ

* Material UI など UI ライブラリを利用する場合、独自 CSS と混在しすぎないようにする。
* テーマ（色、フォント、ブレークポイント）はライブラリのテーマ機能で一元管理。

### 9.2 CSS 設計

* Tailwind / CSS-in-JS / CSS Modules など、プロジェクトで採用した方法に従う。
* Tailwind 利用時:

  * クラスが長くなりすぎる場合は `className` を変数に切り出す or コンポーネント化。

---

## 10. ESLint / Prettier

* ESLint + Prettier を導入し、保存時フォーマットを有効化。
* ESLint の警告は原則無視しない（`// eslint-disable` を使う場合は理由コメント必須）。
* チームで設定したルール（import 順、セミコロン有無など）に従い、差異を最小限にする。

---

## 11. テスト（ユニット / E2E）

### 11.1 ユニットテスト

* テストフレームワーク: Vitest 等。
* 対象:

  * カスタムフック
  * ロジックを含むユーティリティ関数
  * 条件分岐の多いコンポーネント

### 11.2 E2E テスト

* フレームワーク: Playwright / Cypress 等。
* 対象:

  * 主要ユーザーフロー（ログイン → 問題一覧 → 解答 → 結果確認など）。

### 11.3 命名

* テストファイル: `xxx.test.ts`, `xxx.test.tsx`。
* テスト名: `should xxx when yyy` の形式を推奨。

---

## 12. アクセシビリティ

* semantic HTML を基本とする（`button`, `a`, `nav`, `main`, `section` 等）。
* クリックできる UI には `button` / `a` を使用し、`div`+`onClick` だけにしない。
* 画像には `alt` を設定する。
* フォームには `label` を適切に関連付ける。

---

## 13. コメント / ドキュメント

* コメントは「なぜそうしているか」にフォーカスする。
* 型定義やカスタムフックなど、複雑な仕様を持つものには JSDoc 形式でコメントを付与してもよい。

---

## 14. コードレビュー チェックリスト（フロントエンド）

### 1. 開発原則

- [ ] SOLID原則が守られている（単一責任、Props インターフェース分離、依存性逆転など）
- [ ] シンプルな実装になっている（KISS: 過度なHOC・抽象化を避ける）
- [ ] 現在必要な機能のみ実装されている（YAGNI: 将来使うかも、を避ける）
- [ ] ロジックの重複がない（DRY: カスタムフック・共通コンポーネントに抽出）
- [ ] コンポーネントが100-200行以内（関数は20-30行以内）に収まっている

### 2. 構造・責務

* [ ] Feature 配下の構成（components/hooks/pages/types 等）が整理されているか
* [ ] コンポーネントが 1 つの責務に絞られているか
* [ ] Container と Presentational の分離が過度に崩れていないか

### 3. TypeScript / 型安全

* [ ] `any` を使用していないか（例外がある場合は理由コメントがあるか）
* [ ] 公開関数・カスタムフックの戻り値型が明示されているか
* [ ] null / undefined の扱いが曖昧になっていないか

### 4. 状態管理

* [ ] サーバ由来の状態が TanStack Query で管理されているか
* [ ] UI 状態が Zustand or ローカル state で適切に管理されているか
* [ ] Query Key が一貫した命名規則で設計されているか
* [ ] カスタムフック（`useXxx`）にロジックが集約され、コンポーネントが細く保たれているか

### 5. API 通信

* [ ] コンポーネントから直接 axios を呼んでいないか
* [ ] `services/apiClient` 経由で通信が行われているか
* [ ] エラーハンドリングが考慮されているか（ユーザーへのフィードバック含む）

### 6. UI / UX

* [ ] ローディング・エラー状態の表示が実装されているか
* [ ] ボタン連打などに対する考慮があるか（`isSubmitting` で disable 等）
* [ ] 文言の表記揺れがないか

### 7. パフォーマンス

* [ ] 不必要な再レンダリングを引き起こす書き方になっていないか
* [ ] 重い処理が `useMemo` / `useCallback` 等で適切にメモ化されているか
* [ ] 不要な再フェッチが発生していないか

### 8. アクセシビリティ

* [ ] クリック要素に適切な HTML タグが使われているか
* [ ] form 要素に label が紐づいているか
* [ ] 画像に alt が設定されているか

### 9. テスト

* [ ] 重要なロジック・カスタムフックに対してテストが書かれているか
* [ ] 主要なユーザーフローが E2E テストでカバーされているか

---

このフロントエンドコーディング規約とチェックリストを、Pull Request 作成時・レビュー時に参照し、
チーム全体で一貫した実装スタイルと品質を維持すること。
