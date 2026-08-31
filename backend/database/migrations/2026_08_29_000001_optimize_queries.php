<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tambah indexes untuk query performa
        Schema::table('products', function (Blueprint $table) {
            $table->index('stock');
            $table->index('type');
            $table->index('default_supplier');
            $table->index('default_customer');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->index('product_id');
            $table->index('admin_id');
            $table->index(['type', 'product_id']);
            $table->index('created_at');
        });

        Schema::table('suppliers', function (Blueprint $table) {
            if (!Schema::hasColumn('suppliers', 'name')) {
                $table->string('name')->after('id')->nullable();
            }
            $table->index('name');
        });

        Schema::table('customers', function (Blueprint $table) {
            if (!Schema::hasColumn('customers', 'name')) {
                $table->string('name')->after('id')->nullable();
            }
            $table->index('name');
        });

        Schema::table('item_catalogs', function (Blueprint $table) {
            if (!Schema::hasColumn('item_catalogs', 'name')) {
                $table->string('name')->after('id')->nullable();
            }
            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['stock']);
            $table->dropIndex(['type']);
            $table->dropIndex(['default_supplier']);
            $table->dropIndex(['default_customer']);
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex(['product_id']);
            $table->dropIndex(['admin_id']);
            $table->dropIndex(['type', 'product_id']);
            $table->dropIndex(['created_at']);
        });

        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropIndex(['name']);
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex(['name']);
        });

        Schema::table('item_catalogs', function (Blueprint $table) {
            $table->dropIndex(['name']);
        });
    }
};
