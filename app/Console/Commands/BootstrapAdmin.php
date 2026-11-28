<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class BootstrapAdmin extends Command
{
    protected $signature = 'minutas:admin-inicial';

    protected $description = 'Crea el primer administrador con credenciales de configuración';

    public function handle(): int
    {
        if (User::query()->where('role', 'administrator')->where('is_active', true)->exists()) {
            return self::SUCCESS;
        }

        $name = trim((string) env('BOOTSTRAP_ADMIN_NAME'));
        $email = Str::lower(trim((string) env('BOOTSTRAP_ADMIN_EMAIL')));
        $password = (string) env('BOOTSTRAP_ADMIN_PASSWORD');

        if ($name === '' || Str::length($name) > 255 || strlen($email) > 255 ||
            ! filter_var($email, FILTER_VALIDATE_EMAIL) ||
            strlen($password) < 12 || strlen($password) > 72) {
            $this->error('Configura el nombre, correo y contraseña de 12 a 72 bytes del administrador inicial.');

            return self::FAILURE;
        }

        $admin = User::query()->firstOrCreate(
            ['email' => $email],
            ['name' => $name, 'password' => $password, 'role' => 'administrator', 'is_active' => true],
        );

        if ($admin->role !== 'administrator' || ! $admin->is_active) {
            $this->error('El correo configurado pertenece a una cuenta existente sin acceso administrativo.');

            return self::FAILURE;
        }

        $this->info('Administrador inicial disponible.');

        return self::SUCCESS;
    }
}
