<?php

return [
    'disabled' => env('RECAPTCHA_DISABLED', true),
    'siteKey' => env('RECAPTCHA_SITE_KEY', 'insert_recaptcha_site_key'),
    'secretKey' => env('RECAPTCHA_SECRET_KEY', 'insert_recaptcha_secret_key'),
    'verifyScoreThresold' => env('RECAPTCHA_VERIFY_SCORE_THRESOLD', 0.5),
];
