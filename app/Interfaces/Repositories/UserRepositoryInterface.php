<?php

namespace App\Interfaces\Repositories;

use App\Models\User;

interface UserRepositoryInterface {

    public function getLastUsersWithTransactions() : User;
}