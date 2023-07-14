<?php

namespace App\Http\Controllers\Api\V1\Authorized;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\Defense;
use App\Http\Resources\V1\DefenseResource;
use Carbon\Carbon;

class DefendController extends Controller
{
    public function createDefense()
    {
        $account = Auth::user()->current_account;
        $defense = Defense::createForAccount($account);
        return new DefenseResource($defense);
    }

    public function indexDefenses(Request $request)
    {
        $this->validate($request, [
            'start_date' => 'required|date',
            'end_date' => 'required|date',
        ]);
        $startDate = Carbon::parse($request->input('start_date'))->format('Y-m-d');
        $endDate = Carbon::parse($request->input('end_date'))->format('Y-m-d');

        $currentAccount = Auth::user()->current_account;
        $defenses = $currentAccount->defenses()
            ->whereBetween(DB::raw('DATE(created_at)'), array($startDate, $endDate))
            ->with('allocation', 'allocation.assignments')
            ->get();
        return DefenseResource::collection($defenses);
    }
}
