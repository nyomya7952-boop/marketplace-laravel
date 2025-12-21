<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\ShippingController;
use App\Http\Controllers\StripeWebhookController;
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

// コメント関連要確認★
Route::post('/item/{item_id}/comment', [ItemController::class, 'comment'])->name('items.comment')->middleware(['auth', 'verified']);

// いいね関連
Route::post('/item/{item_id}/like', [ItemController::class, 'toggleLike'])->name('items.like.toggle')->middleware(['auth', 'verified']);

// 商品購入関連
Route::get('/purchase/{item_id}', [PurchaseController::class, 'showPurchase'])->name('items.purchase.show')->middleware(['auth', 'verified']);
Route::post('/purchase/{item_id}', [PurchaseController::class, 'purchase'])->name('items.purchase')->middleware(['auth', 'verified']);
Route::get('/purchase/{item_id}/success', [PurchaseController::class, 'purchaseSuccess'])->name('items.purchase.success')->middleware(['auth', 'verified']);

// 送付先住所関連
Route::get('/purchase/address/{item_id}', [ShippingController::class, 'showShipping'])->name('shipping.show')->middleware(['auth', 'verified']);
Route::post('/purchase/address/{item_id}', [ShippingController::class, 'shipping'])->name('shipping.update')->middleware(['auth', 'verified']);

// 商品出品関連
Route::get('/sell', [ItemController::class, 'showCreate'])->name('items.create.show')->middleware(['auth', 'verified']);
Route::post('/sell', [ItemController::class, 'create'])->name('items.create')->middleware(['auth', 'verified']);

// 認証関連
Route::get('/login', [AuthController::class, 'login'])->name('login');
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register'])->name('register.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// メール認証関連
Route::get('/email/verify', [AuthController::class, 'showVerificationNotice'])->name('verification.notice');
Route::post('/email/verification-notification', [AuthController::class, 'resendVerificationEmail'])->name('verification.resend');
Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verify'])->name('verification.verify')->middleware('signed');

// ユーザー関連
Route::get('/mypage', [UserController::class, 'profile'])->name('user.profile')->middleware(['auth', 'verified']);
Route::get('/mypage/profile', [UserController::class, 'edit'])->name('user.edit')->middleware(['auth', 'verified']);
Route::post('/mypage/profile', [UserController::class, 'update'])->name('user.update')->middleware(['auth', 'verified']);

// Stripe Webhook
Route::post('/webhook/stripe', [StripeWebhookController::class, 'handle'])->name('webhook.stripe');
