# パスワードポリシー

## 概要

本ドキュメントは、システムにおけるパスワード管理に関するセキュリティポリシーを定義する。
NIST SP 800-63B および OWASP Authentication Cheat Sheet に準拠した設計とする。

**Last updated:** 2025-12-26

---

## 目次

- [適用範囲](#適用範囲)
- [パスワード構成要件](#パスワード構成要件)
- [パスワード変更ポリシー](#パスワード変更ポリシー)
- [パスワード履歴](#パスワード履歴)
- [アカウントロックアウト](#アカウントロックアウト)
- [パスワードの保存](#パスワードの保存)
- [パスワードリセット](#パスワードリセット)
- [ユーザーガイダンス](#ユーザーガイダンス)
- [実装ガイドライン](#実装ガイドライン)
- [監査・ログ](#監査ログ)
- [参考文献](#参考文献)

---

## 適用範囲

本ポリシーは以下のアカウントに適用される：

| アカウント種別 | 適用レベル | 備考 |
|---------------|-----------|------|
| スタッフアカウント | 必須 | 管理画面へのアクセス権を持つ |
| 管理者アカウント | 必須 + MFA | システム管理権限を持つ |
| 外部連携アカウント | 個別設定 | API キー等は別途管理 |

---

## パスワード構成要件

### 必須要件

| 項目 | 要件 | 根拠 |
|------|------|------|
| 最小文字数 | **12文字以上** | NIST SP 800-63B 推奨 |
| 最大文字数 | 128文字 | 過度な制限は避ける |
| 英大文字 | 1文字以上必須 | 複雑性向上 |
| 英小文字 | 1文字以上必須 | 複雑性向上 |
| 数字 | 1文字以上必須 | 複雑性向上 |
| 記号 | 1文字以上必須 | 複雑性向上 |

### 許可される記号

以下の記号を使用可能とする：

```
! @ # $ % ^ & * ( ) - _ = + [ ] { } | ; : ' " , . < > / ? ` ~
```

### 禁止事項

| 禁止項目 | 説明 |
|---------|------|
| 連続する同一文字 | 3文字以上の連続禁止（例: `aaa`, `111`） |
| 連続する順序文字 | 3文字以上の連続禁止（例: `abc`, `123`, `qwerty`） |
| ユーザー情報の使用 | メールアドレス、名前の一部を含めない |
| 一般的なパスワード | 漏洩パスワードリストに含まれるものを禁止 |
| 過去のパスワード | 直近5世代のパスワードを再利用禁止 |

### 漏洩パスワードチェック

Have I Been Pwned API を使用して、過去に漏洩したパスワードかどうかを検証する：

```php
// Laravel の Password::uncompromised() を使用
Password::min(12)
    ->letters()
    ->mixedCase()
    ->numbers()
    ->symbols()
    ->uncompromised();  // 漏洩パスワードチェック
```

---

## パスワード変更ポリシー

### 定期変更

NIST SP 800-63B の推奨に従い、**定期的な強制変更は行わない**。

> 定期的なパスワード変更の強制は、ユーザーに弱いパスワードや予測可能なパターン
> （例: `Password1!`, `Password2!`）を使用させる傾向がある。

### 変更が必要なケース

以下の場合はパスワード変更を必須とする：

| ケース | 対応 |
|-------|------|
| アカウント侵害の疑い | 即時変更を強制 |
| パスワード漏洩の検知 | 次回ログイン時に変更を要求 |
| 管理者による強制リセット | 次回ログイン時に変更を要求 |
| 長期間未使用（180日以上） | 再認証時に変更を推奨 |

### 変更時の要件

- 現在のパスワードの確認が必須
- 新しいパスワードは構成要件を満たすこと
- 過去5世代のパスワードと異なること

---

## パスワード履歴

### 履歴管理

| 項目 | 設定値 |
|------|--------|
| 保持世代数 | 5世代 |
| 保存形式 | ハッシュ化した状態で保存 |
| 比較方法 | ハッシュ比較 |

### 実装

```php
// パスワード履歴テーブル
Schema::create('password_histories', function (Blueprint $table) {
    $table->id();
    $table->foreignId('staff_id')->constrained()->onDelete('cascade');
    $table->string('password_hash');
    $table->timestamp('created_at');

    $table->index(['staff_id', 'created_at']);
});
```

---

## アカウントロックアウト

### ロックアウト設定

| 項目 | 設定値 | 備考 |
|------|--------|------|
| 最大試行回数 | 5回 | 連続した失敗回数 |
| ロック時間 | 15分 | 自動解除までの時間 |
| 試行回数リセット | ログイン成功時 | 成功でカウンタをリセット |
| 永久ロック閾値 | 累計20回 | 管理者による解除が必要 |

### 段階的ロックアウト

```
試行回数  |  対応
---------|------------------
1-4回    |  警告メッセージを表示
5回      |  15分間ロックアウト
6-9回    |  15分間ロックアウト（継続）
10回     |  30分間ロックアウト
15回     |  60分間ロックアウト
20回以上 |  永久ロック（管理者対応）
```

### ロック解除方法

| 解除方法 | 対象 |
|---------|------|
| 自動解除 | ロック時間経過後 |
| パスワードリセット | ユーザー自身で実施 |
| 管理者による解除 | 永久ロック時 |

---

## パスワードの保存

### ハッシュアルゴリズム

| 項目 | 設定 |
|------|------|
| アルゴリズム | bcrypt |
| コスト係数 | 12（Laravel デフォルト: 10） |
| 代替アルゴリズム | Argon2id（将来的に移行検討） |

### Laravel での設定

```php
// config/hashing.php
'bcrypt' => [
    'rounds' => 12,  // コスト係数
],

// または Argon2id を使用
'driver' => 'argon2id',
'argon' => [
    'memory' => 65536,   // 64MB
    'threads' => 4,
    'time' => 4,
],
```

### 禁止事項

- 平文でのパスワード保存は**厳禁**
- 可逆暗号化（AES等）での保存は**禁止**
- MD5、SHA-1 などの非推奨アルゴリズムは**禁止**
- ソルトなしのハッシュは**禁止**

---

## パスワードリセット

### リセットフロー

```
1. ユーザーがリセットを要求
   ↓
2. 登録メールアドレスへリセットリンクを送信
   ↓
3. ユーザーがリンクをクリック
   ↓
4. トークン検証（有効期限・使用済みチェック）
   ↓
5. 新しいパスワードを設定
   ↓
6. 全デバイスのセッションを無効化
   ↓
7. リセット完了通知をメールで送信
```

### リセットトークン

| 項目 | 設定値 |
|------|--------|
| トークン長 | 64文字（暗号学的に安全な乱数） |
| 有効期限 | 60分 |
| 使用回数 | 1回のみ（使用後は即時無効化） |
| 保存形式 | ハッシュ化して保存 |

### セキュリティ考慮事項

- メールアドレスの存在を漏らさない（同一メッセージを表示）
- リセットリンクは HTTPS のみで送信
- リセット後は全デバイスからログアウト
- リセット要求は1時間に3回まで（レート制限）

---

## ユーザーガイダンス

### パスワード強度インジケーター

ユーザーがパスワードを入力する際に、リアルタイムで強度を表示する：

| 強度 | 条件 | 表示 |
|------|------|------|
| 弱 | 基本要件を満たさない | 🔴 弱い |
| 中 | 基本要件を満たす | 🟡 普通 |
| 強 | 16文字以上 + 全要件 | 🟢 強い |
| 非常に強 | 20文字以上 + 全要件 + 辞書語なし | 🟢 非常に強い |

### ユーザーへの推奨事項

1. **パスフレーズの使用を推奨**
   - 覚えやすく長いフレーズを使用（例: `MyDog$Loves2RunInThePark!`）

2. **パスワードマネージャーの利用を推奨**
   - 1Password、Bitwarden などの信頼できるツール

3. **他サービスとのパスワード使い回し禁止**
   - 各サービスで固有のパスワードを使用

4. **二要素認証（2FA）の有効化を推奨**
   - 管理者アカウントでは必須

### 禁止事項（ユーザー向け）

- パスワードの共有禁止
- パスワードのメモ書き禁止（安全な場所を除く）
- 公共の場でのパスワード入力時は周囲に注意
- 不審なメールのリンクからのパスワード入力禁止

---

## 実装ガイドライン

### バリデーションルール

```php
// app/Providers/AuthServiceProvider.php
use Illuminate\Validation\Rules\Password;

public function boot(): void
{
    // デフォルトのパスワードルールを設定
    Password::defaults(function () {
        return Password::min(12)
            ->letters()
            ->mixedCase()
            ->numbers()
            ->symbols()
            ->uncompromised();  // 漏洩パスワードチェック
    });
}
```

### フォームリクエストでの使用

```php
// app/Http/Requests/Auth/RegisterRequest.php
use Illuminate\Validation\Rules\Password;

public function rules(): array
{
    return [
        'email' => ['required', 'string', 'email', 'max:255', 'unique:staffs'],
        'password' => ['required', 'confirmed', Password::defaults()],
    ];
}

public function messages(): array
{
    return [
        'password.min' => 'パスワードは12文字以上で入力してください',
        'password.letters' => 'パスワードには英字を含めてください',
        'password.mixed' => 'パスワードには大文字と小文字の両方を含めてください',
        'password.numbers' => 'パスワードには数字を含めてください',
        'password.symbols' => 'パスワードには記号を含めてください',
        'password.uncompromised' => 'このパスワードは過去に漏洩が確認されています。別のパスワードを使用してください',
    ];
}
```

### パスワード履歴チェック

```php
// app/Services/PasswordHistoryService.php
final class PasswordHistoryService
{
    private const HISTORY_LIMIT = 5;

    public function isPasswordReused(Staff $staff, string $newPassword): bool
    {
        $histories = PasswordHistory::where('staff_id', $staff->id)
            ->orderBy('created_at', 'desc')
            ->limit(self::HISTORY_LIMIT)
            ->get();

        foreach ($histories as $history) {
            if (Hash::check($newPassword, $history->password_hash)) {
                return true;
            }
        }

        return false;
    }

    public function addToHistory(Staff $staff, string $hashedPassword): void
    {
        PasswordHistory::create([
            'staff_id' => $staff->id,
            'password_hash' => $hashedPassword,
        ]);

        // 古い履歴を削除
        PasswordHistory::where('staff_id', $staff->id)
            ->orderBy('created_at', 'desc')
            ->skip(self::HISTORY_LIMIT)
            ->take(PHP_INT_MAX)
            ->delete();
    }
}
```

### フロントエンド実装

```typescript
// パスワード強度チェック
interface PasswordStrength {
  score: 0 | 1 | 2 | 3 | 4;
  label: '弱い' | '普通' | '強い' | '非常に強い';
  feedback: string[];
}

function checkPasswordStrength(password: string): PasswordStrength {
  const feedback: string[] = [];
  let score = 0;

  if (password.length >= 12) score++;
  else feedback.push('12文字以上にしてください');

  if (/[a-z]/.test(password) && /[A-Z]/.test(password)) score++;
  else feedback.push('大文字と小文字を含めてください');

  if (/\d/.test(password)) score++;
  else feedback.push('数字を含めてください');

  if (/[!@#$%^&*(),.?":{}|<>]/.test(password)) score++;
  else feedback.push('記号を含めてください');

  const labels: Record<number, PasswordStrength['label']> = {
    0: '弱い',
    1: '弱い',
    2: '普通',
    3: '強い',
    4: '非常に強い',
  };

  return {
    score: Math.min(score, 4) as PasswordStrength['score'],
    label: labels[Math.min(score, 4)],
    feedback,
  };
}
```

---

## 監査・ログ

### ログ記録対象イベント

| イベント | ログレベル | 記録項目 |
|---------|-----------|---------|
| ログイン成功 | INFO | ユーザーID, IP, UserAgent, タイムスタンプ |
| ログイン失敗 | WARNING | メールアドレス, IP, 失敗理由, タイムスタンプ |
| アカウントロック | WARNING | ユーザーID, IP, ロック理由, タイムスタンプ |
| パスワード変更 | INFO | ユーザーID, IP, タイムスタンプ |
| パスワードリセット要求 | INFO | メールアドレス, IP, タイムスタンプ |
| パスワードリセット完了 | INFO | ユーザーID, IP, タイムスタンプ |

### ログ出力例

```php
// 認証関連のログ
Log::channel('security')->info('Password changed successfully', [
    'staff_id' => $staff->id,
    'ip_address' => request()->ip(),
    'user_agent' => request()->userAgent(),
    'timestamp' => now()->toIso8601String(),
]);

Log::channel('security')->warning('Account locked due to failed attempts', [
    'staff_id' => $staff->id,
    'failed_attempts' => $staff->failed_login_attempts,
    'ip_address' => request()->ip(),
    'timestamp' => now()->toIso8601String(),
]);
```

### ログ保持期間

| ログ種別 | 保持期間 |
|---------|---------|
| 認証成功ログ | 90日 |
| 認証失敗ログ | 180日 |
| パスワード変更ログ | 1年 |
| アカウントロックログ | 1年 |

---

## 参考文献

### 公式ガイドライン

- [NIST SP 800-63B: Digital Identity Guidelines - Authentication and Lifecycle Management](https://pages.nist.gov/800-63-3/sp800-63b.html)
- [OWASP Authentication Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Authentication_Cheat_Sheet.html)
- [OWASP Password Storage Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Password_Storage_Cheat_Sheet.html)

### Laravel 公式ドキュメント

- [Laravel Validation - Password Rules](https://laravel.com/docs/validation#validating-passwords)
- [Laravel Hashing](https://laravel.com/docs/hashing)
- [Laravel Rate Limiting](https://laravel.com/docs/rate-limiting)

### 関連ドキュメント

- [02_SecurityDesign.md](../backend/02_SecurityDesign.md) - バックエンドセキュリティ設計標準
- [08_SecurityScanning.md](./08_SecurityScanning.md) - セキュリティスキャンガイド

---

## 改訂履歴

| バージョン | 日付 | 変更内容 | 担当者 |
|-----------|------|---------|-------|
| 1.0.0 | 2025-12-26 | 初版作成 | - |
