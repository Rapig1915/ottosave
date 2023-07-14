<?php

namespace App\Http\Resources\V2;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\V1\IncomeAccountOverviewResource;
use App\Http\Resources\V1\CCPayoffAccountOverviewResource;
use App\Http\Resources\V1\AllocationAccountResource;
use App\Http\Resources\V3\LinkedBankAccountResource;

class TransferredAllocationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $resource = $this;
        $transferredAllocationResource = [
            'id' => $resource->id,
            'created_at' => $resource->created_at ? $resource->created_at->format('Y-m-d H:i:sP') : null,
            'updated_at' => $resource->updated_at ? $resource->updated_at->format('Y-m-d H:i:sP') : null,
            'defense_id' => $resource->defense_id,
            'bank_account_id' => $resource->bank_account_id,
            'transferred_from_id' => $resource->transferred_from_id,
            'amount' => (float)$resource->amount,
            'cleared' => $resource->cleared,
            'cleared_out' => $resource->cleared_out,
            'transferred' => $resource->transferred,
        ];

        switch ($resource->bankAccount->slug ?? null) {
            case 'income_deposit':
                $transferredAllocationResource['bank_account'] = new IncomeAccountOverviewResource($resource->bankAccount);
                break;
            case 'cc_payoff':
                $transferredAllocationResource['bank_account'] = new CCPayoffAccountOverviewResource($resource->bankAccount);
                break;
            case 'primary_savings':
            case 'primary_checking':
                $transferredAllocationResource['bank_account'] = new LinkedBankAccountResource($resource->bankAccount);
                break;
            default:
                $transferredAllocationResource['bank_account'] = new AllocationAccountResource($resource->bankAccount);
                break;
        }

        switch ($resource->transferredFromBankAccount->slug ?? null) {
            case 'income_deposit':
                $transferredAllocationResource['transferred_from_bank_account'] = new IncomeAccountOverviewResource($resource->transferredFromBankAccount);
                break;
            case 'cc_payoff':
                $transferredAllocationResource['transferred_from_bank_account'] = new CCPayoffAccountOverviewResource($resource->transferredFromBankAccount);
                break;
            case 'primary_savings':
            case 'primary_checking':
                $transferredAllocationResource['transferred_from_bank_account'] = new LinkedBankAccountResource($resource->transferredFromBankAccount);
                break;
            default:
                $transferredAllocationResource['transferred_from_bank_account'] = new AllocationAccountResource($resource->transferredFromBankAccount);
                break;
        }

        return $transferredAllocationResource;
    }
}
