<?php

namespace OpenapiBundle\Data;

abstract class BaseData
{
    private function __construct()
    {
    }

    /**
     * 单例列表
     * @var static
     */
    protected static $instance;

    /**
     * 根据不同类型做单例操作
     * @param string $moduleType
     * @return $this
     */
    public static function instance(): self
    {
        if (!(self::$instance instanceof static)) {
            self::$instance = new static();
        }
        return self::$instance;
    }

    /**
     * 存储参数的数组
     * @var array
     */
    protected $data = [];

    /**
     * 设置参数
     * @param string $key
     * @param $value
     */
    final public function set(string $key, $value)
    {
        $this->data[$key] = $value;
    }

    /**
     * 获取值
     * @return array
     */
    final public function get(): array
    {
        return $this->data;
    }
}
