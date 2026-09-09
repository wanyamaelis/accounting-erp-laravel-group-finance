<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $installerAdmin = config('installer.admin', null);

        if (is_array($installerAdmin) && ! empty($installerAdmin['email'])) {
            $adminEmail = $installerAdmin['email'];
            $adminName = $installerAdmin['name'] ?? 'Admin User';
            $adminPassword = $installerAdmin['password'] ?? Str::random(12);
        } else {
            $adminEmail = 'admin@example.com';
            $adminName = 'Admin User';
            $adminPassword = Str::random(12);
        }

        $adminUser = User::updateOrCreate(
            ['email' => $adminEmail],
            [
                'name' => $adminName,
                'password' => Hash::make($adminPassword),
                'email_verified_at' => now(),
            ],
        );

        $team = Team::firstOrFail();
        $adminUser->forceFill(['current_team_id' => $team->id])->save();
        $adminUser->teams()->syncWithoutDetaching([$team->id]);

        setPermissionsTeamId($team->id);
        $role = Role::where('name', 'super_admin')->firstOrFail();
        $adminUser->assignRole($role);

        // Only echo password when seeding via CLI and installer didn't supply it
        if (! is_array($installerAdmin) || empty($installerAdmin['password'])) {
            echo "Admin password: {$adminPassword}\n";
        }
    }
}
