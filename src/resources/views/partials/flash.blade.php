@if(session('error') || session('success') || ($errors ?? null)?->any())
    <div class="flash">
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
@endif


