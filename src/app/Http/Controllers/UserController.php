<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Item;
use App\Models\SoldItem;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\ProfileRequest;

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

    public function update(ProfileRequest $request)
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        // バリデーション済みデータを取得
        $validated = $request->validated();

        // 画像アップロード
        if ($request->hasFile('profile_image_path')) {
            $file = $request->file('profile_image_path');
            $extension = strtolower($file->getClientOriginalExtension());

            // 拡張子をチェック（TIFファイルなどを除外）
            $allowedExtensions = ['jpeg', 'png'];
            if (!in_array($extension, $allowedExtensions)) {
                return redirect()->back()
                    ->withErrors(['profile_image_path' => '画像はjpeg,png形式で選択してください'])
                    ->withInput();
            }

            // 古い画像を削除
            if ($user->profile_image_path) {
                Storage::disk('public')->delete($user->profile_image_path);
            }

            // 新しい画像を保存
            $path = $file->store('public/profile');
            $validated['profile_image_path'] = str_replace('public/', '', $path);
        }

        // building_nameも更新（バリデーションルールに含まれていないが、フォームには存在）
        if ($request->has('building_name')) {
            $validated['building_name'] = $request->building_name;
        }

        $user->update($validated);

        return redirect()->route('user.profile')->with('success', 'プロフィールを更新しました');
    }
}
