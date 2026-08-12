<?php
return [
'name'=>env('APP_NAME','LUDES-MARKET'),'env'=>env('APP_ENV','production'),'debug'=>(bool)env('APP_DEBUG',false),'url'=>env('APP_URL','http://localhost'),
'timezone'=>'Asia/Makassar','locale'=>env('APP_LOCALE','id'),'fallback_locale'=>env('APP_FALLBACK_LOCALE','id'),'faker_locale'=>'id_ID','cipher'=>'AES-256-CBC','key'=>env('APP_KEY'),'previous_keys'=>array_filter(explode(',',env('APP_PREVIOUS_KEYS',''))),'maintenance'=>['driver'=>env('APP_MAINTENANCE_DRIVER','file')],
];
