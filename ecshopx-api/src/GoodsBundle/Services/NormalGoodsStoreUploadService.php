<?php

namespace GoodsBundle\Services;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use GuzzleHttp\Client as Client;

use DistributionBundle\Services\DistributorItemsService;
use CompanysBundle\Ego\CompanysActivationEgo;

class NormalGoodsStoreUploadService
{
    public $itemName = null;

    public $defaultItemId = null;

    public $header = [
        '店鋪ID' => 'did',
        '商品編碼' => 'item_bn',
        '庫存' => 'store',
    ];

    public $headerInfo = [
        '店鋪ID' => ['size' => 255, 'remarks' => 'ID=0更新總部庫存', 'is_need' => true],
        '商品編碼' => ['size' => 32, 'remarks' => '', 'is_need' => true],
        '庫存' => ['size' => 255, 'remarks' => '庫存為0-999999999的整數', 'is_need' => true],
    ];

    public $isNeedCols = [
        '店鋪ID' => 'did',
        '商品編碼' => 'item_bn',
        '庫存' => 'store',
    ];
    public $tmpTarget = null;

    /**
     * 驗證上傳的實體商品信息
     */
    public function check($fileObject)
    {
        $extension = $fileObject->getClientOriginalExtension();
        if ($extension != 'xlsx') {
            throw new BadRequestHttpException('實體商品庫存信息上傳隻支持Excel文件格式(xlsx)');
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
        app('log')->info('NormalGoodsStoreUploadService companyId:'.$companyId.',row===>'.var_export($row, 1));
        $rules = [
            'did' => ['required', '請填寫店鋪ID'],
            'item_bn' => ['required', '請填寫商品編碼'],
            'store' => ['required|integer|min:0|max:999999999', '庫存為0-999999999的整數'],
        ];
        $errorMessage = validator_params($row, $rules, false);
        if ($errorMessage) {
            $msg = implode(', ', $errorMessage);
            throw new BadRequestHttpException($msg);
        }
        // 檢查商品是否存在
        $itemsService = new ItemsService();
        $itemInfo = $itemsService->getInfo(['company_id' => $companyId, 'item_bn' => $row['item_bn']]);
        if (!$itemInfo) {
            throw new BadRequestHttpException('商品不存在');
        }
        if ($row['distributor_id'] > 0 && $row['did'] != $row['distributor_id']) {
            throw new BadRequestHttpException('隻能導入所屬店鋪的商品庫存');
        }
        $itemId = $itemInfo['item_id'];
        $store = intval($row['store']);
        $distributorId = $row['did'];

        $distributorItemsService = new DistributorItemsService();
        $itemStoreService = new ItemStoreService();

        $company = (new CompanysActivationEgo())->check($companyId);
        if ($distributorId > 0 && $company['product_model'] == 'standard') {
            $distributorItem = $distributorItemsService->getValidDistributorItemSkuInfo($companyId, $itemId, $distributorId);
            if (!$distributorItem) {
                throw new BadRequestHttpException('店鋪商品不存在');
            }

            if ($distributorItem['is_total_store'] ?? true) {
                throw new BadRequestHttpException('門店庫存為總部庫存');
            } else {
                $filter = [
                    'company_id' => $companyId,
                    'item_id' => $itemId,
                    'distributor_id' => $distributorId
                ];

                app('log')->info('NormalGoodsStoreUploadService item_id:'.$itemId.',store:'.$store.',distributor_id:'.$distributorId.',line:'.__LINE__);
                $distributorItemsService->updateOneBy($filter, ['store' => $store]);
                return $itemStoreService->saveItemStore($itemId, $store, $distributorId);
            }
        } else {
            app('log')->info('NormalGoodsStoreUploadService item_id:'.$itemId.',store:'.$store.',line:'.__LINE__);
            $itemsService->updateStore($itemId, $store, true);
            return $itemStoreService->saveItemStore($itemId, $store);
        }
    }
}
