<?php

namespace OrdersBundle\Services\Orders;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use OrdersBundle\Services\OrderAssociationService;

use OrdersBundle\Traits\GetOrderServiceTrait;

class NormalOrdersUploadService
{
    use GetOrderServiceTrait;

    public $header = [
        '订单号' => 'order_id',
        '快递单号' => 'delivery_code',
        '快递公司' => 'delivery_corp',

    ];

    public $headerInfo = [
        '订单号' => ['size' => 32, 'remarks' => '不得重复，订单号如果大于11位时，请关闭excel单元格的科学记数法，常用禁用方法：“单元格格式”-“自定义”-“类型”改为“0”', 'is_need' => true],
        '快递单号' => ['size' => 255, 'remarks' => '', 'is_need' => true],
        '快递公司' => ['size' => 255, 'remarks' => '', 'is_need' => true],
    ];

    public $isNeedCols = [
        '订单号' => 'order_id',
        '快递单号' => 'delivery_code',
        '快递公司' => 'delivery_corp',
    ];

    public $tmpTarget;

    /**
    * 获取头部标题
    */
    public function getHeaderTitle()
    {
        return ['all' => $this->header, 'is_need' => $this->isNeedCols, 'headerInfo' => $this->headerInfo];
    }


    /**
     * 验证上传的会员信息
     */
    public function check($fileObject)
    {
        $extension = $fileObject->getClientOriginalExtension();
        if ($extension != 'xlsx') {
            throw new BadRequestHttpException('文件上传只支持xlsx文件格式');
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
        $content = file_get_contents($url);

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



    // private function validatorData($row)
    // {
    //     $arr = ['order_id','item_fee', 'total_fee', 'discount_fee', 'freight_fee', 'mobile', 'user_name', 'create_time', 'order_status','receiver_name','receiver_mobile','receiver_zip','receiver_state','receiver_city','receiver_district','receiver_address','pay_type','delivery_status', 'delivery_time', 'delivery_code', 'delivery_corp', 'kunnr'];
    //     $data = [];
    //     foreach($arr as $column) {
    //         if($row[$column]) {
    //             $data[$column] = $row[$column];
    //         }
    //     }

    //     return $data;
    // }

    public function handleRow($companyId, $row)
    {
        if (!$row['order_id']) {
            throw new BadRequestHttpException('订单号错误');
        }
        $row['order_id'] = trim(trim($row['order_id']), "'");
        if ($row['delivery_code'] && $row['delivery_corp']) {
            $orderAssociationService = new OrderAssociationService();
            $order = $orderAssociationService->getOrder($companyId, $row['order_id']);

            if (!$order) {
                throw new BadRequestHttpException('此订单不存在');
            }

            if ('CANCEL' == $order['order_status']) {
                throw new BadRequestHttpException('此订单已取消不能发货');
            }

            $params = [
                'type' => 'new',
                'delivery_type' => 'batch',
                'order_id' => $row['order_id'],
                'company_id' => $companyId,
                'delivery_corp' => trim($row['delivery_corp']),
                'delivery_code' => trim($row['delivery_code']),
            ];
            $orderService = $this->getOrderServiceByOrderInfo($order);

            $result = $orderService->delivery($params);
        } else {
            throw new BadRequestHttpException('无发货信息');
        }
    }
}
