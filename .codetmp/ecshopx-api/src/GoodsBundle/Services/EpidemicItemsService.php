<?php

namespace GoodsBundle\Services;

use EspierBundle\Services\File\AbstractTemplate;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class EpidemicItemsService extends AbstractTemplate
{
    protected $extensionArray = ["xlsx"];

    public $header = [
        '商品條形碼' => 'barcode',
        '商品編號' => 'item_bn',
        '是否設為疫情商品' => 'is_epidemic',
    ];

    public $headerInfo = [
        '商品條形碼' => ['size' => 255, 'remarks' => '', 'is_need' => false],
        '商品編號' => ['size' => 255, 'remarks' => '', 'is_need' => false],
        '是否設為疫情商品' => ['size' => 255, 'remarks' => '1:設為疫情商品  0:設為普通商品', 'is_need' => true],
    ];

    public $isNeedCols = [
        '商品條形碼' => 'barcode',
        '商品編號' => 'item_bn',
        '是否設為疫情商品' => 'is_epidemic',
    ];

    public $tmpTarget = null;

    /**
     * 獲取頭部標題
     */
    public function getHeaderTitle($companyId = 0)
    {
        return ['all' => $this->header, 'is_need' => $this->isNeedCols, 'headerInfo' => $this->headerInfo];
    }


    public function handleRow(int $companyId, array $row): void
    {
        if (!($row['barcode'] ?? []) && !($row['item_bn'] ?? [])) {
            throw new BadRequestHttpException('商品編號 條形碼 必填一項');
        }

        if (!isset($row['is_epidemic'])) {
            throw new BadRequestHttpException('是否設為疫情商品 必填');
        }

        $itemsService = new ItemsService();
        if ($row['item_bn'] ?? []) {
            $filter['item_bn'] = $row['item_bn'];
        }

        if ($row['barcode'] ?? []) {
            $filter['barcode'] = $row['barcode'];
        }

        $itemInfo = $itemsService->getItem($filter);
        if (!$itemInfo) {
            $msg = '編碼為:' . $row['item_bn'] . ' ,條碼為:' . $row['barcode'] . ' 的商品不存在';
            app('log')->debug("\n".$msg);
            throw new BadRequestHttpException($msg);
        }

        $itemsService->simpleUpdateBy($filter, ['is_epidemic' => $row['is_epidemic']]);
    }
}
