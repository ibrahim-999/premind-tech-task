<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            RolesSeeder::class,
            UsersSeeder::class,
            WorkflowSeeder::class,
            DemoPurchaseOrdersSeeder::class,
        ]);

        $this->printCredentials();
    }

    private function printCredentials(): void
    {
        if (! $this->command) {
            return;
        }

        $this->command->newLine();
        $this->command->info('==============================================================');
        $this->command->info(' Premind seeded credentials (password = "secret" for all)');
        $this->command->info('==============================================================');
        $this->command->info(' admin@premind.local          → admin');
        $this->command->info(' sara.manager@premind.local   → manager  (manager of Ali, Omar)');
        $this->command->info(' karim.finance@premind.local  → finance_head');
        $this->command->info(' chen.cfo@premind.local       → cfo');
        $this->command->info(' ravi.cto@premind.local       → cto');
        $this->command->info(' ali.dev@premind.local        → requester (reports to Sara)');
        $this->command->info(' omar.it@premind.local        → requester (reports to Sara)');
        $this->command->info('==============================================================');
        $this->command->newLine();
    }
}
