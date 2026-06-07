# 微信支付证书目录

请在此目录放置以下文件：

1. `apiclient_key.pem - 商户 API 私钥文件
2. `wechatpay_cert.pem - 微信支付平台证书文件

## 微信支付证书获取指南：
- 商户私钥：在微信支付商户平台 -> 账户中心 -> API安全 -> API证书 -> 申请API证书 -> 下载证书解压后获得
- 平台证书：使用微信支付官方工具下载平台证书下载器获取

参考文档：https://pay.weixin.qq.com/docs/merchant/development/book-shop/wechat-pay/development-tools/certificate-tools.html

重要提醒：请妥善保管证书文件，切勿泄露
