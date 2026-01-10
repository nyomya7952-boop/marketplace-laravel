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
- Stripe アカウント（テストモード）が作成済みであること
  - ※ 本プロジェクトでは Stripe のテストモードを使用します。
  - ※ 詳細は 6 章を確認してください。

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

   `src/.env` をエディタで開き、下記の通り修正します。

   ````ini
   // データベース設定（`docker-compose.yml` の設定に合わせて修正）
   DB_CONNECTION=mysql
   DB_HOST=mysql
   DB_PORT=3306
   DB_DATABASE=laravel_db
   DB_USERNAME=laravel_user
   DB_PASSWORD=laravel_pass

   ```ini
   // メール認証設定
   MAIL_MAILER=smtp
   MAIL_HOST=mailhog
   MAIL_PORT=1025
   MAIL_USERNAME=null
   MAIL_PASSWORD=null
   MAIL_ENCRYPTION=null
   MAIL_FROM_ADDRESS="noreply@example.com"
   MAIL_FROM_NAME="${APP_NAME}"

    ```ini
   //　Stripe設定
   // ※ 値は各自のStripeダッシュボード（テストモード）/ Stripe CLIの出力から取得してください
   // ※ 詳細は「Stripe テスト方法」を確認してください
   STRIPE_SECRET=sk_test_xxxxxxxxxxxxxxxxxxxxx
   STRIPE_PUBLIC_KEY=pk_test_xxxxxxxxxxxxxxxxxxxxx
   STRIPE_WEBHOOK_SECRET=whsec_xxxxxxxxxxxxxxxxxxxxx
   ````

   ※ .env ファイルは Git 管理対象外です。

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
   php artisan db:seed
   ```

   完了したら `exit` でコンテナから抜けます。

5. **アプリケーションへのアクセス**

   ブラウザで以下の URL にアクセスして確認します。

   - **アプリケーション**: [http://localhost](http://localhost)
   - **phpMyAdmin**: [http://localhost:8080](http://localhost:8080)

### Stripe テスト方法

Stripe Webhook をテストするために、Stripe CLI を使用します。

**Stripe CLI のインストール**

Stripe CLI がインストールされていない場合は、以下の手順でインストールします。

**Linux (Ubuntu/Debian):**

```bash
# 最新版をダウンロード（amd64 / arm64 は環境に合わせて選択）
# 例: uname -m が x86_64 の場合は amd64、aarch64 の場合は arm64
curl -L https://github.com/stripe/stripe-cli/releases/latest/download/stripe_linux_amd64.tar.gz -o stripe.tar.gz

# 展開
tar -xzf stripe.tar.gz

# 実行ファイルを配置
sudo mv stripe /usr/local/bin/

# 実行権限（通常は不要だが念のため）
sudo chmod +x /usr/local/bin/stripe

# 確認
stripe --version
```

**macOS:**

```bash
# Homebrewを使用
brew install stripe/stripe-cli/stripe
```

**Windows:**

[Stripe CLI のダウンロードページ](https://github.com/stripe/stripe-cli/releases/latest)から最新の`.exe`ファイルをダウンロードしてインストールします。

インストール後、以下のコマンドでバージョンを確認します。

```bash
stripe --version
```

**Stripe CLI へのログイン**

Stripe CLI を初めて使用する場合は、Stripe アカウントにログインする必要があります。

```bash
stripe login
```

このコマンドを実行すると、ブラウザが開き、Stripe アカウントへの認証が求められます。認証が完了すると、CLI が自動的に認証情報を保存します。

### Webhook エンドポイントについて

本プロジェクトでは、Stripe の Webhook を以下の URL で受信します。
この URL は Laravel アプリケーション内で Webhook 受信用として定義されています。

````text
http://localhost/webhook/stripe

**Webhook のテスト**

**前提条件**

- Stripe CLI がインストールされていること（`stripe --version`で確認）
- Stripe CLI にログイン済みであること（`stripe login`でログイン済み）
- Docker コンテナが起動していること（`docker-compose up -d`で起動済み）

**手順**
marketplace-laravel ディレクトリで以下コマンドを実行します。

```bash
stripe listen --forward-to http://localhost/webhook/stripe
````

このコマンドを実行すると、Stripe CLI が Webhook イベントをローカルの`http://localhost/webhook/stripe`に転送します。
なお、コマンド実行中はターミナルを閉じないでください。

さらに、起動ログに **Webhook signing secret（`whsec_...`）** が表示されるので、表示された値を `src/.env` の `STRIPE_WEBHOOK_SECRET` に設定してください。
