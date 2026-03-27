<?php

namespace CompanysBundle\Jobs;

use EspierBundle\Jobs\Job;
use ThirdPartyBundle\Services\MarketingCenter\Request;

class EmployeeJob extends Job
{
    public $data;

    /**
     * 创建一个新的任务实例。
     *
     * @return void
     */
    public function __construct($params)
    {
        $this->data = $params;
    }

    /**
     * 运行任务。
     *
     * @return void
     */
    public function handle()
    {
        $input = $this->data;
        foreach ($input as &$value) {
            if (is_int($value)) {
                $value = strval($value);
            }
            if (is_null($value)) {
                $value = '';
            }
            if (is_array($value) && empty($value)) {
                $value = '';
            }
        }
        $params[0] = $input;
        $request = new Request();
        $request->call($input['company_id'], 'basics.user.proccess', $params);
        return true;
    }
}
