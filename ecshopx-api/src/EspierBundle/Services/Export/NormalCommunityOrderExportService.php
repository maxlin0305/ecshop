<?php

namespace EspierBundle\Services\Export;

use EspierBundle\Interfaces\ExportFileInterface;
use EspierBundle\Services\ExportFileService;
use CommunityBundle\Services\CommunityActivityService;
use EspierBundle\Services\Export\Template\TemplateExport;
use Madnest\Madzipper\Madzipper;

class NormalCommunityOrderExportService implements ExportFileInterface
{
    private function getSheet1Title()
    {
        return [
            'order_id' => '訂單號',
            'activity_name' => '團購標題',
            'username' => '下單人',
            'create_time' => '下單時間',
            'order_status' => '訂單狀態',
            'remark' => '團員備註',
            'activity_trade_no' => '跟團號',
            'item_name' => '商品',
            'item_spec_desc' => '規格',
            'num' => '數量',
            'item_fee' => '商品金額',
            'discount_fee' => '優惠',
            'total_fee' => '訂單金額',
            'receipt_type' => '物流方式',
            'ziti_name' => '自提點',
            // 'ziti_contact_user' => '自提點聯系人',
            // 'ziti_contact_mobile' => '自提點聯系電話',
            'ziti_address' => '自提點地址',
            'receiver_name' => '收貨人',
            'receiver_mobile' => '聯系電話',
            'receiver_address' => '詳細地址',
            'chief_name' => '團長',
            'chief_mobile' => '團長手機號',
            'activity_status' => '活動狀態',
            'activity_delivery_status' => '活動發貨狀態',
        ];
    }

    private function getSheet2Title()
    {
        return [
            'activity_name' => '團購標題',
            'chief_name' => '所屬團長',
            'chief_mobile' => '團長手機號',
            'item_name' => '商品',
            'item_id' => '商品編號',
            'item_bn' => '商品編碼',
            'item_spec_desc' => '規格',
            'num' => '銷售數量',
            'price' => '團當前單價',
            'item_fee' => '商品總金額',
            'ziti_name' => '自提點',
            // 'ziti_contact_user' => '自提點聯系人',
            // 'ziti_contact_mobile' => '自提點聯系電話',
            'ziti_address' => '自提點地址',
        ];
    }

    public function exportData($filter)
    {
        $receiptType = ['logistics' => '快遞配送', 'ziti' => '上門自提', 'dada' => '同城配'];
        $conn = app('registry')->getConnection('default');
        $activityService = new CommunityActivityService();
        $activity = $activityService->lists($filter, 'activity_id,activity_name,activity_status,delivery_status');

        //todo 扩展字段
        $extraFields = [
            ['field_name' => '樓號', 'field_type' => 'text', 'is_numeric' => false],
            ['field_name' => '房號', 'field_type' => 'text', 'is_numeric' => false],
        ];
        $sheet1Title = array_merge($this->getSheet1Title(), array_column($extraFields, 'field_name'));
        $sheet2Title = $this->getSheet2Title();
        $sheet1List = [$sheet1Title];
        $sheet2List = [$sheet2Title];

        foreach ($activity['list'] as $activity) {
            $columns = 'o.order_id,o.user_id,o.remark,o.receipt_type,o.receiver_name,o.receiver_mobile,o.receiver_state,o.receiver_city,o.receiver_district,o.receiver_address,o.order_status,o.delivery_status,o.ziti_status,o.cancel_status,o.create_time';
            $columns .= ',i.item_name,i.item_spec_desc,i.num,i.item_fee,i.discount_fee,i.total_fee,i.price,i.item_id,i.item_bn';
            $columns .= ',r.activity_trade_no,r.ziti_name,r.ziti_contact_user,r.ziti_contact_mobile,r.ziti_address,r.chief_name,r.extra_data';
            $columns .= ',u.username';
            $columns .= ',c.chief_mobile';

            $criteria = $conn->createQueryBuilder();
            $list = $criteria->select($columns)
                ->from('orders_normal_orders_items', 'i')
                ->leftJoin('i', 'orders_normal_orders', 'o', 'i.order_id = o.order_id')
                ->leftJoin('o', 'community_order_rel_activity', 'r', 'o.order_id = r.order_id')
                ->leftJoin('o', 'members_info', 'u', 'o.user_id = u.user_id')
                ->leftJoin('r', 'community_chief', 'c', 'r.chief_id = c.chief_id')
                ->andWhere($criteria->expr()->eq('o.order_type', $criteria->expr()->literal('normal')))
                ->andWhere($criteria->expr()->eq('o.order_class', $criteria->expr()->literal('community')))
                ->andWhere($criteria->expr()->eq('o.act_id', $activity['activity_id']))
                ->andWhere($criteria->expr()->neq('o.order_status', $criteria->expr()->literal('CANCEL')))
                ->andWhere(
                    $criteria->expr()->orX(
                        $criteria->expr()->eq('o.cancel_status', $criteria->expr()->literal('NO_APPLY_CANCEL')),
                        $criteria->expr()->eq('o.cancel_status', $criteria->expr()->literal('FAILS'))
                    )
                )
                ->andWhere($criteria->expr()->isNotNull('r.activity_trade_no'))
                ->addOrderBy('r.activity_id', 'DESC')
                ->addOrderBy('r.activity_trade_no', 'DESC')
                ->execute()->fetchAll();

            foreach ($list as $row) {
                //sheet1
                $data1 = [
                    'order_id' => "\"'".$row['order_id']."\"",
                    'activity_name' => $activity['activity_name'],
                    'username' => fixeddecrypt($row['username']),
                    'create_time' => date('Y-m-d H:i:s', $row['create_time']),
                    'order_status' => $this->getOrderStatusMsg($row),
                    'remark' => $row['remark'],
                    'activity_trade_no' => $row['activity_trade_no'],
                    'item_name' => $row['item_name'],
                    'item_spec_desc' => $row['item_spec_desc'],
                    'num' => $row['num'],
                    'item_fee' => bcdiv($row['item_fee'], 100, 2),
                    'discount_fee' => bcdiv($row['discount_fee'], 100, 2),
                    'total_fee' => bcdiv($row['total_fee'], 100, 2),
                    'receipt_type' => $receiptType[$row['receipt_type']],
                    'ziti_name' => $row['ziti_name'],
                    // 'ziti_contact_user' => $row['ziti_contact_user'],
                    // 'ziti_contact_mobile' => $row['ziti_contact_mobile'],
                    'ziti_address' => $row['ziti_address'],
                    'receiver_name' => fixeddecrypt($row['receiver_name']),
                    'receiver_mobile' => fixeddecrypt($row['receiver_mobile']),
                    'receiver_address' => $row['receiver_state'].$row['receiver_city'].$row['receiver_district'].fixeddecrypt($row['receiver_address']),
                    'chief_name' => $row['chief_name'],
                    'chief_mobile' => $row['chief_mobile'],
                    'activity_status' => CommunityActivityService::activity_status[$activity['activity_status']] ?? '',
                    'activity_delivery_status' => CommunityActivityService::activity_delivery_status[$activity['delivery_status']] ?? '',
                ];
                $row['extra_data'] = json_decode($row['extra_data'], true);
                foreach ($extraFields as $field) {
                    $data1[$field['field_name']] = $row['extra_data'][$field['field_name']] ?? '';
                }
                $sheet1List[] = $data1;

                //sheet2
                $activityItemKey = $activity['activity_id'].'_'.$row['item_id'];
                $sheet2List[$activityItemKey]['activity_name'] = $activity['activity_name'];
                $sheet2List[$activityItemKey]['chief_name'] = $row['chief_name'];
                $sheet2List[$activityItemKey]['chief_mobile'] = $row['chief_mobile'];
                $sheet2List[$activityItemKey]['item_name'] = $row['item_name'];
                $sheet2List[$activityItemKey]['item_id'] = $row['item_id'];
                $sheet2List[$activityItemKey]['item_bn'] = is_numeric($row['item_bn']) ? "\"'".$row['item_bn']."\"" : $row['item_bn'];
                $sheet2List[$activityItemKey]['item_spec_desc'] = $row['item_spec_desc'];
                $sheet2List[$activityItemKey]['num'] = ($sheet2List[$activityItemKey]['num'] ?? 0) + $row['num'];
                $sheet2List[$activityItemKey]['price'] = $row['price'];
                $sheet2List[$activityItemKey]['item_fee'] = ($sheet2List[$activityItemKey]['item_fee'] ?? 0) + $row['item_fee'];
                $sheet2List[$activityItemKey]['ziti_name'] = $row['ziti_name'];
                // $sheet2List[$activityItemKey]['ziti_contact_user'] = $row['ziti_contact_user'];
                // $sheet2List[$activityItemKey]['ziti_contact_mobile'] = $row['ziti_contact_mobile'];
                $sheet2List[$activityItemKey]['ziti_address'] = $row['ziti_address'];
            }
            array_walk($sheet2List, function(&$row) {
                if (is_numeric($row['price'])) {
                    $row['price'] = bcdiv($row['price'], 100, 2);
                }

                if (is_numeric($row['item_fee'])) {
                    $row['item_fee'] = bcdiv($row['item_fee'], 100, 2);
                }
            });
        }
        $result = [
            [
                'sheetname' => '顧客購買明細表',
                'list' => $sheet1List,
            ],
            [
                'sheetname' => '商品匯總表',
                'list' => $sheet2List,
            ]
        ];

        $fileName = 'community-'.date('YmdHis');
        $fileDir = 'excel';
        $fullDir = storage_path('app/excel');
        $templateObj = new TemplateExport($result);
        app('excel')->store($templateObj, $fileDir.'/'.$fileName.'.xlsx');


        // $fileArr[] = $fullDir.'/'.$fileName.'.xlsx';
        $filePath = $fullDir.'/'.$fileName.'.xlsx';

        // if (count($fileArr) > 1) {
        //     $filePath = $this->addFileToZip($fileArr, count($fileArr).'communities-'.date('YmdHis'));
        // } elseif (count($fileArr) == 1) {
        //     $filePath = reset($fileArr);
        // } else {
        //     return false;
        // }

        return $this->getDownloadUrl($filePath);
    }

    private function addFileToZip($fileArr, $fileName)
    {
        $zipFilePath = storage_path('app/excel/zip/'.$fileName.".zip");
        $zipper = new Madzipper();
        $zipper->make($zipFilePath)->add($fileArr);
        $zipper->close();

        return $zipFilePath;
    }

    private function getDownloadUrl($filePath)
    {
        $fileName = basename($filePath);
        $extension = substr($fileName, strrpos($fileName, '.') + 1);
        $filesystem = app('filesystem')->disk('import-file');
        $filesystem->put('export/'.$extension.'/'.$fileName, file_get_contents($filePath));
        $result['filedir'] = 'export/'.$extension.'/';
        $result['filename'] = $fileName;
        $result['url'] = $filesystem->privateDownloadUrl('export/'.$extension.'/'.$fileName, 86400);
        return $result;
    }

    private function getOrderStatusMsg($order)
    {
        switch ($order['order_status']) {
            case "WAIT_GROUPS_SUCCESS":
                $statusMsg = '等待成團';
                break;
            case "NOTPAY":
                $statusMsg = '待支付';
                break;
            case "PAYED":
                if ($order['cancel_status'] == 'WAIT_PROCESS') {
                    $statusMsg = '退款處理中';
                } elseif ($order['ziti_status'] == 'PENDING') {
                    $statusMsg = '待自提';
                } elseif ($order['delivery_status'] == 'PARTAIL') {
                    $statusMsg = '部分發貨';
                } else {
                    $statusMsg = '待發貨';
                }
                break;
            case 'REVIEW_PASS':
                if ($order['delivery_status'] == 'PARTAIL') {
                    $statusMsg = '部分出庫';
                } else {
                    $statusMsg = '審核完成,待出庫';
                    break;
                }
            // no break
            case "CANCEL":
                if ($order['delivery_status'] == 'DONE' || $order['ziti_status'] == 'DONE') {
                    $statusMsg = '已關閉';
                } elseif ($order['cancel_status'] == 'NO_APPLY_CANCEL') {
                    $statusMsg = '已取消';
                } elseif ($order['cancel_status'] == 'WAIT_PROCESS ') {
                    $statusMsg = '退款處理中';
                } elseif ($order['cancel_status'] == 'REFUND_PROCESS') {
                    $statusMsg = '退款處理中';
                } elseif ($order['cancel_status'] == 'SUCCESS') {
                    $statusMsg = '已取消';
                } else {
                    // 退款失敗
                    $statusMsg = '等待退款';
                }
                break;
            case "WAIT_BUYER_CONFIRM":
                $statusMsg = '待收貨';
                break;
            case "DONE":
                $statusMsg = '已完成';
                break;
            case "REFUND_PROCESS":
                $statusMsg = '退款處理中';
                break;
            case "REFUND_SUCCESS":
                $statusMsg = '已退款';
                break;
            default:
                $statusMsg = '訂單異常';
                break;
        }
        return $statusMsg;
    }
}
