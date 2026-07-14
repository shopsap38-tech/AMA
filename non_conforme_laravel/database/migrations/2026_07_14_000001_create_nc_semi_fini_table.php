<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nc_semi_fini', function (Blueprint $table) {
            $table->id();
            $table->string('numero_article', 30);
            $table->string('description_article', 255);
            $table->integer('quantite')->default(0);
            $table->integer('nbr_palettes')->default(0);
            $table->decimal('poids_total_kg', 12, 2)->default(0);
            $table->string('motif', 150)->nullable();
            $table->string('decision_sq', 150)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nc_semi_fini');
    }
};
