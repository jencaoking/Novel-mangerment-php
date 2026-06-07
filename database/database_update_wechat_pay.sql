-- BookMusic Mall - 微信支付支持数据库更新
-- 为 orders 表添加支付渠道字段

ALTER TABLE `orders`
ADD COLUMN `payment_channel` ENUM('alipay', 'wechat') NOT NULL DEFAULT 'alipay' COMMENT '支付渠道：alipay=支付宝，wechat=微信支付' AFTER `status`;

-- 为已存在的订单设置默认支付渠道为 alipay
UPDATE `orders` SET `payment_channel` = 'alipay' WHERE `payment_channel` IS NULL OR `payment_channel` = '';
