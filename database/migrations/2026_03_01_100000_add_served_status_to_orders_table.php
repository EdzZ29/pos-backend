<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void {
        DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('pending','preparing','completed','cancelled','served') NOT NULL DEFAULT 'pending'");
    }

    public function down(): void {
        DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('pending','preparing','completed','cancelled') NOT NULL DEFAULT 'pending'");
    }
};
