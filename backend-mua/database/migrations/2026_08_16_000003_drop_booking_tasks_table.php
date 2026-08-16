<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Fitur checklist booking dicabut. Log lama memakai alias morph 'task'
        // yang sudah tidak terdaftar, sehingga resolve entity akan throw.
        DB::table('activity_logs')->where('entity_type', 'task')->delete();

        Schema::dropIfExists('booking_tasks');
    }

    public function down(): void
    {
        Schema::create('booking_tasks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('booking_id')->index();
            $table->string('title');
            $table->boolean('is_done')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamp('done_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('booking_id')->references('id')->on('bookings')->onDelete('cascade');
        });
    }
};
