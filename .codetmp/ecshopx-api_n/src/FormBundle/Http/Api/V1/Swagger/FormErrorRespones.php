<?php

namespace FormBundle\Http\Api\V1\Swagger;

/**
 * @SWG\Definition(type="object")
 */
class FormErrorRespones
{
    /**
     * @SWG\Property
     * @var string
     */
    public $message;

    /**
     * @SWG\Property
     * @var array
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
     * @var array
     */
    public $debug;
}
