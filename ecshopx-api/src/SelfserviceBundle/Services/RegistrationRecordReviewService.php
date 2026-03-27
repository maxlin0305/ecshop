<?php

namespace SelfserviceBundle\Services;

use SelfserviceBundle\Entities\RegistrationRecord;
use SelfserviceBundle\Jobs\RecordReviewNoticeJob;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use GuzzleHttp\Client as Client;

class RegistrationRecordReviewService
{
    public $header = [
        '報名申請編號' => 'record_id',
        '會員手機號碼' => 'mobile',
        '審核結果' => 'review_result',
        '拒絕原因' => 'reason',
    ];

    public $headerInfo = [
        '報名申請編號' => ['size' => 32, 'is_need' => true, 'remarks' => '報名申請標號，在導出報名中可以查看'],
        '會員手機號碼' => ['size' => 32, 'is_need' => true, 'remarks' => '手機號如果大於11位時，請關閉excel單元格的科學記數法，常用禁用方法：「單元格格式」-「自定義」-「類型」改為「0」'],
        '審核結果' => ['size' => 1,  'is_need' => true, 'remarks' => '1：報名通過，0或者不填：報名被拒絕'],
        '拒絕原因' => ['size' => 32, 'is_need' => false, 'remarks' => '拒絕原因，如果被拒絕，此項必填'],
    ];

    public $isNeedCols = [
        '會員手機號碼' => 'mobile',
        '報名申請編號' => 'record_id',
        '審核結果' => 'review_result',
    ];

    /**
     * 验证上传的会员信息
     */
    public function check($fileObject)
    {
        $extension = $fileObject->getClientOriginalExtension();
        if ($extension != 'xlsx') {
            throw new BadRequestHttpException('活动报名审批上传只支持Excel文件格式');
        }
    }

    public $tmpTarget = null;

    /**
     * getFilePath function
     *
     * @return void
     */
    public function getFilePath($filePath, $fileExt = '')
    {
        $url = $this->getFileSystem()->privateDownloadUrl($filePath);

        $client = new Client();
        $content = $client->get($url)->getBody()->getContents();

        $this->tmpTarget = tempnam('/tmp', 'import-file') . $fileExt;
        file_put_contents($this->tmpTarget, $content);

        return $this->tmpTarget;
    }

    public function finishHandle()
    {
        unlink($this->tmpTarget);
        return true;
    }


    public function getFileSystem()
    {
        return app('filesystem')->disk('import-file');
    }

    /**
     * 获取头部标题
     */
    public function getHeaderTitle()
    {
        return ['all' => $this->header, 'is_need' => $this->isNeedCols, 'headerInfo' => $this->headerInfo];
    }

    private function validatorData($row)
    {
        $arr = ['mobile', 'record_id', 'review_result', 'reason'];
        $data = [];
        foreach ($arr as $column) {
            if ($row[$column]) {
                $data[$column] = trim($row[$column]);
            }
        }
        return $data;
    }

    public function handleRow($companyId, $row)
    {
        $validatorData = $this->validatorData($row);

        $rules = [
            'mobile' => ['required|max:32', '请填写正确的手机号'],
            'record_id' => ['required', '请填写正确的报名编号'],
            // 'review_result' => ['required', '请填写正确的审核结果'],
        ];
        $errorMessage = validator_params($validatorData, $rules, false);
        if ($errorMessage) {
            $msg = implode(', ', $errorMessage);
            throw new BadRequestHttpException($msg);
        }

        try {
            $registrationRecordRepository = app('registry')->getManager('default')->getRepository(RegistrationRecord::class);
            $filter = [
                'company_id' => $companyId,
                'record_id' => $row['record_id'],
                'mobile' => strval($row['mobile']),
            ];

            $status = intval($row['review_result']) === 1 ? 'passed' : 'rejected';
            //if ($status == 'rejected' && !$row['reason']) {
            //    throw new BadRequestHttpException('拒绝原因必填');
            //}
            $params = [
                'status' => $status,
                'reason' => $row['reason'] ?? '',
            ];
            $result = $registrationRecordRepository->updateBy($filter, $params);

            $recordReviewNoticeJob = (new RecordReviewNoticeJob($companyId, $row['record_id']))->onQueue('sms');
            app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($recordReviewNoticeJob);
        } catch (\Exception $e) {
            throw new BadRequestHttpException('保存数据错误');
        }
    }
}
