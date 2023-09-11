<?php

namespace App\Interfaces\Repositories;

use App\Models\Transaction;

interface TransactionRepositoryInterface {

    public function create(array $inputs) : Transaction;
    public function changeStatus(Transaction $transaction, $status) : Transaction;
}