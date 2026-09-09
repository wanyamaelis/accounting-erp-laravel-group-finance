@extends('installer.layout')

@section('content')
    <h2>System Requirements</h2>

    <h3>PHP version</h3>
    <p>Required: {{ $checks['php_version_required'] }}</p>
    <p>Detected: {{ PHP_VERSION }} — <strong>{{ $checks['php_version'] ? 'OK' : 'FAIL' }}</strong></p>

    <h3>Extensions</h3>
    <ul>
        @foreach($checks['extensions'] as $ext => $ok)
            <li>{{ $ext }}: <strong>{{ $ok ? 'OK' : 'MISSING' }}</strong></li>
        @endforeach
    </ul>

    <h3>Writable paths</h3>
    <ul>
        @foreach($checks['writable'] as $path => $ok)
            <li>{{ $path }}: <strong>{{ $ok ? 'Writable' : 'Not writable' }}</strong></li>
        @endforeach
    </ul>

    <h3>Vendor</h3>
    <p>vendor/autoload.php present: <strong>{{ $checks['vendor_exists'] ? 'Yes' : 'No' }}</strong></p>

    @if(! $checks['vendor_exists'])
        <div class="error">Vendor directory missing. For cPanel deployments without SSH, build locally and upload vendor/ (see documentation).</div>
    @endif

    <div class="actions">
        <a href="{{ route('installer.database') }}">Continue to Database</a>
    </div>
@endsection
