<?php

namespace OpenapiBundle\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

class ApiResponseException extends HttpException
{
    public function __construct(string $message = "", ?string $errorCode = null)
    {
        parent::__construct(200, $message, null, [], $errorCode);
    }

    /**
     * 响应体类型
     * @var string
     */
    protected $dataType = "json";

    /**
     * 获取响应体类型
     * @return string
     */
    public function getDataType(): string
    {
        return $this->dataType;
    }

    /**
     * 设置响应体类型
     * @param string $dataType
     */
    public function setDataType(string $dataType): void
    {
        $this->dataType = $dataType;
    }

    /**
     * 数据格式
     * @var array
     */
    protected $data = [
        "status" => "success", // 响应状态
        "code" => 0, // 错误码
        "message" => "", // 返回信息
        "data" => null // 返回数据
    ];

    /**
     * 设置数据
     * @param array $result
     */
    public function set(array $result)
    {
        $this->data = $result;
    }

    /**
     * 返回数据
     * @return array
     */
    public function get(): array
    {
        return $this->data;
    }
}
