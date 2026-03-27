<?php

namespace WsugcBundle\Services;

use WsugcBundle\Entities\MpsFeedLog;
class MpsFeedLogService
{
    public function __construct()
    {
        $this->entityRepository = app('registry')->getManager('default')->getRepository(MpsFeedLog::class);
    }

    public function saveData($params, $filter = [])
    {
        if ($filter) {
            $result = $this->entityRepository->updateOneBy($filter, $params);
        } else {
            $result = $this->entityRepository->create($params);
        }
        return $result;
    }

    // 如果可以直接调取Repositories中的方法，则直接调用
    public function __call($method, $parameters)
    {
        return $this->entityRepository->$method(...$parameters);
    }
    public function getLogsList($filter, $cols = "*", $page = 1, $pageSize = -1, $orderBy = [])
    {
        if (!$orderBy) {
            //预约开始时间早的在前。
            $orderBy = ['log_id' => 'desc'];
        }
        $lists = $this->entityRepository->lists($filter, $cols, $page, $pageSize, $orderBy);
        if (!($lists['list'] ?? [])) {
            return [];
        }
        foreach ($lists['list'] as &$v) {
            $v = $this->formatLogs($v);

            ksort($v);
        }
        return $lists;
    }
    /**
     * [getCatList 所有业务分类]
     * @Author   sksk
     * @DateTime 2021-07-31T11:23:31+0800
     * @param    [type]                   $filter   [description]
     * @param    string                   $cols     [description]
     * @param    integer                  $page     [description]
     * @param    integer                  $pageSize [description]
     * @param    array                    $orderBy  [description]
     * @return   [type]                             [description]
     */
    public function getCatList($filter, $cols = "*", $page = 1, $pageSize = -1, $orderBy = [])
    {
        $tmp = $this->getApiCat();
        sort($tmp);
        $lists['list'] = $tmp;
        return $lists;
    }
    /**
     * [getPartnerList 所有第三方]
     * @Author   sksk
     * @DateTime 2021-07-31T11:23:24+0800
     * @param    [type]                   $filter   [description]
     * @param    string                   $cols     [description]
     * @param    integer                  $page     [description]
     * @param    integer                  $pageSize [description]
     * @param    array                    $orderBy  [description]
     * @return   [type]                             [description]
     */
    public function getPartnerList($filter, $cols = "*", $page = 1, $pageSize = -1, $orderBy = [])
    {
        $tmp = $this->getApiPartner();
        sort($tmp);
        $lists['list'] = $tmp;
        return $lists;
    }
    /**
     * [getAllApiList 所有api]
     * @Author   sksk
     * @DateTime 2021-07-31T11:23:18+0800
     * @param    [type]                   $filter   [description]
     * @param    string                   $cols     [description]
     * @param    integer                  $page     [description]
     * @param    integer                  $pageSize [description]
     * @param    array                    $orderBy  [description]
     * @return   [type]                             [description]
     */
    public function getAllApiList($filter, $cols = "*", $page = 1, $pageSize = -1, $orderBy = [], $data = [])
    {
        $apis = $this->getAllApis($data);
        $allApis = [];
        foreach ($apis as $ktop => $vtop) {
            foreach ($vtop as $k => $v) {
                $allApis[] = ['api_path' => $v['api_path'], 'api_name' => '[' . $ktop . ']' . $v['api_name']];
            }
        }
        $lists['list'] = $allApis;

        return $lists;
    }
    /**
     * [formatLogs 格式化活动数据]
     * @Author   sksk
     * @DateTime 2021-07-14T10:14:36+0800
     * @param    [type]                   $v [description]
     * @return   [type]                      [description]
     */
    function formatLogs($v)
    {

        $v['created_time_text'] = date('Y-m-d H:i:s', $v['created']);
        /* if($v['cat_id'] ?? null){
            $catService=new YuyueLogsCatService();
            $catInfo=$catService->getInfoById($v['cat_id']);
            $v['cat_name']= $catInfo['cat_name'];
        }*/
        $v['status_text'] = $this->getStatusText($v['status']);
        return $v;
    }
    /**
     * [getLogsDetail description]
     * @Author   sksk
     * @DateTime 2021-07-09T14:09:22+0800
     * @param    [type]                   $filter [description]
     * @return   [type]                           [description]
     */
    public function getLogsDetail($filter, $user_id = "")
    {
        $activityinfo = $this->getInfo($filter);
        if ($activityinfo) {
            $activityinfo = $this->formatLogs($activityinfo);
        }
        ksort($activityinfo);
        return $activityinfo;
    }
    public function getStatusText($key = "", $lang = 'zh')
    {
        $rs = array(
            'succ' => array('zh' => '成功', 'en' => 'Comming Soon'),
            'failed' => array('zh' => '失败', 'en' => 'In Progress'),
            'pending' => array('zh' => '请求中', 'en' => 'Closed'),
        );
        if ($key != '') {
            return $rs[$key][$lang];
        } else {
            return $rs;
        }
    }
    /**
     * [getApiPartner 获取所有接口分类]
     * @Author   sksk
     * @DateTime 2021-07-31T09:37:02+0800
     * @return   [type]                   [description]
     */
    function getApiCat($key = "")
    {
        $cat['User'] = ['cat_name' => '用户', 'cat_id' => 'User'];
        $cat['Order'] = ['cat_name' => '订单', 'cat_id' => 'Order'];
        $cat['Goods'] = ['cat_name' => '商品', 'cat_id' => 'Goods'];
        if ($key) {
            return $cat[$key];
        }
        return $cat;
    }
    /**
     * [getApiPartner 获取所有外部第三方]
     * @Author   sksk
     * @DateTime 2021-07-31T09:37:02+0800
     * @return   [type]                   [description]
     */
    function getApiPartner($key = "")
    {
        $partners['ytpos'] = ['partner_name' => 'POS', 'partner_id' => 'ytpos'];
        $partners['ecshopx'] = ['partner_name' => '商城', 'partner_id' => 'ecshopx'];
        $partners['qunmai'] = ['partner_name' => '群脉CRM', 'partner_id' => 'qunmai'];
        if ($key) {
            return $partners[$key];
        }
        return $partners;
    }
    /**
     * [getAllApis 获取所有第三方API]
     * @Author   sksk
     * @DateTime 2021-07-31T09:39:28+0800
     * @return   [type]                   [description]
     */
    function getAllApis($data = "")
    {


        //  'TradeType' => 'GetEnableCounterProductStock',
        $apis['ytpos'] = [
            'GetEnableCounterProductStock' => ['api_name' => '查询商品库存', 'cat_id' => 'Goods', 'api_path' => 'GetEnableCounterProductStock'],

            'SynchroSaleOrderInfo/NS' => ['api_name' => '同步订单', 'cat_id' => 'Order', 'api_path' => 'SynchroSaleOrderInfo/NS'],

            'SynchroSaleOrderInfo/SR' => ['api_name' => '同步退单', 'cat_id' => 'Order', 'api_path' => 'SynchroSaleOrderInfo/SR'],
        ];

        //群脉crm
        $apis['qunmai'] = [
            //创建客户
            'user.createOrUpdate' => ['api_name' => '创建或更新客户', 'cat_id' => 'User', 'api_path' => '/v2/members/upsert', 'method' => 'POST'],

           

           

            //创建订单
            'order.updateOrder' => ['api_name' => '创建更新订单', 'cat_id' => 'Order', 'api_path' => '/modules/trade/trade/upsert', 'method' => 'POST'],

            //创建退款单
            'order.refundOrder' => ['api_name' => '创建退款单', 'cat_id' => 'Order', 'api_path' => '/modules/trade/trade-refund/upsert', 'method' => 'POST'],


            //获取优惠券
            'coupon.getCoupons' => ['api_name' => '获取优惠券', 'cat_id' => 'Coupon', 'api_path' => '/apps/implcommon/coupons', 'method' => 'GET'],

            //获取用户优惠券
            'coupon.getUserCoupons' => ['api_name' => '获取用户优惠券', 'cat_id' => 'Coupon', 'api_path' => '/apps/implcommon/membershipDiscounts', 'method' => 'GET'],

              //核销优惠券
              'coupon.redemptionCoupons' => ['api_name' => '核销优惠券', 'cat_id' => 'Coupon', 'api_path' => sprintf("/v2/coupons/%s/redemption", $data), 'method' => 'PUT'],

              //回退核销优惠券
              'coupon.rollbackCoupons' => ['api_name' => '回退核销优惠券', 'cat_id' => 'Coupon', 'api_path' => '/apps/implcommon/coupons/rollback', 'method' => 'POST'],

        ];

        //等等其他第三方
        return $apis;
    }
    /**
     * [getApisByPartner 根据partner获取接口]
     * @Author   sksk
     * @DateTime 2021-07-31T09:40:12+0800
     * @param    [type]                   $key [description]
     * @return   [type]                        [description]
     */
    function getApisByPartner($key,$data="")
    {

        $apis = $this->getAllApis($data);

        if (isset($apis[$key])) {

            return $apis[$key];
        } else {
            return [];
        }
    }
    public function getMillisecond()
    {
        list($s1, $s2) = explode(' ', microtime());
        return (float)sprintf('%.0f', (floatval($s1) + floatval($s2)) * 1000);
    }
    /**
     * [writeLogCommon 写入通用第三方日志]
     * @Author   sksk
     * @DateTime 2021-07-31T17:17:54+0800
     * @param    string                   $partner           [description]
     * @param    string                   $company_id        [description]
     * @param    string                   $app_id            [description]
     * @param    string                   $request_api       [请求接口]
     * @param    string                   $request_url       [请求地址]
     * @param    string                   $request_params    [请求参数]
     * @param    string                   $response_text     [响应结果]
     * @param    string                   $response_code     [响应业务代码]
     * @param    string                   $response_httpcode [响应http代码]
     * @param    string                   $begin_time        [开始时间ms]
     * @param    string                   $end_time          [结束时间ms]
     * @param    array                    $rel_id_rs         [业务关联编号        $rel_id_rs=['value'=>$data['Mobile'],'key'=>'mobile'];]
     * @return   [type]                                      [description]
     */
    function writeLogCommon($partner = "", $company_id = "", $app_id = "", $request_api = "", $request_url = "", $request_params = "", $response_text = "", $response_code = "", $response_httpcode = "", $begin_time = "", $end_time = "", $rel_id_rs = [], $request_from = "", $request_to = "", $request_type = "", $status = "", $message = "")
    {
        //$end_time=$this->getMillisecond();
        //写入日志
        $this->apis = $this->getApisByPartner($partner);
        $params['company_id'] = $company_id;
        $params['app_id'] = $app_id;
        $params['request_id'] = $begin_time;
        $params['request_url'] = $request_url; //完整url
        $params['request_name'] = $this->apis[$request_api]['api_name'] ?? ''; //中文名
        $params['request_api'] = $request_api; //英文api路径
        $params['request_params'] = $request_params; //请求参数
        $params['response_text'] = $response_text; //返回参数
        $params['response_code'] = $response_code;
        $params['response_httpcode'] = $response_httpcode;

        $params['response_time'] = time();
        $params['request_usetime'] = $end_time - $begin_time;
        $params['cat_id'] = $this->apis[$request_api]['cat_id'] ?? '';;
        $params['request_type'] = $request_type;
        $params['request_from'] = $request_from;
        $params['request_to'] = $request_to;
        $params['rel_id'] = $rel_id_rs['value'] ?? '';
        $params['status'] = $status;
        $params['message'] = $message;
        if ($rel_id_rs['key'] ?? null) {
            $params[$rel_id_rs['key']] = $rel_id_rs['value'] ?? '';
        }
        //app('log')->debug('mallcoo-rel_id_rs:' . var_export($rel_id_rs, true));

        //app('log')->debug('mallcoo-请求参数:' . var_export($params, true));

        return $this->saveData($params);
    }


    function sftpDownLoad(){
        return copy("ssh2.sftp://" . intval($this->ressftp) . $remote, $local);
    }
}
