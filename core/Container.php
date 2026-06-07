<?php
namespace Core;

use ReflectionClass;
use Exception;

class Container {
    private $instances = [];

    public function get($className) {
        if (isset($this->instances[$className])) {
            return $this->instances[$className];
        }

        try {
            $reflector = new ReflectionClass($className);
        } catch (\ReflectionException $e) {
            throw new Exception("类不存在: [$className]", 500, $e);
        }

        if (!$reflector->isInstantiable()) {
            throw new Exception("类无法被实例化: [$className]");
        }

        $constructor = $reflector->getConstructor();

        if (is_null($constructor)) {
            $instance = new $className();
            $this->instances[$className] = $instance;
            return $instance;
        }

        $parameters = $constructor->getParameters();
        $dependencies = $this->resolveDependencies($parameters);

        $instance = $reflector->newInstanceArgs($dependencies);
        $this->instances[$className] = $instance;
        
        return $instance;
    }

    private function resolveDependencies($parameters) {
        $dependencies = [];
        foreach ($parameters as $parameter) {
            $type = $parameter->getType();
            
            if ($type === null) {
                if ($parameter->isDefaultValueAvailable()) {
                    $dependencies[] = $parameter->getDefaultValue();
                } else {
                    throw new Exception("无法解析类的依赖参数: {$parameter->getName()}");
                }
            } else {
                $dependencies[] = $this->get($type->getName());
            }
        }
        return $dependencies;
    }
}