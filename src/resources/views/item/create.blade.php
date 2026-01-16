@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/create.css') }}">
@endsection

@section('content')
<div class="create__content">
    <h1 class="create__title">商品の出品</h1>

    <form action="{{ route('items.create') }}" method="post" enctype="multipart/form-data" class="create__form" novalidate>
        @csrf

        <!-- 商品画像 -->
        <div class="create__section">
            <h2 class="create__section-title">商品画像</h2>
            <div class="create__image-container">
                <div class="create__image-preview" id="image-preview">
                    <span class="create__image-placeholder">画像を選択してください</span>
                </div>
                <label for="image" class="create__image-button">
                    <input type="file" name="image" id="image" accept="image/jpeg,image/png" required style="display: none;">
                    画像を選択する
                </label>
            </div>
            @error('image')
                <div class="create__error">{{ $message }}</div>
            @enderror
        </div>

        <!-- 商品の詳細 -->
        <div class="create__section">
            <h2 class="create__section-title">商品の詳細</h2>

            <!-- カテゴリー -->
            <div class="create__subsection">
                <h3 class="create__subsection-title">カテゴリー</h3>
                <div class="create__categories">
                    @foreach($categories as $category)
                        <label class="create__category-item">
                            <input type="checkbox" name="category_ids[]" value="{{ $category->id }}" class="create__category-checkbox" {{ in_array($category->id, old('category_ids', [])) ? 'checked' : '' }}>
                            <span class="create__category-label">{{ $category->name }}</span>
                        </label>
                    @endforeach
                </div>
                @error('category_ids')
                    <div class="create__error">{{ $message }}</div>
                @enderror
            </div>

            <!-- 商品の状態 -->
            <div class="create__subsection">
                <h3 class="create__subsection-title">商品の状態</h3>
                <select name="condition_id" id="condition_id" class="create__select" required>
                    <option value="">選択してください</option>
                    @foreach($conditions as $condition)
                        <option value="{{ $condition->id }}" {{ old('condition_id') == $condition->id ? 'selected' : '' }}>
                            {{ $condition->name }}
                        </option>
                    @endforeach
                </select>
                @error('condition_id')
                    <div class="create__error">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <!-- 商品名と説明 -->
        <div class="create__section">
            <h2 class="create__section-title">商品名と説明</h2>

            <!-- 商品名 -->
            <div class="create__field">
                <label for="name" class="create__label">商品名</label>
                <input type="text" name="name" id="name" class="create__input" value="{{ old('name') }}" required maxlength="100" placeholder="商品名を入力してください">
                @error('name')
                    <div class="create__error">{{ $message }}</div>
                @enderror
            </div>

            <!-- ブランド名 -->
            <div class="create__field">
                <label for="brand_name" class="create__label">ブランド名</label>
                <input type="text" name="brand_name" id="brand_name" class="create__input" value="{{ old('brand_name') }}" maxlength="100" placeholder="ブランド名を入力してください">
                @error('brand_name')
                    <div class="create__error">{{ $message }}</div>
                @enderror
            </div>

            <!-- 商品の説明 -->
            <div class="create__field">
                <label for="description" class="create__label">商品の説明</label>
                <textarea name="description" id="description" class="create__textarea" rows="5" placeholder="商品の説明を入力してください">{{ old('description') }}</textarea>
                @error('description')
                    <div class="create__error">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <!-- 販売価格 -->
        <div class="create__section">
            <h2 class="create__section-title">販売価格</h2>
            <div class="create__price-container">
                <input type="number" name="price" id="price" class="create__price-input" value="{{ old('price') }}" required min="1" placeholder="¥">
            </div>
            @error('price')
                <div class="create__error">{{ $message }}</div>
            @enderror
        </div>

        <!-- 出品ボタン -->
        <div class="create__actions">
            <button type="submit" class="create__submit-button">出品する</button>
        </div>
    </form>
</div>
@endsection

@section('js')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const imageInput = document.getElementById('image');
    const imagePreview = document.getElementById('image-preview');
    const form = document.querySelector('.create__form');

    // 画像プレビュー
    if (imageInput && imagePreview) {
        imageInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    imagePreview.innerHTML = '<img src="' + e.target.result + '" alt="商品画像プレビュー" class="create__preview-image">';
                };
                reader.readAsDataURL(file);
            } else {
                imagePreview.innerHTML = '<span class="create__image-placeholder">画像を選択してください</span>';
            }
        });
    }

    // フォーム送信の処理
    if (form) {
        form.addEventListener('submit', function(e) {
            console.log('フォーム送信が実行されました');
            const submitButton = form.querySelector('.create__submit-button');
            if (submitButton) {
                // 二重送信を防ぐ
                if (submitButton.disabled) {
                    e.preventDefault();
                    return false;
                }
                submitButton.disabled = true;
                submitButton.textContent = '送信中...';
            }
        }, false);
    }
});
</script>
@endsection

