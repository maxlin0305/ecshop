<?php

namespace PointBundle\Jobs;

use EspierBundle\Jobs\Job;
use PointBundle\Services\PointMemberService;

class SendMemberPointJob extends Job
{
    /**
     * 上传文件的基本信息
     */
    protected $uploadFileInfo;

    public function __construct()
    {
    }

    /**
     * 运行任务。
     *
     * @param  Mailer  $mailer
     * @return void
     */
    public function handle()
    {
        $pointMemberService = new PointMemberService();
        $pointMemberService->SendMemberPoint();
    }
}
