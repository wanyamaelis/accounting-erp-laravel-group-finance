@extends('installer.layout')

@section('content')
    <h2>Administrator account</h2>

    @if($errors->any())
        <div class="error">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('installer.administrator.store') }}">
        @csrf
        <label>Name
            <input name="name" value="Administrator" required />
        </label>
        <label>Email
            <input name="email" type="email" value="admin@example.com" required />
        </label>
        <label>Password
            <input name="password" type="password" required />
        </label>
        <label>Confirm Password
            <input name="password_confirmation" type="password" required />
        </label>
        <div class="actions">
            <button type="submit">Save & Continue</button>
        </div>
    </form>
@endsection
