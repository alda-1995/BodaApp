<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class RolesAndAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $superadminRole = Role::firstOrCreate(['name' => 'superadmin'], ['display_name' => 'SuperAdmin']);
        $organizerRole = Role::firstOrCreate(['name' => 'organizer'], ['display_name' => 'Organizador']);
        Role::firstOrCreate(['name' => 'coadmin'], ['display_name' => 'Coadministrador']);

        $superadminUser = User::firstOrCreate(
            ['email' => 'canalla.agency@gmail.com'],
            [
                'name' => 'Canalla Agency',
                'password' => Hash::make('aldair123')
            ]
        );

        // $organizer = User::firstOrCreate(
        //     ['email' => 'aldair.canalla.agency@gmail.com'],
        //     [
        //         'name' => 'Aldair Reyes Sánchez',
        //         'password' => Hash::make('aldair123')
        //     ]
        // );

        $superadminUser->roles()->syncWithoutDetaching([$superadminRole->id]);

        // $organizer->roles()->syncWithoutDetaching([$organizerRole->id]);
    }
}
