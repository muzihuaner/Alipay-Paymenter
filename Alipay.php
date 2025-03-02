<?php

namespace App\Extensions\Gateways\Alipay;

use App\Helpers\ExtensionHelper;
use App\Classes\Extensions\Gateway;
use App\Models\Invoice;
use Illuminate\Support\Facades\Log;

class Alipay extends Gateway
{
    public function getMetadata()
    {
        return [
            'display_name' => '支付宝Paymenter支付插件',
            'version' => '1.0.0',
            'author' => 'muzihuaner',
            'website' => 'https://github.com/muzihuaner/Alipay-Paymenter',
        ];
    }

    public function pay($total, $products, $orderId)
    {
        // 支付宝支付配置
        $gateway = ExtensionHelper::getConfig('Alipay', 'sandbox') ? 'https://openapi.alipaydev.com/gateway.do' : 'https://openapi.alipay.com/gateway.do';
        $app_id = ExtensionHelper::getConfig('Alipay', 'app_id');
        $private_key = ExtensionHelper::getConfig('Alipay', 'private_key');
        
        // 构建订单信息
        $subject = '';
        foreach ($products as $product) {
            $subject .= $product['name'] . ', ';
        }
        $subject = substr($subject, 0, -2);
        
        // 构建支付宝请求参数
        $params = [
            'app_id' => $app_id,
            'method' => 'alipay.trade.page.pay',
            'format' => 'JSON',
            'charset' => 'utf-8',
            'sign_type' => 'RSA2',
            'timestamp' => date('Y-m-d H:i:s'),
            'version' => '1.0',
            'notify_url' => route('alipay.webhook'),
            'return_url' => route('clients.invoice.show', $orderId),
            'biz_content' => json_encode([
                'out_trade_no' => $orderId,
                'total_amount' => number_format($total, 2, '.', ''),
                'subject' => $subject,
                'product_code' => 'FAST_INSTANT_TRADE_PAY'
            ])
        ];

        // 签名逻辑这里需要实现
        $params['sign'] = $this->generateSign($params, $private_key);

        // 构建跳转URL
        $url = $gateway . '?' . http_build_query($params);
        return $url;
    }

    public function getConfig()
    {
        return [
            'app_id' => [
                'type' => 'text',
                'name' => 'app_id',
                'friendlyName' => '支付宝应用ID',
                'description' => '您的支付宝应用ID',
                'required' => true,
            ],
            'private_key' => [
                'type' => 'textarea',
                'name' => 'private_key',
                'friendlyName' => '商户私钥',
                'description' => '您的商户私钥',
                'required' => true,
            ],
            'public_key' => [
                'type' => 'textarea',
                'name' => 'public_key',
                'friendlyName' => '支付宝公钥',
                'description' => '支付宝提供的公钥',
                'required' => true,
            ],
            'sandbox' => [
                'type' => 'boolean',
                'name' => 'sandbox',
                'friendlyName' => '沙箱模式',
                'description' => '启用支付宝沙箱环境进行测试',
                'required' => false,
            ],
        ];
    }

    private function generateSign($params, $privateKey)
    {
        // 1. 对参数进行排序
        ksort($params);
        
        // 2. 构建签名字符串
        $stringToBeSigned = "";
        foreach ($params as $k => $v) {
            if ($k != "sign" && $v !== "" && $v !== null) {
                $stringToBeSigned .= "&{$k}={$v}";
            }
        }
        $stringToBeSigned = substr($stringToBeSigned, 1);
        
        // 3. 签名
        $privateKey = "-----BEGIN RSA PRIVATE KEY-----\n" .
            wordwrap($privateKey, 64, "\n", true) .
            "\n-----END RSA PRIVATE KEY-----";
            
        openssl_sign($stringToBeSigned, $sign, $privateKey, OPENSSL_ALGO_SHA256);
        
        return base64_encode($sign);
    }

    public function webhook()
    {
        // 获取支付宝回调数据
        $data = $_POST;
        
        // 验证签名
        $public_key = ExtensionHelper::getConfig('Alipay', 'public_key');
        if ($this->verifySign($data, $public_key)) {
            // 支付成功
            if ($data['trade_status'] == 'TRADE_SUCCESS') {
                $orderId = $data['out_trade_no'];
                $invoice = Invoice::findOrFail($orderId);
                
                // 验证金额
                if ($data['total_amount'] == number_format($invoice->total(), 2, '.', '')) {
                    ExtensionHelper::paymentDone($orderId, '支付宝支付', $data['trade_no']);
                }
            }
        }
        
        echo 'success';
    }

    private function verifySign($data, $publicKey)
    {
        // 1. 获取签名
        $sign = $data['sign'];
        unset($data['sign']);
        unset($data['sign_type']);
        
        // 2. 对数据进行排序
        ksort($data);
        
        // 3. 构建待验证字符串
        $stringToVerify = "";
        foreach ($data as $k => $v) {
            if ($v !== "" && $v !== null) {
                $stringToVerify .= "&{$k}={$v}";
            }
        }
        $stringToVerify = substr($stringToVerify, 1);
        
        // 4. 验证签名
        $publicKey = "-----BEGIN PUBLIC KEY-----\n" .
            wordwrap($publicKey, 64, "\n", true) .
            "\n-----END PUBLIC KEY-----";
            
        return openssl_verify(
            $stringToVerify,
            base64_decode($sign),
            $publicKey,
            OPENSSL_ALGO_SHA256
        ) === 1;
    }
}
