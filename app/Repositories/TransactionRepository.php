<?php

namespace App\Repositories;

use App\Interfaces\Repositories\TransactionRepositoryInterface;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Collection;

class TransactionRepository implements TransactionRepositoryInterface {

    public function create(array $inputs) : Transaction {

        return Transaction::create([
            'card_id' => $inputs['card_number'],
            'destination_card_id' => $inputs['destination_card_number'],
            'amount' => $inputs['amount'] + TRANSACTION_WAGE
        ]);
    }

    public function changeStatus(Transaction $transaction, $status) : Transaction {

        $transaction->status = $status;
        $transaction->save();
        
        return $transaction;
    }
}