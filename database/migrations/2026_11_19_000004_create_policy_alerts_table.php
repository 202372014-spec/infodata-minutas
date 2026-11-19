<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('policy_alerts', function (Blueprint $table): void {
            $table->id();
            $table->string('policy_number', 60);
            $table->string('client_name');
            $table->decimal('contracted_hours', 8, 2);
            $table->decimal('remaining_hours', 8, 2);
            $table->decimal('remaining_percentage', 6, 2);
            $table->unsignedTinyInteger('threshold_percentage')->default(10);
            $table->json('recipients');
            $table->string('status', 30)->default('Pendiente');
            $table->dateTime('sent_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->unique(['policy_number', 'threshold_percentage']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('policy_alerts');
    }
};
