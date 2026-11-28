<?php

namespace App\Console\Commands;

use App\Models\SaePolicy;
use App\Models\SaeService;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use JsonException;

class ImportSaeServices extends Command
{
    protected $signature = 'sae:import {file : Archivo JSON normalizado} {--json : Emite los conteos en JSON}';

    protected $description = 'Importa servicios de un respaldo de Aspel SAE de solo lectura';

    public function handle(): int
    {
        $path = $this->argument('file');
        $contents = is_file($path) && is_readable($path) ? file_get_contents($path) : false;

        if ($contents === false) {
            $this->error('No se pudo leer el archivo de importación.');

            return self::FAILURE;
        }

        if (str_starts_with($contents, "\xEF\xBB\xBF")) {
            $contents = substr($contents, 3);
        }

        try {
            $rows = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            $this->error('El archivo de importación no contiene JSON válido.');

            return self::FAILURE;
        }

        $rules = [
            'source_service' => ['required', 'string', 'regex:/\\A[A-Za-z0-9][A-Za-z0-9._-]{0,59}\\z/', 'distinct:ignore_case'],
            'source_folio' => ['required', 'string', 'max:60'],
            'source_date' => ['nullable', 'date_format:Y-m-d'],
            'client_code' => ['required', 'string', 'max:60'],
            'client_name' => ['required', 'string', 'max:180'],
            'policy_number' => ['required', 'string', 'max:60'],
            'policy_date' => ['nullable', 'date_format:Y-m-d'],
            'contracted_hours' => ['required', 'numeric', 'decimal:0,2', 'between:0.01,999999.99'],
            'available_hours' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'lte:rows.*.contracted_hours'],
            'adviser' => ['nullable', 'string', 'max:150'],
            'reporter' => ['nullable', 'string', 'max:150'],
            'recipient' => ['nullable', 'string', 'max:150'],
            'recipient_email' => ['nullable', 'email', 'max:180'],
            'service_status' => ['required', 'string', 'max:60'],
            'issue' => ['nullable', 'string', 'max:10000'],
            'work' => ['nullable', 'string', 'max:10000'],
            'pending' => ['nullable', 'string', 'max:10000'],
        ];

        $validator = Validator::make(['rows' => $rows], [
            'rows' => ['required', 'array', 'list'],
            'rows.*' => ['required', 'array'],
            ...Arr::mapWithKeys($rules, fn (array $rule, string $field) => ["rows.*.{$field}" => $rule]),
        ]);

        if ($validator->fails()) {
            $this->error('Se omitió la importación por campos inválidos: '.implode(', ', array_slice($validator->errors()->keys(), 0, 10)));

            return self::FAILURE;
        }

        $policies = [];
        $services = [];

        foreach ($validator->validated()['rows'] as $row) {
            $policy = Arr::only($row, ['client_code', 'policy_number', 'policy_date', 'contracted_hours', 'available_hours']);
            $policy['client_code'] = mb_strtoupper(trim($policy['client_code']));
            $policy['policy_number'] = mb_strtoupper(trim($policy['policy_number']));
            $policy['policy_date'] = $policy['policy_date'] ?? null;
            $policy['contracted_hours'] = round((float) $policy['contracted_hours'], 2);
            $policy['available_hours'] = round((float) $policy['available_hours'], 2);
            $policyId = hash('sha256', json_encode([
                $policy['client_code'], $policy['policy_number'], $policy['policy_date'],
            ], JSON_THROW_ON_ERROR));

            if (isset($policies[$policyId]) && $policies[$policyId] !== $policy) {
                $this->error('Se omitió la importación porque una póliza tiene saldos o términos distintos en el mismo archivo.');

                return self::FAILURE;
            }

            $policies[$policyId] = $policy;
            $services[$row['source_service']] = [
                'policy_id' => $policyId,
                'source_folio' => $row['source_folio'],
                'source_date' => $row['source_date'] ?? null,
                'client_name' => $row['client_name'],
                'adviser' => $row['adviser'] ?? null,
                'reporter' => $row['reporter'] ?? null,
                'recipient' => $row['recipient'] ?? null,
                'recipient_email' => $row['recipient_email'] ?? null,
                'service_status' => $row['service_status'],
                'issue' => $row['issue'] ?? null,
                'work' => $row['work'] ?? null,
                'pending' => $row['pending'] ?? null,
            ];
        }

        DB::transaction(function () use ($policies, $services): void {
            foreach ($policies as $id => $policy) {
                SaePolicy::query()->firstOrCreate(['id' => $id], $policy);
            }

            foreach ($services as $id => $service) {
                SaeService::query()->updateOrCreate(['id' => $id], $service);
            }
        }, 3);

        if ($this->option('json')) {
            $this->line(json_encode(['services' => count($services), 'policies' => count($policies)], JSON_THROW_ON_ERROR));
        } else {
            $this->info(sprintf('Importación completada: %d servicios y %d pólizas.', count($services), count($policies)));
        }

        return self::SUCCESS;
    }
}
