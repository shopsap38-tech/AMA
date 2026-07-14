<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nc_big_bag', function (Blueprint $table) {
            $table->id();
            $table->date('date_nc');
            $table->integer('total_big_bag')->default(0);
            $table->decimal('tonnage', 12, 2)->default(0);   // en kg
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nc_big_bag');
    }
};
