# ST-004: Pre-commit フックの設定

最終更新: 2025-12-23

---

## ストーリー

**開発者として**、コミット前に自動的にコード品質をチェックしたい。
**なぜなら**、CI で失敗する前にローカルで問題を発見し、修正したいからだ。

---

## 関連情報

| 項目 | 値 |
|------|-----|
| Epic | [EPIC-005: CI/CD 環境構築](../epic.md) |
| ポイント | 2 |
| 優先度 | Should |
| ステータス | Draft |

---

## 受け入れ条件

1. [ ] pre-commit フックが設定されていること
2. [ ] ステージングされた PHP ファイルに対して PHPStan が実行されること
3. [ ] ステージングされた PHP ファイルに対して Pint が実行されること
4. [ ] ステージングされた TS/TSX ファイルに対して ESLint が実行されること
5. [ ] ステージングされた TS/TSX ファイルに対して Prettier が実行されること
6. [ ] チェックが失敗した場合、コミットが中止されること
7. [ ] フック設定用のセットアップスクリプトが提供されること

---

## 技術仕様

### Pre-commit フックスクリプト

```bash
#!/bin/bash
# .git/hooks/pre-commit

set -e

echo "Running pre-commit checks..."

# ステージングされたファイルを取得
STAGED_PHP_FILES=$(git diff --cached --name-only --diff-filter=ACM | grep '\.php$' || true)
STAGED_TS_FILES=$(git diff --cached --name-only --diff-filter=ACM | grep -E '\.(ts|tsx)$' || true)

# バックエンドチェック（PHP ファイルがある場合）
if [ -n "$STAGED_PHP_FILES" ]; then
    echo "Checking PHP files..."

    # PHPStan
    echo "Running PHPStan..."
    cd backend
    ./vendor/bin/phpstan analyse --memory-limit=256M --no-progress $STAGED_PHP_FILES

    # Pint
    echo "Running Pint..."
    ./vendor/bin/pint --test $STAGED_PHP_FILES
    cd ..
fi

# フロントエンドチェック（TS/TSX ファイルがある場合）
if [ -n "$STAGED_TS_FILES" ]; then
    echo "Checking TypeScript files..."

    cd frontend

    # ESLint
    echo "Running ESLint..."
    npx eslint $STAGED_TS_FILES

    # Prettier
    echo "Running Prettier..."
    npx prettier --check $STAGED_TS_FILES

    cd ..
fi

echo "Pre-commit checks passed!"
```

### セットアップスクリプト

```bash
#!/bin/bash
# scripts/setup-hooks.sh

set -e

echo "Setting up Git hooks..."

# pre-commit フックをコピー
cp scripts/pre-commit .git/hooks/pre-commit

# 実行権限を付与
chmod +x .git/hooks/pre-commit

echo "Git hooks have been set up successfully!"
echo "Pre-commit hook will run PHPStan, Pint, ESLint, and Prettier on staged files."
```

### 実行フロー

```
git commit
    │
    ├── ステージングファイル取得
    │
    ├── PHP ファイルあり?
    │   └── Yes ──→ PHPStan + Pint
    │
    ├── TS/TSX ファイルあり?
    │   └── Yes ──→ ESLint + Prettier
    │
    └── 全チェック成功?
        ├── Yes ──→ コミット実行
        └── No ──→ コミット中止
```

### チェック内容

| ファイル種別 | チェック内容 | ツール |
|-------------|-------------|--------|
| *.php | 静的解析 | PHPStan |
| *.php | コードスタイル | Pint |
| *.ts, *.tsx | リント | ESLint |
| *.ts, *.tsx | フォーマット | Prettier |

---

## 成果物

| 成果物 | 配置場所 |
|--------|---------|
| pre-commit フック | scripts/pre-commit |
| セットアップスクリプト | scripts/setup-hooks.sh |

---

## タスク

### Design Tasks（外部設計）

- [ ] チェック対象ファイルの決定
- [ ] チェック内容の決定
- [ ] エラー時の挙動決定

### Spec Tasks（詳細設計）

- [ ] pre-commit スクリプトの作成
- [ ] setup-hooks.sh スクリプトの作成
- [ ] README への設定手順追記
- [ ] 動作確認

---

## 使用方法

### 初回セットアップ

```bash
# セットアップスクリプトを実行
./scripts/setup-hooks.sh

# または手動でコピー
cp scripts/pre-commit .git/hooks/pre-commit
chmod +x .git/hooks/pre-commit
```

### フックのスキップ（緊急時のみ）

```bash
# pre-commit フックをスキップ
git commit --no-verify -m "緊急修正"
```

---

## トラブルシューティング

| 問題 | 原因 | 解決策 |
|------|------|--------|
| Permission denied | 実行権限なし | `chmod +x .git/hooks/pre-commit` |
| PHPStan not found | パス不正 | backend ディレクトリで実行確認 |
| ESLint not found | node_modules 未インストール | `npm ci` を実行 |
| 全ファイルがチェックされる | ステージング取得失敗 | git diff コマンド確認 |

---

## 変更履歴

| 日付 | 変更内容 |
|------|---------|
| 2025-12-23 | 初版作成 |
