<?php

return [
    'partnerId' => env('FINICITY_PARTNER_ID', 'insert_partner_id'),
    'secret' => env('FINICITY_SECRET', '123'),
    'appKey' => env('FINICITY_APP_KEY', '123'),
    'env' => env('FINICITY_ENV', 'development'),
    'experienceId' => env('FINICITY_EXPERIENCE_ID'),
    'hideBankIds' => env('FINICITY_HIDE_BANK_IDS', ''),
];
