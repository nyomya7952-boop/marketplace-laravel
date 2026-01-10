<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Item;
use App\Models\Like;
use App\Models\Comment;
use App\Models\MasterData;
use App\Http\Requests\CommentRequest;

class ItemController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->get('tab', 'recommended'); // デフォルトはおすすめ
        $search = $request->get('search'); // 検索キーワード

        if ($tab === 'mylist') {
            // マイリストタブ：いいねした商品のみ
            if (!Auth::check()) {
                // 未認証の場合は空のコレクション
                $items = collect([]);
            } else {
                // 認証ユーザーがいいねした商品を取得
                $likedItemIds = Auth::user()->likes()->pluck('item_id');
                $query = Item::whereIn('id', $likedItemIds);

                // 検索キーワードがある場合、商品名で部分一致検索　　　　　　　　　　　　　　　　　　　　
                if ($search) {
                    $query->where('name', 'like', '%' . $search . '%');
                }

                $items = $query->get();
            }
        } else {
            // おすすめタブ：全商品（自分が出品した商品を除く）
            $query = Item::query();

            // 認証済みの場合、自分が出品した商品を除外
            if (Auth::check()) {
                $query->where('user_id', '!=', Auth::id());
            }

            // 検索キーワードがある場合、商品名で部分一致検索
            if ($search) {
                $query->where('name', 'like', '%' . $search . '%');
            }

            $items = $query->get();
        }

        return view('item.index', [
            'items' => $items,
            'activeTab' => $tab,
            'search' => $search
        ]);
    }

    public function showDetail($item_id)
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

    public function sendComment(CommentRequest $request, $item_id)
    {
        // 未ログインの場合はバリデーションエラーとして返す
        if (!Auth::check()) {
            return redirect()->route('items.detail', ['item_id' => $item_id])
                ->withErrors(['content' => 'コメントするにはログインしてください']);
        }

        $item = Item::findOrFail($item_id);

        Comment::create([
            'user_id' => Auth::id(),
            'item_id' => $item->id,
            'content' => $request->content,
        ]);

        return redirect()->route('items.detail', ['item_id' => $item->id])
            ->with('success', 'コメントを投稿しました');
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
