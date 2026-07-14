<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nc_desinfectant', function (Blueprint $table) {
            $table->id();
            $table->string('type_article', 50)->nullable();
            $table->string('numero_article', 30);
            $table->string('description_article', 255);
            $table->integer('stock_mag')->default(0);
            $table->date('date_expiration')->nullable();   // NULL = « aucune date »
            $table->string('code_um', 50)->nullable();
            $table->integer('nbr_palettes')->default(0);
            $table->decimal('prix_unitaire', 12, 4)->default(0);
            $table->decimal('poids_par_carton_kg', 12, 4)->default(0);
            $table->date('date_blocage')->nullable();
            $table->string('motif', 150)->nullable();
            $table->string('responsable', 150)->nullable();
            $table->string('decision_cq', 150)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nc_desinfectant');
    }
};
