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
        Schema::create('payementogos', function (Blueprint $table) {
            $table->id();
            $table->string('ref')->nullable();
            $table->string('telephone')->nullable();
            $table->string('name')->nullable();
            $table->string('pname')->nullable();
            $table->decimal('montant', 15, 2)->nullable();
            $table->string('currency', 10)->default('XOF');
            $table->string('numcommande')->nullable();
            $table->string('otp')->nullable();
            $table->string('pays', 5)->default('TG');
            $table->string('operateurs')->nullable();
            $table->string('status')->nullable();
            $table->json('raw_response')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payementogos');
    }
}; 