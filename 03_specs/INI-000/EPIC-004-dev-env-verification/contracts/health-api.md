# API Contract: Health Check

**Feature**: [spec.md](../spec.md)
**Date**: 2025-12-24

## Overview

開発環境の動作確認に使用するヘルスチェック API のコントラクト定義。

## Endpoints

### 1. 基本ヘルスチェック

**Endpoint**: `GET /api/health`

**Description**: バックエンドサービスの稼働状態を確認

**Request**: なし

**Response**:

| Status | Description |
|--------|-------------|
| 200 | サービス正常稼働 |

**Response Body** (200):
```json
{
  "status": "ok",
  "timestamp": "2025-12-24T12:00:00+09:00",
  "laravel_version": "12.0.0"
}
```

**Response Schema**:
```typescript
interface HealthResponse {
  status: 'ok';
  timestamp: string;      // ISO 8601 形式
  laravel_version: string;
}
```

---

### 2. データベースヘルスチェック

**Endpoint**: `GET /api/health/db`

**Description**: MySQL データベースへの接続状態を確認

**Request**: なし

**Response**:

| Status | Description |
|--------|-------------|
| 200 | データベース接続正常 |
| 503 | データベース接続失敗 |

**Response Body** (200):
```json
{
  "status": "ok",
  "connection": "mysql",
  "database": "library"
}
```

**Response Body** (503):
```json
{
  "status": "error",
  "message": "Database connection failed"
}
```

**Response Schema**:
```typescript
// 正常時
interface DatabaseHealthOkResponse {
  status: 'ok';
  connection: string;  // 接続ドライバ名
  database: string;    // データベース名
}

// エラー時
interface DatabaseHealthErrorResponse {
  status: 'error';
  message: string;
}

type DatabaseHealthResponse = DatabaseHealthOkResponse | DatabaseHealthErrorResponse;
```

---

## 使用例

### curl

```bash
# 基本ヘルスチェック
curl http://localhost:80/api/health

# データベースヘルスチェック
curl http://localhost:80/api/health/db
```

### JavaScript (fetch)

```javascript
// 基本ヘルスチェック
const response = await fetch('http://localhost/api/health');
const data = await response.json();
console.log(data.status); // 'ok'

// データベースヘルスチェック
const dbResponse = await fetch('http://localhost/api/health/db');
const dbData = await dbResponse.json();
if (dbData.status === 'ok') {
  console.log(`Connected to ${dbData.database}`);
}
```

## 備考

- これらのエンドポイントは認証不要
- CORS は全オリジンから許可（開発環境）
- Nginx 経由でアクセス（/api/* → backend:9000）
