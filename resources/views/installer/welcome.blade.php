@extends('installer.layout')

@section('content')
    <h2>Welcome</h2>
    <p>Welcome to {{ config('app.name') }} installer. This installer will guide you through requirements, database, and administrator setup.</p>
    <p><a href="{{ route('installer.requirements') }}">Start installation</a></p>
@endsection
