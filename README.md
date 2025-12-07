# Marketplace Laravel

Laravel を使用したマーケットプレイスアプリケーションです。Docker を使用して開発環境を構築します。

## 使用技術

### フロントエンド・バックエンド

- **PHP**: 8.1
- **Framework**: Laravel 8.x

### インフラ・データベース

- **Docker / Docker Compose**
- **Web Server**: Nginx
- **Database**: MySQL 8.0
- **Database Management**: phpMyAdmin

## 環境構築手順

### 前提条件

- Docker Desktop (または Docker Engine + Docker Compose) がインストールされていること。

### 構築ステップ

1. **リポジトリのクローン**

   ローカルにディレクトリを作成のうえ、ディレクトリ上で下記コマンドを実行します。

   ```bash
   git clone git@github.com:nyomya7952-boop/marketplace-laravel.git
   ```

2. **環境設定ファイルの作成**
   `src` ディレクトリにある `.env.example` をコピーして `.env` を作成します。

   Linux/Mac:

   ```bash
   cp src/.env.example src/.env
   ```

   `src/.env` をエディタで開き、データベース設定を `docker-compose.yml` の設定に合わせて修正します。

   ```ini
   DB_CONNECTION=mysql
   DB_HOST=mysql
   DB_PORT=3306
   DB_DATABASE=laravel_db
   DB_USERNAME=laravel_user
   DB_PASSWORD=laravel_pass
   ```

3. **Docker コンテナの起動**
   プロジェクトルートで以下のコマンドを実行します。

   ```bash
   docker-compose up -d --build
   ```

4. **依存関係のインストールとセットアップ**
   PHP コンテナに入り、Composer パッケージのインストールと Laravel の初期設定を行います。

   ```bash
   docker-compose exec php bash
   ```

   コンテナ内で以下を実行します:

   ```bash
   # 依存ライブラリのインストール
   composer install

   # アプリケーションキーの生成
   php artisan key:generate

   # データベースのマイグレーション
   php artisan migrate

   # シーダーの実行
   # php artisan db:seed
   ```

   完了したら `exit` でコンテナから抜けます。

5. **アプリケーションへのアクセス**
   ブラウザで以下の URL にアクセスして確認します。

   - **アプリケーション**: [http://localhost](http://localhost)
   - **phpMyAdmin**: [http://localhost:8080](http://localhost:8080)
