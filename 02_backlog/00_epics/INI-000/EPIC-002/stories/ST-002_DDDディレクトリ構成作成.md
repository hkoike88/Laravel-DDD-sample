# ST-002: DDD ディレクトリ構成の作成

最終更新: 2025-12-23

---

## ストーリー

**開発者として**、DDD（ドメイン駆動設計）に基づいたディレクトリ構成を作成したい。
**なぜなら**、ビジネスロジックを適切に分離し、保守性の高いコードベースを構築したいからだ。

---

## 関連情報

| 項目 | 値 |
|------|-----|
| Epic | [EPIC-002: バックエンド初期設定](../epic.md) |
| ポイント | 2 |
| 優先度 | Must |
| ステータス | Draft |

---

## 受け入れ条件

1. [ ] `app/src/` ディレクトリが作成されていること
2. [ ] 各境界づけられたコンテキスト用ディレクトリが存在すること
3. [ ] Common（共有）ディレクトリが存在すること
4. [ ] 各レイヤー（Domain/Application/Presentation/Infrastructure）が定義されていること
5. [ ] Composer の autoload 設定が更新されていること

---

## 技術仕様

### ディレクトリ構成

```
app/src/
├── BookManagement/              # 書籍管理コンテキスト
│   ├── Domain/
│   │   ├── Models/              # Entity / ValueObject
│   │   ├── Repositories/        # Repository Interface
│   │   ├── Services/            # Domain Service
│   │   └── Exceptions/          # ドメイン例外
│   ├── Application/
│   │   ├── UseCases/            # ユースケース
│   │   ├── DTO/                 # Data Transfer Object
│   │   └── Repositories/        # Repository 実装
│   ├── Presentation/
│   │   └── HTTP/
│   │       ├── Controllers/
│   │       ├── Requests/
│   │       └── Resources/
│   └── Infrastructure/
│       └── EloquentModels/      # Eloquent モデル
├── LoanManagement/              # 貸出管理コンテキスト
│   └── (同様の構成)
├── UserManagement/              # ユーザー管理コンテキスト
│   └── (同様の構成)
└── Common/                      # 共有リソース
    ├── Domain/
    │   ├── ValueObjects/        # 共通 ValueObject
    │   └── Exceptions/          # 共通例外
    └── Infrastructure/
        ├── Persistence/         # 永続化基盤
        └── Services/            # 共通サービス
```

### ディレクトリ作成スクリプト

```bash
#!/bin/bash
# scripts/create-ddd-structure.sh

BASE_DIR="app/src"

# 境界づけられたコンテキスト
CONTEXTS=("BookManagement" "LoanManagement" "UserManagement")

# 各コンテキストのレイヤー
LAYERS=(
    "Domain/Models"
    "Domain/Repositories"
    "Domain/Services"
    "Domain/Exceptions"
    "Application/UseCases"
    "Application/DTO"
    "Application/Repositories"
    "Presentation/HTTP/Controllers"
    "Presentation/HTTP/Requests"
    "Presentation/HTTP/Resources"
    "Infrastructure/EloquentModels"
)

# コンテキストディレクトリ作成
for context in "${CONTEXTS[@]}"; do
    for layer in "${LAYERS[@]}"; do
        mkdir -p "$BASE_DIR/$context/$layer"
        touch "$BASE_DIR/$context/$layer/.gitkeep"
    done
done

# Common ディレクトリ作成
mkdir -p "$BASE_DIR/Common/Domain/ValueObjects"
mkdir -p "$BASE_DIR/Common/Domain/Exceptions"
mkdir -p "$BASE_DIR/Common/Infrastructure/Persistence"
mkdir -p "$BASE_DIR/Common/Infrastructure/Services"

echo "DDD directory structure created successfully!"
```

### Composer autoload 設定

```json
{
    "autoload": {
        "psr-4": {
            "App\\": "app/",
            "Src\\": "app/src/"
        }
    }
}
```

```bash
# autoload 再生成
composer dump-autoload
```

---

## 成果物

| 成果物 | 配置場所 |
|--------|---------|
| DDD ディレクトリ構成 | backend/app/src/ |
| ディレクトリ作成スクリプト | backend/scripts/create-ddd-structure.sh |
| 更新された composer.json | backend/composer.json |

---

## タスク

### Design Tasks（外部設計）

- [ ] コンテキスト境界の確定
- [ ] レイヤー構成の確定

### Spec Tasks（詳細設計）

- [ ] ディレクトリ作成スクリプトの作成
- [ ] ディレクトリ構成の作成
- [ ] composer.json の autoload 更新
- [ ] autoload 再生成

---

## 変更履歴

| 日付 | 変更内容 |
|------|---------|
| 2025-12-23 | 初版作成 |
