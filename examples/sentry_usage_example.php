<?php
/**
 * Sentry 使用示例
 * 
 * 展示如何在项目中使用 Sentry 进行错误监控和性能追踪
 */

// 示例 1: 在控制器中捕获异常
class ExampleController {
    
    public function exampleAction() {
        try {
            // 模拟可能失败的操作
            $result = $this->riskyOperation();
            return ['success' => true, 'data' => $result];
            
        } catch (\Throwable $e) {
            // 捕获异常并发送到 Sentry
            if (defined('SENTRY_DSN') && !empty(SENTRY_DSN)) {
                \Sentry\captureException($e);
            }
            
            // 记录日志
            error_log("Example action failed: " . $e->getMessage());
            
            return ['success' => false, 'error' => '操作失败'];
        }
    }
    
    private function riskyOperation() {
        // 模拟可能抛出异常的操作
        if (rand(0, 1) === 0) {
            throw new \Exception("随机失败 - 这是一个测试异常");
        }
        return "操作成功";
    }
}

// 示例 2: 添加上下文信息
function processOrderWithSentry($orderId, $userId) {
    if (defined('SENTRY_DSN') && !empty(SENTRY_DSN)) {
        // 添加用户上下文
        \Sentry\configureScope(function (\Sentry\State\Scope $scope) use ($userId, $orderId) {
            $scope->setUser(['id' => $userId]);
            $scope->setTag('order_id', $orderId);
            $scope->setTag('operation', 'process_order');
        });
    }
    
    try {
        // 处理订单逻辑
        echo "处理订单: $orderId, 用户: $userId\n";
        
        // 模拟订单处理
        if ($orderId <= 0) {
            throw new \InvalidArgumentException("无效的订单ID: $orderId");
        }
        
        return true;
        
    } catch (\Throwable $e) {
        if (defined('SENTRY_DSN') && !empty(SENTRY_DSN)) {
            \Sentry\captureException($e);
        }
        throw $e;
    }
}

// 示例 3: 性能监控（事务）
function monitoredDatabaseQuery($query) {
    $transaction = null;
    
    if (defined('SENTRY_DSN') && !empty(SENTRY_DSN)) {
        $transactionContext = new \Sentry\Tracing\TransactionContext();
        $transactionContext->setOp('db.query');
        $transactionContext->setName(substr($query, 0, 50)); // 限制名称长度
        $transaction = \Sentry\startTransaction($transactionContext);
    }
    
    try {
        // 执行数据库查询
        echo "执行查询: $query\n";
        $result = "查询结果";
        
        if ($transaction) {
            $transaction->setStatus(\Sentry\Tracing\SpanStatus::ok());
        }
        
        return $result;
        
    } catch (\Throwable $e) {
        if ($transaction) {
            $transaction->setStatus(\Sentry\Tracing\SpanStatus::internalError());
        }
        if (defined('SENTRY_DSN') && !empty(SENTRY_DSN)) {
            \Sentry\captureException($e);
        }
        throw $e;
        
    } finally {
        if ($transaction) {
            $transaction->finish();
        }
    }
}

// 示例 4: 捕获最后一个错误
function handleErrorExample() {
    // 触发一个警告
    $result = @file_get_contents('non_existent_file.txt');
    
    // 捕获最后的错误
    if (defined('SENTRY_DSN') && !empty(SENTRY_DSN)) {
        \Sentry\captureLastError();
    }
    
    echo "已捕获最后错误\n";
}

// 运行示例
echo "=== Sentry 使用示例 ===\n\n";

echo "1. 异常捕获示例:\n";
$controller = new ExampleController();
$result = $controller->exampleAction();
print_r($result);
echo "\n";

echo "2. 带上下文的订单处理:\n";
try {
    processOrderWithSentry('ORD-12345', 'USER-67890');
    echo "订单处理成功\n";
} catch (\Throwable $e) {
    echo "订单处理失败: " . $e->getMessage() . "\n";
}
echo "\n";

echo "3. 性能监控示例:\n";
monitoredDatabaseQuery("SELECT * FROM users WHERE id = 1");
echo "\n";

echo "4. 错误捕获示例:\n";
handleErrorExample();
echo "\n";

echo "=== 示例完成 ===\n";
echo "请检查 Sentry 仪表板查看捕获的事件\n";
