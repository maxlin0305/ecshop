<?php

namespace CommentsBundle\Interfaces;

interface CommentInterface
{
    /**
     * add comment
     *
     * @param $postdata
     * @return
     */
    public function createComment($postdata);
}
