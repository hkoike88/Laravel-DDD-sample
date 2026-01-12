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
- モデル: `claude-sonnet-4-5-20250929` (Claude 4.5 Sonnet - 2026年1月時点の最新)
- 料金: $3 input / $15 output per million tokens（2026年1月時点）
- 最新の料金: https://www.anthropic.com/pricing
- 最大トークン数: 4096
- クレジット残高が不足している場合、レビューは実行されません

### レビューの制約
- Claude はコンテキストに基づいてレビューしますが、完全ではありません
- 人間によるレビューも必ず実施してください
- Claude のレビューは参考として活用してください

### コスト管理

#### 月額予算の設定
Anthropic Console で以下を設定することを推奨します：

1. **Usage Limits**: 月額上限を設定（例: $50/month）
2. **Notification**: 80%到達時にメール通知
3. **Hard Limit**: 100%到達時に API を自動停止

#### コスト削減策

1. **差分サイズ制限**: 大規模 PR は分割を推奨（現在: 50KB、`.github/workflows/pr-review.yml` の `MAX_DIFF_SIZE` で調整可能）
2. **レビュー対象の絞り込み**: `paths-ignore` で不要なファイルを除外
   ```yaml
   on:
     pull_request:
       branches:
         - main
       paths-ignore:
         - '**.md'
         - 'docs/**'
         - '99_reviews/**'
   ```
3. **手動トリガー**: 緊急時は `.github/workflows/pr-review.yml` の `on` を `workflow_dispatch` に変更

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
"model": "claude-sonnet-4-5-20250929",  # Claude 4.5 Sonnet (推奨・バランス型)
# または
"model": "claude-opus-4-5-20251101",    # Claude 4.5 Opus (最高精度・高コスト)
# または
"model": "claude-haiku-4-5-20251001",   # Claude 4.5 Haiku (最速・最低コスト)
```

**利用可能なモデル (2026年1月時点)**:

#### Claude 4.5 シリーズ (最新)
- `claude-opus-4-5-20251101`: 最高精度、コーディング・エージェントに最適 ($5 input / $25 output)
- `claude-sonnet-4-5-20250929`: バランス型、本番環境推奨 ($3 input / $15 output)
- `claude-haiku-4-5-20251001`: 最速・最低コスト ($1 input / $5 output)

#### 旧モデル (互換性維持)
- `claude-opus-4-1`: 2025年8月版
- `claude-sonnet-4`: 2025年5月版
- `claude-3-5-sonnet-20241022`: Claude 3.5世代

**推奨**: コストと性能のバランスから `claude-sonnet-4-5-20250929` を推奨します。

### 最大トークン数の変更

より詳細なレビューが必要な場合、`max_tokens` を増やしてください：

```yaml
"max_tokens": 8192,  # より長いレビュー
```

## 参考リンク

- [Anthropic API Documentation](https://docs.anthropic.com/claude/reference/getting-started-with-the-api)
- [GitHub Actions Documentation](https://docs.github.com/en/actions)
- [GitHub Script Action](https://github.com/actions/github-script)
