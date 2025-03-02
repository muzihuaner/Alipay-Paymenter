<?php

use Illuminate\Support\Facades\Route;

Route::post('/Alipay/webhook', [\App\Extensions\Gateways\Alipay\Alipay::class, 'webhook'])->name('alipay.webhook');
