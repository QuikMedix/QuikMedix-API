@extends('layouts.app')

@section('content')
    <main class="container">
        <h1>Authorize application</h1>
        <p><strong>{{ $client->name }}</strong> is requesting access to your {{ config('app.name') }} account.</p>

        @if (count($scopes) > 0)
            <p>This application will be able to:</p>
            <ul>
                @foreach ($scopes as $scope)
                    <li>{{ $scope->description }}</li>
                @endforeach
            </ul>
        @endif

        <form method="POST" action="{{ route('passport.authorizations.approve') }}">
            @csrf
            <input type="hidden" name="state" value="{{ $request->state }}">
            <input type="hidden" name="client_id" value="{{ $client->id }}">
            <input type="hidden" name="auth_token" value="{{ $authToken }}">
            <button type="submit" class="btn btn-primary">Authorize</button>
        </form>

        <form method="POST" action="{{ route('passport.authorizations.deny') }}" class="mt-3">
            @csrf
            @method('DELETE')
            <input type="hidden" name="state" value="{{ $request->state }}">
            <input type="hidden" name="client_id" value="{{ $client->id }}">
            <input type="hidden" name="auth_token" value="{{ $authToken }}">
            <button type="submit" class="btn btn-secondary">Cancel</button>
        </form>
    </main>
@endsection
