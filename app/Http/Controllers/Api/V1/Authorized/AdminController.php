<?php

namespace App\Http\Controllers\Api\V1\Authorized;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Http\Resources\V1\NotificationViewResource;
use App\Http\Resources\V2\AdminUserListAccountResource;
use App\Models\Account;
use App\Models\Assignment;
use App\Models\BankAccount;
use App\Models\Transaction;
use App\Services\External\FinicityService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Storage;
use Artisan;

class AdminController extends Controller
{
    public function getEmailNotificationViews()
    {
        $mailPath = app_path('Mail/Notifications');

        if (!is_dir($mailPath)) {
            throw new HttpException(404, 'Email folder not found.');
        }

        $files = scandir($mailPath);
        // unset the current and previous directory links
        unset($files[0]);
        unset($files[1]);

        return NotificationViewResource::collection(collect($files));
    }

    public function getRenderedEmailNotification(Request $request)
    {
        $emailClass = '\App\Mail\Notifications\\' . $request->input('email');
        return new $emailClass();
    }

    public function reactivateAccount(Account $account)
    {
        if ($account->status === 'active') {
            abort(400, 'Account already active');
        }
        $account->reactivate();
        return new AdminUserListAccountResource($account);
    }

    public function resetAccountToDemoMode(Account $account)
    {
        if ($account->status === 'demo') {
            abort(400, 'Account already in demo mode');
        } elseif ($account->subscription_provider === 'itunes') {
            abort(400, 'iOS subscribers are ineligible for demo mode');
        }
        try {
            DB::beginTransaction();
            $account->deactivate();
            $account->expire_date = Carbon::now()->subDays(1);
            $account->initializeForDemo();
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }

        return new AdminUserListAccountResource($account);
    }

    public function getFinicitySubscriptions()
    {
        $FinicityService = new FinicityService();
        $requestPath = 'aggregation/v1/customers';
        $payload = [
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'Finicity-App-Key' => $FinicityService->app_key,
                'Finicity-App-Token' => $FinicityService->access_token
            ],
            'query' => [
                'start' => 1,
                'limit' => 25,
                'type' => config('finicity.env') === 'production' ? 'active' : 'testing'
            ]
        ];
        
        $result = [
            'total' => 0,
            'active' => 0,
            'idle' => 0
        ];

        $moreDataAvailable = false;
        do {
            $response = $FinicityService->client->get($requestPath, $payload);
            $responseJson = json_decode($response->getBody()->getContents());
            $moreDataAvailable = $responseJson->moreAvailable;
            $customers = $responseJson->customers;

            foreach ($customers as $customer) {
                $finictyCustomer = \App\Models\FinicityCustomer::where('customer_id', $customer->id)->first();
                $isIdleCustomer = is_null($finictyCustomer);

                $result['total'] = $result['total'] + 1;
                if($isIdleCustomer) {
                    $result['idle'] = $result['idle'] + 1;
                } else {
                    $result['active'] = $result['active'] + 1;
                }
            }

            if(!$moreDataAvailable){
                break;
            }

            $payload['query']['start'] = $payload['query']['start'] + $payload['query']['limit'];
        }while($moreDataAvailable);

        return response()->json($result);
    }

    public function getSystemSubscriptions()
    {
        $result = [
            'total' => 0,
            'active' => 0,
            'orphaned' => 0
        ];

        Account::with(['accountUsers', 'finicity_customer'])->chunk(1000, function ($accounts) use(&$result){
            foreach($accounts as $account){
                $finicityCustomerExist = !is_null($account->finicity_customer);
                if($finicityCustomerExist){

                    $hasOwnerUser = $account->accountUsers->contains(function ($accountUser) {
                        return $accountUser->hasRole('owner');
                    });
                    $isAccountActive = in_array($account->status, ['active', 'grace', 'free_trial', 'trial_grace', 'pending_renewal']);
                    $accountDowngraded = $account->subscription_plan === 'basic';
                    $orphanedCustomer = !$hasOwnerUser || !$isAccountActive || $accountDowngraded;

                    $result['total'] = $result['total'] + 1;
                    if($orphanedCustomer){
                        $result['orphaned'] = $result['orphaned'] + 1;
                    } else {
                        $result['active'] = $result['active'] + 1;
                    }
                    
                }
            }
        });

        return response()->json($result);
    }

    public function invokeCommand(Request $request)
    {
        $request->validate([
            'command' => 'required' 
        ]);

        $command = $request->command;

        $argvs = explode(' ', $command);
        $signature = $argvs[0] ?? '';
        $arguments = [];

        $allowedCommands = ['dym:remove-idle-finicity-customers', 'dym:remove-orphan-finicity-customers'];
        if(!in_array($signature, $allowedCommands)){
            abort(403, 'This command is not allowed to invoke remotely.');
        }

        foreach($argvs as $argv){
            $keyValuePair = explode('=', $argv);
            $isValidPair = count($keyValuePair) === 2;
            if($isValidPair){
                $arguments[$keyValuePair[0]] = $keyValuePair[1];
            }
        }

        $code = date('Ymdhis') . '-' . uniqid();
        $arguments['--code'] = $code;

        Artisan::queue($signature, $arguments);
        
        return response()->json([
            'code' => $code,
            'signature' => $signature,
            'arguments' => $arguments
        ]);
    }

    public function getCommandOutput($code)
    {
        $isEmptyCode = empty($code);
        if($isEmptyCode){
            return 'ERROR! Empty command code!';
        }

        $logPath = "logs/commands/{$code}.log";
        $notFound = !Storage::disk('local')->exists($logPath);
        if($notFound){
            return 'No command output found with code #' . $code;
        }

        return Storage::disk('local')->get($logPath);
    }

    public function makeDeposit(Request $request)
    {
        $request->validate([
            'bankAccountId' => 'required',
            'amount' => 'required|numeric',
            'category' => 'required',
            'date' => 'required'
        ]);

        $bankAccountId = $request->bankAccountId;
        $amount = $request->amount;
        $category = $request->category;
        $date = new Carbon($request->date . ' ' . date('H:i:s'));
        $description = 'Test deposit ' . date('YmdHis');

        $bankAccount = BankAccount::find($bankAccountId);
        $institutionAccount = $bankAccount->institutionAccount;
        if(!$institutionAccount){
            abort(403);
        }

        $linkedAtDate = $institutionAccount->linked_at ? Carbon::parse($institutionAccount->linked_at) : Carbon::parse($institutionAccount->created_at);
        if($linkedAtDate->gt($date)){
            abort(400, 'Put in a later date.');
        }

        $transaction = Transaction::mergeOrCreate([
            'amount' => -$amount,
            'remote_transaction_date' => $date,
            'remote_category' => $category,
            'action_type' => 'digital',
            'merchant' => $description
        ]);

        $bankAccount->balance_current = bcadd($bankAccount->balance_current, $amount);
        $bankAccount->save();
        $institutionAccount->balance_current = $bankAccount->balance_current;
        $institutionAccount->save();

        $institutionAccount->processTransctions([$transaction]);

        $bankAccount->refreshSubAccountBalances();

        return 'OK';
    }
}
