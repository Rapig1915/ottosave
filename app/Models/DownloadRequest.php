<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\BankAccount\Allocation;
use App\Models\Account;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DownloadRequest extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'json',
        'token',
        'used',
        'expire_at',
    ];

    protected $casts = [
        'expire_at' => 'date'
    ];

    public static function addRequest($json, $tokenPrefix = '', $until = "+1 minutes"){
        $data = [
            'token' => uniqid($tokenPrefix),
            'expire_at' => date('Y-m-d H:i:s', strtotime($until)),
            'json' => json_encode($json)
        ];
        return DownloadRequest::create($data);
    }
}
