<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationAndAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_guest_cannot_query_minutes(): void
    {
        $this->getJson('/api/minutas')->assertUnauthorized();
    }

    public function test_a_guest_opening_a_protected_route_is_returned_to_login(): void
    {
        $this->get('/api/minutas')->assertRedirect('/');
    }

    public function test_an_active_user_can_start_a_real_session(): void
    {
        $user = User::factory()->create([
            'email' => 'jonatan@infodata.mx',
            'password' => 'Prototipo2026',
            'role' => 'administrator',
            'is_active' => true,
        ]);

        $this->postJson('/api/acceso', [
            'email' => 'jonatan@infodata.mx',
            'password' => 'Prototipo2026',
            'remember' => true,
        ])
            ->assertOk()
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.role', 'administrator')
            ->assertJsonPath('data.permissions.manageUsers', true);

        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'auth.login_succeeded',
        ]);
    }

    public function test_an_invalid_login_is_rejected_and_audited(): void
    {
        User::factory()->create([
            'email' => 'jonatan@infodata.mx',
            'password' => 'Prototipo2026',
            'role' => 'administrator',
        ]);

        $this->postJson('/api/acceso', [
            'email' => 'jonatan@infodata.mx',
            'password' => 'incorrecta',
        ])->assertUnprocessable();

        $this->assertGuest();
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => null,
            'action' => 'auth.login_failed',
        ]);
    }

    public function test_an_auditor_cannot_create_minutes(): void
    {
        $auditor = User::factory()->create([
            'role' => 'auditor',
            'is_active' => true,
        ]);

        $this->actingAs($auditor)
            ->postJson('/api/minutas', [])
            ->assertForbidden();
    }

    public function test_only_an_administrator_can_create_users(): void
    {
        $administrator = User::factory()->create([
            'role' => 'administrator',
            'is_active' => true,
        ]);

        $this->actingAs($administrator)
            ->postJson('/api/usuarios', [
                'name' => 'Consultora de prueba',
                'email' => 'consultora@infodata.mx',
                'role' => 'consultant',
                'password' => 'Temporal2026',
            ])
            ->assertCreated()
            ->assertJsonPath('data.role', 'consultant');

        $this->assertDatabaseHas('users', [
            'email' => 'consultora@infodata.mx',
            'role' => 'consultant',
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'user.created',
        ]);
    }
}
