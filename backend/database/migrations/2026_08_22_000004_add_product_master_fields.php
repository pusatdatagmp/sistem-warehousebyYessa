<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->enum('type', ['basah', 'kering'])->default('kering')->after('name');
            $table->string('default_supplier')->nullable()->after('type');
            $table->string('default_customer')->nullable()->after('default_supplier');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['type', 'default_supplier', 'default_customer']);
        });
    }
};
