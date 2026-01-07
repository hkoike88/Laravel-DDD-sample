# Data Model: 開発環境動作確認

**Feature**: [spec.md](./spec.md)
**Date**: 2025-12-24
**Status**: N/A

## Overview

このフィーチャーは開発環境の動作検証を行うものであり、新規のデータモデルやエンティティの作成は含まれません。

## 既存のデータモデル

このフィーチャーでは、以下の既存リソースを検証のために使用します：

### ヘルスチェック API レスポンス

既存の `HealthController` が返す JSON レスポンス形式：

#### 基本ヘルスチェック (`GET /api/health`)

```typescript
interface HealthResponse {
  status: 'ok';
  timestamp: string; // ISO 8601 形式
  laravel_version: string;
}
```

#### データベースヘルスチェック (`GET /api/health/db`)

```typescript
// 正常時
interface DatabaseHealthOkResponse {
  status: 'ok';
  connection: string;
  database: string;
}

// エラー時
interface DatabaseHealthErrorResponse {
  status: 'error';
  message: string;
}

type DatabaseHealthResponse = DatabaseHealthOkResponse | DatabaseHealthErrorResponse;
```

## 備考

- 新規テーブル作成: なし
- 新規エンティティ作成: なし
- スキーマ変更: なし

このフィーチャーの目的は既存環境の検証であり、データモデルの変更は範囲外です。
