<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Item;
use App\Http\Requests\AddressRequest;

class ShippingController extends Controller
{
    public function showShipping($item_id)
    {
        $item = Item::findOrFail($item_id);
        $user = Auth::user();

        $shippingPostalCode = session('shipping_postal_code', $user->postal_code);
        $shippingAddress = session('shipping_address', $user->address);
        $shippingBuildingName = session('shipping_building_name', $user->building_name);

        return view('shipping.shipping', [
            'item' => $item,
            'shippingPostalCode' => $shippingPostalCode,
            'shippingAddress' => $shippingAddress,
            'shippingBuildingName' => $shippingBuildingName,
        ]);
    }

    public function changeShipping(AddressRequest $request, $item_id)
    {
        session([
            'shipping_postal_code' => $request->postal_code,
            'shipping_address' => $request->address,
            'shipping_building_name' => $request->building_name,
        ]);

        return redirect()->route('items.purchase.show', ['item_id' => $item_id])
            ->with('success', '配送先住所を変更しました');
    }
}
