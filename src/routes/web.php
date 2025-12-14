<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

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
