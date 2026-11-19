<?php

namespace Tests\Feature;

use App\Models\Minute;
use App\Models\ServiceSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MinuteDatabaseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create([
            'role' => 'administrator',
            'is_active' => true,
        ]));
    }

    public function test_the_home_page_loads_minutes_from_the_database(): void
    {
        $minute = $this->createMinute();

        $this->get('/')
            ->assertOk()
            ->assertSee($minute->folio);
    }

    public function test_a_minute_can_be_queried_by_folio(): void
    {
        $minute = $this->createMinute();

        $this->getJson("/api/minutas/{$minute->folio}")
            ->assertOk()
            ->assertJsonPath('data.id', $minute->folio)
            ->assertJsonPath('data.client', 'Cliente de prueba');
    }

    public function test_a_generated_minute_is_saved_in_sqlite(): void
    {
        $session = ServiceSession::create([
            'source_service' => 'SAE-SRV-9999',
            'user_id' => auth()->id(),
            'started_at' => now()->subMinutes(90),
            'ended_at' => now(),
            'elapsed_seconds' => 5400,
            'status' => 'completed',
        ]);

        $payload = [
            'id' => 'INF-269999',
            'sessionId' => $session->id,
            'sourceService' => 'SAE-SRV-9999',
            'client' => 'Cliente de prueba',
            'used' => 1.5,
            'policy' => 'POL-TEST',
            'contracted' => 20,
            'availableBefore' => 20,
            'validity' => 'Vigente',
            'adviser' => 'Jonatan Salazar',
            'reporter' => 'Usuario de prueba',
            'recipient' => 'Responsable de prueba',
            'email' => 'responsable@example.test',
            'serviceStatus' => 'Concluido',
            'issue' => 'Solicitud de demostración.',
            'work' => 'Se verificó el funcionamiento de la minuta.',
            'pending' => 'Sin actividades pendientes.',
        ];

        $this->postJson('/api/minutas', $payload)
            ->assertCreated()
            ->assertJsonPath('data.id', 'INF-269999');

        $this->assertDatabaseHas('minutes', [
            'folio' => 'INF-269999',
            'source_service' => 'SAE-SRV-9999',
            'elapsed_seconds' => 5400,
            'hours_used' => 1.5,
            'available_before_hours' => 20,
            'available_hours' => 18.5,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'minute.created',
            'subject_id' => 'INF-269999',
        ]);
    }

    private function createMinute(): Minute
    {
        return Minute::create([
            'folio' => 'INF-260001',
            'source_service' => 'SAE-SRV-0001',
            'client_name' => 'Cliente de prueba',
            'service_date' => '2026-11-01',
            'start_time' => '09:00',
            'end_time' => '10:00',
            'elapsed_seconds' => 3600,
            'hours_used' => 1,
            'policy_number' => 'POL-TEST',
            'contracted_hours' => 10,
            'available_before_hours' => 10,
            'available_hours' => 9,
            'validity' => 'Vigente',
            'adviser' => 'Jonatan Salazar',
            'reporter' => 'Usuario de prueba',
            'recipient' => 'Responsable de prueba',
            'recipient_email' => 'responsable@example.test',
            'service_status' => 'Concluido',
            'send_status' => 'Pendiente',
            'issue' => 'Solicitud de prueba.',
            'work_performed' => 'Se verificó el sistema.',
            'pending_activity' => 'Sin actividades pendientes.',
        ]);
    }
}
