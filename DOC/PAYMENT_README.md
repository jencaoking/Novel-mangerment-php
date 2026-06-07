# 支付宝支付集成说明

## 📋 功能概述

本项目已成功集成支付宝PC网站支付功能,支持:
- ✅ 用户点击购买后自动跳转至支付宝支付页面
- ✅ 异步通知处理(服务器到服务器)
- ✅ 同步返回处理(用户浏览器跳转)
- ✅ 订单状态自动更新
- ✅ 签名验证防止伪造
- ✅ 幂等性处理避免重复回调

---

## ⚡ 5分钟快速开始

### 第一步：执行数据库更新

```bash
mysql -u root -p bookmusic < database_update_alipay.sql
```

### 第二步：配置支付宝参数

编辑 `.env` 文件，填写以下信息：

```env
# 将以下占位符替换为你的真实配置
ALIPAY_APP_ID=2021xxxxxxxxxxxxx
ALIPAY_PRIVATE_KEY=MIIEvQIBADANBgkqhkiG9w0BAQEFAASCBKcwggSjAgEAAoIBAQC...
ALIPAY_PUBLIC_KEY=MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA...
ALIPAY_GATEWAY_URL=https://openapi.alipay.com/gateway.do
ALIPAY_RETURN_URL=http://localhost:8000/payment/return
ALIPAY_NOTIFY_URL=http://localhost:8000/payment/notify
```

**如何获取这些值？**

1. **APP_ID**: 登录 [支付宝开放平台](https://open.alipay.com/) → 控制台 → 应用列表 → 查看APP_ID

2. **私钥和公钥**: 
   - 下载 [密钥生成工具](https://opendocs.alipay.com/common/02kipk)
   - 生成RSA2密钥对
   - 在开放平台上传"应用公钥"
   - 复制生成的"应用私钥"到 `ALIPAY_PRIVATE_KEY`
   - 复制平台返回的"支付宝公钥"到 `ALIPAY_PUBLIC_KEY`

⚠️ **重要**: 私钥和公钥需要是**纯文本**，去除所有 `-----BEGIN...-----` 标记和换行符！

### 第三步：测试支付

1. 启动项目
2. 以用户身份登录
3. 浏览商品并点击"立即购买"
4. 系统会自动跳转到支付宝支付页面

---

## 🔧 配置步骤

### 1. 申请支付宝开放平台账号

1. 访问 [支付宝开放平台](https://open.alipay.com/)
2. 注册并实名认证
3. 创建应用,获取 `APP_ID`

### 2. 生成密钥对

#### 方式一:使用支付宝密钥生成工具(推荐)
1. 下载 [支付宝密钥生成工具](https://opendocs.alipay.com/common/02kipk)
2. 生成应用私钥和应用公钥
3. 在支付宝开放平台上传应用公钥,获取支付宝公钥

#### 方式二:使用OpenSSL命令
```bash
# 生成私钥
openssl genrsa -out app_private_key.pem 2048

# 生成公钥
openssl rsa -in app_private_key.pem -pubout -out app_public_key.pem
```

### 3. 配置项目参数

编辑 `.env` 文件,填写以下配置:

```env
# 支付宝支付配置
ALIPAY_APP_ID=你的APP_ID
ALIPAY_PRIVATE_KEY=你的应用私钥(不包含-----BEGIN RSA PRIVATE KEY-----等标记)
ALIPAY_PUBLIC_KEY=支付宝公钥(从开放平台获取,不包含标记)
ALIPAY_GATEWAY_URL=https://openapi.alipay.com/gateway.do
ALIPAY_RETURN_URL=http://你的域名/payment/return
ALIPAY_NOTIFY_URL=http://你的域名/payment/notify
```

**重要提示:**
- 私钥和公钥需要去除首尾的 `-----BEGIN...-----` 和 `-----END...-----` 标记
- 将换行符替换为空字符串,保持在一行
- 生产环境必须使用HTTPS

### 4. 更新数据库

执行SQL脚本添加必要字段:

```bash
mysql -u root -p bookmusic < database_update_alipay.sql
```

或手动执行:
```sql
ALTER TABLE orders 
ADD COLUMN trade_no VARCHAR(64) DEFAULT NULL COMMENT '支付宝交易号' AFTER pay_time;

CREATE INDEX idx_trade_no ON orders(trade_no);
CREATE INDEX idx_order_status ON orders(status);
```

### 5. 配置支付宝开放平台

在支付宝开放平台的应用设置中:

1. **接口加签方式**: 选择"公钥",上传应用公钥
2. **授权回调地址**: 填写 `http://你的域名/payment/return`
3. **异步通知地址**: 填写 `http://你的域名/payment/notify` (也可在代码中动态指定)

---

## 🧪 沙箱环境测试（推荐）

如果还没有正式的应用，可以使用支付宝沙箱进行测试：

### 1. 创建沙箱应用
访问：https://openhome.alipay.com/develop/sandbox/app

### 2. 获取沙箱配置
- 沙箱APP_ID
- 沙箱应用私钥
- 支付宝公钥

### 3. 修改 `.env`
```env
ALIPAY_APP_ID=你的沙箱APP_ID
ALIPAY_PRIVATE_KEY=沙箱应用私钥
ALIPAY_PUBLIC_KEY=支付宝公钥
ALIPAY_GATEWAY_URL=https://openapi-sandbox.dl.alipaydev.com/gateway.do
```

### 4. 下载沙箱版支付宝
- Android: https://ur.alipay.com/kdW7
- iOS: App Store搜索"沙箱版支付宝"

### 5. 使用沙箱账号测试
在沙箱控制台获取买家和卖家账号进行测试

---

## ✅ 验证是否配置成功

### 检查点1：数据库字段
```sql
DESCRIBE orders;
-- 应该看到 trade_no 字段
```

### 检查点2：路由注册
访问任意商品页面，点击"立即购买"，应该跳转到支付宝

### 检查点3：日志记录
查看 `logs/error.log`，不应该有支付相关的错误

---

## 🚀 使用流程

### 用户购买流程

```
1. 用户浏览商品 → /product/{id}
2. 点击"立即购买"按钮
3. 系统创建订单(状态:pending)
4. 自动跳转至支付宝支付页面
5. 用户完成支付
6. 支付宝异步通知服务器 → /payment/notify
7. 服务器验证签名并更新订单状态为paid
8. 用户浏览器跳转回商城 → /payment/return
9. 显示支付成功,用户可在"我的订单"查看
```

### 订单状态流转

```
pending(待支付) → paid(已支付) → completed(已完成)
                ↓
            cancelled(已取消)
```

---

## 📁 核心文件说明

| 文件路径 | 说明 |
|---------|------|
| `includes/AlipaySDK.php` | 支付宝SDK封装类,处理签名、请求生成 |
| `app/Controllers/PaymentController.php` | 支付控制器,处理支付跳转、回调 |
| `public/index.php` | 路由注册(第77-82行) |
| `.env` | 支付宝配置参数 |
| `includes/config.php` | 配置常量定义(第36-47行) |
| `database_update_alipay.sql` | 数据库变更脚本 |

---

## 🔐 安全特性

### 1. 签名验证
- 所有异步通知和同步返回都经过RSA2签名验证
- 使用支付宝公钥验证,防止伪造请求

### 2. 金额校验
- 异步通知时比对订单金额与通知金额
- 不一致则拒绝处理并记录日志

### 3. 幂等性处理
- 检查订单是否已支付,避免重复处理
- 同一笔订单多次回调只处理一次

### 4. 订单归属验证
- 支付前验证订单归属当前登录用户
- 防止越权操作

---

## ❓ 常见问题速查

| 问题 | 解决方案 |
|------|---------|
| 签名验证失败 | 检查密钥格式，确保在一行且无标记 |
| 跳转后显示错误 | 检查APP_ID是否正确 |
| 收不到异步通知 | 确保URL可公网访问，或使用ngrok |
| 订单状态未更新 | 检查 `logs/error.log` 查看详细错误 |

### Q1: 签名验证失败
**原因:**
- 私钥/公钥格式不正确
- 密钥包含换行符或标记

**解决:**
- 确保密钥在一行,去除所有标记
- 检查是否正确复制密钥内容

### Q2: 异步通知收不到
**原因:**
- 服务器URL不可公网访问
- 防火墙阻止了支付宝请求

**解决:**
- 使用内网穿透工具
- 检查服务器防火墙设置
- 查看支付宝开放平台的异步通知日志

### Q3: 支付成功但订单状态未更新
**原因:**
- 异步通知处理出错
- 数据库更新失败

**解决:**
- 检查 `logs/error.log` 日志
- 确认数据库连接正常
- 验证 `trade_no` 字段是否存在

---

## 📊 监控与日志

### 日志位置
- 错误日志: `logs/error.log`
- PHP错误: 根据 `php.ini` 配置

### 关键日志点
1. 支付跳转: `支付宝支付跳转失败`
2. 异步通知接收: `支付宝异步通知收到数据`
3. 签名验证: `支付宝异步通知签名验证失败`
4. 订单更新: `支付宝异步通知：订单支付成功`

---

## 🔄 后续优化建议

1. **添加微信支付**: 类似架构,创建WechatPay SDK
2. **订单超时取消**: 定时任务检查超时未支付订单
3. **退款功能**: 实现支付宝退款接口
4. **对账功能**: 定期与支付宝账单对账
5. **支付统计**: 增加支付方式、成功率等统计

---

## 📞 技术支持

- 支付宝开放平台文档: https://opendocs.alipay.com/
- 支付宝技术支持: https://open.alipay.com/service

---

**最后更新**: 2026-06-05