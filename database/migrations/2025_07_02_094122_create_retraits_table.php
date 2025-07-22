<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
    Schema::create('retraits', function (Blueprint $table) {
    $table->id();
    $table->foreignId('proprietaire_id')->constrained()->onDelete('cascade');
    $table->decimal('montant', 10, 2);
    $table->string('raison')->nullable();
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('retraits');
    }
};
