# 業務フロー（スイムレーン図）

<!--
現行業務の詳細なフローをスイムレーン形式で記述します
各役割・部門の作業を時系列で可視化します
-->

最終更新: 2024-04-01

## 目的

このドキュメントは、現行の図書館業務の詳細なフローを **スイムレーン形式** で可視化します。
誰が、どのタイミングで、何をするかを明確にし、デジタル化による改善ポイントを特定します。

## 対象業務

| 業務 | ドキュメント | 優先度 | Epic |
|------|-------------|--------|------|
| 貸出業務 | [lending-process.md](./lending-process.md) | MVP | EP-02 |
| 返却業務 | [return-process.md](./return-process.md) | MVP | EP-02 |
| 予約業務 | [reservation-process.md](./reservation-process.md) | MVP | EP-03 |
| 蔵書登録・管理 | [book-registration-process.md](./book-registration-process.md) | MVP | EP-01 |
| 利用者登録・管理 | [user-registration-process.md](./user-registration-process.md) | MVP | EP-04 |

## 記法の説明

### Mermaid sequenceDiagram

- **participant**: 役割（利用者、館員、システムなど）
- **矢印（->>）**: メッセージ・アクションの流れ
- **alt / else**: 条件分岐
- **Note**: 補足説明

---

## 変更履歴

| 日付 | バージョン | 変更内容 | 更新者 |
|------|-----------|---------|-------|
| 2024-04-01 | v1.0 | 初版作成 | 高橋 美咲 |

---

**作成者**: 高橋 美咲（PO）
**レビュアー**: 山田 恵子（ベテラン司書）
