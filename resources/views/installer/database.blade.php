@extends('installer.layout')

@section('content')
    <h2>Database configuration</h2>

    @if($errors->any())
        <div class="error">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('installer.database.test') }}">
        @csrf
        <label>Driver
            <select name="DB_CONNECTION">
                <option value="mysql">MySQL</option>
                <option value="pgsql">PostgreSQL</option>
                <option value="sqlite">SQLite</option>
            </select>
        </label>
        <label>Host
            <input name="DB_HOST" value="127.0.0.1" />
        </label>
        <label>Port
            <input name="DB_PORT" />
        </label>
        <label>Database
            <input name="DB_DATABASE" required />
        </label>
        <label>Username
            <input name="DB_USERNAME" />
        </label>
        <label>Password
            <input name="DB_PASSWORD" type="password" />
        </label>
        <div class="actions">
            <button type="submit">Test & Continue</button>
        </div>
    </form>
@endsection
