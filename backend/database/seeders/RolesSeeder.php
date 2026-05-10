<?php

namespace Database\Seeders;

use App\Domains\User\Models\Role;
use Illuminate\Database\Seeder;

class RolesSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['name' => 'admin',         'label' => 'Administrator'],
            ['name' => 'manager',       'label' => 'Line Manager'],
            ['name' => 'finance_head',  'label' => 'Finance Head'],
            ['name' => 'cfo',           'label' => 'Chief Financial Officer'],
            ['name' => 'cto',           'label' => 'Chief Technology Officer'],
            ['name' => 'requester',     'label' => 'Requester'],
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role['name']], $role);
        }
    }
}
