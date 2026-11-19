<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('minutes', function (Blueprint $table): void {
            $table->unsignedInteger('elapsed_seconds')->default(0)->after('end_time');
            $table->decimal('available_before_hours', 8, 2)->nullable()->after('contracted_hours');
        });

        DB::table('minutes')
            ->select(['id', 'hours_used', 'available_hours'])
            ->orderBy('id')
            ->each(function (object $minute): void {
                DB::table('minutes')->where('id', $minute->id)->update([
                    'elapsed_seconds' => (int) round(((float) $minute->hours_used) * 3600),
                    'available_before_hours' => round(
                        (float) $minute->available_hours + (float) $minute->hours_used,
                        2,
                    ),
                ]);
            });
    }

    public function down(): void
    {
        Schema::table('minutes', function (Blueprint $table): void {
            $table->dropColumn(['elapsed_seconds', 'available_before_hours']);
        });
    }
};
