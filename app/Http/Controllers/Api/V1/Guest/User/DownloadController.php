<?php

namespace App\Http\Controllers\Api\V1\Guest\User;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\TransactionCSVResource;
use App\Models\BankAccount;
use App\Models\DownloadRequest;
use App\Traits\CSVResponseTrait;
use Illuminate\Http\Request;

class DownloadController extends Controller{

    use CSVResponseTrait;

    public function downloadAccountTransactions($token)
    {
        $now = date('Y-m-d H:i:s');
        $downloadEntry = DownloadRequest::where('token', $token)
                                        ->where('used', 0)
                                        ->where('expire_at', '>', $now)
                                        ->first();
        if(!$downloadEntry){
            abort(404, 'Cannot find a valid request with your token.');
        }

        $params = json_decode($downloadEntry->json, true);
        $bankAccountId = $params['bankAccountId'] ?? 0;
        $startDate = $params['startDate'] ?? '';
        $endDate = $params['endDate'] ?? '';
        if(empty($bankAccountId) || empty($startDate) || empty($endDate)){
            abort(400, 'Invalid params');
        }

        $bankAccount = BankAccount::findOrFail($bankAccountId);
        $transactions = $bankAccount->transactions()->withTrashed()->whereBetween('remote_transaction_date', [$startDate, $endDate])->get();
        $dataRows = TransactionCSVResource::collection($transactions)->toArray(null);

        $downloadEntry->update([
            'used' => true
        ]);

        $csvColumns = ['Date', 'Description', 'Amount'];
        return $this->makeCSVResponse($dataRows, 'transactions', $csvColumns);
    }

}
