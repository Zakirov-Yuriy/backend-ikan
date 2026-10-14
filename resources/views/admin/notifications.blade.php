{{-- Сообщение об успехе после сохранения и ошибки формы --}}
@if(session('success'))
    <div class="alert alert-success"><span>{{ session('success') }}</span></div>
@endif
@if($errors->any())
    @foreach($errors->all() as $error)
        <div class="alert alert-danger"><span>{{ $error }}</span></div>
    @endforeach
@endif
