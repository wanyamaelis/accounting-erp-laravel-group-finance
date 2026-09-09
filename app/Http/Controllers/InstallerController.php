<?php

namespace App\Http\Controllers;

use App\Support\Installer;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;

class InstallerController extends Controller
{
    public function welcome(Installer $installer)
    {
        if ($installer->installed()) {
            abort(404);
        }

        return view('installer.welcome');
    }

    public function requirements(Installer $installer)
    {
        if ($installer->installed()) {
            abort(404);
        }

        $composer = base_path('composer.json');
        $requiredPhp = null;
        if (file_exists($composer)) {
            $data = json_decode(file_get_contents($composer), true);
            $requiredPhp = $data['require']['php'] ?? null;
        }

        $checks = [
            'php_version' => version_compare(PHP_VERSION, '8.5.0', '>='),
            'php_version_required' => $requiredPhp ?? '^8.5',
            'extensions' => [],
            'writable' => [],
            'vendor_exists' => file_exists(base_path('vendor/autoload.php')),
        ];

        $extensions = ['ctype', 'curl', 'dom', 'fileinfo', 'mbstring', 'openssl', 'pdo', 'tokenizer', 'xml', 'json', 'bcmath', 'zip'];
        foreach ($extensions as $ext) {
            $checks['extensions'][$ext] = extension_loaded($ext);
        }

        $writable = [
            storage_path() => is_writable(storage_path()),
            storage_path('app') => is_writable(storage_path('app')),
            storage_path('framework') => is_writable(storage_path('framework')),
            storage_path('logs') => is_writable(storage_path('logs')),
            base_path('bootstrap/cache') => is_writable(base_path('bootstrap/cache')),
        ];

        $checks['writable'] = $writable;

        return view('installer.requirements', compact('checks'));
    }

    public function showDatabase(Installer $installer)
    {
        if ($installer->installed()) {
            abort(404);
        }

        return view('installer.database');
    }

    public function testDatabase(Request $request, Installer $installer)
    {
        if ($installer->installed()) {
            abort(404);
        }

        $data = $request->validate([
            'DB_CONNECTION' => 'required|string|in:mysql,pgsql,sqlite',
            'DB_HOST' => 'nullable|string',
            'DB_PORT' => 'nullable|numeric',
            'DB_DATABASE' => 'required|string',
            'DB_USERNAME' => 'nullable|string',
            'DB_PASSWORD' => 'nullable|string',
        ]);

        $ok = $installer->testDatabaseConnection($data);

        if (! $ok) {
            return back()->withErrors(['db' => 'Unable to connect using provided database credentials.']);
        }

        Session::put('installer.db', $data);

        return redirect()->route('installer.administrator');
    }

    public function showAdministrator(Installer $installer)
    {
        if ($installer->installed()) {
            abort(404);
        }

        return view('installer.administrator');
    }

    public function storeAdministrator(Request $request, Installer $installer)
    {
        if ($installer->installed()) {
            abort(404);
        }

        $v = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'password' => 'required|string|min:12|confirmed',
        ]);

        $v->validate();

        Session::put('installer.admin', [
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'password' => $request->input('password'),
        ]);

        return redirect()->route('installer.install');
    }

    public function install(Installer $installer)
    {
        if ($installer->installed()) {
            abort(404);
        }

        $db = Session::get('installer.db', []);
        $admin = Session::get('installer.admin', []);

        if (empty($db) || empty($admin)) {
            return redirect()->route('installer.welcome')->withErrors('Installation state expired. Please retry.');
        }

        $appKey = $installer->generateKey();

        $envValues = [
            'APP_ENV' => 'production',
            'APP_DEBUG' => 'false',
            'APP_KEY' => $appKey,
            'APP_URL' => request()->getSchemeAndHttpHost(),
            'DB_CONNECTION' => $db['DB_CONNECTION'],
            'DB_HOST' => $db['DB_HOST'] ?? '127.0.0.1',
            'DB_PORT' => $db['DB_PORT'] ?? ($db['DB_CONNECTION'] === 'pgsql' ? 5432 : 3306),
            'DB_DATABASE' => $db['DB_DATABASE'],
            'DB_USERNAME' => $db['DB_USERNAME'] ?? '',
            'DB_PASSWORD' => $db['DB_PASSWORD'] ?? '',
        ];

        $installer->writeEnv($envValues);

        Artisan::call('config:clear');

        Config::set('installer.admin', [
            'name' => $admin['name'],
            'email' => $admin['email'],
            'password' => $admin['password'],
        ]);

        try {
            $installer->runMigrationsAndSeed();
        } catch (\Throwable $e) {
            return back()->withErrors(['migrate' => 'Migrations / seeding failed: ' . $e->getMessage()]);
        }

        try {
            $installer->markInstalled();
        } catch (\Throwable $e) {
            return back()->withErrors(['lock' => 'Failed to finalize installation: ' . $e->getMessage()]);
        }

        Artisan::call('config:clear');
        Artisan::call('route:clear');
        Artisan::call('view:clear');
        Artisan::call('cache:clear');

        Session::forget('installer.admin');
        Session::forget('installer.db');

        return redirect()->to(route('login'));
    }

    public function complete(Installer $installer)
    {
        if (! $installer->installed()) {
            return redirect()->route('installer.welcome');
        }

        return view('installer.complete');
    }
}
