<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'jonatan@infodata.mx'],
            [
                'name' => 'Jonatan Salazar',
                'password' => 'Prototipo2026',
                'role' => 'administrator',
                'is_active' => true,
            ]
        );

        User::updateOrCreate(
            ['email' => 'maricela@infodata.mx'],
            [
                'name' => 'Maricela Cázares',
                'password' => 'Prototipo2026',
                'role' => 'consultant',
                'is_active' => true,
            ]
        );

        User::updateOrCreate(
            ['email' => 'auditoria@infodata.mx'],
            [
                'name' => 'Cuenta de Auditoría',
                'password' => 'Prototipo2026',
                'role' => 'auditor',
                'is_active' => true,
            ]
        );

        $this->call(MinuteSeeder::class);
    }
}
