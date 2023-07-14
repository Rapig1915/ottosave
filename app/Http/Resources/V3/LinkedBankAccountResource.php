<?php

namespace App\Http\Resources\V3;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\V2\InstitutionAccountResource;

class LinkedBankAccountResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'created_at' => $this->created_at ? $this->created_at->format('Y-m-d H:i:sP') : null,
            'updated_at' => $this->updated_at ? $this->updated_at->format('Y-m-d H:i:sP') : null,
            'account_id' => $this->account_id,
            'institution_account_id' => $this->institution_account_id,
            'parent_bank_account_id' => $this->parent_bank_account_id,
            'sub_accounts' => LinkedBankAccountResource::collection($this->sub_accounts),
            'sub_account_order' => $this->sub_account_order,
            'name' => $this->name,
            'slug' => $this->slug,
            'type' => $this->type,
            'color' => $this->color,
            'icon' => $this->icon,
            'purpose' => $this->purpose,
            'is_required' => $this->is_required,
            'is_balance_overridden' => (bool)$this->is_balance_overridden,
            'appears_in_account_list' => (bool)($this->parent_bank_account_id ? false : $this->appears_in_account_list),
            'online_banking_url' => $this->online_banking_url,
            'balance_current' => (float)$this->balance_current,
            'balance_available' => (float)$this->balance_available,
            'balance_limit' => (float)$this->balance_limit,
            'balance_limit_override' => (float)$this->balance_limit_override,
            'allocation_balance_adjustment' => (float)$this->allocation_balance_adjustment,
            'assignment_balance_adjustment' => (float)$this->assignment_balance_adjustment,
            'institution_account' => new InstitutionAccountResource($this->institutionAccount),
        ];
    }
}
