<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Item;
use App\Models\Category;
use App\Models\Brand;
use App\Models\MasterData;
use App\Http\Requests\ExhibitionRequest;

class ListingController extends Controller
{
    public function showListing()
    {
        $categories = Category::all();
        $conditions = MasterData::where('type', 'condition')->orderBy('id', 'asc')->get();

        return view('item.create', [
            'categories' => $categories,
            'conditions' => $conditions,
        ]);
    }

    public function createListing(ExhibitionRequest $request)
    {
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
}
