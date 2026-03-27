<?php

namespace MembersBundle\Services;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use GuzzleHttp\Client as Client;

class MemberWhitelistUploadService
{
    public $header = [
        '手機號碼' => 'mobile',
        '姓名' => 'name',
    ];

    public $headerInfo = [
        '手機號碼' => ['size' => 32, 'remarks' => '不得重復，手機號如果大於11位時，請關閉excel單元格的科學記數法，常用禁用方法：「單元格格式」-「自定義」-「類型」改為「0」', 'is_need' => true],
        '姓名' => ['size' => 20, 'remarks' => '', 'is_need' => true],
    ];

    public $isNeedCols = [
        '手機號碼' => 'mobile',
        '姓名' => 'name',
    ];

    /**
     * 驗證上傳的白名單
     */
    public function check($fileObject)
    {
        $extension = $fileObject->getClientOriginalExtension();
        if ($extension != 'xlsx') {
            throw new BadRequestHttpException('白名單上傳只支持Excel文件格式');
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
     * 獲取頭部標題
     */
    public function getHeaderTitle()
    {
        return ['all' => $this->header, 'is_need' => $this->isNeedCols, 'headerInfo' => $this->headerInfo];
    }

    private function validatorData($row)
    {
        $arr = ['mobile', 'name'];
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
            'mobile' => ['required|max:32', '請填寫正確的手機號'],
            'name' => ['required|max:20', '請填寫正確的姓名'],
        ];
        $errorMessage = validator_params($validatorData, $rules, false);
        if ($errorMessage) {
            $msg = implode(', ', $errorMessage);
            throw new BadRequestHttpException($msg);
        }
        $whitelistService = new MembersWhitelistService();
        $ismobile = ismobile($row['mobile']);
        if (!$ismobile) {
            throw new BadRequestHttpException('請填寫正確的手機號');
        }

        $whitelist = $whitelistService->getInfo(['company_id' => $companyId, 'mobile' => $row['mobile']]);
        if ($whitelist) {
            throw new BadRequestHttpException('當前手機號的白名單已經存在');
        }

        //新增
        $whitelist_data = [
            'company_id' => $companyId,
            'mobile' => trim($row['mobile']),
            'name' => trim($row['name']),
        ];
        $result = $whitelistService->createData($whitelist_data);
    }
}
