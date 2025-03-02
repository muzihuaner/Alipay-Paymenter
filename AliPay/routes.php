<?php

use Illuminate\Support\Alipay\Route;

include_once __DIR__ . '/index.php';

Route::post('/alipay/webhook', function () {
    Alipay_webhook(request());
})->name('alipay.webhook');