<?php

namespace Database\Seeders;

use App\Domains\User\Models\Role;
use App\Domains\User\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsersSeeder extends Seeder
{
    private const PASSWORD = 'secret';

    public function run(): void
    {
        $admin = $this->upsertUser([
            'email' => 'admin@premind.local',
            'name' => 'Aisha Admin',
            'is_active' => true,
        ], ['admin']);

        $sara = $this->upsertUser([
            'email' => 'sara.manager@premind.local',
            'name' => 'Sara Manager',
            'manager_id' => $admin->id,
            'department_id' => 1,
            'is_active' => true,
        ], ['manager']);

        $karim = $this->upsertUser([
            'email' => 'karim.finance@premind.local',
            'name' => 'Karim Finance',
            'manager_id' => $admin->id,
            'department_id' => 2,
            'is_active' => true,
        ], ['finance_head']);

        $this->upsertUser([
            'email' => 'chen.cfo@premind.local',
            'name' => 'Chen CFO',
            'manager_id' => $admin->id,
            'department_id' => 2,
            'is_active' => true,
        ], ['cfo']);

        $this->upsertUser([
            'email' => 'ravi.cto@premind.local',
            'name' => 'Ravi CTO',
            'manager_id' => $admin->id,
            'department_id' => 3,
            'is_active' => true,
        ], ['cto']);

        $this->upsertUser([
            'email' => 'ali.dev@premind.local',
            'name' => 'Ali Developer',
            'manager_id' => $sara->id,
            'department_id' => 3,
            'is_active' => true,
        ], ['requester']);

        $this->upsertUser([
            'email' => 'omar.it@premind.local',
            'name' => 'Omar IT',
            'manager_id' => $sara->id,
            'department_id' => 3,
            'is_active' => true,
        ], ['requester']);
    }

    private function upsertUser(array $attributes, array $roleNames): User
    {
        $user = User::firstOrCreate(
            ['email' => $attributes['email']],
            $attributes + ['password' => Hash::make(self::PASSWORD)]
        );

        $user->fill($attributes)->save();

        $roleIds = Role::whereIn('name', $roleNames)->pluck('id');
        $user->roles()->syncWithoutDetaching($roleIds);

        return $user;
    }
}
