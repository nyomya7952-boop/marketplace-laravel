@php
    $hasFlash = session('error') || session('success') || ($errors ?? null)?->any();
@endphp

{{-- 
  flash領域は常にDOMに存在させる（購入画面などAJAXでもJSから書き込めるようにする）
  空のときはCSSで非表示にする
--}}
<div class="flash" id="flash-container" data-has-flash="{{ $hasFlash ? '1' : '0' }}">
    @if(session('error'))
        <div class="flash__alert flash__alert--error">{{ session('error') }}</div>
    @endif

    @if(session('success'))
        <div class="flash__alert flash__alert--success">{{ session('success') }}</div>
    @endif

    @if(($errors ?? null)?->any())
        <div class="flash__alert flash__alert--error">
            <ul class="flash__list">
                @foreach($errors->all() as $error)
                    <li class="flash__list-item">{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
</div>


