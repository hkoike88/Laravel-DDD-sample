# Claude によるプルリクエスト自動レビュー設定

## 概要

main ブランチへのプルリクエスト作成時に、Claude が自動的にコードレビューを実行します。

## セットアップ手順

### 1. Anthropic API キーの取得

1. [Anthropic Console](https://console.anthropic.com/) にアクセス
2. API Keys セクションで新しい API キーを作成
3. キーをコピー（一度しか表示されません）

### 2. GitHub Secrets の設定

1. GitHub リポジトリの **Settings** → **Secrets and variables** → **Actions** に移動
2. **New repository secret** をクリック
3. 以下の Secret を追加:
   - Name: `ANTHROPIC_API_KEY`
   - Secret: (コピーした API キー)

### 3. ワークフローの動作確認

1. feature ブランチを作成:
   ```bash
   git checkout -b feature/test
   ```

2. 何か変更を加えてコミット:
   ```bash
   git add .
   git commit -m "test"
   git push origin feature/test
   ```

3. GitHub で main ブランチへのプルリクエストを作成

4. しばらくすると Claude がコメントを投稿します

## レビュー内容

Claude は以下の観点でコードをレビューします：

### 1. コーディング規約
- PSR-12（PHP）
- TypeScript/React のベストプラクティス

### 2. セキュリティ
- SQL インジェクション
- XSS、CSRF
- 認証・認可

### 3. アーキテクチャ
- DDD 原則
- レイヤー分離
- 依存関係

### 4. パフォーマンス
- N+1 問題
- 不要なクエリ
- メモリリーク

### 5. テスト
- テストカバレッジ
- エッジケース

### 6. ドキュメント
- コメント
- README
- 型定義

## レビュー出力フォーマット

```markdown
## 🤖 Claude によるコードレビュー

### 📊 総合評価
- 承認推奨度: ✅ 承認推奨 / ⚠️ 修正提案あり / ❌ 要修正

### ✅ 良い点
- [具体的な良い点]

### ⚠️ 改善提案
- [ファイル名:行番号] 改善提案

### ❌ 重大な問題
- [ファイル名:行番号] 問題点

### 📝 その他
- [その他の気づき]
```

## 制限事項

### 差分サイズの制限
- 差分が 50,000 文字を超える場合、ファイルリストのみが表示されます
- 大きな変更は複数のプルリクエストに分割することを推奨します

### API 使用料
- Claude API は従量課金制です
- モデル: `claude-3-5-sonnet-20241022`
- 最大トークン数: 4096

### レビューの制約
- Claude はコンテキストに基づいてレビューしますが、完全ではありません
- 人間によるレビューも必ず実施してください
- Claude のレビューは参考として活用してください

## トラブルシューティング

### レビューコメントが投稿されない

1. **API キーの確認**
   - GitHub Secrets に `ANTHROPIC_API_KEY` が正しく設定されているか確認

2. **ワークフローの確認**
   - Actions タブでワークフローの実行ログを確認
   - エラーメッセージがある場合は内容を確認

3. **権限の確認**
   - ワークフローに `pull-requests: write` 権限があるか確認

### API エラー

- **401 Unauthorized**: API キーが無効
- **429 Too Many Requests**: レート制限に達しています
- **500 Internal Server Error**: Anthropic API の問題

## カスタマイズ

### レビュー観点の変更

`.github/workflows/pr-review.yml` の `## レビュー観点` セクションを編集してください。

### モデルの変更

別の Claude モデルを使用する場合、`model` パラメータを変更してください：

```yaml
"model": "claude-3-5-sonnet-20241022",  # 最新の Sonnet
# または
"model": "claude-3-opus-20240229",      # より高精度
```

### 最大トークン数の変更

より詳細なレビューが必要な場合、`max_tokens` を増やしてください：

```yaml
"max_tokens": 8192,  # より長いレビュー
```

## 参考リンク

- [Anthropic API Documentation](https://docs.anthropic.com/claude/reference/getting-started-with-the-api)
- [GitHub Actions Documentation](https://docs.github.com/en/actions)
- [GitHub Script Action](https://github.com/actions/github-script)
