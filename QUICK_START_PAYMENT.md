# 🚀 支付宝支付快速配置指南

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

## ❓ 常见问题速查

| 问题 | 解决方案 |
|------|---------|
| 签名验证失败 | 检查密钥格式，确保在一行且无标记 |
| 跳转后显示错误 | 检查APP_ID是否正确 |
| 收不到异步通知 | 确保URL可公网访问，或使用ngrok |
| 订单状态未更新 | 检查 `logs/error.log` 查看详细错误 |

---

## 📞 需要帮助？

- 详细文档：[PAYMENT_README.md](PAYMENT_README.md)
- 支付宝官方文档：https://opendocs.alipay.com/
- 查看错误日志：`logs/error.log`

---

**祝你集成顺利！** 🎉
