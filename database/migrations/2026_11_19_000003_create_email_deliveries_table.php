<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_deliveries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('minute_id')->nullable()->constrained()->nullOnDelete();
            $table->string('policy_number', 60)->nullable();
            $table->string('type', 40);
            $table->json('recipients');
            $table->json('copies')->nullable();
            $table->string('subject');
            $table->longText('body');
            $table->boolean('has_attachment')->default(false);
            $table->string('status', 30)->default('Pendiente');
            $table->text('error_message')->nullable();
            $table->dateTime('sent_at')->nullable();
            $table->timestamps();

            $table->index(['type', 'status']);
            $table->index('policy_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_deliveries');
    }
};
