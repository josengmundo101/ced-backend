<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole = Role::where('name', 'Admin')->first();

        if ($adminRole) {
            User::updateOrCreate(
                ['email' => 'superadmin@ced.com'],
                [
                    'name' => 'Super Admin',
                    'password' => Hash::make('SuperAdmin@123'), // change to your chosen temp password
                    'role_id' => $adminRole->id,
                    'is_temporary_password' => true,
                ]
            );
        }
    }
}