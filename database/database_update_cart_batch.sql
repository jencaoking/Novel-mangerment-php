-- 购物车与合并支付功能数据库更新脚本
-- 为批次流水号模式添加索引优化

USE bookmusic_mall;

-- 为 orders.trade_no 字段添加索引，优化批次查询性能
-- 该字段用于存储批次流水号（Batch Trade No），支持合并支付场景
ALTER TABLE orders ADD INDEX idx_trade_no (trade_no);

-- 验证索引是否创建成功
SHOW INDEX FROM orders WHERE Key_name = 'idx_trade_no';
