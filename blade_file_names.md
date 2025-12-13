# 画面名称、パス、メソッド、コントローラー、アクション、blade ファイル名の対応表

| 画面名称                                 | パス                        | メソッド | コントローラー     | アクション | blade ファイル名 |
| ---------------------------------------- | --------------------------- | -------- | ------------------ | ---------- | ---------------- |
| 商品一覧画面（トップ画面）               | /                           | GET      | ItemController     | index      | index            |
| ★ 商品一覧画面（トップ画面）\_マイリスト | /?tab=mylist                | GET      | ItemController     | index      | index            |
| 会員登録画面                             | /register                   | POST     | AuthController     | register   | register         |
| ログイン画面                             | /login                      | GET      | AuthController     | login      | login            |
| 商品詳細画面                             | /item/{item_id}             | GET      | ItemController     | detail     | detail           |
| 商品購入画面                             | /purchase/{item_id}         | POST     | ItemController     | purchase   | purchase         |
| 住所変更ページ                           | /purchase/address/{item_id} | POST     | ShippingController | shipping   | shipping         |
| 商品出品画面                             | /sell                       | POST     | ItemController     | create     | create           |
| プロフィール画面                         | /mypage                     | GET      | UserController     | profile    | profile          |
| プロフィール編集画面                     | /mypage/profile             | POST     | UserController     | edit       | edit             |
| ★ プロフィール画面\_購入した商品一覧     | /mypage?page=buy            | GET      | UserController     | profile    | profile          |
| ★ プロフィール画面\_出品した商品一覧     | /mypage?page=sell           | GET      | UserController     | profile    | profile          |

## ファイル配置例

```
resources/views/
├── items/
│   ├── index.blade.php      # 商品一覧画面
│   ├── detail.blade.php     # 商品詳細画面
│   ├── create.blade.php     # 商品出品画面
│   └── purchase.blade.php   # 商品購入画面
├── auth/
│   ├── login.blade.php      # ログイン画面
│   └── register.blade.php   # 会員登録画面
├── users/
│   ├── profile.blade.php    # プロフィール画面
│   └── edit.blade.php       # プロフィール編集画面
└── shipping/
    └── edit.blade.php       # 送付先住所変更画面
```

## ルーティング例

```php
// routes/web.php

// 商品関連
Route::get('/', [ItemController::class, 'index'])->name('items.index');
Route::get('/item/{item_id}', [ItemController::class, 'detail'])->name('items.detail');
Route::post('/purchase/{item_id}', [ItemController::class, 'purchase'])->name('items.purchase');
Route::post('/sell', [ItemController::class, 'create'])->name('items.create');

// 認証関連
Route::get('/login', [AuthController::class, 'login'])->name('login');
Route::post('/register', [AuthController::class, 'register'])->name('register');

// ユーザー関連
Route::get('/mypage', [UserController::class, 'profile'])->name('users.profile');
Route::post('/mypage/profile', [UserController::class, 'edit'])->name('users.edit');

// 送付先住所関連
Route::post('/purchase/address/{item_id}', [ShippingController::class, 'shipping'])->name('shipping.update');
```

## 補足説明

### ★ マークが付いた画面について

- **商品一覧画面（トップ画面）\_マイリスト**: `index.blade.php` を使用。`tab=mylist` パラメータで表示内容を切り替え。
- **プロフィール画面\_購入した商品一覧**: `profile.blade.php` を使用。`page=buy` パラメータで表示内容を切り替え。
- **プロフィール画面\_出品した商品一覧**: `profile.blade.php` を使用。`page=sell` パラメータで表示内容を切り替え。

これらは同じ blade ファイル内で条件分岐により表示内容を変更する設計です。
