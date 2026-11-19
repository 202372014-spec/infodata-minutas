<?php

namespace Tests\Feature;

use App\Mail\MinuteServiceMail;
use App\Mail\PolicyThresholdAlertMail;
use App\Models\Minute;
use App\Models\ServiceSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ServiceTimingAndDeliveryTest extends TestCase
{
    use RefreshDatabase;

    private User $administrator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->administrator = User::factory()->create([
            'role' => 'administrator',
            'is_active' => true,
        ]);
        $this->actingAs($this->administrator);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_the_service_timer_is_persisted_and_stopped_by_the_server(): void
    {
        Carbon::setTestNow('2026-11-19 09:00:00');

        $start = $this->postJson('/api/servicios/SAE-SRV-TIMER/iniciar')
            ->assertCreated()
            ->assertJsonPath('data.status', 'running');

        $sessionId = $start->json('data.id');

        Carbon::setTestNow('2026-11-19 10:30:15');

        $this->postJson('/api/servicios/SAE-SRV-TIMER/finalizar')
            ->assertOk()
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonPath('data.elapsedSeconds', 5415);

        $this->assertDatabaseHas('service_sessions', [
            'id' => $sessionId,
            'elapsed_seconds' => 5415,
            'status' => 'completed',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'service.stopped',
            'subject_id' => (string) $sessionId,
        ]);
    }

    public function test_a_minute_keeps_real_and_discounted_time_and_alerts_only_once(): void
    {
        Mail::fake();

        $first = $this->completedSession('SAE-SRV-ALERT-1', 5400);
        $this->postJson('/api/minutas', $this->minutePayload($first, 'INF-261901', 'SAE-SRV-ALERT-1'))
            ->assertCreated()
            ->assertJsonPath('data.actualSeconds', 5400)
            ->assertJsonPath('data.discountedHours', 0.5)
            ->assertJsonPath('data.available', 1)
            ->assertJsonPath('data.remainingPercentage', 10)
            ->assertJsonPath('alert.percentage', 10);

        $this->assertDatabaseHas('policy_alerts', [
            'policy_number' => 'POL-10-PCT',
            'remaining_percentage' => 10,
        ]);
        $this->assertDatabaseHas('email_deliveries', [
            'type' => 'Alerta de póliza',
            'policy_number' => 'POL-10-PCT',
        ]);
        Mail::assertSent(PolicyThresholdAlertMail::class, 1);

        $second = $this->completedSession('SAE-SRV-ALERT-2', 1800);
        $this->postJson('/api/minutas', $this->minutePayload($second, 'INF-261902', 'SAE-SRV-ALERT-2'))
            ->assertCreated();

        $this->assertDatabaseCount('policy_alerts', 1);
        Mail::assertSent(PolicyThresholdAlertMail::class, 1);
    }

    public function test_a_minute_pdf_is_generated_with_the_new_time_fields(): void
    {
        $minute = $this->storedMinute();

        $response = $this->get("/api/minutas/{$minute->folio}/pdf")
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->assertStringStartsWith('%PDF', (string) $response->getContent());
    }

    public function test_the_email_contains_service_details_and_attaches_the_pdf(): void
    {
        Mail::fake();
        $minute = $this->storedMinute();

        $this->postJson("/api/minutas/{$minute->folio}/enviar")
            ->assertOk()
            ->assertJsonPath('data.status', 'Simulado');

        $this->assertDatabaseHas('email_deliveries', [
            'minute_id' => $minute->id,
            'type' => 'Minuta terminada',
            'status' => 'Simulado',
            'has_attachment' => true,
        ]);
        $this->assertDatabaseHas('minutes', [
            'id' => $minute->id,
            'send_status' => 'Simulada',
        ]);
        $deliveryBody = (string) $minute->emailDeliveries()->first()->body;
        $this->assertStringContainsString('Servicio solicitado:', $deliveryBody);
        $this->assertStringContainsString('Tiempo real:', $deliveryBody);
        $this->assertStringContainsString('Tiempo descontado de la póliza:', $deliveryBody);

        Mail::assertSent(MinuteServiceMail::class, function (MinuteServiceMail $mail): bool {
            return $mail->hasTo('responsable@example.test')
                && count($mail->attachments()) === 1;
        });
    }

    private function completedSession(string $sourceService, int $elapsedSeconds): ServiceSession
    {
        return ServiceSession::create([
            'source_service' => $sourceService,
            'user_id' => $this->administrator->id,
            'started_at' => now()->subSeconds($elapsedSeconds),
            'ended_at' => now(),
            'elapsed_seconds' => $elapsedSeconds,
            'status' => 'completed',
        ]);
    }

    private function minutePayload(ServiceSession $session, string $folio, string $sourceService): array
    {
        return [
            'id' => $folio,
            'sessionId' => $session->id,
            'sourceService' => $sourceService,
            'client' => 'Cliente con póliza crítica',
            'used' => 0.5,
            'policy' => 'POL-10-PCT',
            'contracted' => 10,
            'availableBefore' => 1.5,
            'validity' => 'Vigente',
            'adviser' => 'Jonatan Salazar',
            'reporter' => 'Usuario de prueba',
            'recipient' => 'Responsable de prueba',
            'email' => 'responsable@example.test',
            'serviceStatus' => 'Concluido',
            'issue' => 'Servicio solicitado para probar el correo.',
            'work' => 'Resultado obtenido satisfactoriamente.',
            'pending' => 'Sin actividades pendientes.',
        ];
    }

    private function storedMinute(): Minute
    {
        return Minute::create([
            'folio' => 'INF-261999',
            'source_service' => 'SAE-SRV-PDF',
            'client_name' => 'Cliente de PDF',
            'service_date' => '2026-11-19',
            'start_time' => '09:00:00',
            'end_time' => '10:30:00',
            'elapsed_seconds' => 5400,
            'hours_used' => 1.25,
            'policy_number' => 'POL-PDF',
            'contracted_hours' => 10,
            'available_before_hours' => 5,
            'available_hours' => 3.75,
            'validity' => 'Vigente',
            'adviser' => 'Jonatan Salazar',
            'reporter' => 'Usuario de prueba',
            'recipient' => 'Responsable de prueba',
            'recipient_email' => 'responsable@example.test',
            'service_status' => 'Concluido',
            'send_status' => 'Pendiente',
            'issue' => 'Servicio solicitado para validar el PDF.',
            'work_performed' => 'Resultado obtenido y verificado.',
            'pending_activity' => 'Sin actividades pendientes.',
        ]);
    }
}
