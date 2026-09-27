@props(['title' => 'Please fix the following:'])

@if ($errors->any())
    <div class="alert alert-danger" role="alert">
        <b>{{ $title }}</b>
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
