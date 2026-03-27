<?php

namespace DataCubeBundle\Interfaces;

interface MiniProgramInterface
{
    /**
     * getPages
     *
     * @return
     */
    public function getPages();

    /**
     * generatePath
     *
     * @param  array  $pathInfo
     * @return
     */
    public function generatePath(array $pathInfo);
}
