<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Requests\TransactionRequest;
use App\Interfaces\Repositories\AccountRepositoryInterface;
use App\Interfaces\Repositories\CardRepositoryInterface;
use App\Interfaces\Repositories\TransactionRepositoryInterface;
use App\Interfaces\Repositories\WageRepositoryInterface;

class TransactionController extends Controller
{

    private TransactionRepositoryInterface $transactionRepository;
    private CardRepositoryInterface $cardRepository;
    private AccountRepositoryInterface $accountRepository;
    private wageRepositoryInterface $wageRepository;

    public function __construct(TransactionRepositoryInterface $transactionRepository, CardRepositoryInterface $cardRepository, AccountRepositoryInterface $accountRepository, WageRepositoryInterface $wageRepository)
    {
        
        $this->transactionRepository = $transactionRepository;
        $this->cardRepository = $cardRepository;
        $this->accountRepository = $accountRepository;
        $this->wageRepository = $wageRepository;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(TransactionRequest $request)
    {

        // get first card info from db
        $card = $this->cardRepository->findByCardNumber($request->card_number);

        // create new transaction
        $transaction = $this->transactionRepository->create($request->only(['card_number', 'destination_card_number', 'amount']));

        // check if the first card account have enough amount of credit for transaction and wage
        if(($card->account->balance + TRANSACTION_WAGE) < $request->amount){

            $transaction = $this->transactionRepository->changeStatus($transaction, 'no_balance');

            return 'balance is not enough!';
        }

        // get the destination transaction card info
        $destinationCard = $this->cardRepository->findByCardNumber($request->destination_card_number);

        try {

            DB::transaction(function () use ($request, $destinationCard, $card, $transaction) {

                // create wage in db for this transaction
                $this->wageRepository->create($transaction->id);
    
                // sub and sum operation on the first card and destionation card accounts on db 
                $this->accountRepository->subBalance($card->account, $request->amount);
                $this->accountRepository->subBalance($destinationCard->account, $request->amount);
            });
        } catch (\Throwable $th) {

            // change transaction status to error on database
            $transaction = $this->transactionRepository->changeStatus($transaction, 'error_happend');
        }

        return 'done';
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
