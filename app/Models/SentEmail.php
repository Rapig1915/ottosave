<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SentEmail extends Model
{
    protected $table = 'sent_emails';
    public $timestamps = false;
    public $fillable = [
        'account_user_id',
        'email_identifier',
        'send_date'
    ];
    public function accountUsers()
    {
        return $this->belongsToMany('App\Models\AccountUser');
    }
}
