<?php

namespace GoodsBundle\Services;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use GuzzleHttp\Client as Client;

class NormalGoodsTagUploadService
{
    public $header = [
        '商品貨號' => 'item_bn',
        '標簽名稱' => 'tag_name',
    ];

    public $headerInfo = [
        '商品貨號' => ['size' => 32, 'remarks' => '', 'is_need' => true],
        '標簽名稱' => ['size' => 255, 'remarks' => '商品標簽為全量覆蓋，不填表示清空商品所有標簽。多個標簽用英文逗號“,”隔開', 'is_need' => false],
    ];

    public $isNeedCols = [
        '商品貨號' => 'item_bn',
        '標簽名稱' => 'tag_name',
    ];
    public $tmpTarget = null;

    /**
     * 驗證上傳的實體商品信息
     */
    public function check($fileObject)
    {
        $extension = $fileObject->getClientOriginalExtension();
        if ($extension != 'xlsx') {
            throw new BadRequestHttpException('實體商品批量打標簽信息上傳隻支持Excel文件格式(xlsx)');
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
        app('log')->info('NormalGoodsTagUploadService companyId:'.$companyId.',row===>'.var_export($row, 1));
        $rules = [
            'item_bn' => ['required', '請填寫商品貨號'],
        ];
        $errorMessage = validator_params($row, $rules, false);
        if ($errorMessage) {
            $msg = implode(', ', $errorMessage);
            throw new BadRequestHttpException($msg);
        }
        // 檢查商品是否存在
        $itemsService = new ItemsService();
        $itemInfo = $itemsService->getInfo(['item_bn' => $row['item_bn']]);
        if (!$itemInfo) {
            throw new BadRequestHttpException('未查詢到對應商品');
        }
        $itemId = $itemInfo['item_id'];
        $tag_name = [];
        if ($row['tag_name'] !== null) {
            $tag_name = explode(',', $row['tag_name']);
        }
        $itemsTagsService = new ItemsTagsService();
        $tagIds = false;
        if ($tag_name) {
            $filter = [
                'company_id' => $companyId,
                'tag_name' => $tag_name,
            ];
            $tagsList = $itemsTagsService->getListTags($filter, 1, -1);
            $tagIds = array_column($tagsList['list'], 'tag_id');
            if (count($tag_name) > count($tagIds)) {
                throw new BadRequestHttpException('未查詢到對應標簽');
            }
        }
        if ($tagIds) {
            $result = $itemsTagsService->checkActivity($itemId, $tagIds, $companyId);
            if (!$result) {
                throw new BadRequestHttpException('商品標簽導致活動衝突');
            }
        }
        try {
            $itemsTagsService->createRelTagsByItemId($itemId, $tagIds, $companyId);
        } catch (\Exception $e) {
            throw new BadRequestHttpException('更新商品標簽數據失敗，請重新上傳或聯係客服處理');
        }
        return true;
    }
}
