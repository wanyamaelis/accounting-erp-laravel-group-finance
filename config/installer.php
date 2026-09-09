<?php

return [
    'lock_file' => storage_path('app/private/installed'),

    'admin_role' => env('INSTALLER_ADMIN_ROLE', config('filament-shield.super_admin.name', 'super_admin')),
];
