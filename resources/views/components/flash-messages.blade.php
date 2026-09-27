@props(['dismissible' => false])

@foreach (['success' => 'alert-success', 'error' => 'alert-danger'] as $key => $class)
    @if (session()->has($key))
        <div class="alert {{ $class }} @if ($dismissible) alert-dismissible fade show @endif" role="alert">
            {{ session($key) }}
            @if ($dismissible)
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            @endif
        </div>
    @endif
@endforeach
