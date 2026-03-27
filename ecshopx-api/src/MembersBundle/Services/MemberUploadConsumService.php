<?php

namespace MembersBundle\Services;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use GuzzleHttp\Client as Client;

class MemberUploadConsumService
{
    public $header = [
        '手機號碼' => 'mobile',
        '消費金額' => 'consumption',
    ];

    public $isNeedCols = [
        '手機號碼' => 'mobile',
        '消費金額' => 'consumption',
    ];

    public $headerInfo = [
        '手機號碼' => ['size' => 32, 'remarks' => '手機號必須是會員', 'is_need' => true],
        '消費金額' => ['size' => 10, 'remarks' => '消費金額以元位單位', 'is_need' => true],
    ];

    /**
     * 验证上传的会员信息
     */
    public function check($fileObject)
    {
        $extension = $fileObject->getClientOriginalExtension();
        if ($extension != 'xlsx') {
            throw new BadRequestHttpException('会员信息上传只支持Excel文件格式');
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

    public function handleRow($companyId, $row)
    {
        $rules = [
            'mobile' => ['required|max:32', '请填写正确的手机号'],
            'consumption' => ['required|numeric', '请上传有效的消费金额'],
        ];
        $errorMessage = validator_params($row, $rules, false);
        if ($errorMessage) {
            $msg = implode(', ', $errorMessage);
            throw new BadRequestHttpException($msg);
        }

        $consumption = round($row['consumption']);
        $memberService = new MemberService();
        $userId = $memberService->getUserIdByMobile($row['mobile'], $companyId);
        if (!$userId) {
            throw new BadRequestHttpException('手机号不存在');
        }

        $memberService->updateMemberConsumption($userId, $companyId, $consumption);
    }
}
