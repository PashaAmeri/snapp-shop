<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Requests\TransactionRequest;
use App\Http\Resources\LastTransactionsResource;
use App\Interfaces\Repositories\AccountRepositoryInterface;
use App\Interfaces\Repositories\CardRepositoryInterface;
use App\Interfaces\Repositories\TransactionRepositoryInterface;
use App\Interfaces\Repositories\WageRepositoryInterface;
use App\Jobs\SmsJob;
use App\Models\Transaction;
use App\Models\User;
use App\Notifications\SmsToClient;
use Carbon\Carbon;
use GrahamCampbell\ResultType\Success;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;

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
     * Store a newly created resource in storage.
     */
    public function store(TransactionRequest $request)
    {

        // get first card info from db
        $card = $this->cardRepository->findByCardNumber($request->card_number);

        // get the destination transaction card info
        $destinationCard = $this->cardRepository->findByCardNumber($request->destination_card_number);

        // create new transaction
        $transaction = $this->transactionRepository->create($card->id, $destinationCard->id, $request->amount);

        // check if the first card account have enough amount of credit for transaction and wage
        if(!$this->checkBalance($transaction, $card->account->balance, $request->amount)){

            return response([
                'success' => false,
                'message' => 'Account Balance is not enough!'
            ], Response::HTTP_NOT_ACCEPTABLE);
        }

        try {

            DB::transaction(function () use ($request, $destinationCard, $card, $transaction) {

                // create wage in db for this transaction
                $this->wageRepository->create($transaction->id);
    
                // sub and sum operation on the first card and destionation card accounts on db 
                $this->accountRepository->subBalance($card->account, $request->amount);
                $this->accountRepository->sumBalance($destinationCard->account, $request->amount);

                // send sms after transaction commited to users on the queueu
                dispatch(new SmsJob($card, $request->amount, GIVE_MONNY_SMS_MESSAGE))->delay(now()->addSeconds(30))->afterCommit();
                dispatch(new SmsJob($destinationCard, $request->amount, GET_MONNY_SMS_MESSAGE))->delay(now()->addSeconds(30))->afterCommit();
            });

        } catch (\Throwable $th) {

            // change transaction status to error on database
            $transaction = $this->transactionRepository->changeStatus($transaction, 'error_happend');
            return 'error ' . $th->getMessage();

            return response([
                'success' => false,
                'message' => 'Error: something went wrong! ' . $th->getMessage()
            ], Response::HTTP_CONFLICT);
        }

        return response([
            'success' => true,
            'message' => 'payment is successfull.'
        ], Response::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     */
    public function showLastTransactions()
    {

        $users = User::with(['transactions' => function ($q) {

                $q->with('card', 'destinationCard');
                $q->where('transactions.created_at', '>', Carbon::now()->subMinutes(10))
                    ->orderBy('created_at', 'DESC');
            }])->withCount('transactions')
            ->orderByDesc('transactions_count')
            ->limit(3)
            ->get();
        
        return LastTransactionsResource::make($users);
    }

    //----------------------------------------------------

    private function checkBalance(Transaction $transaction, $balance, $amount) : bool
    {

        if($balance < ($amount + TRANSACTION_WAGE)){

            $transaction = $this->transactionRepository->changeStatus($transaction, 'no_balance');

            return false;
        }

        return true;
    }
}
