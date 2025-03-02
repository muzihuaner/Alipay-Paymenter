# 支付宝Paymenter支付插件 Alipay Paymenter payment plug-in

A easy way to integrate Alipay into Paymenter
将支付宝轻松集成到 Paymenter

## 安装 Installation
Download the code and place it in the `app/Extensions/Gateways/Alipay` directory.
下载代码并将其放在 `app/Extensions/Gateways/Alipay` 目录中。

To use this payment interface, you need to:
Install Alipay SDK:
要使用这个支付接口，你需要：
安装支付宝 SDK：
```
composer require alipaysdk/easysdk
```

## 配置 Configuration
In the admin panel, go to `Settings > Payment Gateways` and click on the `Alipay` gateway. Enter your email address and press `Save`.

在管理面板中，转到“设置 > 支付网关”，然后单击“Alipay”网关。输入相关信息，然后按“保存”。

Apply for an application on the Alipay open platform and obtain the necessary parameters:
Application ID (app_id)
Application private key (private_key)
Alipay public key (alipay_public_key)

在支付宝开放平台申请应用并获取必要的参数：
应用ID（app_id）
应用私钥（private_key）
支付宝公钥（alipay_public_key）

## 使用 Usage
When a user selects the `Alipay` gateway, they will be redirected to the Alipay payment page. After the payment is completed, the user will be redirected back to the site. 

当用户选择`支付宝`网关时，他们将被重定向到支付宝支付页面。支付完成后，用户将被重定向回网站。
