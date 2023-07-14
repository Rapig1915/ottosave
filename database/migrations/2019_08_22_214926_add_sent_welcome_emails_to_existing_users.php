<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AddSentWelcomeEmailsToExistingUsers extends Migration
{
    public function up()
    {
        $accountUserIds = DB::table('account_user')->select('id')->get()->pluck('id')->all();
        $sentEmails = [];
        foreach ($accountUserIds as $accountUserId) {
            $sentEmails[] = [
                'account_user_id' => $accountUserId,
                'email_identifier' => 'welcome_email',
                'send_date' => Carbon::now()
            ];
        }
        DB::table('sent_emails')->insert($sentEmails);
    }

    public function down()
    {
        //not applicable
    }
}
