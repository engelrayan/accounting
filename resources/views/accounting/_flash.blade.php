@if(session('success'))
    <div class="ac-alert ac-alert--success" role="alert">
        <span>{{ session('success') }}</span>
        <button class="ac-alert__dismiss" data-dismiss="alert" type="button">&times;</button>
    </div>
@endif

@if(session('info'))
    <div class="ac-alert ac-alert--info" role="alert">
        <span>{{ session('info') }}</span>
        <button class="ac-alert__dismiss" data-dismiss="alert" type="button">&times;</button>
    </div>
@endif

@if(session('error'))
    <div class="ac-alert ac-alert--error" role="alert">
        <span>{{ session('error') }}</span>
        <button class="ac-alert__dismiss" data-dismiss="alert" type="button">&times;</button>
    </div>
@endif

@if($errors->any())
    <div class="ac-alert ac-alert--error" role="alert">
        <ul class="ac-alert__list">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button class="ac-alert__dismiss" data-dismiss="alert" type="button">&times;</button>
    </div>
@endif
