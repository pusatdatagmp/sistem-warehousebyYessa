<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('stock', 15, 3)->default(0)->change();
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->decimal('qty', 15, 3)->change();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->unsignedInteger('stock')->default(0)->change();
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->unsignedInteger('qty')->change();
        });
    }
};
