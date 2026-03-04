<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {

    /** Check if an index exists (works for both MySQL and PostgreSQL) */
    private function indexExists(string $table, string $indexName): bool
    {
        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            $count = DB::selectOne(
                "SELECT COUNT(*) AS cnt FROM pg_indexes WHERE tablename = ? AND indexname = ?",
                [$table, $indexName]
            );
            return (int) ($count->cnt ?? 0) > 0;
        }

        // MySQL / MariaDB / SQLite
        try {
            $indexes = DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$indexName]);
            return count($indexes) > 0;
        } catch (\Throwable $e) {
            // SQLite: just try to create – IF NOT EXISTS equivalent via try/catch
            return false;
        }
    }

    public function up(): void
    {
        // Payments: speed up order→payment look-ups and status filters
        Schema::table('payments', function (Blueprint $table) {
            if (!$this->indexExists('payments', 'payments_order_id_index')) {
                $table->index('order_id', 'payments_order_id_index');
            }
            if (!$this->indexExists('payments', 'payments_status_index')) {
                $table->index('status', 'payments_status_index');
            }
        });

        // Order items: speed up order→items look-ups
        Schema::table('order_items', function (Blueprint $table) {
            if (!$this->indexExists('order_items', 'order_items_order_id_index')) {
                $table->index('order_id', 'order_items_order_id_index');
            }
            if (!$this->indexExists('order_items', 'order_items_product_id_index')) {
                $table->index('product_id', 'order_items_product_id_index');
            }
        });

        // Products: speed up category filter
        Schema::table('products', function (Blueprint $table) {
            if (!$this->indexExists('products', 'products_category_id_index')) {
                $table->index('category_id', 'products_category_id_index');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndexIfExists('payments_order_id_index');
            $table->dropIndexIfExists('payments_status_index');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropIndexIfExists('order_items_order_id_index');
            $table->dropIndexIfExists('order_items_product_id_index');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropIndexIfExists('products_category_id_index');
        });
    }
};


return new class extends Migration {
    public function up(): void
    {
        // Payments: speed up order→payment look-ups and status filters
        Schema::table('payments', function (Blueprint $table) {
            if (!$this->hasIndex('payments', 'payments_order_id_index')) {
                $table->index('order_id', 'payments_order_id_index');
            }
            if (!$this->hasIndex('payments', 'payments_status_index')) {
                $table->index('status', 'payments_status_index');
            }
        });

        // Order items: speed up order→items look-ups
        Schema::table('order_items', function (Blueprint $table) {
            if (!$this->hasIndex('order_items', 'order_items_order_id_index')) {
                $table->index('order_id', 'order_items_order_id_index');
            }
            if (!$this->hasIndex('order_items', 'order_items_product_id_index')) {
                $table->index('product_id', 'order_items_product_id_index');
            }
        });

        // Products: speed up category filter
        Schema::table('products', function (Blueprint $table) {
            if (!$this->hasIndex('products', 'products_category_id_index')) {
                $table->index('category_id', 'products_category_id_index');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndexIfExists('payments_order_id_index');
            $table->dropIndexIfExists('payments_status_index');
        });
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropIndexIfExists('order_items_order_id_index');
            $table->dropIndexIfExists('order_items_product_id_index');
        });
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndexIfExists('products_category_id_index');
        });
    }

    /** Check whether an index already exists (avoids duplicate-index errors). */
    private function hasIndex(string $table, string $index): bool
    {
        $sm = Schema::getConnection()->getDoctrineSchemaManager();
        $indexes = $sm->listTableIndexes($table);
        return isset($indexes[$index]);
    }
};
