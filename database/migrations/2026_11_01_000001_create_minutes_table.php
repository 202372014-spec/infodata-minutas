<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('minutes', function (Blueprint $table) {
            $table->id();
            $table->string('folio')->unique();
            $table->string('source_service')->nullable()->unique();
            $table->string('client_name');
            $table->date('service_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->decimal('hours_used', 6, 2);
            $table->string('policy_number');
            $table->decimal('contracted_hours', 8, 2);
            $table->decimal('available_hours', 8, 2);
            $table->string('validity', 30)->default('Vigente');
            $table->string('adviser');
            $table->string('reporter');
            $table->string('recipient');
            $table->string('recipient_email');
            $table->string('service_status', 60)->default('Concluido');
            $table->string('send_status', 30)->default('Pendiente');
            $table->timestamp('sent_at')->nullable();
            $table->text('issue');
            $table->text('work_performed');
            $table->text('pending_activity')->nullable();
            $table->timestamps();

            $table->index(['service_date', 'send_status']);
            $table->index('policy_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('minutes');
    }
};
