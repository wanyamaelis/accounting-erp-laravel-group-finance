@extends('installer.layout')

@section('content')
    <h2>Installation complete</h2>
    <p>The application has been installed. You can now <a href="{{ route('login') }}">log in</a>.</p>
@endsection
