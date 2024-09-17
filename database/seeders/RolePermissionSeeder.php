<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    public function run()
    {
        $adminRole = Role::create(['name' => 'admin']);
        $etudiantRole = Role::create(['name' => 'etudiant']);
        $cleintRole = Role::create(['name' => 'client']);
        // Réinitialiser les rôles et permissions mis en cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Créer les permissions
        $permissions = [
            'voir utilisateurs',
            'créer utilisateurs',
            'éditer utilisateurs',
            'supprimer utilisateurs',
            'voir cours',
            'créer cours',
            'éditer cours',
            'supprimer cours',
            'ajouter produit',
            'commander',
            'voir commandes',
            'créer commandes',
            'payer une commande',
            'supprimer une commande',
            'voir factures',
        ];


        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }
        // Créer ou trouver les rôles
        // $roleAdmin = Role::findOrCreate('administrateur', 'api');
        // $roleClient = Role::findOrCreate('client', 'api');
        // $roleEtudiant = Role::findOrCreate('étudiant', 'api');

        $adminRole->givePermissionTo(Permission::all());
        //role etudiant
        $etudiantRole->givePermissionTo([
            'voir cours',
        ]);
        // Role client
        $cleintRole->givePermissionTo([
            'commander',
            'voir commandes',
            'créer commandes',
            'payer une commande',
            'supprimer une commande',
            'voir factures',
        ]);
    }

}
