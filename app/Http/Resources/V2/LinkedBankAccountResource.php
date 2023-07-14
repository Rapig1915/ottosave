<?php

namespace App\Http\Resources\V2;

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
        $hiddenPurposes = [
            'primary_savings',
            'primary_checking'
        ];
        return [
            'id' => $this->id,
            'created_at' => $this->created_at ? $this->created_at->format('Y-m-d H:i:sP') : null,
            'updated_at' => $this->updated_at ? $this->updated_at->format('Y-m-d H:i:sP') : null,
            'account_id' => $this->account_id,
            'institution_account_id' => $this->institution_account_id,
            'name' => $this->name,
            'slug' => $this->slug,
            'type' => $this->type,
            'color' => $this->color,
            'icon' => $this->icon,
            'purpose' => in_array($this->purpose, $hiddenPurposes) ? 'none' : $this->purpose,
            'is_required' => $this->is_required,
            'is_balance_overridden' => (bool)$this->is_balance_overridden,
            'appears_in_account_list' => (bool)($this->parent_bank_account_id ? false : $this->appears_in_account_list),
            'online_banking_url' => $this->online_banking_url,
            'balance_current' => (float)$this->balance_current,
            'balance_available' => (float)$this->balance_available,
            'balance_limit' => (float)$this->balance_limit,
            'allocation_balance_adjustment' => (float)$this->allocation_balance_adjustment,
            'assignment_balance_adjustment' => (float)$this->assignment_balance_adjustment,
            'institution_account' => new InstitutionAccountResource($this->institutionAccount),
        ];
    }
}
