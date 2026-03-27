<?php

namespace OrdersBundle\Services\Orders;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use OrdersBundle\Services\OrderAssociationService;

use OrdersBundle\Traits\GetOrderServiceTrait;

class NormalOrdersCancelUploadService
{
    use GetOrderServiceTrait;

    public $header = [
        '订单号' => 'order_id',
        '取消原因' => 'cancel_reason',
    ];

    public $headerInfo = [
        '订单号' => ['size' => 32, 'remarks' => '不得重复，订单号如果大于11位时，请关闭excel单元格的科学记数法，常用禁用方法：“单元格格式”-“自定义”-“类型”改为“0”', 'is_need' => true],
        '取消原因' => ['size' => 255, 'remarks' => '', 'is_need' => true],
    ];

    public $isNeedCols = [
        '订单号' => 'order_id',
        '取消原因' => 'cancel_reason',
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

    public function handleRow($companyId, $row)
    {
        $params['order_id'] = $row['order_id'];
        $params['cancel_reason'] = $row['cancel_reason'];
        $params['company_id'] = $companyId;
        $params['cancel_from'] = 'shop'; //商家取消订单

        $orderAssociationService = new OrderAssociationService();
        $order = $orderAssociationService->getOrder($companyId, $params['order_id']);
        if (!$order) {
            throw new BadRequestHttpException("订单号为{$params['order_id']}的订单不存在");
        }
        if ($order['order_type'] != 'normal') {
            throw new BadRequestHttpException("实体类订单才能取消订单");
        }
        //获取订单用户信息
        $params['user_id'] = $order['user_id'];
        $params['mobile'] = $order['mobile'];
        $params['operator_type'] = 'admin';
        $params['operator_id'] = $row['operator_id'];

        $orderService = $this->getOrderServiceByOrderInfo($order);
        if ($order['delivery_status'] == 'PENDING') {
            $result = $orderService->cancelOrder($params);
        } elseif ($order['delivery_status'] == 'PARTAIL') {
            $result = $orderService->partailCancelOrder($params);
        } else {
            throw new BadRequestHttpException("没有商品可以取消");
        }
    }
}
