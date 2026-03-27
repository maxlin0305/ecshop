<?php

namespace ThemeBundle\Http\Api\V1\Swagger;

/**
 * @SWG\Definition(type="object")
 */
class WechatErrorRespones
{
    /**
     * @SWG\Property
     * @var string
     */
    public $message;

    /**
     * @SWG\Property
     */
    public $errors;

    /**
     * @SWG\Property(format="int32")
     * @var int
     */
    public $code;

    /**
     * @SWG\Property(format="int32")
     * @var int
     */
    public $status_code;

    /**
     * @SWG\Property
     */
    public $debug;
}
