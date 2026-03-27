<?php

namespace GoodsBundle\Services;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use GuzzleHttp\Client as Client;

class NormalGoodsProfitUploadService
{
    public $header = [
        '商品編碼' => 'item_bn',
        '分潤類型' => 'profit_type',
        '拉新分潤' => 'profit',
        '推廣分潤' => 'popularize_profit',
    ];

    public $headerInfo = [
        '商品編碼' => ['size' => 32, 'remarks' => '', 'is_need' => true],
        '分潤類型' => ['size' => 255, 'remarks' => '分潤類型:0,1或2, 0默認分潤 1固定比例分潤 2固定金額分潤', 'is_need' => true],
        '拉新分潤' => ['size' => 255, 'remarks' => '1:按照比例分潤 1-100, 2:按照固定金額分潤(元)，最多兩位小數', 'is_need' => true],
        '推廣分潤' => ['size' => 255, 'remarks' => '1:按照比例分潤 1-100, 2:按照固定金額分潤(元)，最多兩位小數', 'is_need' => true],
    ];

    public $isNeedCols = [
        '商品編碼' => 'item_bn',
        '分潤類型' => 'profit_type',
        '拉新分潤' => 'profit',
        '推廣分潤' => 'popularize_profit',
    ];

    public $tmpTarget = null;
    /**
     * 驗證上傳的實體商品信息
     */
    public function check($fileObject)
    {
        $extension = $fileObject->getClientOriginalExtension();
        if ($extension != 'xlsx') {
            throw new BadRequestHttpException('實體商品分潤信息上傳隻支持Excel文件格式(xlsx)');
        }
    }

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

    public function getFileSystem()
    {
        return app('filesystem')->disk('import-file');
    }

    public function finishHandle()
    {
        unlink($this->tmpTarget);
        return true;
    }

    /**
     * 獲取頭部標題
     */
    public function getHeaderTitle()
    {
        return ['all' => $this->header, 'is_need' => $this->isNeedCols, 'headerInfo' => $this->headerInfo];
    }

    public function handleRow($companyId, $row)
    {
        app('log')->info('NormalGoodsProfitUploadService companyId:'.$companyId.',row===>'.var_export($row, 1));
        $rules = [
            'item_bn' => ['required', '請填寫商品編碼'],
            'profit_type' => ['required', '請填寫分潤類型'],
        ];
        if ($row['profit_type']) {
            $rules = [
                'profit' => ['required', '請填寫拉新分潤'],
                'popularize_profit' => ['required', '請填寫推廣分潤'],
            ];
        }
        $errorMessage = validator_params($row, $rules, false);
        if ($errorMessage) {
            $msg = implode(', ', $errorMessage);
            throw new BadRequestHttpException($msg);
        }

        $itemsProfitService = new ItemsProfitService();

        if (!in_array($row['profit_type'], [$itemsProfitService::STATUS_PROFIT_DEFAULT, $itemsProfitService::STATUS_PROFIT_SCALE, $itemsProfitService::STATUS_PROFIT_FEE])) {
            throw new BadRequestHttpException('分潤類型錯誤');
        }

        // 檢查商品是否存在
        $itemsService = new ItemsService();
        $itemInfo = $itemsService->getInfo(['item_bn' => $row['item_bn']]);
        if (!$itemInfo) {
            throw new BadRequestHttpException('商品不存在');
        }
        $itemId = $itemInfo['item_id'];

        $itemsProfitService->deleteBy(['item_id' => $itemId, 'company_id' => $companyId]);
        if ($itemsProfitService::STATUS_PROFIT_DEFAULT != $row['profit_type']) {
//            $profitConfData = [
//                'profit' => bcmul(bcdiv($row['profit'], 100, 4), $itemInfo['price']),
//                'popularize_profit' => bcmul(bcdiv($row['popularize_profit'], 100, 4), $itemInfo['price']),
//            ];
            if ($row['profit_type'] == $itemsProfitService::STATUS_PROFIT_SCALE) {
                $profitConfData = [
                    'profit' => $row['profit'],
                    'popularize_profit' => $row['popularize_profit'],
                ];
            } else {
                $profitConfData = [
                    'profit' => bcmul($row['profit'], 100),
                    'popularize_profit' => bcmul($row['popularize_profit'], 100),
                ];
            }
            $itemProfitInfo = [
                'item_id' => $itemId,
                'company_id' => $companyId,
                'profit_type' => $row['profit_type'],
                'profit_conf' => $profitConfData,
            ];
            $profitType = $itemsProfitService::STATUS_PROFIT_SCALE == $row['profit_type'] ? $itemsProfitService::PROFIT_ITEM_PROFIT_SCALE : $itemsProfitService::PROFIT_ITEM_PROFIT_FEE;
            $profitFee = $itemsProfitService::STATUS_PROFIT_SCALE == $row['profit_type'] ? bcmul(bcdiv($row['popularize_profit'], 100, 4), $itemInfo['price']) : $row['popularize_profit'];
            $result = $itemsProfitService->create($itemProfitInfo);
            $itemsService->updateBy(['item_id' => $itemId], ['profit_type' => $profitType, 'profit_fee' => $profitFee]);
        } else {
            $profitConfData = [
                'profit' => '',
                'popularize_profit' => '',
            ];
            $itemProfitInfo = [
                'item_id' => $itemId,
                'company_id' => $companyId,
                'profit_type' => $row['profit_type'],
                'profit_conf' => $profitConfData,
            ];
            $itemsProfitService->create($itemProfitInfo);
        }
    }
}
