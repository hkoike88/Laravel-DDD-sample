# ST-005: CI/CD 動作確認

最終更新: 2025-12-23

---

## ストーリー

**開発者として**、CI パイプラインと pre-commit フックが正常に動作することを確認したい。
**なぜなら**、品質チェックの仕組みが意図通りに機能することを保証したいからだ。

---

## 関連情報

| 項目 | 値 |
|------|-----|
| Epic | [EPIC-005: CI/CD 環境構築](../epic.md) |
| ポイント | 1 |
| 優先度 | Must |
| ステータス | Draft |

---

## 受け入れ条件

1. [ ] GitHub Actions CI がプッシュ時に実行されること
2. [ ] GitHub Actions CI が PR 作成時に実行されること
3. [ ] Backend ジョブが成功すること
4. [ ] Frontend ジョブが成功すること
5. [ ] pre-commit フックがコミット時に実行されること
6. [ ] 意図的なエラーで CI が失敗することを確認
7. [ ] CI 結果が GitHub の PR 画面に表示されること

---

## 確認手順

### 1. GitHub Actions CI の確認

#### 1.1 プッシュトリガーの確認

```bash
# 新しいブランチを作成
git checkout -b test/ci-verification

# 軽微な変更を加える（例: コメント追加）
echo "// CI verification test" >> backend/app/Http/Controllers/Controller.php

# コミット & プッシュ
git add .
git commit -m "test: CI 動作確認"
git push origin test/ci-verification
```

GitHub の Actions タブで CI が実行されることを確認。

#### 1.2 PR トリガーの確認

1. GitHub で PR を作成
2. CI が自動実行されることを確認
3. PR 画面に CI 結果が表示されることを確認

### 2. Backend ジョブの確認

| 確認項目 | 期待結果 |
|----------|---------|
| PHP セットアップ | 8.3 がインストールされる |
| MySQL サービス | 起動・接続成功 |
| Composer キャッシュ | 2回目以降でヒット |
| PHPStan | エラーなしで完了 |
| Pint | エラーなしで完了 |
| Pest | 全テスト成功 |

### 3. Frontend ジョブの確認

| 確認項目 | 期待結果 |
|----------|---------|
| Node.js セットアップ | 20 がインストールされる |
| npm キャッシュ | 2回目以降でヒット |
| ESLint | エラーなしで完了 |
| Prettier | エラーなしで完了 |
| TypeScript | エラーなしで完了 |
| Build | 成功 |
| Vitest | 全テスト成功 |

### 4. Pre-commit フックの確認

```bash
# フックのセットアップ
./scripts/setup-hooks.sh

# PHP ファイルを変更してコミット
echo "// test" >> backend/app/Http/Controllers/Controller.php
git add backend/app/Http/Controllers/Controller.php
git commit -m "test: pre-commit hook"
# → PHPStan と Pint が実行されることを確認

# TS ファイルを変更してコミット
echo "// test" >> frontend/src/main.tsx
git add frontend/src/main.tsx
git commit -m "test: pre-commit hook"
# → ESLint と Prettier が実行されることを確認
```

### 5. 失敗ケースの確認

#### 5.1 PHPStan エラー

```php
// 意図的に型エラーを発生させる
function test(): string {
    return 123; // 型不一致
}
```

CI が失敗することを確認。

#### 5.2 ESLint エラー

```typescript
// 意図的にリントエラーを発生させる
const unusedVar = "test"; // 未使用変数
```

CI が失敗することを確認。

---

## 確認チェックリスト

### GitHub Actions CI

| 確認項目 | 確認 |
|----------|------|
| push トリガー動作 | [ ] |
| pull_request トリガー動作 | [ ] |
| Backend ジョブ成功 | [ ] |
| Frontend ジョブ成功 | [ ] |
| キャッシュ動作 | [ ] |
| PR 画面への結果表示 | [ ] |

### Pre-commit フック

| 確認項目 | 確認 |
|----------|------|
| セットアップスクリプト動作 | [ ] |
| PHP ファイルチェック | [ ] |
| TS/TSX ファイルチェック | [ ] |
| エラー時のコミット中止 | [ ] |

### 失敗ケース

| 確認項目 | 確認 |
|----------|------|
| PHPStan エラーで CI 失敗 | [ ] |
| Pint エラーで CI 失敗 | [ ] |
| ESLint エラーで CI 失敗 | [ ] |
| TypeScript エラーで CI 失敗 | [ ] |
| テスト失敗で CI 失敗 | [ ] |

---

## 成果物

| 成果物 | 配置場所 |
|--------|---------|
| 確認結果レポート | （ドキュメントまたは口頭報告） |

---

## タスク

### Spec Tasks（詳細設計）

- [ ] CI プッシュトリガーの確認
- [ ] CI PR トリガーの確認
- [ ] Backend ジョブの確認
- [ ] Frontend ジョブの確認
- [ ] pre-commit フックの確認
- [ ] 失敗ケースの確認
- [ ] チェックリストの完了

---

## トラブルシューティング

| 問題 | 原因 | 解決策 |
|------|------|--------|
| CI が実行されない | ブランチ名不一致 | トリガー設定確認 |
| MySQL 接続失敗 | サービス起動遅延 | health check 確認 |
| キャッシュがヒットしない | キーの不一致 | キャッシュキー確認 |
| pre-commit が動作しない | フック未設定 | setup-hooks.sh 再実行 |

---

## 後片付け

```bash
# テストブランチの削除
git checkout develop
git branch -D test/ci-verification
git push origin --delete test/ci-verification

# 変更のリバート（必要に応じて）
git checkout -- backend/app/Http/Controllers/Controller.php
git checkout -- frontend/src/main.tsx
```

---

## 変更履歴

| 日付 | 変更内容 |
|------|---------|
| 2025-12-23 | 初版作成 |
