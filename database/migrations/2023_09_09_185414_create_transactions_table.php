<?php

use App\Models\Card;
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

        Schema::create('transactions', function (Blueprint $table) {

            $table->id();
            $table->foreignIdFor(Card::class);
            $table->foreignIdFor(Card::class, 'destination_card_id');
            $table->integer('amount');
            $table->enum('status', ['succeed', 'no_balance', 'destination_card_not_valid']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
