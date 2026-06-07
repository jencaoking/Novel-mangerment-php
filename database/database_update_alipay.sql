-- 为orders表添加支付宝交易号字段
ALTER TABLE orders 
ADD COLUMN trade_no VARCHAR(64) DEFAULT NULL COMMENT '支付宝交易号' AFTER pay_time;

-- 添加索引以提高查询效率
CREATE INDEX idx_trade_no ON orders(trade_no);
CREATE INDEX idx_order_status ON orders(status);
