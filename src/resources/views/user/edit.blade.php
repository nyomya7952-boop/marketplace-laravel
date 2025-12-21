@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/edit.css') }}">
@endsection

@section('content')
<div class="edit-form__content">
    <div class="edit-form__heading">
        <h2>プロフィール設定</h2>
    </div>
    <form class="form" action="{{ route('user.update') }}" method="post" enctype="multipart/form-data">
        @csrf
        <div class="form__group">
            <div class="form__group-content">
                <div class="form__input--text">
                    <img id="profile-image-preview" src="{{ Auth::user()->profile_image_path ? asset('storage/' . Auth::user()->profile_image_path) : asset('storage/images/profile.jpg') }}" alt="プロフィール画像" style="max-width: 200px; max-height: 200px; object-fit: cover;">
                </div>
            </div>
            <div class="form__group-content">
                <div class="form__input--text">
                    <label for="profile_image_path" class="form__file-label">画像を選択する</label>
                    <input type="file" name="profile_image_path" id="profile_image_path" accept="image/*" />
                </div>
            </div>
            <div class="form__error">
                @error('profile_image_path')
                {{ $message }}
                @enderror
            </div>
        </div>
        <div class="form__group">
            <div class="form__group-title">
                <span class="form__label--item">ユーザー名</span>
            </div>
            <div class="form__group-content">
                <div class="form__input--text">
                    <input type="text" name="name" value="{{ old('name', Auth::user()->name) }}" />
                </div>
                <div class="form__error">
                    @error('name')
                    {{ $message }}
                    @enderror
                </div>
            </div>
        </div>
        <div class="form__group">
            <div class="form__group-title">
                <span class="form__label--item">郵便番号</span>
            </div>
            <div class="form__group-content">
                <div class="form__input--text">
                    <input type="text" name="postal_code" value="{{ old('postal_code', Auth::user()->postal_code) }}" />
                </div>
                <div class="form__error">
                    @error('postal_code')
                    {{ $message }}
                    @enderror
                </div>
            </div>
        </div>
        <div class="form__group">
            <div class="form__group-title">
                <span class="form__label--item">住所</span>
            </div>
            <div class="form__group-content">
                <div class="form__input--text">
                    <input type="text" name="address" value="{{ old('address', Auth::user()->address) }}" />
                </div>
                <div class="form__error">
                    @error('address')
                    {{ $message }}
                    @enderror
                </div>
            </div>
        </div>
        <div class="form__group">
            <div class="form__group-title">
                <span class="form__label--item">建物名</span>
            </div>
            <div class="form__group-content">
                <div class="form__input--text">
                    <input type="text" name="building_name" value="{{ old('building_name', Auth::user()->building_name) }}" />
                </div>
            </div>
        </div>
        <div class="form__button">
            <button class="form__button-submit" type="submit">更新する</button>
        </div>
    </form>
</div>
@endsection

@section('js')
<script>
    document.getElementById('profile_image_path').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('profile-image-preview').src = e.target.result;
            };
            reader.readAsDataURL(file);
        }
    });
</script>
@endsection
