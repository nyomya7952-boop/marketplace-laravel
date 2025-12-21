<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\Item;
use App\Models\Like;
use App\Models\Comment;
use App\Models\Category;
use App\Models\Brand;
use App\Models\MasterData;

class ItemController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->get('tab', 'recommended'); // デフォルトはおすすめ

        if ($tab === 'mylist') {
            // マイリストタブ：いいねした商品のみ
            if (!Auth::check()) {
                // 未認証の場合は空のコレクション
                $items = collect([]);
            } else {
                // 認証ユーザーがいいねした商品を取得
                $likedItemIds = Auth::user()->likes()->pluck('item_id');
                $items = Item::whereIn('id', $likedItemIds)->get();
            }
        } else {
            // おすすめタブ：全商品（自分が出品した商品を除く）
            $query = Item::query();

            // 認証済みの場合、自分が出品した商品を除外
            if (Auth::check()) {
                $query->where('user_id', '!=', Auth::id());
            }

            $items = $query->get();
        }

        return view('item.index', [
            'items' => $items,
            'activeTab' => $tab
        ]);
    }

    public function detail($item_id)
    {
        $item = Item::with(['brand', 'categories', 'comments.user', 'likes'])
            ->findOrFail($item_id);

        // 商品の状態を取得
        $condition = null;
        if ($item->condition_id) {
            $condition = MasterData::find($item->condition_id);
        }

        // 現在のユーザーがいいねしているかどうかを判定
        $isLiked = false;
        if (Auth::check()) {
            $isLiked = Like::where('user_id', Auth::id())
                ->where('item_id', $item->id)
                ->exists();
        }

        return view('item.detail', [
            'item' => $item,
            'condition' => $condition,
            'isLiked' => $isLiked
        ]);
    }

    public function comment(Request $request, $item_id)
    {
        $request->validate([
            'content' => 'required|string|max:255',
        ]);

        $item = Item::findOrFail($item_id);

        Comment::create([
            'user_id' => Auth::id(),
            'item_id' => $item->id,
            'content' => $request->content,
        ]);

        return redirect()->route('items.detail', ['item_id' => $item->id])
            ->with('success', 'コメントを投稿しました');
    }

    public function showCreate()
    {
        $categories = Category::all();
        $conditions = MasterData::where('type', 'condition')->get();

        return view('item.create', [
            'categories' => $categories,
            'conditions' => $conditions,
        ]);
    }

    public function create(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'category_ids' => 'required|array|min:1',
            'category_ids.*' => 'exists:categories,id',
            'condition_id' => 'required|exists:master_data,id',
            'brand_name' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:255',
            'price' => 'required|integer|min:1',
        ]);

        DB::beginTransaction();
        try {
            // 画像をアップロード
            $imagePath = $request->file('image')->store('public/items');
            $imagePath = str_replace('public/', '', $imagePath);

            // ブランドの処理（存在しない場合は作成）
            $brandId = null;
            if ($request->brand_name) {
                $brand = Brand::firstOrCreate(['name' => $request->brand_name]);
                $brandId = $brand->id;
            }

            // 商品を作成
            $item = Item::create([
                'name' => $request->name,
                'image_path' => $imagePath,
                'user_id' => Auth::id(),
                'brand_id' => $brandId,
                'price' => $request->price,
                'description' => $request->description,
                'condition_id' => $request->condition_id,
            ]);

            // カテゴリを関連付け
            $item->categories()->attach($request->category_ids);

            DB::commit();

            return redirect()->route('items.index')
                ->with('success', '商品を出品しました');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('items.create.show')
                ->withInput()
                ->with('error', '商品の出品に失敗しました: ' . $e->getMessage());
        }
    }

    public function toggleLike($item_id)
    {
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'ログインが必要です'
            ], 401);
        }

        $item = Item::findOrFail($item_id);
        $userId = Auth::id();

        // 既にいいねしているか確認
        $like = Like::where('user_id', $userId)
            ->where('item_id', $item->id)
            ->first();

        if ($like) {
            // いいねを削除
            $like->delete();
            $isLiked = false;
        } else {
            // いいねを追加
            Like::create([
                'user_id' => $userId,
                'item_id' => $item->id,
            ]);
            $isLiked = true;
        }

        // 更新されたいいね数を取得
        $likeCount = $item->likes()->count();

        return response()->json([
            'success' => true,
            'isLiked' => $isLiked,
            'likeCount' => $likeCount
        ]);
    }
}
