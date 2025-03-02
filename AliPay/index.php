<?php

use App\Helpers\ExtensionHelper;
use Illuminate\Support\Facades\Http;

function Alipay_pay($total, $products, $orderId)
{
    $appId = ExtensionHelper::getConfig('Alipay', 'app_id');
    $privateKey = ExtensionHelper::getConfig('Alipay', 'private_key');
    $alipayPublicKey = ExtensionHelper::getConfig('Alipay', 'alipay_public_key');

    $aop = new \AopClient();
    $aop->gatewayUrl = "https://openapi.alipay.com/gateway.do";
    $aop->appId = $appId;
    $aop->rsaPrivateKey = $privateKey;
    $aop->alipayrsaPublicKey = $alipayPublicKey;
    $aop->apiVersion = '1.0';
    $aop->signType = 'RSA2';
    $aop->postCharset = 'UTF-8';
    $aop->format = 'json';

    $request = new \AlipayTradePagePayRequest();
    $request->setReturnUrl(route('clients.invoice.show', $orderId));
    $request->setNotifyUrl(route('payment.alipay.notify'));

    $description = 'Products: ';
    foreach ($products as $product) {
        $description .= $product->name . ' x' . $product->quantity . ', ';
    }

    $bizcontent = [
        'out_trade_no' => (string) $orderId,
        'total_amount' => number_format($total, 2, '.', ''),
        'subject' => substr($description, 0, 256),
        'product_code' => 'FAST_INSTANT_TRADE_PAY'
    ];

    $request->setBizContent(json_encode($bizcontent));
    $result = $aop->pageExecute($request, 'GET');
    
    return $result;
}

function Alipay_webhook($request)
{
    $appId = ExtensionHelper::getConfig('Alipay', 'app_id');
    $alipayPublicKey = ExtensionHelper::getConfig('Alipay', 'alipay_public_key');

    $aop = new \AopClient();
    $aop->alipayrsaPublicKey = $alipayPublicKey;

    $flag = $aop->rsaCheckV1($request->all(), null, 'RSA2');
    
    if (!$flag) {
        return response()->json(['message' => 'Invalid signature'], 403);
    }

    if ($request->input('trade_status') == 'TRADE_SUCCESS') {
        ExtensionHelper::paymentDone($request->input('out_trade_no'));
    }

    return response()->json(['message' => 'success'], 200);
}

function Alipay_getConfig()
{
    return [
        [
            'name' => 'app_id',
            'friendlyName' => '支付宝应用ID',
            'type' => 'text',
            'required' => true,
        ],
        [
            'name' => 'private_key',
            'friendlyName' => '应用私钥',
            'type' => 'textarea',
            'required' => true,
        ],
        [
            'name' => 'alipay_public_key',
            'friendlyName' => '支付宝公钥',
            'type' => 'textarea',
            'required' => true,
        ],
    ];
}