<?php
return ['xendit' => [
    'secret_key' => env('XENDIT_SECRET_KEY'),
    'webhook_token' => env('XENDIT_WEBHOOK_TOKEN'),
    'ssl_verify' => (bool) env('XENDIT_SSL_VERIFY', true),
]];
