<?php

namespace ReservationBundle\Http\Api\V1\Swagger;

/**
 * @SWG\Definition(type="object")
 */
class ReservationErrorResponse
{
    /**
     * @SWG\Property
     * @var string
     */
    public $message;

    /**
     * @SWG\Property
     * @var string
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
     * @SWG\Property(format="int32")
     * @var string
     */
    public $debug;
}
