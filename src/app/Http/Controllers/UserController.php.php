<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class UserController extends Controller
{
    public function profile(Request $request)
    {
        $user = Auth::user();
        $page = $request->get('page', 'sell'); // デフォルトは出品した商品

        // 出品した商品を取得
        $soldItems = $user->items()->get();

        // 購入した商品を取得（SoldItemを通じて）
        $purchasedItems = $user->soldItems()->with('item')->get()->map(function ($soldItem) {
            return $soldItem->item;
        });

        return view('user.profile', [
            'user' => $user,
            'soldItems' => $soldItems,
            'purchasedItems' => $purchasedItems,
            'activeTab' => $page
        ]);
    }
    public function edit()
    {
        return view('user.edit');
    }
}
