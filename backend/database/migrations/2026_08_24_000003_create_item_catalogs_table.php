<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_catalogs', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('unit', 30);
            $table->enum('type', ['basah', 'kering']);
            $table->timestamps();
        });
    }

    public function down(): void { Schema::dropIfExists('item_catalogs'); }
};