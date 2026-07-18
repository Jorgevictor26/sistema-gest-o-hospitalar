<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('role_user', function (Blueprint $table): void {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['role_id']);

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('role_id')->references('id')->on('roles')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('role_user', function (Blueprint $table): void {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['role_id']);

            $table->foreign('user_id')->references('id')->on('roles')->cascadeOnDelete();
            $table->foreign('role_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }
};
