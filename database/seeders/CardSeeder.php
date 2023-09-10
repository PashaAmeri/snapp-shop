<?php

namespace Database\Seeders;

use App\Models\Card;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CardSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        
        Card::create([
            'account_id' => '1',
            'card_number' => '1234123412341234'
        ]);

        Card::create([
            'account_id' => '2',
            'card_number' => '4321432143214321'
        ]);

        Card::create([
            'account_id' => '3',
            'card_number' => '7894789478947894'
        ]);
    }
}
