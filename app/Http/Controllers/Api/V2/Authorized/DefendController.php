<?php

namespace App\Http\Controllers\Api\V2\Authorized;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Models\Defense;
use App\Models\BankAccount\Allocation;
use App\Models\Assignment;
use App\Http\Resources\V2\TransferredAllocationResource;
use Carbon\Carbon;

class DefendController extends Controller
{
    public function transferFunds($defense_id, Request $request)
    {
        $currentAccount = Auth::user()->current_account;
        $defense = $currentAccount->defenses()->findOrFail($defense_id);

        $parentAllocationPayload = $request->all();
        $childAllocationPayloads = $parentAllocationPayload['child_allocations'] ?? [];

        $releventBankAccountIds = collect([$parentAllocationPayload])
            ->concat($childAllocationPayloads)
            ->map(function($allocationPayload, $index) {
                return [$allocationPayload['bank_account_id'], $allocationPayload['transferred_from_id']];
            })
            ->flatten()
            ->filter()
            ->unique()
            ->all();
        $releventBankAccounts = $currentAccount->bankAccounts()->whereIn('id', $releventBankAccountIds)->get();

        $userHasAccess = count($releventBankAccounts) === count($releventBankAccountIds);
        if (!$userHasAccess) {
            throw new HttpException(403, "You do not have access to this Bank Account");
        }
        try {
            DB::beginTransaction();

            $isInternalTransfer = $parentAllocationPayload['bank_account_id'] === $parentAllocationPayload['transferred_from_id'];
            $allocation = Allocation::mergeOrCreate($parentAllocationPayload);
            $allocation->transferred = true;
            $defense->allocation()->save($allocation);
            $allocationHasAssignments = !empty($parentAllocationPayload['allocatedAssignments']);
            if ($allocationHasAssignments) {
                $assignments = Assignment::bulkMerge($parentAllocationPayload['allocatedAssignments']);
                $allocation->assignments()->saveMany($assignments);
            }

            $childAllocations = [];
            foreach ($childAllocationPayloads as $childAllocationPayload) {
                $childAllocation = Allocation::mergeOrCreate($childAllocationPayload);
                $childAllocation->transferred = true;
                $childAllocation->parent_allocation_id = $allocation->id;
                $defense->allocation()->save($childAllocation);
                $allocationHasAssignments = !empty($childAllocationPayload['allocatedAssignments']);
                if ($allocationHasAssignments) {
                    $assignments = Assignment::bulkMerge($childAllocationPayload['allocatedAssignments']);
                    $childAllocation->assignments()->saveMany($assignments);
                }
                $childAllocations[] = $childAllocation;
            }

            foreach ($releventBankAccounts as $bankAccount) {
                $bankAccount->clearAllocations();
            }
            $currentAccount->payoffAccount->deleteAssignedPayments();
            DB::commit();
            $allAllocations = collect([$allocation])->concat($childAllocations)->each(function($allocation) {
                $allocation->refresh();
                if ($allocation->bankAccount) {
                    $allocation->bankAccount->loadOverviewAttributes();
                }
                if ($allocation->transferredFromBankAccount) {
                    $allocation->transferredFromBankAccount->loadOverviewAttributes();
                }
            });
            return TransferredAllocationResource::collection($allAllocations);
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }
}
