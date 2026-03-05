<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void {
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Cash, GCash, Card
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Seed default payment methods
        DB::table('payment_methods')->insert([
            ['name' => 'Cash',  'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'GCash', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Card',  'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void {
        Schema::dropIfExists('payment_methods');
    }
};