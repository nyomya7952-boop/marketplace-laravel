<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Item;
use App\Models\SoldItem;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    public function profile(Request $request)
    {
        $user = Auth::user();

        // 認証されていない場合はログイン画面へリダイレクト
        if (!$user) {
            return redirect()->route('login');
        }

        $page = $request->get('page', 'sell'); // デフォルトは出品した商品

        // 出品した商品を取得（ログインユーザーの出品のみ）
        $soldItems = Item::where('user_id', $user->id)->get();

        // 購入した商品を取得（SoldItemを通じてログインユーザーの購入のみ）
        $purchasedItemIds = SoldItem::where('user_id', $user->id)->pluck('item_id');
        $purchasedItems = Item::whereIn('id', $purchasedItemIds)->get();

        return view('user.profile', [
            'user' => $user,
            'soldItems' => $soldItems,
            'purchasedItems' => $purchasedItems,
            'activeTab' => $page
        ]);
    }

    public function edit()
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        return view('user.edit', [
            'user' => $user,
        ]);
    }

    public function update(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:20',
            'postal_code' => 'required|string|max:8',
            'address' => 'required|string|max:255',
            'building_name' => 'nullable|string|max:255',
            'profile_image_path' => 'nullable|image|max:2048',
        ]);

        // 画像アップロード
        if ($request->hasFile('profile_image_path')) {
            $path = $request->file('profile_image_path')->store('public/profile');
            $validated['profile_image_path'] = str_replace('public/', '', $path);
        }

        $user->update($validated);

        return redirect()->route('user.profile')->with('success', 'プロフィールを更新しました');
    }
}
