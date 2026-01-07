# バックエンド バリデーション設計標準

## 概要

本プロジェクトのバックエンドにおけるバリデーション（入力検証）設計標準を定める。
FormRequest と ValueObject の責務を明確に分離し、一貫性のある入力検証とエラーメッセージを提供する。

---

## 基本方針

- **多層検証**: Presentation 層と Domain 層の両方で検証
- **責務分離**: 形式検証と業務検証を明確に分離
- **早期失敗**: 無効な入力は早期に検出・拒否
- **ユーザーフレンドリー**: 分かりやすいエラーメッセージを提供
- **セキュアバイデフォルト**: 許可リスト方式で入力を制限

---

## バリデーション層の設計

### 検証の流れ

```
[クライアント]
     ↓
┌─────────────────────────────────────────────────────────────┐
│ Presentation 層: FormRequest                                │
│ - 形式検証（型、長さ、フォーマット）                          │
│ - 存在確認（exists ルール）                                  │
│ - 認可チェック（authorize メソッド）                         │
│ → 検証失敗時: 422 Unprocessable Entity                      │
└─────────────────────────────────────────────────────────────┘
     ↓
┌─────────────────────────────────────────────────────────────┐
│ Application 層: UseCase / Handler                           │
│ - 業務的な事前条件チェック（オプション）                      │
│ - 複数リソース間の整合性チェック                              │
│ → 検証失敗時: 400 Bad Request / 409 Conflict                │
└─────────────────────────────────────────────────────────────┘
     ↓
┌─────────────────────────────────────────────────────────────┐
│ Domain 層: ValueObject / Entity                             │
│ - ビジネスルール検証（不変条件）                              │
│ - 状態遷移の可否判定                                         │
│ → 検証失敗時: DomainException                               │
└─────────────────────────────────────────────────────────────┘
```

### 各層の責務

| 層 | 責務 | 検証内容 | 失敗時 |
|----|------|---------|--------|
| Presentation | 形式検証 | 型、長さ、フォーマット、必須/任意 | 422 |
| Application | 業務事前条件 | リソースの状態、複合条件 | 400/409 |
| Domain | ビジネスルール | 不変条件、状態遷移 | 例外 |

---

## FormRequest の設計

### 基本構造

```php
<?php

declare(strict_types=1);

namespace App\Http\Requests\Book;

use Illuminate\Foundation\Http\FormRequest;

final class CreateBookRequest extends FormRequest
{
    /**
     * リクエストの認可チェック
     */
    public function authorize(): bool
    {
        // 認可ロジック（Policy を使用する場合は true を返す）
        return true;
    }

    /**
     * バリデーションルール
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'author' => ['required', 'string', 'max:255'],
            'isbn' => ['nullable', 'string', 'size:13', 'regex:/^[0-9]+$/'],
            'published_at' => ['nullable', 'date', 'before_or_equal:today'],
            'category_id' => ['required', 'string', 'size:26', 'exists:categories,id'],
        ];
    }

    /**
     * バリデーション属性名
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'title' => '書籍タイトル',
            'author' => '著者名',
            'isbn' => 'ISBN',
            'published_at' => '出版日',
            'category_id' => 'カテゴリ',
        ];
    }

    /**
     * カスタムエラーメッセージ
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => '書籍タイトルは必須です',
            'isbn.size' => 'ISBNは13桁で入力してください',
            'isbn.regex' => 'ISBNは数字のみで入力してください',
            'published_at.before_or_equal' => '出版日は今日以前の日付を指定してください',
        ];
    }
}
```

### FormRequest の責務

**やるべきこと:**
- 入力データの型・形式チェック
- 文字列長・数値範囲のチェック
- 必須/任意の判定
- フォーマット検証（メール、URL、日付等）
- 外部キーの存在確認（`exists` ルール）
- ユニーク制約の確認（`unique` ルール）

**やらないこと:**
- ビジネスルールの検証
- 複雑な条件分岐を伴う検証
- 他のエンティティの状態に依存する検証
- Domain 層のロジックに依存する検証

### 検証ルールのカテゴリ

#### 必須/任意

```php
public function rules(): array
{
    return [
        // 必須
        'title' => ['required', 'string'],

        // 任意（null 許容）
        'description' => ['nullable', 'string'],

        // 条件付き必須
        'discount_rate' => ['required_if:has_discount,true', 'numeric'],

        // いずれか必須
        'email' => ['required_without:phone', 'email'],
        'phone' => ['required_without:email', 'string'],

        // 配列に1つ以上の要素が必須
        'items' => ['required', 'array', 'min:1'],
    ];
}
```

#### 型チェック

```php
public function rules(): array
{
    return [
        // 文字列
        'name' => ['required', 'string'],

        // 整数
        'quantity' => ['required', 'integer'],

        // 数値（小数含む）
        'price' => ['required', 'numeric'],

        // 真偽値
        'is_active' => ['required', 'boolean'],

        // 配列
        'tags' => ['required', 'array'],

        // 日付
        'due_date' => ['required', 'date'],

        // ファイル
        'attachment' => ['required', 'file'],
    ];
}
```

#### 長さ・範囲

```php
public function rules(): array
{
    return [
        // 文字列長
        'title' => ['required', 'string', 'min:1', 'max:255'],
        'isbn' => ['required', 'string', 'size:13'],  // 固定長

        // 数値範囲
        'quantity' => ['required', 'integer', 'min:1', 'max:100'],
        'discount_rate' => ['required', 'numeric', 'between:0,100'],

        // 配列要素数
        'items' => ['required', 'array', 'min:1', 'max:50'],

        // ファイルサイズ（KB）
        'image' => ['required', 'file', 'max:5120'],  // 5MB
    ];
}
```

#### フォーマット

```php
public function rules(): array
{
    return [
        // メールアドレス
        'email' => ['required', 'email:rfc,dns'],

        // URL
        'website' => ['nullable', 'url'],

        // 正規表現
        'phone' => ['required', 'regex:/^0[0-9]{9,10}$/'],
        'postal_code' => ['required', 'regex:/^[0-9]{3}-[0-9]{4}$/'],

        // 日付フォーマット
        'birth_date' => ['required', 'date_format:Y-m-d'],

        // UUID / ULID
        'book_id' => ['required', 'string', 'size:26'],  // ULID

        // JSON
        'metadata' => ['nullable', 'json'],
    ];
}
```

#### 選択肢・列挙

```php
use Illuminate\Validation\Rule;

public function rules(): array
{
    return [
        // 固定値から選択
        'status' => ['required', Rule::in(['draft', 'published', 'archived'])],

        // Enum から選択
        'format' => ['required', Rule::enum(BookFormat::class)],

        // 禁止値
        'username' => ['required', 'string', Rule::notIn(['admin', 'root', 'system'])],
    ];
}
```

#### 比較・条件

```php
public function rules(): array
{
    return [
        // 日付比較
        'start_date' => ['required', 'date'],
        'end_date' => ['required', 'date', 'after:start_date'],
        'published_at' => ['nullable', 'date', 'before_or_equal:today'],

        // フィールド比較
        'password' => ['required', 'string', 'min:12'],
        'password_confirmation' => ['required', 'same:password'],

        // 条件付きルール
        'discount_code' => [
            'nullable',
            'string',
            Rule::when($this->has_discount, ['required', 'exists:discount_codes,code']),
        ],
    ];
}
```

#### 配列・ネスト

```php
public function rules(): array
{
    return [
        // 配列全体
        'items' => ['required', 'array', 'min:1', 'max:50'],

        // 配列要素
        'items.*' => ['required', 'array'],
        'items.*.product_id' => ['required', 'string', 'size:26', 'exists:products,id'],
        'items.*.quantity' => ['required', 'integer', 'min:1', 'max:100'],

        // ネストしたオブジェクト
        'address' => ['required', 'array'],
        'address.postal_code' => ['required', 'string', 'regex:/^[0-9]{3}-[0-9]{4}$/'],
        'address.city' => ['required', 'string', 'max:100'],
        'address.street' => ['required', 'string', 'max:255'],
    ];
}
```

---

## ValueObject の設計

### 基本構造

```php
<?php

declare(strict_types=1);

namespace Packages\Domain\Book\Domain\Model;

use InvalidArgumentException;

final class Isbn
{
    private const LENGTH = 13;

    private function __construct(
        private readonly string $value
    ) {}

    /**
     * 文字列から ISBN を生成
     *
     * @throws InvalidArgumentException
     */
    public static function fromString(string $value): self
    {
        $normalized = self::normalize($value);

        if (!self::isValid($normalized)) {
            throw new InvalidArgumentException('無効なISBN形式です');
        }

        return new self($normalized);
    }

    /**
     * ISBN を正規化（ハイフン除去）
     */
    private static function normalize(string $value): string
    {
        return str_replace('-', '', $value);
    }

    /**
     * ISBN の妥当性を検証
     */
    private static function isValid(string $value): bool
    {
        // 長さチェック
        if (strlen($value) !== self::LENGTH) {
            return false;
        }

        // 数字のみ
        if (!ctype_digit($value)) {
            return false;
        }

        // チェックディジット検証
        return self::validateCheckDigit($value);
    }

    /**
     * チェックディジットを検証
     */
    private static function validateCheckDigit(string $isbn): bool
    {
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $digit = (int) $isbn[$i];
            $sum += ($i % 2 === 0) ? $digit : $digit * 3;
        }

        $checkDigit = (10 - ($sum % 10)) % 10;

        return $checkDigit === (int) $isbn[12];
    }

    public function value(): string
    {
        return $this->value;
    }

    /**
     * ハイフン付きフォーマットで返す
     */
    public function formatted(): string
    {
        return sprintf(
            '%s-%s-%s-%s-%s',
            substr($this->value, 0, 3),
            substr($this->value, 3, 1),
            substr($this->value, 4, 4),
            substr($this->value, 8, 4),
            substr($this->value, 12, 1)
        );
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
```

### ValueObject の責務

**やるべきこと:**
- ビジネスルールに基づく検証
- 不変条件（invariant）の保証
- 値の正規化・変換
- ドメイン固有のフォーマット検証
- 値の等価性比較

**やらないこと:**
- 必須/任意の判定（それは FormRequest の責務）
- 外部リソースへのアクセス（DB、API 等）
- 他のエンティティの状態に依存する検証

### ValueObject パターン集

#### メールアドレス

```php
final class Email
{
    private const MAX_LENGTH = 254;

    private function __construct(
        private readonly string $value
    ) {}

    public static function fromString(string $value): self
    {
        $normalized = mb_strtolower(trim($value));

        if (!filter_var($normalized, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('無効なメールアドレス形式です');
        }

        if (mb_strlen($normalized) > self::MAX_LENGTH) {
            throw new InvalidArgumentException('メールアドレスが長すぎます');
        }

        return new self($normalized);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function domain(): string
    {
        return substr($this->value, strpos($this->value, '@') + 1);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
```

#### パスワード

```php
final class Password
{
    private const MIN_LENGTH = 12;

    private function __construct(
        private readonly string $hashedValue
    ) {}

    /**
     * 平文パスワードからハッシュ化して生成
     */
    public static function fromPlainText(string $plainText): self
    {
        self::validate($plainText);

        return new self(Hash::make($plainText));
    }

    /**
     * ハッシュ済みパスワードから復元
     */
    public static function fromHash(string $hash): self
    {
        return new self($hash);
    }

    /**
     * パスワード要件を検証
     *
     * @throws InvalidArgumentException
     */
    private static function validate(string $plainText): void
    {
        $errors = [];

        if (mb_strlen($plainText) < self::MIN_LENGTH) {
            $errors[] = self::MIN_LENGTH . '文字以上';
        }

        if (!preg_match('/[a-z]/', $plainText)) {
            $errors[] = '小文字を含む';
        }

        if (!preg_match('/[A-Z]/', $plainText)) {
            $errors[] = '大文字を含む';
        }

        if (!preg_match('/[0-9]/', $plainText)) {
            $errors[] = '数字を含む';
        }

        if (!preg_match('/[^a-zA-Z0-9]/', $plainText)) {
            $errors[] = '記号を含む';
        }

        if (!empty($errors)) {
            throw new InvalidArgumentException(
                'パスワードは以下の要件を満たす必要があります: ' . implode('、', $errors)
            );
        }
    }

    public function verify(string $plainText): bool
    {
        return Hash::check($plainText, $this->hashedValue);
    }

    public function hash(): string
    {
        return $this->hashedValue;
    }
}
```

#### 金額

```php
final class Money
{
    private const MIN_AMOUNT = 0;
    private const MAX_AMOUNT = 999999999;  // 9億9999万9999円

    private function __construct(
        private readonly int $amount,
        private readonly string $currency = 'JPY'
    ) {}

    public static function fromInt(int $amount, string $currency = 'JPY'): self
    {
        if ($amount < self::MIN_AMOUNT) {
            throw new InvalidArgumentException('金額は0以上である必要があります');
        }

        if ($amount > self::MAX_AMOUNT) {
            throw new InvalidArgumentException('金額が上限を超えています');
        }

        return new self($amount, $currency);
    }

    public static function zero(string $currency = 'JPY'): self
    {
        return new self(0, $currency);
    }

    public function add(self $other): self
    {
        $this->ensureSameCurrency($other);

        return new self($this->amount + $other->amount, $this->currency);
    }

    public function subtract(self $other): self
    {
        $this->ensureSameCurrency($other);

        $result = $this->amount - $other->amount;
        if ($result < 0) {
            throw new InvalidArgumentException('金額がマイナスになります');
        }

        return new self($result, $this->currency);
    }

    public function multiply(int $multiplier): self
    {
        if ($multiplier < 0) {
            throw new InvalidArgumentException('乗数は0以上である必要があります');
        }

        return new self($this->amount * $multiplier, $this->currency);
    }

    private function ensureSameCurrency(self $other): void
    {
        if ($this->currency !== $other->currency) {
            throw new InvalidArgumentException('通貨が異なります');
        }
    }

    public function amount(): int
    {
        return $this->amount;
    }

    public function currency(): string
    {
        return $this->currency;
    }

    public function format(): string
    {
        if ($this->currency === 'JPY') {
            return '¥' . number_format($this->amount);
        }

        return $this->currency . ' ' . number_format($this->amount);
    }

    public function equals(self $other): bool
    {
        return $this->amount === $other->amount
            && $this->currency === $other->currency;
    }
}
```

#### 日付範囲

```php
final class DateRange
{
    private function __construct(
        private readonly DateTimeImmutable $startDate,
        private readonly DateTimeImmutable $endDate
    ) {}

    public static function create(DateTimeImmutable $startDate, DateTimeImmutable $endDate): self
    {
        if ($startDate > $endDate) {
            throw new InvalidArgumentException('開始日は終了日以前である必要があります');
        }

        return new self($startDate, $endDate);
    }

    public static function fromStrings(string $start, string $end): self
    {
        return self::create(
            new DateTimeImmutable($start),
            new DateTimeImmutable($end)
        );
    }

    public function contains(DateTimeImmutable $date): bool
    {
        return $date >= $this->startDate && $date <= $this->endDate;
    }

    public function overlaps(self $other): bool
    {
        return $this->startDate <= $other->endDate
            && $this->endDate >= $other->startDate;
    }

    public function days(): int
    {
        return $this->startDate->diff($this->endDate)->days + 1;
    }

    public function startDate(): DateTimeImmutable
    {
        return $this->startDate;
    }

    public function endDate(): DateTimeImmutable
    {
        return $this->endDate;
    }
}
```

---

## FormRequest と ValueObject の連携

### Controller での使用

```php
final class BookController extends Controller
{
    public function __construct(
        private CreateBookHandler $createBookHandler,
    ) {}

    public function store(CreateBookRequest $request): JsonResponse
    {
        // FormRequest で形式検証済みのデータを取得
        $validated = $request->validated();

        // UseCase に渡す Command を生成
        // ValueObject への変換は Handler 内で行う
        $command = new CreateBookCommand(
            title: $validated['title'],
            author: $validated['author'],
            isbn: $validated['isbn'] ?? null,
            categoryId: $validated['category_id'],
        );

        $book = $this->createBookHandler->handle($command);

        return response()->json(
            BookResource::fromDomain($book),
            Response::HTTP_CREATED
        );
    }
}
```

### Handler での ValueObject 生成

```php
final class CreateBookHandler
{
    public function __construct(
        private BookRepository $bookRepository,
        private CategoryRepository $categoryRepository,
    ) {}

    public function handle(CreateBookCommand $command): Book
    {
        // 文字列から ValueObject を生成
        // ここでビジネスルール検証が行われる
        $bookId = BookId::generate();
        $title = BookTitle::fromString($command->title);
        $author = Author::fromString($command->author);
        $isbn = $command->isbn !== null
            ? Isbn::fromString($command->isbn)
            : null;
        $categoryId = CategoryId::fromString($command->categoryId);

        // カテゴリの存在確認
        $category = $this->categoryRepository->find($categoryId);

        // エンティティを生成
        $book = Book::create(
            id: $bookId,
            title: $title,
            author: $author,
            isbn: $isbn,
            category: $category,
        );

        $this->bookRepository->save($book);

        return $book;
    }
}
```

### 例外の変換

ValueObject で発生した例外は、Exception Handler で適切な HTTP レスポンスに変換する。

```php
// app/Exceptions/Handler.php
public function render($request, Throwable $e): Response
{
    // ValueObject のバリデーションエラー
    if ($e instanceof InvalidArgumentException) {
        return response()->json([
            'error' => [
                'code' => 'VALIDATION_ERROR',
                'message' => $e->getMessage(),
            ],
        ], Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    // Domain 層のビジネスルール違反
    if ($e instanceof DomainException) {
        return response()->json([
            'error' => [
                'code' => 'BUSINESS_RULE_VIOLATION',
                'message' => $e->getMessage(),
            ],
        ], Response::HTTP_BAD_REQUEST);
    }

    return parent::render($request, $e);
}
```

---

## カスタムバリデーションルール

### Rule クラス

```php
<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final class Ulid implements ValidationRule
{
    private const LENGTH = 26;
    private const PATTERN = '/^[0-9A-HJKMNP-TV-Z]{26}$/i';

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value)) {
            $fail(':attributeは文字列である必要があります');
            return;
        }

        if (strlen($value) !== self::LENGTH) {
            $fail(':attributeは26文字である必要があります');
            return;
        }

        if (!preg_match(self::PATTERN, $value)) {
            $fail(':attributeの形式が正しくありません');
        }
    }
}
```

### 使用例

```php
use App\Rules\Ulid;

public function rules(): array
{
    return [
        'book_id' => ['required', new Ulid()],
        'category_id' => ['required', new Ulid()],
    ];
}
```

### カスタムルール例

#### 郵便番号

```php
final class JapanesePostalCode implements ValidationRule
{
    private const PATTERN = '/^[0-9]{3}-[0-9]{4}$/';

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!preg_match(self::PATTERN, $value)) {
            $fail(':attributeは「000-0000」の形式で入力してください');
        }
    }
}
```

#### 電話番号

```php
final class JapanesePhoneNumber implements ValidationRule
{
    // 固定電話、携帯電話に対応
    private const PATTERNS = [
        '/^0[0-9]{9}$/',           // 固定電話（ハイフンなし）
        '/^0[0-9]{10}$/',          // 携帯電話（ハイフンなし）
        '/^0[0-9]{1,4}-[0-9]{1,4}-[0-9]{3,4}$/',  // ハイフンあり
    ];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $normalized = str_replace(['-', ' ', '　'], '', $value);

        foreach (self::PATTERNS as $pattern) {
            if (preg_match($pattern, $normalized)) {
                return;
            }
        }

        $fail(':attributeの形式が正しくありません');
    }
}
```

#### 全角カタカナ

```php
final class Katakana implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!preg_match('/^[ァ-ヶー]+$/u', $value)) {
            $fail(':attributeは全角カタカナで入力してください');
        }
    }
}
```

#### ユニーク（更新時）

```php
final class UniqueExceptSelf implements ValidationRule
{
    public function __construct(
        private string $table,
        private string $column,
        private ?string $exceptId = null,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $query = DB::table($this->table)
            ->where($this->column, $value);

        if ($this->exceptId !== null) {
            $query->where('id', '!=', $this->exceptId);
        }

        if ($query->exists()) {
            $fail(':attributeは既に使用されています');
        }
    }
}

// 使用例
public function rules(): array
{
    return [
        'email' => [
            'required',
            'email',
            new UniqueExceptSelf('users', 'email', $this->route('user')?->id),
        ],
    ];
}
```

---

## エラーメッセージ設計

### メッセージの原則

1. **ユーザー視点**: 技術的な表現を避け、ユーザーが理解できる言葉で
2. **具体的**: 何が問題で、どうすればよいかを明示
3. **一貫性**: 同じ種類のエラーには同じ形式のメッセージ
4. **日本語**: 本プロジェクトでは日本語でメッセージを提供

### メッセージパターン

| エラー種別 | パターン | 例 |
|-----------|---------|-----|
| 必須 | 「{属性名}は必須です」 | 「書籍タイトルは必須です」 |
| 形式 | 「{属性名}は{形式}で入力してください」 | 「電話番号は数字のみで入力してください」 |
| 長さ | 「{属性名}は{条件}である必要があります」 | 「パスワードは12文字以上である必要があります」 |
| 範囲 | 「{属性名}は{範囲}で入力してください」 | 「数量は1〜100の間で入力してください」 |
| 存在 | 「指定された{属性名}は存在しません」 | 「指定されたカテゴリは存在しません」 |
| 重複 | 「この{属性名}は既に使用されています」 | 「このメールアドレスは既に使用されています」 |

### 言語ファイル

```php
// resources/lang/ja/validation.php
<?php

return [
    // 標準ルールのメッセージ
    'required' => ':attributeは必須です',
    'string' => ':attributeは文字列である必要があります',
    'integer' => ':attributeは整数である必要があります',
    'numeric' => ':attributeは数値である必要があります',
    'email' => ':attributeは有効なメールアドレス形式で入力してください',
    'url' => ':attributeは有効なURL形式で入力してください',
    'date' => ':attributeは有効な日付形式で入力してください',
    'boolean' => ':attributeは true または false である必要があります',

    // 長さ・サイズ
    'min' => [
        'numeric' => ':attributeは:min以上である必要があります',
        'string' => ':attributeは:min文字以上で入力してください',
        'array' => ':attributeは:min個以上の要素が必要です',
    ],
    'max' => [
        'numeric' => ':attributeは:max以下である必要があります',
        'string' => ':attributeは:max文字以下で入力してください',
        'array' => ':attributeは:max個以下にしてください',
        'file' => ':attributeは:maxKB以下のファイルを選択してください',
    ],
    'size' => [
        'string' => ':attributeは:size文字で入力してください',
    ],
    'between' => [
        'numeric' => ':attributeは:minから:maxの間で入力してください',
        'string' => ':attributeは:minから:max文字の間で入力してください',
    ],

    // 比較
    'same' => ':attributeと:otherが一致しません',
    'different' => ':attributeと:otherは異なる値にしてください',
    'confirmed' => ':attributeが確認用の値と一致しません',
    'before' => ':attributeは:date以前の日付を指定してください',
    'after' => ':attributeは:date以降の日付を指定してください',
    'before_or_equal' => ':attributeは:date以前の日付を指定してください',
    'after_or_equal' => ':attributeは:date以降の日付を指定してください',

    // 存在・重複
    'exists' => '指定された:attributeは存在しません',
    'unique' => 'この:attributeは既に使用されています',
    'in' => '選択された:attributeは有効ではありません',
    'not_in' => '選択された:attributeは使用できません',

    // フォーマット
    'regex' => ':attributeの形式が正しくありません',
    'date_format' => ':attributeは:format形式で入力してください',

    // ファイル
    'file' => ':attributeはファイルである必要があります',
    'image' => ':attributeは画像ファイルである必要があります',
    'mimes' => ':attributeは:values形式のファイルである必要があります',
    'mimetypes' => ':attributeは:values形式のファイルである必要があります',
    'dimensions' => ':attributeの画像サイズが不正です',

    // 配列
    'array' => ':attributeは配列である必要があります',

    // 属性名
    'attributes' => [
        'title' => '書籍タイトル',
        'author' => '著者名',
        'isbn' => 'ISBN',
        'email' => 'メールアドレス',
        'password' => 'パスワード',
        'password_confirmation' => 'パスワード（確認）',
        'name' => '氏名',
        'phone' => '電話番号',
        'postal_code' => '郵便番号',
        'address' => '住所',
        'category_id' => 'カテゴリ',
        'quantity' => '数量',
        'price' => '価格',
        'description' => '説明',
        'due_date' => '期限日',
        'start_date' => '開始日',
        'end_date' => '終了日',
    ],
];
```

### API エラーレスポンス形式

```php
// バリデーションエラー（422）
{
    "error": {
        "code": "VALIDATION_ERROR",
        "message": "入力内容に問題があります",
        "details": {
            "title": ["書籍タイトルは必須です"],
            "isbn": [
                "ISBNは13桁で入力してください",
                "ISBNは数字のみで入力してください"
            ]
        }
    }
}

// ビジネスルール違反（400）
{
    "error": {
        "code": "BUSINESS_BOOK_NOT_AVAILABLE",
        "message": "この書籍は現在貸出できません"
    }
}
```

---

## セキュリティ考慮事項

### 入力サニタイズ

```php
public function rules(): array
{
    return [
        // HTML タグを禁止
        'name' => ['required', 'string', 'max:255', 'regex:/^[^<>]*$/'],

        // 制御文字を禁止
        'comment' => [
            'required',
            'string',
            function ($attribute, $value, $fail) {
                if (preg_match('/[\x00-\x1F\x7F]/', $value)) {
                    $fail(':attributeに不正な文字が含まれています');
                }
            },
        ],
    ];
}
```

### 危険な入力のブロック

```php
// 内部 URL のブロック
public function rules(): array
{
    return [
        'callback_url' => [
            'required',
            'url',
            function ($attribute, $value, $fail) {
                $host = parse_url($value, PHP_URL_HOST);

                $blockedHosts = [
                    'localhost',
                    '127.0.0.1',
                    '0.0.0.0',
                    '169.254.169.254',  // AWS メタデータ
                ];

                if (in_array($host, $blockedHosts, true)) {
                    $fail('内部URLは指定できません');
                }

                // プライベートIPのブロック
                if (filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false && filter_var($host, FILTER_VALIDATE_IP)) {
                    $fail('内部IPアドレスは指定できません');
                }
            },
        ],
    ];
}
```

### パスワード強度

```php
use Illuminate\Validation\Rules\Password;

public function rules(): array
{
    return [
        'password' => [
            'required',
            'string',
            Password::min(12)
                ->letters()
                ->mixedCase()
                ->numbers()
                ->symbols()
                ->uncompromised(),  // 漏洩パスワードチェック
            'confirmed',
        ],
    ];
}
```

---

## テスト

### FormRequest のテスト

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Requests\Book;

use App\Http\Requests\Book\CreateBookRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

final class CreateBookRequestTest extends TestCase
{
    private function validate(array $data): \Illuminate\Validation\Validator
    {
        $request = new CreateBookRequest();

        return Validator::make($data, $request->rules());
    }

    /** @test */
    public function 正常な入力でバリデーションが通る(): void
    {
        $validator = $this->validate([
            'title' => 'テスト書籍',
            'author' => 'テスト著者',
            'isbn' => '9784123456789',
            'category_id' => '01HGXW1234567890ABCDEFGH',
        ]);

        $this->assertTrue($validator->passes());
    }

    /** @test */
    public function タイトルは必須(): void
    {
        $validator = $this->validate([
            'author' => 'テスト著者',
            'category_id' => '01HGXW1234567890ABCDEFGH',
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('title', $validator->errors()->toArray());
    }

    /** @test */
    public function ISBNは13桁である必要がある(): void
    {
        $validator = $this->validate([
            'title' => 'テスト書籍',
            'author' => 'テスト著者',
            'isbn' => '123456789',  // 9桁
            'category_id' => '01HGXW1234567890ABCDEFGH',
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('isbn', $validator->errors()->toArray());
    }
}
```

### ValueObject のテスト

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Book;

use InvalidArgumentException;
use Packages\Domain\Book\Domain\Model\Isbn;
use PHPUnit\Framework\TestCase;

final class IsbnTest extends TestCase
{
    /** @test */
    public function 正常なISBNで生成できる(): void
    {
        $isbn = Isbn::fromString('9784123456780');

        $this->assertSame('9784123456780', $isbn->value());
    }

    /** @test */
    public function ハイフン付きISBNを正規化する(): void
    {
        $isbn = Isbn::fromString('978-4-12-345678-0');

        $this->assertSame('9784123456780', $isbn->value());
    }

    /** @test */
    public function 13桁でない場合は例外(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Isbn::fromString('123456789');
    }

    /** @test */
    public function チェックディジットが不正な場合は例外(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Isbn::fromString('9784123456789');  // チェックディジット不正
    }

    /** @test */
    public function フォーマット済みで出力できる(): void
    {
        $isbn = Isbn::fromString('9784123456780');

        $this->assertSame('978-4-1234-5678-0', $isbn->formatted());
    }
}
```

---

## チェックリスト

### FormRequest

- [ ] すべての入力フィールドにルールが定義されているか
- [ ] 必須/任意が明確に定義されているか
- [ ] 適切な型チェックが行われているか
- [ ] 文字列長・数値範囲のチェックがあるか
- [ ] 外部キーの存在確認（exists）があるか
- [ ] 属性名が日本語で定義されているか
- [ ] カスタムエラーメッセージが必要な場合に定義されているか

### ValueObject

- [ ] 不変条件がコンストラクタで検証されているか
- [ ] ビジネスルールが適切に実装されているか
- [ ] 例外メッセージがユーザーフレンドリーか
- [ ] equals メソッドが実装されているか
- [ ] 単体テストが書かれているか

### エラーメッセージ

- [ ] 日本語で記述されているか
- [ ] ユーザーが理解できる表現か
- [ ] 何が問題か明確か
- [ ] どうすればよいか示されているか

### セキュリティ

- [ ] HTML タグ・スクリプトが適切にブロックされているか
- [ ] パスワード強度のチェックがあるか
- [ ] 機密情報がログに出力されていないか

---

## 関連ドキュメント

- [01_ArchitectureDesign](./01_ArchitectureDesign/) - アーキテクチャ設計標準
- [02_CodingStandards.md](./02_CodingStandards.md) - コーディング規約
- [03_SecurityDesign.md](./03_SecurityDesign.md) - セキュリティ設計
- [07_ErrorHandling.md](./07_ErrorHandling.md) - エラーハンドリング設計

---

## 変更履歴

| 日付 | 変更内容 |
|------|---------|
| 2025-12-25 | 初版作成 |
