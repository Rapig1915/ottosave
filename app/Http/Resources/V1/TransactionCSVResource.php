<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class TransactionCSVResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $transaction = $this;
        $shouldDebitNegative = $transaction->bankAccount->type !== 'credit';

        return [
            'date' => $transaction->remote_transaction_date->format('m/d/Y'),
            'description' => $transaction->remote_merchant,
            'amount' => number_format((float) ($shouldDebitNegative ? -1 : 1)*$transaction->amount, 2, '.', '')
        ];
    }
}
