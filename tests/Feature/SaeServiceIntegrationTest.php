<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class SaeServiceIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['minutes.imported_services' => true]);
    }

    public function test_imported_services_supply_authoritative_data_and_share_a_persistent_policy_balance(): void
    {
        $first = $this->source('SAE-001');
        $second = $this->source('SAE-002');
        $this->import([$first, $second]);

        $user = User::factory()->create(['role' => 'consultant', 'is_active' => true]);
        $this->actingAs($user)->getJson('/api/servicios')
            ->assertOk()
            ->assertJsonPath('data.0.id', 'SAE-001')
            ->assertJsonPath('data.0.client', 'Cliente de prueba')
            ->assertJsonPath('data.0.available', 6);

        $sessionId = $this->postJson('/api/servicios/SAE-001/iniciar')->assertCreated()->json('data.id');
        $this->postJson('/api/servicios/SAE-001/finalizar')->assertOk();

        $payload = [
            'sessionId' => $sessionId,
            'sourceService' => 'SAE-001',
            'used' => 1.25,
            'id' => 'FOLIO-MANUAL',
            'client' => 'Nombre alterado',
            'availableBefore' => 999,
        ];
        $created = $this->postJson('/api/minutas', $payload)
            ->assertCreated()
            ->assertJsonPath('data.client', 'Cliente de prueba')
            ->assertJsonPath('data.available', 4.75)
            ->assertJsonPath('data.adviser', $user->name);
        $this->assertStringStartsWith('INF-SAE-', $created->json('data.id'));

        $this->postJson('/api/minutas', $payload)->assertUnprocessable();
        $this->postJson('/api/servicios/SAE-001/iniciar')->assertNotFound();
        $this->import([$first, $second]);

        $this->getJson('/api/servicios')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', 'SAE-002')
            ->assertJsonPath('data.0.available', 4.75);

        $secondSession = $this->postJson('/api/servicios/SAE-002/iniciar')->assertCreated()->json('data.id');
        $this->postJson('/api/servicios/SAE-002/finalizar')->assertOk();
        $this->postJson('/api/minutas', [
            'sessionId' => $secondSession,
            'sourceService' => 'SAE-002',
            'used' => 5,
        ])->assertUnprocessable()->assertJsonValidationErrors('used');
        $this->assertDatabaseCount('minutes', 1);
    }

    public function test_another_consultant_cannot_use_the_imported_services_timer(): void
    {
        $this->import([$this->source('SAE-003')]);

        $owner = User::factory()->create(['role' => 'consultant', 'is_active' => true]);
        $this->actingAs($owner)->postJson('/api/servicios/SAE-003/iniciar')->assertCreated();

        $other = User::factory()->create(['role' => 'consultant', 'is_active' => true]);
        $this->actingAs($other)->getJson('/api/servicios')->assertJsonCount(0, 'data');
        $this->postJson('/api/servicios/SAE-003/finalizar')->assertForbidden();
        $this->postJson('/api/servicios/NO-IMPORTADO/iniciar')->assertNotFound();
    }

    public function test_the_snapshot_upload_requires_admin_and_reports_only_counts(): void
    {
        $send = fn () => $this->post('/api/servicios/importar', [
            'file' => UploadedFile::fake()->createWithContent(
                'servicios.json',
                json_encode([$this->source('SAE-004')], JSON_THROW_ON_ERROR),
            ),
        ], ['Accept' => 'application/json']);

        $this->actingAs(User::factory()->create(['role' => 'consultant', 'is_active' => true]));
        $send()->assertForbidden();

        $this->actingAs(User::factory()->create(['role' => 'administrator', 'is_active' => true]));
        $send()->assertOk()->assertJsonPath('data.services', 1)->assertJsonPath('data.policies', 1);
        $this->assertDatabaseCount('sae_services', 1);
        $this->assertDatabaseHas('audit_logs', ['action' => 'sae.services_imported']);
    }

    private function import(array $rows): void
    {
        $path = tempnam(sys_get_temp_dir(), 'sae-test-');

        try {
            file_put_contents($path, json_encode($rows, JSON_THROW_ON_ERROR));
            $this->artisan('sae:import', ['file' => $path])->assertSuccessful();
        } finally {
            unlink($path);
        }
    }

    private function source(string $id): array
    {
        return [
            'source_service' => $id,
            'source_folio' => $id,
            'client_code' => 'PRUEBA-001',
            'client_name' => 'Cliente de prueba',
            'policy_number' => 'POL-001',
            'policy_date' => '2026-11-01',
            'contracted_hours' => 10,
            'available_hours' => 6,
            'recipient_email' => 'contacto@example.test',
            'service_status' => 'Pendiente',
        ];
    }
}
