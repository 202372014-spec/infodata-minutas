<?php

namespace Tests\Feature;

use App\Models\Minute;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MinuteDatabaseTest extends TestCase
{
    use RefreshDatabase;

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
        $payload = [
            'id' => 'INF-269999',
            'sourceService' => 'SAE-SRV-9999',
            'client' => 'Cliente de prueba',
            'date' => '2026-11-01',
            'start' => '09:00',
            'end' => '10:30',
            'used' => 1.5,
            'policy' => 'POL-TEST',
            'contracted' => 20,
            'available' => 18.5,
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
            'hours_used' => 1,
            'policy_number' => 'POL-TEST',
            'contracted_hours' => 10,
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
