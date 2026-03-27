<?php

namespace SelfserviceBundle\Services;

use PromotionsBundle\Services\SmsManagerService;
use SelfserviceBundle\Entities\RegistrationRecord;

class RegistrationRecordService
{
    private $entityRepository;

    public function __construct()
    {
        $this->entityRepository = app('registry')->getManager('default')->getRepository(RegistrationRecord::class);
    }

    public function saveData($params, $filter = [])
    {
        if ($filter) {
            return $this->entityRepository->updateOneBy($filter, $params);
        } else {
            return $this->entityRepository->create($params);
        }
    }

    // 如果可以直接调取Repositories中的方法，则直接调用
    public function __call($method, $parameters)
    {
        return $this->entityRepository->$method(...$parameters);
    }

    public function getRocordList($filter, $page = 1, $pageSize = -1, $orderBy = [])
    {
        $lists = $this->entityRepository->lists($filter, '*', $page, $pageSize, $orderBy);
        if (!($lists['list'] ?? [])) {
            return [];
        }

        $activityIds = array_column($lists['list'], 'activity_id');
        $registrationActivityService = new RegistrationActivityService();
        $ayList = $registrationActivityService->getLists(['activity_id' => $activityIds], 'activity_id, activity_name,start_time,end_time,join_limit,is_sms_notice,is_wxapp_notice');
        $aylist = array_column($ayList, null, 'activity_id');
        foreach ($lists['list'] as &$v) {
            $v['activity_name'] = $aylist[$v['activity_id']]['activity_name'] ?? '';
            $v['start_time'] = $aylist[$v['activity_id']]['start_time'] ?? '';
            $v['end_time'] = $aylist[$v['activity_id']]['end_time'] ?? '';
            $v['start_date'] = $v['start_time'] ? date('Y-m-d H:i:s', $v['start_time']) : '';
            $v['end_date'] = $v['end_time'] ? date('Y-m-d H:i:s', $v['end_time']) : '';
            $v['join_limit'] = $aylist[$v['activity_id']]['join_limit'] ?? 1;
            $v['is_sms_notice'] = $aylist[$v['activity_id']]['is_sms_notice'] ?? false;
            $v['is_wxapp_notice'] = $aylist[$v['activity_id']]['is_wxapp_notice'] ?? false;
            $v['content'] = (array)json_decode($v['content'], true);
            $v['create_date'] = date('Y-m-d H:i:s', $v['created']);
        }
        return $lists;
    }

    public function getRocordInfo($id)
    {
        $info = $this->entityRepository->getInfoById($id);
        if (!$info) {
            return [];
        }
        $registrationActivityService = new RegistrationActivityService();
        $aylist = $registrationActivityService->getInfo(['activity_id' => $info['activity_id']], 'activity_id, activity_name,start_time,end_time,join_limit,is_sms_notice,is_wxapp_notice');
        $info['activity_name'] = $aylist['activity_name'] ?? '';
        $info['start_time'] = $aylist['start_time'] ?? '';
        $info['end_time'] = $aylist['end_time'] ?? '';
        $info['start_date'] = $aylist['start_time'] ? date('Y-m-d H:i:s', $aylist['start_time']) : '';
        $info['end_date'] = $aylist['end_time'] ? date('Y-m-d H:i:s', $aylist['end_time']) : '';
        $info['join_limit'] = $aylist['join_limit'] ?? 1;
        $info['is_sms_notice'] = $aylist['is_sms_notice'] ?? false;
        $info['is_wxapp_notice'] = $aylist['is_wxapp_notice'] ?? false;
        $info['content'] = (array)json_decode($info['content'], true);
        return $info;
    }



    public function sendMassage($companyId, $recordId)
    {
        try {
            $record = $this->entityRepository->getInfo(['company_id' => $companyId, 'record_id' => $recordId]);
            $registrationActivityService = new RegistrationActivityService();
            $activity = $registrationActivityService->getInfo(['company_id' => $companyId, 'activity_id' => $record['activity_id']]);

            $content = [
                'activity_name' => $activity['activity_name'],
                'review_result' => $record['status'] == 'passed' ? '报名通过，允许参与' : '报名被拒绝',
            ];
            if ($activity['is_sms_notice'] == 'true') {
                $this->sendSmsMsg($companyId, $content, $record['mobile']);
            }
            if ($activity['is_wxapp_notice'] == 'true') {
                $this->sendWxappMsg($companyId, $content, $record['wxapp_appid'], $record['open_id']);
            }
            return true;
        } catch (\Exception $e) {
            app('log')->debug('报名活动审核通知'.$e->getMessage());
        }
    }

    private function sendSmsMsg($companyId, $content, $mobile)
    {
        //判断短信模版是否开启
        $smsManagerService = new SmsManagerService($companyId);
        $templateData = $smsManagerService->getOpenTemplateInfo($companyId, 'registration_result_notice');
        if (!$templateData) {
            return true;
        }

        try {
            app('log')->debug('短信发送内容: registration_result_notice =>'.$mobile."---".var_export($content, 1));
            $smsManagerService->send($mobile, $companyId, 'registration_result_notice', $content);
        } catch (\Exception $e) {
            app('log')->debug('短信发送失败: registration_result_notice =>'.$e->getMessage());
        }
    }

    private function sendWxappMsg($companyId, $content, $wxappId, $openId)
    {
        try {
            $sendData['scenes_name'] = 'registrationResultNotice';
            $sendData['company_id'] = $companyId;
            $sendData['appid'] = $wxappId;
            $sendData['openid'] = $openId;
            $sendData['data'] = $content;
            app('log')->debug('小程序模板消息发送内容: registration_result_notice =>'.var_export($sendData, 1));
            app('wxaTemplateMsg')->send($sendData);
        } catch (\Exception $e) {
            app('log')->debug('小程序模板消息发送失败: registration_result_notice =>'.$e->getMessage());
        }
    }

    /**
     * 处理报名详情的字段脱敏
     * @param  array $content       报名详情的内容
     * @param  int $datapassBlock 是否脱敏
     * @return array                处理后的数据
     */
    public function fixeddecryptRocordContent($content, $datapassBlock)
    {
        if (!$content || !$datapassBlock) {
            return $content;
        }
        foreach ($content as $key => &$value) {
            foreach ($value['formdata'] as $k => &$formdata) {
                $formdata['answer'] = $formdata['answer'] ?? '';
                if ($formdata['field_name'] == 'username') {
                    $formdata['answer'] = data_masking('truename', (string) $formdata['answer']);
                }
                if ($formdata['field_name'] == 'mobile') {
                    $formdata['answer'] = data_masking('mobile', (string) $formdata['answer']);
                }
                if ($formdata['field_name'] == 'birthday') {
                    $formdata['answer'] = data_masking('birthday', (string) $formdata['answer']);
                }
                if ($formdata['field_name'] == 'bankcard') {
                    $formdata['answer'] = data_masking('bankcard', (string) $formdata['answer']);
                }
                if ($formdata['field_name'] == 'idcard') {
                    $formdata['answer'] = data_masking('idcard', (string) $formdata['answer']);
                }
                if ($formdata['field_name'] == 'address') {
                    $formdata['answer'] = data_masking('detailedaddress', (string) $formdata['answer']);
                }
            }
        }
        return $content;
    }
}
