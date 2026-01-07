# ADR-0005: 認証方式（Laravel Sanctum）

## ステータス

採用

## コンテキスト

SPA + REST API 構成において、以下の認証要件を満たす必要がある。

- セキュアな認証・セッション管理
- CSRF 対策
- SPA からの API 呼び出し
- シンプルな実装

## 決定

**Laravel Sanctum 4.0** を採用し、**Cookie ベースのセッション認証** を使用する。

### 採用理由

1. **SPA 認証に特化**
   - Laravel 公式の SPA 認証ソリューション
   - Cookie + Session による認証が標準サポート

2. **CSRF 対策が組み込み**
   - XSRF-TOKEN Cookie による自動的な CSRF 保護
   - 追加設定なしでセキュアな通信

3. **シンプルな実装**
   - JWT のようなトークン管理が不要
   - ステートレス vs ステートフルの議論を回避

4. **Laravel との統合**
   - 認証ミドルウェア、Gate/Policy との連携がスムーズ
   - 既存の auth 機能をそのまま利用可能

5. **セキュリティ**
   - HttpOnly Cookie でトークン盗難を防止
   - セッション固定攻撃対策が組み込み

## 比較検討

| 項目 | Sanctum (Cookie) | Sanctum (Token) | JWT (tymon/jwt-auth) | Passport |
|------|------------------|-----------------|---------------------|----------|
| SPA 向け | ◎ | ○ | ○ | △ |
| セキュリティ | ◎ | ○ | △ | ○ |
| 実装の簡単さ | ◎ | ◎ | ○ | △ |
| ステートレス | × | ○ | ◎ | ○ |
| CSRF 対策 | 自動 | 不要 | 不要 | 不要 |
| 学習コスト | 低 | 低 | 中 | 高 |

### 認証方式の比較

| 方式 | Cookie + Session | Bearer Token (JWT) |
|------|-----------------|-------------------|
| 格納場所 | HttpOnly Cookie | localStorage / Memory |
| XSS 耐性 | ◎（JS からアクセス不可） | △（localStorage は危険） |
| CSRF 耐性 | △（対策必要、Sanctum は自動） | ◎（Cookie を使わない） |
| ステートレス | × | ◎ |
| サーバー負荷 | セッション管理が必要 | 検証のみ |

### 不採用理由

- **JWT (tymon/jwt-auth)**: トークンの安全な保管場所がない。リフレッシュトークンの管理が複雑
- **Passport**: OAuth2 サーバーが必要な場合向け。本プロジェクトにはオーバースペック
- **Sanctum Token**: モバイルアプリや外部 API 向け。SPA では Cookie の方がセキュア

## 結果

### メリット

- HttpOnly Cookie により XSS によるトークン盗難を防止
- CSRF 対策が自動的に適用される
- セッション管理は Laravel 標準の仕組みを利用

### デメリット

- ステートレスではないため、スケールアウト時にセッション共有が必要
- 同一ドメイン（またはサブドメイン）での運用が前提

### リスクと対策

| リスク | 対策 |
|--------|------|
| セッションハイジャック | HTTPS 必須、Secure Cookie 設定 |
| 複数サーバー運用 | Redis セッションドライバーを使用 |
| CORS 設定ミス | 本番環境で許可ドメインを厳密に設定 |

## 実装方針

### バックエンド設定

```php
// config/cors.php
return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_origins' => [env('FRONTEND_URL', 'http://localhost:5173')],
    'supports_credentials' => true,
];

// config/sanctum.php
return [
    'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', 'localhost')),
];
```

### フロントエンド設定

```typescript
// lib/api/client.ts
const apiClient = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL,
  withCredentials: true, // Cookie を送信
});

// 初期化時に CSRF Cookie を取得
await apiClient.get('/sanctum/csrf-cookie');
```

### 認証フロー

```
1. GET /sanctum/csrf-cookie  → XSRF-TOKEN Cookie 取得
2. POST /api/login           → セッション Cookie 取得
3. GET /api/user             → 認証済みユーザー情報取得
4. POST /api/logout          → セッション破棄
```

## 参考資料

- [Laravel Sanctum 公式ドキュメント](https://laravel.com/docs/sanctum)
- [バックエンド セキュリティ設計](../../99_standard/backend/03_SecurityDesign.md)
- [フロントエンド セキュリティ設計](../../99_standard/frontend/03_SecurityDesign.md)
