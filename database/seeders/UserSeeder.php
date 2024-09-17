<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    public function run()
    {
        //utilisateur administrateur
        $admin = User::firstOrCreate([
            'email' => 'admin@example.com',
        ], [
            'nom_complet' => 'Admin User',
            'telephone' => '770000000',
            'role' => 'admin',
            'password' => Hash::make('adminpassword'),
        ]);
        $adminRole = Role::where('name', 'admin')->first();
        $admin->assignRole($adminRole); //assigner role admin

        //utilisateur étudiant
        $etudiant = User::firstOrCreate([
            'email' => 'etudiant@example.com',
        ], [
            'nom_complet' => 'Etudiant User',
            'telephone' => '771111111',
            'role' => 'etudiant',
            'password' => Hash::make('etudiantpassword'),
        ]);
        $etudiantRole = Role::where('name', 'etudiant')->first();
        $etudiant->assignRole($etudiantRole);  // Assigner le rôle étudiant

        //utilisateur client
        $client = User::firstOrCreate([
            'email' => 'client@example.com',
        ], [
            'nom_complet' => 'Client User',
            'telephone' => '772222222',
            'role' => 'client',
            'password' => Hash::make('clientpassword'),
        ]);
        $clientRole = Role::where('name', 'client')->first();
        $client->assignRole($clientRole);  // Assigner le rôle client
    }
}
