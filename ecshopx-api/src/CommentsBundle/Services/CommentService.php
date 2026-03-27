<?php

namespace CommentsBundle\Services;

use CommentsBundle\Interfaces\CommentInterface;

class CommentService
{
    /**
     * @var CommentInterface
     */
    public $commentInterface;

    /**
     * CommentService
     */
    public function __construct(CommentInterface $commentInterface)
    {
        $this->commentInterface = $commentInterface;
    }

    /**
     * Dynamically call the CommentService instance.
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->commentInterface->$method(...$parameters);
    }
}
