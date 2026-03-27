<?php

namespace PromotionsBundle\Services;

use App\Jobs\SmsCodeSendJob;
use PromotionsBundle\Entities\SmsIdiograph;
use PromotionsBundle\Entities\SmsTemplate;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * 短信服务
 */
class SmsService
{
    public $driver;
    public function __construct($smsInterface = null)
    {
        if ($smsInterface) {
            $this->driver = $smsInterface->connection();
        }
    }

    /**
     * 发送通知
     * @params $sendToPhones array 发送到的手机号
     * @params $companyId int 企业ID
     * @params $templateName string 模版名称
     * @params $data array 模版需要的数据
     */
    public function send($sendToPhones, $companyId, $templateName, $data): void
    {
        $smsSign = $this->getSmsSign($companyId);
        if ($smsSign) {
            $sign = '【'.$smsSign.'】';
        } else {
            throw new BadRequestHttpException('签名未设置,不能发送短');
        }

        //获取到模版短信类型
        $templateData = $this->getTemplateInfo($companyId, $templateName);
        if (!$templateData) {
            $defaultSmsTemplateService = new DefaultSmsTemplateService();
            $templateData = $defaultSmsTemplateService->getByName($templateName);
            if (!empty($templateData['is_open'])) {
                $templateData['is_open'] = 'true';
                $this->updateTemplate($companyId, $templateName, $templateData);
            } else {
                throw new BadRequestHttpException('短信模版未启用');
            }
        } else {
            if ($templateData['is_open'] != 'true') {
                throw new BadRequestHttpException('短信模版未启用');
            }
        }
        $sendType = $templateData['sms_type'];
        $contents = $this->templateCompilers($templateData['content'], $data);
        //处理数据
        $contents .= $sign;
        if (!is_array($sendToPhones)) {
            $sendToPhones = [
                $sendToPhones,
            ];
        }
        $gotoJob = (new SmsCodeSendJob($sendToPhones, $contents))->onQueue('sms');
        app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($gotoJob);
    }

    /**
     * 发送指定内容短信
     */
    public function sendContent($companyId, $sendToPhones, $data, $sendType = 'notice')
    {
        $smsSign = $this->getSmsSign($companyId);
        if ($smsSign) {
            $sign = '【'.$smsSign.'】';
        } else {
            throw new BadRequestHttpException('签名未设置,不能发送短');
        }
        if ($sendType == 'notice') {
            if (is_array($data)) {
                $contents = $this->templateCompilers($data['content'], $data['params'], $data['replaceParams']);
            } else {
                $contents = $data;
            }
        } elseif ($sendType == 'fan-out') {
            $contents = $data;
            $contents .= " 退订回N";
            $sendToPhones = is_array($sendToPhones) ? implode(',', $sendToPhones) : $sendToPhones;
        }
        //处理数据
        $contents .= $sign;
        $smsContents = [
            ['phones' => $sendToPhones, 'content' => $contents]
        ];
        return $this->driver->send($smsContents, $sendType);
    }

    //编译模版
    private function templateCompilers($tmplContent, $data, $replaceParams = null)
    {
        $defaultSmsTemplateService = new DefaultSmsTemplateService();
        if (!$replaceParams) {
            $replaceParams = $defaultSmsTemplateService->getReplaceParams();
        }
        $contents = $tmplContent;
        if ($replaceParams && $data) {
            foreach ($replaceParams as $paramsKey => $paramsValue) {
                if (isset($data[$paramsKey])) {
                    $replacements[$paramsKey] = $data[$paramsKey];
                } else {
                    $replacements[$paramsKey] = '';
                }
                $patterns[$paramsValue] = '/{{'.$paramsValue.'}}/';
            }
            $contents = preg_replace($patterns, $replacements, $tmplContent);
        }

        return $contents;
    }

    /*
     * 根据模版名称获取到已开启的模版的配置信息
     *
     * @params $companyId int 企业ID
     * @params $templateName string 模版名称
     */
    public function getOpenTemplateInfo($companyId, $templateName)
    {
        $smsTemplateRepository = app('registry')->getManager('default')->getRepository(SmsTemplate::class);
        $templateInfo = $smsTemplateRepository->get(['company_id' => $companyId, 'tmpl_name' => $templateName]);
        if ($templateInfo && $templateInfo['is_open'] == 'true') {
            return $templateInfo;
        } else {
            return null;
        }
    }

    /**
     *  查询短信模板
     * @param $companyId
     * @param $templateName
     * @return null
     */
    public function getTemplateInfo($companyId, $templateName)
    {
        $smsTemplateRepository = app('registry')->getManager('default')->getRepository(SmsTemplate::class);
        $templateInfo = $smsTemplateRepository->get(['company_id' => $companyId, 'tmpl_name' => $templateName]);
        if ($templateInfo) {
            return $templateInfo;
        } else {
            return null;
        }
    }

    /*
     *  模版列表
     *
     * @params $companyId int 企业ID
     */
    public function listsTemplateByCompanyId($companyId)
    {
        $smsTemplateRepository = app('registry')->getManager('default')->getRepository(SmsTemplate::class);
        $templateLists = $smsTemplateRepository->lists(['company_id' => $companyId]);
        foreach ($templateLists['list'] as $row) {
            $list[$row['tmpl_name']] = $row;
        }

        $defaultSmsTemplateService = new DefaultSmsTemplateService();
        $defaultLists = $defaultSmsTemplateService->lists();
        $return = [];
        foreach ($defaultLists as $tmplName => $row) {
            if (isset($list[$tmplName])) {
                $return[$row['tmpl_type']][] = $list[$tmplName];
            } else {
                if (!empty($row['is_open'])) {
                    $row['is_open'] = 'true';
                }
                $return[$row['tmpl_type']][] = $row;
            }
        }

        return $return;
    }

    //启用模版
    public function updateTemplate($companyId, $templateName, $params)
    {
        $smsTemplateRepository = app('registry')->getManager('default')->getRepository(SmsTemplate::class);
        $templateInfo = $smsTemplateRepository->get(['company_id' => $companyId, 'tmpl_name' => $templateName]);
        if ($templateInfo) {
            $smsTemplateRepository->updateTemplate($companyId, $templateName, $params);
        } else {
            $defaultSmsTemplateService = new DefaultSmsTemplateService();
            $templateInfo = $defaultSmsTemplateService->getByName($templateName);
            $templateInfo['company_id'] = $companyId;
            $templateInfo['is_open'] = $params['is_open'];
            $smsTemplateRepository->create($templateInfo);
        }
        return true;
    }

    /**
     * 保存短信签名
     */
    public function saveSmsSign($shopexUid, $companyId, $newContent)
    {
        if ($this->checkSign($newContent)) {
            $content = '【'.$newContent.'】';
        }

        $smsIdiograph = app('registry')->getManager('default')->getRepository(SmsIdiograph::class);
        $idiograph = $smsIdiograph->get(['shopex_uid' => $shopexUid, 'company_id' => $companyId]);
        if ($idiograph) {
            $oldContent = '【'.$idiograph->getIdiograph().'】';
            $this->driver->updateSmsSign($content, $oldContent);
            return $smsIdiograph->update($shopexUid, $companyId, $newContent);
        } else {
            $this->driver->addSmsSign($content);
            return $smsIdiograph->create($shopexUid, $companyId, $newContent);
        }
    }

    /**
     * 获取短信签名
     */
    public function getSmsSign($companyId)
    {
        $smsIdiograph = app('registry')->getManager('default')->getRepository(SmsIdiograph::class);
        $idiograph = $smsIdiograph->get(['company_id' => $companyId]);
        if ($idiograph) {
            return $idiograph->getIdiograph();
        } else {
            return null;
        }
    }

    private function checkSign($sign)
    {
        if (mb_strlen(urldecode(trim($sign)), 'utf-8') > 8 || mb_strlen(urldecode(trim($sign)), 'utf-8') < 3) {
            throw new BadRequestHttpException('签名长度为3到8字');
        }

        $arr = array('天猫','tmall','淘宝','taobao','1号店','易迅','京东','亚马逊','test','测试');
        for ($i = 0; $i < count($arr) ; $i++) {
            if (strstr(strtolower($sign), $arr[$i])) {
                throw new BadRequestHttpException('非法签名');
            }
        }

        $arr = array(
            '【', '】',
        );
        if ((strstr($sign, $arr[0]) && (strstr($sign, $arr[1]))) != false) {
            throw new BadRequestHttpException('签名中含有非法字符');
        }

        return true;
    }

    /**
     * Dynamically call the rightsService instance.
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->driver->$method(...$parameters);
    }
}
