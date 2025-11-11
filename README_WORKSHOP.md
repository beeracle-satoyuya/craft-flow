# 染物屋高橋 体験プログラム予約管理システム

## 概要

「染物屋高橋」様向けの体験プログラム予約管理アプリケーションです。スタッフが顧客からの予約情報を管理（作成・編集・一覧表示）できるシステムです。

## 技術スタック

- **バックエンド**: Laravel 12.x
- **フロントエンド**: Livewire Volt (Functional)
- **UI**: Tailwind CSS + Flux UI
- **データベース**: MySQL/SQLite
- **認証**: Laravel Fortify

## データベース設計

### テーブル構成

#### 1. users（ユーザー/スタッフ）
- 登録スタッフの情報を管理
- Laravel Fortifyによる認証機能

#### 2. workshop_categories（体験プログラムカテゴリ）
- `id`: ID
- `name`: カテゴリ名（藍染め体験、型染め体験など）
- `created_at`, `updated_at`: タイムスタンプ

#### 3. workshops（体験プログラム）
- `id`: ID
- `workshop_category_id`: カテゴリID（外部キー）
- `name`: プログラム名
- `description`: 説明
- `duration_minutes`: 所要時間（分）
- `max_capacity`: 最大受入人数
- `price_per_person`: 料金（1人）
- `is_active`: アクティブフラグ
- `created_at`, `updated_at`: タイムスタンプ

#### 4. reservations（予約情報）
- `id`: ID
- `workshop_id`: 体験プログラムID（外部キー）
- `user_id`: 登録スタッフID（外部キー）
- `customer_name`: 顧客氏名
- `customer_email`: 顧客メールアドレス
- `customer_phone`: 顧客電話番号
- `reservation_datetime`: 予約日時
- `num_people`: 人数
- `status`: 予約状況（pending, confirmed, canceled）
- `source`: 予約経路（web, phone, walk-in）
- `comment`: コメント
- `options`: オプション（JSON）
- `cancellation_reason`: キャンセル理由
- `deleted_at`: ソフトデリート
- `created_at`, `updated_at`: タイムスタンプ

## 主な機能

### 1. 予約管理
- **一覧表示**: 予約の検索・フィルタリング機能付き
- **新規作成**: 顧客情報と予約詳細の登録
- **編集**: 既存予約の更新
- **詳細表示**: 予約情報の詳細確認
- **削除**: 予約の削除（ソフトデリート）

### 2. 体験プログラム管理
- **一覧表示**: プログラムのカード形式表示
- **新規作成**: 新しいプログラムの登録
- **編集**: プログラム情報の更新
- **詳細表示**: プログラム詳細と予約統計
- **有効/無効切り替え**: プログラムの公開制御

### 3. カテゴリ管理
- **一覧表示**: カテゴリの管理
- **新規作成**: インライン追加
- **編集**: インライン編集
- **削除**: プログラムが紐づいていない場合のみ削除可能

## セットアップ手順

### 1. 依存関係のインストール

```bash
# Composerパッケージのインストール
composer install

# npmパッケージのインストール
npm install
```

### 2. 環境設定

```bash
# .envファイルをコピー
cp .env.example .env

# アプリケーションキーの生成
php artisan key:generate
```

### 3. データベース設定

`.env`ファイルでデータベース接続情報を設定：

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=craft_flow
DB_USERNAME=root
DB_PASSWORD=
```

または、SQLiteを使用する場合：

```env
DB_CONNECTION=sqlite
DB_DATABASE=/Users/YUYA/camp/craft-flow/database/database.sqlite
```

### 4. データベースのマイグレーションとシード

```bash
# マイグレーションを実行
php artisan migrate

# 初期データを投入
php artisan db:seed
```

シード実行後、以下のログイン情報が使用可能になります：

- **Email**: `staff@somemonoya-takahashi.jp`
- **Password**: `password`

### 5. アセットのビルド

```bash
# 開発環境
npm run dev

# 本番環境
npm run build
```

### 6. アプリケーションの起動

```bash
# 開発サーバーを起動
php artisan serve
```

ブラウザで `http://localhost:8000` にアクセスします。

## ディレクトリ構成

```
app/
├── Models/                      # Eloquentモデル
│   ├── User.php
│   ├── WorkshopCategory.php
│   ├── Workshop.php
│   └── Reservation.php
├── Policies/                    # 認可ポリシー
│   └── ReservationPolicy.php
└── ...

database/
├── migrations/                  # マイグレーションファイル
│   ├── 2025_11_11_030009_create_workshop_categories_table.php
│   ├── 2025_11_11_030010_create_workshops_table.php
│   └── 2025_11_11_030010_create_reservations_table.php
└── seeders/                    # シーダーファイル
    ├── DatabaseSeeder.php
    ├── WorkshopCategorySeeder.php
    └── WorkshopSeeder.php

resources/
└── views/
    ├── livewire/
    │   ├── reservations/      # 予約管理コンポーネント
    │   │   ├── index.blade.php
    │   │   ├── create.blade.php
    │   │   ├── edit.blade.php
    │   │   └── show.blade.php
    │   ├── workshops/         # プログラム管理コンポーネント
    │   │   ├── index.blade.php
    │   │   ├── create.blade.php
    │   │   ├── edit.blade.php
    │   │   └── show.blade.php
    │   └── workshop-categories/ # カテゴリ管理コンポーネント
    │       └── index.blade.php
    └── dashboard.blade.php    # ダッシュボード

routes/
└── web.php                    # ルーティング定義
```

## ルーティング

すべてのルートは認証が必要です（`auth`ミドルウェア）。

### 予約管理
- `GET /reservations` - 予約一覧
- `GET /reservations/create` - 予約作成
- `GET /reservations/{reservation}` - 予約詳細
- `GET /reservations/{reservation}/edit` - 予約編集

### 体験プログラム管理
- `GET /workshops` - プログラム一覧
- `GET /workshops/create` - プログラム作成
- `GET /workshops/{workshop}` - プログラム詳細
- `GET /workshops/{workshop}/edit` - プログラム編集

### カテゴリ管理
- `GET /workshop-categories` - カテゴリ管理

## 初期データ

シーダーで以下のデータが投入されます：

### テストユーザー
1. 染物屋 太郎 (staff@somemonoya-takahashi.jp)
2. 高橋 花子 (takahashi@somemonoya-takahashi.jp)

### カテゴリ
- 藍染め体験
- 型染め体験
- 絞り染め体験
- 草木染め体験
- 手ぬぐい染め体験

### 体験プログラム
- 藍染めハンカチ体験（90分、3,000円）
- 藍染めストール体験（120分、5,500円）
- 型染めトートバッグ体験（120分、4,500円）
- 絞り染めTシャツ体験（150分、4,000円）
- 草木染めストール体験（180分、6,000円）
- 手ぬぐい染め体験（90分、2,500円）

## セキュリティ

- **認証**: Laravel Fortifyによる認証システム
- **認可**: Policyベースのアクセス制御
- **CSRF対策**: Laravelの標準機能
- **XSS対策**: Bladeテンプレートの自動エスケープ
- **SQLインジェクション対策**: Eloquent ORMの使用

## 開発規約

- PSR-12準拠のコードフォーマット
- 厳密な型指定（`declare(strict_types=1)`）
- PHPDocコメントの記述
- Volt Functional スタイルの使用
- 日本語でのコメント記述

## トラブルシューティング

### マイグレーションエラー

```bash
# データベースをリセットして再実行
php artisan migrate:fresh --seed
```

### キャッシュクリア

```bash
# すべてのキャッシュをクリア
php artisan optimize:clear
```

### パーミッションエラー

```bash
# storage と bootstrap/cache のパーミッションを修正
chmod -R 775 storage bootstrap/cache
```

## ライセンス

このプロジェクトはMITライセンスの下で公開されています。

## お問い合わせ

開発元: BEERACLE株式会社

