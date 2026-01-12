# ============================================
# プロジェクト Makefile
# ============================================

.PHONY: help up down restart logs shell test lint seed seed-books import-books generate-books migrate fresh security security-backend security-frontend security-audit phpstan phpmd phpmd-report metrics coverage coverage-html coverage-report

# デフォルトターゲット
help:
	@echo "使用可能なコマンド:"
	@echo ""
	@echo "=== Docker ==="
	@echo "  make up        - Docker コンテナを起動"
	@echo "  make down      - Docker コンテナを停止"
	@echo "  make restart   - Docker コンテナを再起動"
	@echo "  make logs      - Docker ログを表示"
	@echo "  make shell     - バックエンドコンテナに入る"
	@echo ""
	@echo "=== テスト・Lint ==="
	@echo "  make test           - 全テストを実行"
	@echo "  make coverage       - テストカバレッジを表示（ターミナル）"
	@echo "  make coverage-html  - テストカバレッジをHTML形式で生成"
	@echo "  make coverage-report - テストカバレッジを詳細表示（最低80%）"
	@echo "  make lint           - コードスタイルを修正"
	@echo "  make lint-check     - コードスタイルをチェック（修正なし）"
	@echo ""
	@echo "=== セキュリティスキャン ==="
	@echo "  make security          - 全セキュリティスキャンを実行"
	@echo "  make security-backend  - バックエンドのセキュリティスキャン"
	@echo "  make security-frontend - フロントエンドのセキュリティスキャン"
	@echo "  make security-audit    - 依存パッケージの脆弱性チェックのみ"
	@echo "  make phpstan           - PHPStan静的解析（認知的複雑度を含む）"
	@echo ""
	@echo "=== コード品質分析 ==="
	@echo "  make metrics           - PHP Metricsでコード品質分析"
	@echo "  make phpmd             - PHPMD循環的複雑度チェック（ターミナル出力）"
	@echo "  make phpmd-report      - PHPMD循環的複雑度チェック（HTMLレポート生成）"
	@echo ""
	@echo "=== データベース ==="
	@echo "  make migrate   - マイグレーションを実行"
	@echo "  make fresh     - DBをリセットしてマイグレーション"
	@echo ""
	@echo "=== シードデータ ==="
	@echo "  make seed           - 全シーダーを実行"
	@echo "  make seed-books     - サンプル蔵書データを投入（100件以上）"
	@echo "  make import-books   - CSVから蔵書をインポート（要: FILE=path/to/file.csv）"
	@echo "  make generate-books - ランダム蔵書データを生成（オプション: COUNT=500 STATUS=available）"
	@echo ""

# ============================================
# Docker
# ============================================

up:
	docker compose up -d

down:
	docker compose down

restart:
	docker compose restart

logs:
	docker compose logs -f

shell:
	docker compose exec backend sh

# ============================================
# テスト・Lint
# ============================================

test:
	docker compose exec backend sh -c "cd /var/www/html && ./vendor/bin/pest"

# テストカバレッジ（ターミナル表示）
coverage:
	docker compose exec backend sh -c "cd /var/www/html && php artisan test --coverage"

# テストカバレッジ（HTML形式で生成）
coverage-html:
	@echo ""
	@echo "========================================"
	@echo "テストカバレッジ HTML生成"
	@echo "========================================"
	@echo ""
	@docker compose exec backend sh -c "cd /var/www/html && php artisan test --coverage-html=build/coverage"
	@echo ""
	@echo "========================================"
	@echo "レポートが生成されました"
	@echo "場所: backend/build/coverage/index.html"
	@echo "========================================"

# テストカバレッジ（詳細表示、最低80%要求）
coverage-report:
	@echo ""
	@echo "========================================"
	@echo "テストカバレッジ 詳細レポート"
	@echo "========================================"
	@echo ""
	@docker compose exec backend sh -c "cd /var/www/html && ./vendor/bin/pest --coverage --min=80"

lint:
	docker compose exec backend sh -c "cd /var/www/html && ./vendor/bin/pint"

lint-check:
	docker compose exec backend sh -c "cd /var/www/html && ./vendor/bin/pint --test"

# ============================================
# データベース
# ============================================

migrate:
	docker compose exec backend sh -c "cd /var/www/html && php artisan migrate"

fresh:
	docker compose exec backend sh -c "cd /var/www/html && php artisan migrate:fresh"

# ============================================
# シードデータ
# ============================================

# 全シーダーを実行
seed:
	docker compose exec backend sh -c "cd /var/www/html && php artisan db:seed"

# サンプル蔵書データを投入（100件以上の日本古典文学）
seed-books:
	docker compose exec backend sh -c "cd /var/www/html && php artisan db:seed --class=BookSeeder"

# CSVから蔵書をインポート
# 使用例: make import-books FILE=storage/app/books.csv
# ドライラン: make import-books FILE=storage/app/books.csv DRY_RUN=1
import-books:
ifndef FILE
	$(error FILE is required. Usage: make import-books FILE=path/to/file.csv)
endif
ifdef DRY_RUN
	docker compose exec backend sh -c "cd /var/www/html && php artisan import:books $(FILE) --dry-run"
else
	docker compose exec backend sh -c "cd /var/www/html && php artisan import:books $(FILE)"
endif

# ランダム蔵書データを生成
# 使用例: make generate-books COUNT=500
# 状態指定: make generate-books COUNT=200 STATUS=available
COUNT ?= 100
generate-books:
ifdef STATUS
	docker compose exec backend sh -c "cd /var/www/html && php artisan book:generate $(COUNT) --status=$(STATUS)"
else
	docker compose exec backend sh -c "cd /var/www/html && php artisan book:generate $(COUNT)"
endif

# ============================================
# セキュリティスキャン
# ============================================

# 全セキュリティスキャンを実行
security: security-backend security-frontend
	@echo ""
	@echo "========================================"
	@echo "全セキュリティスキャンが完了しました"
	@echo "========================================"

# バックエンドのセキュリティスキャン（composer audit + PHPStan）
security-backend:
	@echo ""
	@echo "========================================"
	@echo "バックエンド セキュリティスキャン開始"
	@echo "========================================"
	@echo ""
	@echo "--- Composer Audit ---"
	@docker compose exec backend sh -c "cd /var/www/html && composer audit" || true
	@echo ""
	@echo "--- PHPStan Security Analysis ---"
	@docker compose exec backend sh -c "cd /var/www/html && ./vendor/bin/phpstan analyse --configuration=phpstan.neon"

# フロントエンドのセキュリティスキャン（npm audit）
security-frontend:
	@echo ""
	@echo "========================================"
	@echo "フロントエンド セキュリティスキャン開始"
	@echo "========================================"
	@echo ""
	@echo "--- npm Audit ---"
	@docker compose exec frontend sh -c "npm audit" || true

# 依存パッケージの脆弱性チェックのみ（レポートなし）
security-audit:
	@echo ""
	@echo "========================================"
	@echo "依存パッケージ脆弱性チェック"
	@echo "========================================"
	@echo ""
	@echo "--- Backend: Composer Audit ---"
	@docker compose exec backend sh -c "cd /var/www/html && composer audit" || true
	@echo ""
	@echo "--- Frontend: npm Audit ---"
	@docker compose exec frontend sh -c "npm audit" || true

# PHPStan静的解析
phpstan:
	docker compose exec backend sh -c "cd /var/www/html && ./vendor/bin/phpstan analyse --configuration=phpstan.neon --memory-limit=512M"

# ============================================
# コード品質分析
# ============================================

# PHP Metricsでコード品質分析
metrics:
	@echo ""
	@echo "========================================"
	@echo "PHP Metrics コード品質分析"
	@echo "========================================"
	@echo ""
	@docker compose exec backend sh -c "cd /var/www/html && ./vendor/bin/phpmetrics --report-html=./storage/phpmetrics ./app ./packages"
	@echo ""
	@echo "========================================"
	@echo "レポートが生成されました"
	@echo "場所: backend/storage/phpmetrics/index.html"
	@echo "========================================"

# PHPMD循環的複雑度チェック（ターミナル出力）
phpmd:
	@echo ""
	@echo "========================================"
	@echo "PHPMD 循環的複雑度チェック"
	@echo "========================================"
	@echo ""
	@docker compose exec backend sh -c "cd /var/www/html && ./vendor/bin/phpmd ./app,./packages text phpmd.xml"
	@echo ""
	@echo "========================================"
	@echo "PHPMD チェック完了"
	@echo "========================================"

# PHPMD循環的複雑度チェック（HTMLレポート生成）
phpmd-report:
	@echo ""
	@echo "========================================"
	@echo "PHPMD HTMLレポート生成"
	@echo "========================================"
	@echo ""
	@docker compose exec backend sh -c "cd /var/www/html && mkdir -p ./storage/phpmd && ./vendor/bin/phpmd ./app,./packages html phpmd.xml --reportfile ./storage/phpmd/report.html" || true
	@echo ""
	@echo "========================================"
	@echo "レポートが生成されました"
	@echo "場所: backend/storage/phpmd/report.html"
	@echo "========================================"
