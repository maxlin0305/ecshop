<?php

namespace CompanysBundle\Interfaces;

interface OperatorLogsInterface
{
    /**
     * addLogs
     *
     * @param $logsInfo
     * @return
     */
    public function addLogs($logsInfo);

    /**
     * get getLogsList
     *
     * @param  filter
     * @param  page
     * @param  pageSize
     * @param  orderBy
     * @return
     */
    public function getLogsList($filter, $page, $pageSize, $orderBy);

    /**
     * deleteLogs
     *
     * @param filter
     * @return
     */
    public function deleteLogs($filter);
}
