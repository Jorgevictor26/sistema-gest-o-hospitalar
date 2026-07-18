<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('attendance_id')->constrained()->restrictOnDelete();
            $table->decimal('amount', 10, 2);
            $table->string('method', 30);
            $table->string('reference')->nullable();
            $table->string('notes', 500)->nullable();
            $table->foreignId('received_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('paid_at');
            $table->foreignId('voided_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('voided_at')->nullable();
            $table->string('void_reason', 500)->nullable();
            $table->timestamps();

            $table->index(['attendance_id', 'voided_at']);
            $table->index('paid_at');
        });

        DB::table('payments')->insertUsing(
            [
                'attendance_id',
                'amount',
                'method',
                'notes',
                'received_by',
                'paid_at',
                'created_at',
                'updated_at',
            ],
            DB::table('attendances')
                ->select([
                    'id',
                    'amount_paid',
                    DB::raw("'other'"),
                    DB::raw("'Pagamento inicial migrado'"),
                    'registered_by',
                    'created_at',
                    'created_at as payment_created_at',
                    'updated_at',
                ])
                ->where('amount_paid', '>', 0)
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
