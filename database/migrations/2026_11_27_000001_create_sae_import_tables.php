<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sae_policies', function (Blueprint $table): void {
            $table->string('id', 64)->primary();
            $table->string('client_code', 60);
            $table->string('policy_number', 60);
            $table->date('policy_date')->nullable();
            $table->decimal('contracted_hours', 8, 2);
            $table->decimal('available_hours', 8, 2);
            $table->timestamps();
        });

        Schema::create('sae_services', function (Blueprint $table): void {
            $table->string('id', 60)->primary();
            $table->string('policy_id', 64);
            $table->foreign('policy_id')->references('id')->on('sae_policies');
            $table->string('source_folio', 60);
            $table->date('source_date')->nullable();
            $table->string('client_name', 180);
            $table->string('adviser', 150)->nullable();
            $table->string('reporter', 150)->nullable();
            $table->string('recipient', 150)->nullable();
            $table->string('recipient_email', 180)->nullable();
            $table->string('service_status', 60);
            $table->text('issue')->nullable();
            $table->text('work')->nullable();
            $table->text('pending')->nullable();
            $table->boolean('enabled')->default(true);
            $table->timestamps();
        });

        Schema::table('minutes', function (Blueprint $table): void {
            $table->string('sae_policy_id', 64)->nullable();
        });

        Schema::table('policy_alerts', function (Blueprint $table): void {
            $table->string('policy_identity', 64)->nullable();
        });

        DB::table('policy_alerts')->select('id', 'policy_number')->chunkById(100, function ($alerts): void {
            foreach ($alerts as $alert) {
                DB::table('policy_alerts')->where('id', $alert->id)->update([
                    'policy_identity' => hash('sha256', $alert->policy_number),
                ]);
            }
        });

        Schema::table('policy_alerts', function (Blueprint $table): void {
            $table->unique(['policy_identity', 'threshold_percentage']);
            $table->dropUnique(['policy_number', 'threshold_percentage']);
        });
    }

    public function down(): void
    {
        Schema::table('policy_alerts', function (Blueprint $table): void {
            $table->unique(['policy_number', 'threshold_percentage']);
            $table->dropUnique(['policy_identity', 'threshold_percentage']);
            $table->dropColumn('policy_identity');
        });
        Schema::table('minutes', fn (Blueprint $table) => $table->dropColumn('sae_policy_id'));
        Schema::dropIfExists('sae_services');
        Schema::dropIfExists('sae_policies');
    }
};
