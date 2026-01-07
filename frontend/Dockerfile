# ===================================
# Frontend Dockerfile (Node.js + Vite)
# ===================================
# React + TypeScript + Vite 開発サーバー

FROM node:20-alpine

# 作業ディレクトリ設定
WORKDIR /app

# パッケージファイルをコピー（キャッシュ効率化）
COPY package*.json ./

# 依存パッケージのインストール
RUN npm install

# ソースコードをコピー
COPY . .

# Vite 開発サーバーのポート
EXPOSE 5173

# 開発サーバーを起動（ホスト0.0.0.0でコンテナ外からアクセス可能に）
CMD ["npm", "run", "dev", "--", "--host", "0.0.0.0"]
