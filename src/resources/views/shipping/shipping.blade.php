@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/shipping.css') }}">
@endsection

@section('content')
<div class="shipping__content">
    <div class="shipping__container">
        <h1 class="shipping__title">送付先住所変更</h1>

        <form action="{{ route('shipping.update', ['item_id' => $item->id]) }}" method="post" class="shipping__form" novalidate>
            @csrf

            <div class="shipping__field">
                <label for="postal_code" class="shipping__label">郵便番号</label>
                <input type="text" name="postal_code" id="postal_code" class="shipping__input" value="{{ old('postal_code', $shippingPostalCode === '000' ? '' : $shippingPostalCode) }}" required maxlength="8" placeholder="例: 123-4567">
                @error('postal_code')
                    <div class="shipping__error">{{ $message }}</div>
                @enderror
            </div>

            <div class="shipping__field">
                <label for="address" class="shipping__label">住所</label>
                <input type="text" name="address" id="address" class="shipping__input" value="{{ old('address', $shippingAddress === '住所未設定' ? '' : $shippingAddress) }}" required maxlength="255" placeholder="例: 東京都渋谷区...">
                @error('address')
                    <div class="shipping__error">{{ $message }}</div>
                @enderror
            </div>

            <div class="shipping__field">
                <label for="building_name" class="shipping__label">建物名</label>
                <input type="text" name="building_name" id="building_name" class="shipping__input" value="{{ old('building_name', $shippingBuildingName) }}" maxlength="255" placeholder="例: マンション名 101号室">
                @error('building_name')
                    <div class="shipping__error">{{ $message }}</div>
                @enderror
            </div>

            <div class="shipping__actions">
                <a href="{{ route('items.purchase.show', ['item_id' => $item->id]) }}" class="shipping__cancel-button">キャンセル</a>
                <button type="submit" class="shipping__submit-button">変更する</button>
            </div>
        </form>
    </div>
</div>
@endsection

