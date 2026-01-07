# Epics - エピック管理

最終更新: 2025-12-23

## 概要

このディレクトリは **Epic（エピック）** を管理する。
エピックはイニシアチブを実現するための大きな機能単位であり、複数のUser Storyに分解される。

---

## 階層構造

```
Initiative（事業ゴール）
    └─ Use Case（業務行動）
         └─ Epic            ← このディレクトリで管理
             └─ User Story
                 ├─ Design Tasks   ← 外部設計
                 └─ Spec Tasks     ← 詳細設計
```

| 階層 | 説明 | 管理場所 |
|------|------|---------|
| Initiative | 事業ゴール（なぜやるか） | `01_vision/initiatives/` |
| Use Case | 業務行動（誰が何をするか） | `01_vision/initiatives/{ID}/usecases/` |
| Epic | 大きな機能単位 | `02_backlog/00_epics/` |
| User Story | ユーザー価値単位 | `02_backlog/00_epics/{INI-ID}/{EP-ID}/stories/` |
| Tasks | 作業単位（Design/Spec） | 各Storyに紐づく |

---

## ディレクトリ構造

```
00_epics/
├── README.md                     # このファイル
└── INI-XXX/                      # イニシアチブ別ディレクトリ
    └── EPIC-XXX/                 # エピック
        ├── epic.md               # エピック概要
        └── stories/              # User Story 一覧
            └── ST-XXX.md
```

---

## エピック一覧

### INI-000: 開発環境構築

| ID | 名称 | 優先度 | ステータス |
|----|------|--------|----------|
| [EPIC-001](./INI-000/EPIC-001/epic.md) | Docker 環境構築 | Must | Draft |
| [EPIC-002](./INI-000/EPIC-002/epic.md) | バックエンド初期設定 | Must | Planned |
| [EPIC-003](./INI-000/EPIC-003/epic.md) | フロントエンド初期設定 | Must | Planned |
| [EPIC-004](./INI-000/EPIC-004/epic.md) | 開発環境動作確認 | Must | Planned |
| [EPIC-005](./INI-000/EPIC-005/epic.md) | 職員認証機能 | Must | Planned |
| [EPIC-006](./INI-000/EPIC-006/epic.md) | 職員ログアウト機能 | Must | Planned |
| [EPIC-007](./INI-000/EPIC-007/epic.md) | セキュリティ対策準備 | Must | Planned |

### INI-001: 図書館業務デジタル化プロジェクト（MVP）

| ID | 名称 | 優先度 | ステータス |
|----|------|--------|----------|
| EP-001 | 蔵書検索機能の実現 | Must | Planned |
| EP-002 | 貸出・返却のデジタル化 | Must | Planned |
| EP-003 | 予約管理のデジタル化 | Should | Planned |
| EP-004 | 利用者管理 | Must | Planned |
| EP-005 | レポート機能 | Could | Planned |

---

## 優先度付けの基準

本プロジェクトでは **MoSCoW法** を使用します。

| 優先度 | 意味 | 判断基準 |
|--------|------|---------|
| **Must** | 必須 | MVPに必要。これがないとリリースできない |
| **Should** | 重要 | できれば入れたい。リリース後早期に対応 |
| **Could** | あると良い | 余裕があれば対応 |
| **Won't** | 今回はやらない | スコープ外（将来検討） |

---

## ステータス定義

| ステータス | 説明 |
|-----------|------|
| Planned | 計画中・未着手 |
| Draft | 作成中 |
| Ready | 着手可能 |
| In Progress | 実装中 |
| Done | 完了 |

---

## Epic から Story への展開

Epic を作成したら、User Story に分解してバックログで管理する。

```
02_backlog/00_epics/INI-000/EPIC-001/
    ├── epic.md              # Epic 定義
    └── stories/
        ├── ST-001.md        # User Story
        │   ├─ Design Tasks  # 外部設計タスク
        │   └─ Spec Tasks    # 詳細設計タスク
        └── ST-002.md
```

---

## 関連ドキュメント

- [イニシアチブ一覧](../../01_vision/initiatives/README.md)
- [バックログ概要](../README.md)

---

**責任者**: 高橋 美咲（PO）
