<?php

namespace EspierBundle\Services\Export;

use EspierBundle\Interfaces\ExportFileInterface;
use OrdersBundle\Services\RightsService;
use OrdersBundle\Services\Rights\TimesCardService;
use MembersBundle\Services\UserService;
use EspierBundle\Services\ExportFileService;

class RightExportService implements ExportFileInterface
{
    private $title = [
        'mobile' => '手機號',
        'rights_name' => '權益名稱',
        'total_num' => ' 權益總數',
        'total_consum_num' => '消耗次數',
        'total_surplus_num' => '剩余次數',
        'start_time' => '權益開始時間',
        'end_time' => '權益到期時間',
        'order_id' => '訂單號',
        'rights_from' => '權益來源',
        'operator_desc' => '操作員手機及姓名',
        'user_nickname' => '會員昵稱/姓名',
        'sex' => '會員性別',
    ];
    public function exportData($filter)
    {
        // 是否需要數據脫敏 1:是 0:否
        $datapassBlock = $filter['datapass_block'];
        unset($filter['datapass_block']);
        $rightsObj = new RightsService(new TimesCardService());
        $count = $rightsObj->countRights($filter);
        if (!$count) {
            return response()->json(['filename' => '','url' => '']);
        }
        $fileName = date('YmdHis').$filter['company_id'].'right';
        $orderList = $this->getLists($filter, $count, $datapassBlock);

        $exportService = new ExportFileService();
        $result = $exportService->exportCsv($fileName, $this->title, $orderList);
        return $result;
    }

    private function getLists($filter, $count, $datapassBlock)
    {
        $rightsObj = new RightsService(new TimesCardService());
        $userService = new UserService();

        $limit = 500;
        $orderBy = ['mobile' => 'DESC', 'created' => 'desc'];
        $title = $this->title;
        $fileNum = ceil($count / $limit);
        for ($j = 1; $j <= $fileNum; $j++) {
            $orderList = [];
            $rights = $rightsObj->getRightsList($filter, $j, $limit, $orderBy);
            foreach ($rights['list'] as $key => $value) {
                $user_nickname = isset($userInfo['username']) ? $userInfo['username'] : '未知';
                if ($datapassBlock) {
                    $value['mobile'] = data_masking('mobile', (string) $value['mobile']);
                    $operatoreArr = explode(' : ', $value['operator_desc']);
                    if ($operatoreArr[0] && $operatoreArr[1]) {
                        $operator_mobile = data_masking('mobile', (string) $operatoreArr[0]);
                        $operator_name = data_masking('truename', (string) $operatoreArr[1]);
                        $value['operator_desc'] = $operator_mobile . ' : ' . $operator_name;
                    }
                    $user_nickname != '未知' and $user_nickname = data_masking('truename', (string) $user_nickname);
                }
                foreach ($title as $k => $v) {
                    if ($k == "order_id" && isset($value[$k])) {
                        $orderList[$key][$k] = "\"'".$value[$k]."\"";
                    } elseif ($k == "start_time" && isset($value[$k])) {
                        $orderList[$key][$k] = date('Y-m-d H:i:s', $value[$k]);
                    } elseif ($k == "end_time" && isset($value[$k])) {
                        $orderList[$key][$k] = date('Y-m-d H:i:s', $value[$k]);
                    } elseif (isset($value[$k])) {
                        $orderList[$key][$k] = $value[$k];
                    } else {
                        $orderList[$key][$k] = 'null';
                    }
                }
                $userInfo = $userService->getUserById($value['user_id'], $value['company_id']);
                $orderList[$key]['user_nickname'] = $user_nickname;
                $orderList[$key]['sex'] = isset($userInfo['sex']) ? ($userInfo['sex'] == 1 ? "男" : "女") : '未知';
            }
            yield $orderList;
        }
    }
}
