<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('doctors', function (Blueprint $table): void {
            $table->string('professional_number')->nullable()->unique()->after('speciality');
            $table->boolean('is_available')->default(true)->after('commission_percentage');
        });
    }

    public function down(): void
    {
        Schema::table('doctors', function (Blueprint $table): void {
            $table->dropUnique(['professional_number']);
            $table->dropColumn(['professional_number', 'is_available']);
        });
    }
};
