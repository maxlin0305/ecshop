<?php

namespace WsugcBundle\Services;


use AftersalesBundle\Services\AftersalesRefundService;
use AftersalesBundle\Services\AftersalesService;
use EspierBundle\Jobs\UploadFileJob;
use EspierBundle\Services\UploadFileService;
use OrdersBundle\Entities\CancelOrders;
use OrdersBundle\Entities\NormalOrdersItems;
use OrdersBundle\Services\OrderDeliveryService;
use OrdersBundle\Services\Orders\NormalOrderService;
use OrdersBundle\Services\OrderService;
use OrdersBundle\Services\TradeService;


class MpsFeedDownloadService
{
    public $csv_prefix='wct';
    public $csv_brand_name='massimodutti';
    public $csv_file_name='lengow_dutticn.csv';
    //public $csv_brand_name='MASSIMODUTTI';
    public $last_days=1;//1天前
    //先dowload，再upload文件到oss，然后执行UploadService
    function downFeedFile()
    {
        $config = [
            'host' => env('MPS_FEED_SFTP_HOST'),
            'port' => env('MPS_FEED_SFTP_PORT', '22'),
            'user' => env('MPS_FEED_SFTP_USERNAME'),
            'public_key' => storage_path('static/' . env('MPS_FEED_SFTP_PUBLIC_KEY')),
            'private_key' => storage_path('static/' . env('MPS_FEED_SFTP_PRIVATE_KEY')),
            'password' => env('MPS_FEED_SFTP_PRIVATE_PASSWORD')
        ];
        $msSftpService = new MpsSftpService($config);
        $rootDir = '/';
        $mps_dir=storage_path('uploads/mps_feed/');
        if(!file_exists($mps_dir)){
            if (!mkdir($mps_dir, 0777) && !is_dir($mps_dir)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $mps_dir));
            }
        }
        $localDir = storage_path('uploads/mps_feed/' . date('Y-m-d') . '/');
        if (!file_exists($localDir)) {
            if (!mkdir($localDir, 0777) && !is_dir($localDir)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $localDir));
            }
        }
        //判断目录是否存在
        $ret = $msSftpService->ssh2_dir_exits($rootDir);

        //遍历目录
        $fileList_sftp = $msSftpService->fileList($rootDir);

        $fileNames = [];
        //先删除文件夹
        foreach ($fileList_sftp as $k => $filename) {
            //下载文件
            if($filename==$this->csv_file_name){
                $fileNames = $msSftpService->downSftp($rootDir . $filename, $localDir . $filename, $filename, $localDir);
            }
        }
        return $fileNames;
    }
    /**
     * 上传文件，并且保存文件上传记录
     *
     * @param object $fileObject SplFileInfo
     */
    public function uploadFeedFile($companyId = 1, $distributorId = 1, $fileType = "mps_feed", $fileObject = [], $shouldQueue = true,$fromJob=1)
    {
        //$shouldQueue=false;
        //$filename_remote='lengow_dutticn'.'.csv';//$this->downFeedFile();
        $localFilePrefix = 'uploads/mps_feed/' . date('Y-m-d') . '/';
        $testMode = false;
        $filename_remote = [];
        //!$testMode &&  !$testMode &&
        //
        // $filename_remote=['lengow_dutticn_0.csv','lengow_dutticn_1.csv','lengow_dutticn_2.csv','lengow_dutticn_3.csv','lengow_dutticn_4.csv','lengow_dutticn_5.csv','lengow_dutticn_6.csv','lengow_dutticn_7.csv','lengow_dutticn_8.csv','lengow_dutticn_9.csv','lengow_dutticn_10.csv','lengow_dutticn_11.csv','lengow_dutticn_12.csv'];
        if ( ((!file_exists(storage_path($localFilePrefix . 'lengow_dutticn_12.csv')) && $fromJob) || !$fromJob)) {
            //12不存在+且来自后台 或者不是来自JOB的都是 点击强制拉去的 就重新拉？？
            app('log')->debug('当日feed文件目录不存在lengow_dutticn_12，开始从sftp下载。 ---->>>>' . "\n" . json_encode($filename_remote, JSON_UNESCAPED_UNICODE));

            $filename_remote = $this->downFeedFile();
        } else {
            //读取文件目录里的文件名
            $filename_remote = scandir(storage_path($localFilePrefix));
            foreach ($filename_remote  as $k => $v) {
                if ($v == '.' || $v == '..') {
                    unset($filename_remote[$k]);
                }
            }
           // $filename_remote=['lengow_dutticn_1.csv'];
            app('log')->debug('当日feed文件目录已存在。 ---->>>>' . "\n" . json_encode($filename_remote, JSON_UNESCAPED_UNICODE));

        }
        //print_r($filename_remote);exit;
        // if(!$testMode){
        //     //非测试模式，实时下载
        //     $filename_remote=$this->downFeedFile();
        // }
        // else{
        //     //测试模式，如果不存在第一个0文件，也下载

        // }
        app('log')->debug('feed 遍历 filename_remote 开始：---->>>>' . "\n" . json_encode($filename_remote, JSON_UNESCAPED_UNICODE).',是否走队列shouldQueue：'.$shouldQueue);
        //print_r($shouldQueue);exit;
        foreach ($filename_remote as $k => $fileNameNew) {
            $uploadFileService = new UploadFileService();
            $uploadFileService->getUpdateFile($fileType);
            $uploadTime = time();
            //$fileName = $filename_remote;//'lengow_dutticn-nofenlie.csv';// $fileObject->getClientOriginalName();

            // $fileNameNew = $fileName;//'lengow_dutticn-nofenlie2'.'.csv';// $fileObject->getClientOriginalName();

            $filePath = $uploadFileService->putFilePath($companyId, $fileType, $uploadTime, $fileNameNew);

            $file = storage_path('uploads/mps_feed/' . date('Y-m-d') . '/' . $fileNameNew);

            if (method_exists($uploadFileService->uploadFile, 'getFileSystem')) {
                $uploadFileService->uploadFile->getFileSystem()->put($filePath, file_get_contents($file));
            } else {
                app('filesystem')->put($filePath, file_get_contents($file));
            }

            $data = [
                'company_id' => $companyId,
                'file_name' => $fileNameNew,
                'file_size' => filesize($file), // $fileObject->getSize(),
                'handle_status' => 'wait', //等待处理
                'file_type' => $fileType,
                'handle_line_num' => 0,
                'created' => $uploadTime,
                'distributor_id' => $distributorId,
                'left_job_num' => 1, //默认剩余一个待处理的子任务
            ];
            $data = $uploadFileService->entityRepository->create($data);

            if ($shouldQueue) {
                // 将处理文件加入到队列
                $gotoJob = (new UploadFileJob($data))->onQueue('slow');
                app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($gotoJob);
            } else {
                $uploadFileService->handleUploadFile($data, $shouldQueue);
            }
        }
        app('log')->debug('feed 遍历 filename_remote 结束：---->>>>' . "\n" . json_encode($filename_remote, JSON_UNESCAPED_UNICODE));

        return $data;
    }

    //计划任务定时拉取
    function schedulePullFeed($fromJob = 1, $schedule = true)
    {
        $result = [];
        try {
            app('log')->debug(($fromJob ? '计划任务拉取feed文件' : '后台手动拉取feed文件') . '---->开始');
            $result = $this->uploadFeedFile(1, 1, 'mps_feed', [], $schedule,$fromJob);
            app('log')->debug(($fromJob ? '计划任务拉取feed文件' : '后台手动拉取feed文件') . '---->结束');
        } catch (\Exception $e) {
            app('log')->debug(($fromJob ? '计划任务拉取feed文件' : '后台手动拉取feed文件') . '---->' . $e->getMessage());
        }
        return $result;
    }
    function generateOrderCsvFile(){
        //支付
        $this->genPaidCsvFile();
        //作废
        $this->genCancelCsvFile();
        //发货
        $this->genShippedCsvFile();
        //退货
        $this->genReturndCsvFile();

        //生成后直接上传
        $this->pushOrderCsvFile();//
    }
    function pushOrderCsvFile(){
        $allOrderCsvType=[
            'paid',
            'shipped',
            'cancelled',
            'returned',
        ];
        $config = [
            'host' => env('MPS_FEED_SFTP_HOST'),
            'port' => env('MPS_FEED_SFTP_PORT', '22'),
            'user' => env('MPS_FEED_ORDERCSV_USERNAME'),
            'public_key' => storage_path('static/' . env('MPS_FEED_ORDERCSV_PUBLIC_KEY')),
            'private_key' => storage_path('static/' . env('MPS_FEED_ORDERCSV_PRIVATE_KEY')),
            'password' => env('MPS_FEED_ORDERCSV_PRIVATE_PASSWORD')
        ];
        $msSftpService = new MpsSftpService($config);
        $last_days_time=strtotime('-'.$this->last_days.'day');
        $dir_pre='uploads/order_csv/' . date('Y-m-d',$last_days_time) . '/';
        foreach($allOrderCsvType as $k=>$type){
            $fileNameNew=$this->csv_prefix.'_'.$this->csv_brand_name.'_'.$type.'_'.date('Y_m_d',$last_days_time).'.csv';
            $local_file = storage_path($dir_pre.$fileNameNew);
            if($local_file){
                $remote_file='/'.strtoupper($this->csv_brand_name).'/toITX/'.strtoupper($type).'/'.$fileNameNew;
                app('log')->debug('开始上传'.$type.'日志文件到sftp-cancel_detail'.'local_file:'.$local_file.',remote_file:'.$remote_file);
                $msSftpService->upSftp($local_file,$remote_file);
            }
        }
        //     //支付
        //      $this->pushPaidCsvFile();
        //  //作废
        //     $this->pushCancelCsvFile();
        //     //退货
        // $this->pushReturndCsvFile();
        //     // //发货
        // $this->pushShippedCsvFile();
    }
    /**
     * 支付 function
     *
     * @return void
     */
    function genPaidCsvFile(){
        $header=['Type','OrderPaymentDate','Brand','OrderID','OrderItemID','SKU','Units','Amount'];
        $tradeService = new TradeService();
        //step1:先查支付单2022-10-11 16:29:06
        $filter = array();
        $filter['trade_state']='SUCCESS';
        $yestoday_date=date('Y-m-d',strtotime('-'.$this->last_days.' day'));
        $filter['time_start_begin'] = strtotime($yestoday_date);
        $filter['time_start_end']   = strtotime(date('Y-m-d',$filter['time_start_begin']).' 23:59:59');
        $orderBy = ['time_start'=>'DESC'];
        $orgData = $tradeService->getTradeList($filter, $orderBy,10000, 1);
        $allOrderIds=[];
        //print_r($orgData);exit;

        $allPayDate=[];//payDate
        if($orgData['list']??null){
            $allOrderIds=array_column($orgData['list'],'orderId');
            foreach($orgData['list'] as $k=>$v){
                $allPayDate[$v['orderId']]=$v['payDate'];
            }
        }

        //step2:再查订单
        $result =[];
        $filter=[];
        if($allOrderIds){
            $filter['order_id'] = $allOrderIds;
            $filter['company_id']=1;
            //昨天
            //$filter['create_time|gte'] = strtotime('-1 day');
            //$filter['create_time|lte']   = strtotime(date('Y-m-d',$filter['time_start_begin']).' 23:59:59');

            $orderService = new OrderService(new NormalOrderService());
            $orderBy = ['create_time' => 'DESC'];
            $result = $orderService->getOrderList($filter,1, -1, $orderBy);
        }


        //写入文件 文件名
        $col_type='P';
        $csv_type='Paid';
        $data=[];
        if($result['list']??null){
            foreach($result['list'] as $k=>$v){
                foreach($v['items'] as $kk=>$vv){
                    $data[]=[
                        $col_type,
                        date('Y-m-d',strtotime($allPayDate[$v['order_id']]??strtotime($yestoday_date))),



                        strtoupper($this->csv_brand_name),
                        $v['order_id'],
                        $vv['id'],
                        $vv['item_bn'],
                        $vv['num'],
                        $vv['total_fee'],
                    ];
                }
            }
        }
        return $this->putOrderCsv($csv_type,$header,$data);
    }

    /**
     * 售前取消 function
     *
     * @return void
     */
    function genCancelCsvFile(){
        $header=['Type','OrderPaymentDate','OrderCancelledDate','Brand','OrderID','OrderItemID','SKU','Units','Amount'];

        //step1:先查售前退款单2022-10-11 16:29:06
        $aftersalesService = new AftersalesRefundService();
        $filter = array();
        $yestoday_date=date('Y-m-d',strtotime('-'.$this->last_days.' day'));
        $filter['create_time|gte'] = strtotime($yestoday_date);
        $filter['create_time|lte']   = strtotime(date('Y-m-d',$filter['create_time|gte']).' 23:59:59');
        $filter['refund_type'] = 1;//售前退款
        $filter['refund_status'] ='SUCCESS';//售前退款成功
        $orderBy = ['create_time'=>'DESC'];
        $orgData = $aftersalesService->getRefundsList($filter, 0, 1000,$orderBy);
        $orgData = $orgData['list']??[];
        $allOrderIds=[0];
        $allCanceledData=[]; //payDate
        $allrefund_id_ids=[0];//退款单号
        if($orgData??null){
            $allOrderIds=array_column($orgData,'order_id');
            foreach($orgData as $k=>$v){
                $allCanceledData[$v['order_id']]=date('Y-m-d',$v['update_time']);
                $allrefund_id_ids[]=$v['refund_id'];
            }
        }

        //step2:查询支付单，需要支付时间
        $tradeService = new TradeService();
        $filter = array();
        $filter['trade_state']='SUCCESS';
        $filter['order_id']=$allOrderIds;
        $orgData = $tradeService->getTradeList($filter, $orderBy,10000, 1);
        $allPayDate=[];//payDate
        if($orgData['list']??null){
            $allOrderIds=array_column($orgData['list'],'orderId');
            foreach($orgData['list'] as $k=>$v){
                $allPayDate[$v['orderId']]=$v['payDate'];
            }
        }

        //step3:再查订单
        $result =[];
        $filter=[];
        $orderItems=[];
        if($allOrderIds){
            $filter['order_id'] = $allOrderIds;
            $filter['company_id']=1;
            //昨天
            //$filter['create_time|gte'] = strtotime('-1 day');
            //$filter['create_time|lte']   = strtotime(date('Y-m-d',$filter['time_start_begin']).' 23:59:59');
            $orderService = new OrderService(new NormalOrderService());
            $orderBy = ['create_time' => 'DESC'];
            $result = $orderService->getOrderList($filter,1, -1, $orderBy);
            if($result['list']??null){
                foreach($result['list'] as $k=>$v){
                    foreach($v['items'] as $kk=>$vv){
                        $orderItems[$vv['id']]=$vv;
                    }
                }
            }
        }
        //查询 cancelOrder里实际取消的item_id
        $result=[];
        $cancelOrderRepository = app('registry')->getManager('default')->getRepository(CancelOrders::class);
        $cancelList=$cancelOrderRepository->lists(['order_id'=>$allOrderIds],["cancel_id" => "DESC"],1000,1);
        app('log')->debug('生成退款order_csv日志-cancelList'.json_encode($cancelList));
        $result=$cancelList['list']??[];
        if($result){
            $this->normalOrdersItemsRepository = app('registry')->getManager('default')->getRepository(NormalOrdersItems::class);
            $orderItems=[];
            foreach($result as $k=>$v){
                $cancel_detail=$v['cancel_detail'];
                app('log')->debug('生成退款order_csv日志-cancel_detail'.json_encode($cancel_detail));

                if(!$cancel_detail){
                    //全部退
                    $orderItems = $this->normalOrdersItemsRepository->getList(['order_id'=>$v['order_id']]);
                    $orderItems=$orderItems['list']??[];
                }
                else{
                    //售前部分退
                    $cancel_detail=ltrim($cancel_detail,',');
                    $cancel_detail=rtrim($cancel_detail,',');
                    $orderItems = $this->normalOrdersItemsRepository->getList(['order_id'=>$v['order_id'],'id|in'=>explode(',',$cancel_detail)]);
                    $orderItems=$orderItems['list']??[];
                }
                app('log')->debug('生成退款order_csv日志-orderItems'.json_encode($orderItems));

                $result[$k]['items']=$orderItems;
            }
        }

        //写入文件 文件名
        $col_type='C';
        $csv_type='Cancelled';
        $data=[];
        if($result??null){
            foreach($result as $k=>$v){
                foreach($v['items'] as $kk=>$vv){
                    $data[]=[
                        $col_type,
                        date('Y-m-d',strtotime($allPayDate[$v['order_id']]??strtotime($yestoday_date))),

                        //取消时间
                        date('Y-m-d',strtotime($allCanceledData[$v['order_id']]??strtotime($yestoday_date))),

                        strtoupper($this->csv_brand_name),
                        $v['order_id'],
                        $vv['id'],
                        $vv['item_bn'],
                        $vv['num'],
                        $vv['total_fee'],
                    ];
                }
            }
        }
        return $this->putOrderCsv($csv_type,$header,$data);

    }
    /**
     * 售后单
     *
     * @return void
     */
    function genReturndCsvFile(){
        $header=['Type','OrderPaymentDate','OrderReturnedDate','Brand','OrderID','OrderItemID','SKU','Units','Amount','Status'];
        //step1:先查售后单2022-10-11 16:29:06
        $aftersalesService = new AftersalesService();
        $filter = array();
        $yestoday_date=date('Y-m-d',   strtotime('-'.$this->last_days.' day'));
        $filter['create_time|gte']   = strtotime($yestoday_date);
        $filter['create_time|lte']   = strtotime(date('Y-m-d',$filter['create_time|gte']).' 23:59:59');
        $filter['company_id']=1;
        $orderBy = ['create_time'=>'DESC'];
        //
        $orgDataAftersalesList = $aftersalesService->getAftersalesList($filter, 0, 1000, $orderBy, true);
        $orgDataAftersalesList = $orgDataAftersalesList['list']??[];
        $allOrderIds=[0];
        $allReturnedData=[]; //payDate
        //$allrefund_id_ids=[];//退款单号
        if($orgDataAftersalesList??null){
            $allOrderIds=array_column($orgDataAftersalesList,'order_id');
            foreach($orgDataAftersalesList as $k=>$v){
                $allReturnedData[$v['order_id']]=date('Y-m-d',$v['create_time']);
                //$allrefund_id_ids[]=$v['refund_id'];
            }
        }
        //step2:查询支付单，需要支付时间
        $tradeService = new TradeService();
        $filter = array();
        $filter['trade_state']='SUCCESS';
        $filter['order_id']=$allOrderIds;
        $orgData = $tradeService->getTradeList($filter, $orderBy,10000, 1);
        $allPayDate=[];//payDate
        if($orgData['list']??null){
            $allOrderIds=array_column($orgData['list'],'orderId');
            foreach($orgData['list'] as $k=>$v){
                $allPayDate[$v['orderId']]=$v['payDate'];
            }
        }
        //step3:再查订单
/*         $result =[];
        $filter=[];
        $orderItems=[];
        if($allOrderIds){
            $filter['order_id'] = $allOrderIds;
            $filter['company_id']=1;
            $orderService = new OrderService(new NormalOrderService());
            $orderBy = ['create_time' => 'DESC'];
            $result = $orderService->getOrderList($filter,1, -1, $orderBy);
            if($result['list']??null){
                foreach($result['list'] as $k=>$v){
                    foreach($v['items'] as $kk=>$vv){
                        $orderItems[$vv['id']]=$vv;
                    }
                }
            }
        } */
        //查询 cancelOrder里实际取消的item_id
        //写入文件 文件名
        $col_type='R';
        $csv_type='Returned';
        $data=[];
        if($orgDataAftersalesList){
            foreach($orgDataAftersalesList as $k=>$v){
                if($v['aftersales_status']=='0' || $v['aftersales_status']=='1'){
                    $aftersales_status='W';
                }
                elseif($v['aftersales_status']=='2'){
                    $aftersales_status='A';
                }
                elseif($v['aftersales_status']=='3' || $v['aftersales_status']=='4'){
                    $aftersales_status='C';
                }
                else{
                    $aftersales_status='W';
                }
                foreach($v['detail'] as $kk=>$vv){
                    $data[]=[
                        $col_type,
                        date('Y-m-d',strtotime($allPayDate[$v['order_id']]??strtotime($yestoday_date))),
                        //售后时间
                        date('Y-m-d',strtotime($allReturnedData[$v['order_id']]??strtotime($yestoday_date))),
                        strtoupper($this->csv_brand_name),
                        $v['order_id'],
                        $vv['orderItem']['id'],
                        $vv['item_bn'],
                        $vv['num'],
                        $vv['orderItem']['total_fee'],
                        $aftersales_status,
                    ];
                }
            }
        }
        return $this->putOrderCsv($csv_type,$header,$data);
    }
    /**
     * 发货 function
     *
     * @return void
     */
    function genShippedCsvFile(){
        $header=['Type','OrderPaymentDate','OrderShippedDate','Brand','OrderID','OrderItemID','SKU','Units','Amount'];


        $orderDeliveryService = new OrderDeliveryService();
        //step1:先查发货单2022-10-11 16:29:06
        $filter = array();
        //$filter['trade_state']='SUCCESS';
        $yestoday_date=date('Y-m-d',strtotime('-'.$this->last_days.' day'));
        $filter['delivery_time|gte'] = strtotime($yestoday_date);
        $filter['delivery_time|lte']   = strtotime(date('Y-m-d',$filter['delivery_time|gte']).' 23:59:59');
        $orderBy = ['orders_delivery_id'=>'DESC'];
        $orgData = $orderDeliveryService->ordersDeliveryRepository->getLists($filter,'*', 1, -1,$orderBy);
        $allOrderIds=[0];
        //print_r($orgData);exit;

        $allShippedDate=[];//payDate
        $alldelivery_ids=[0];
        if($orgData??null){
            $allOrderIds=array_column($orgData,'order_id');
            foreach($orgData as $k=>$v){
                $allShippedDate[$v['order_id']]=date('Y-m-d',$v['delivery_time']);
                $alldelivery_ids[]=$v['orders_delivery_id'];
            }
        }

        //step2:查询支付单，需要支付时间
        $tradeService = new TradeService();
        $filter = array();
        $filter['trade_state']='SUCCESS';
        $filter['order_id']=$allOrderIds;
        $orgData = $tradeService->getTradeList($filter, $orderBy,10000, 1);
        $allPayDate=[];//payDate
        if($orgData['list']??null){
            $allOrderIds=array_column($orgData['list'],'orderId');
            foreach($orgData['list'] as $k=>$v){
                $allPayDate[$v['orderId']]=$v['payDate'];
            }
        }

        //step3:再查订单
        $result =[];
        $filter=[];
        $orderItems=[];
        if($allOrderIds){
            $filter['order_id'] = $allOrderIds;
            $filter['company_id']=1;
            //昨天
            //$filter['create_time|gte'] = strtotime('-1 day');
            //$filter['create_time|lte']   = strtotime(date('Y-m-d',$filter['time_start_begin']).' 23:59:59');

            $orderService = new OrderService(new NormalOrderService());
            $orderBy = ['create_time' => 'DESC'];
            $result = $orderService->getOrderList($filter,1, -1, $orderBy);
            if($result['list']??null){
                foreach($result['list'] as $k=>$v){
                    foreach($v['items'] as $kk=>$vv){
                        $orderItems[$vv['id']]=$vv;
                    }
                }
            }
        }


        //发货明细
        $filterDeliveryItems['orders_delivery_id']= $alldelivery_ids;
        $result = $orderDeliveryService->ordersDeliveryItemsRepository->getLists($filterDeliveryItems,'*', 1, -1,['orders_delivery_id'=>'desc']);


        //写入文件 文件名
        $col_type='S';
        $csv_type='Shipped';
        $data=[];
        if($result??null){
            foreach($result as $k=>$v){
                    $data[]=[
                        $col_type,
                        //支付时间

                        date('Y-m-d',strtotime($allPayDate[$v['order_id']]??strtotime($yestoday_date))),


                        //发货时间
                        date('Y-m-d',strtotime($allShippedDate[$v['order_id']]??strtotime($yestoday_date))),
                        strtoupper($this->csv_brand_name),
                        $v['order_id'],
                        $v['order_items_id'],
                        $orderItems[$v['order_items_id']]['item_bn'],
                        //发货数量
                        $v['num'],
                        $orderItems[$v['order_items_id']]['total_fee'],
                    ];
            }
        }
        return $this->putOrderCsv($csv_type,$header,$data);


    }
    /**
     * 计划任务创建订单日志 function
     *
     * @param integer $fromJob
     * @param boolean $schedule
     * @return void
     */
    function scheduleGenerateOrderCsv($fromJob = 1, $schedule = true)
    {
        $result = [];
        try {
            app('log')->debug(($fromJob ? '计划任务生成订单日志文件' : '后台手动生成订单日志文件') . '---->开始');
            $result = $this->generateOrderCsvFile(1, 1, 'mps_feed', [], $schedule);
            app('log')->debug(($fromJob ? '计划任务生成订单日志文件' : '后台手动生成订单日志文件') . '---->结束');
        } catch (\Exception $e) {
            app('log')->debug(($fromJob ? '计划任务生成订单日志文件' : '后台手动生成订单日志文件') . '---->' . $e->getMessage());
        }
        return $result;
    }

    /**
     * 计划任务上传订单日志 function
     *
     * @param integer $fromJob
     * @param boolean $schedule
     * @return void
     */
    function schedulePushOrderCsv($fromJob = 1, $schedule = true)
    {
        $result = [];
        try {
            app('log')->debug(($fromJob ? '计划任务上传订单日志文件' : '后台手动上传订单日志文件') . '---->开始');
            $result = $this->pushOrderCsvFile(1, 1, 'mps_feed', [], $schedule);
            app('log')->debug(($fromJob ? '计划任务上传订单日志文件' : '后台手动上传订单日志文件') . '---->结束');
        } catch (\Exception $e) {
            app('log')->debug(($fromJob ? '计划任务上传订单日志文件' : '后台手动上传订单日志文件') . '---->' . $e->getMessage());
        }
        return $result;
    }
    /**
     * 写入订单日志到CSV uploads/order_csv/2022-10-09/
     *
     * @param string $type
     * @param array $header
     * @param [type] $data
     * @return void
     */
    function putOrderCsv($type="",$header=[],$data){
        $last_days_time=strtotime('-'.$this->last_days.'day');
        $dir_pre='uploads/order_csv/' . date('Y-m-d',$last_days_time) . '/';
        if(!file_exists(storage_path($dir_pre))){
            mkdir(storage_path($dir_pre),0777);
        }
        $type=strtolower($type);
        $fileNameNew=$this->csv_prefix.'_'.$this->csv_brand_name.'_'.$type.'_'.date('Y_m_d',$last_days_time).'.csv';
        $file = storage_path($dir_pre.$fileNameNew);
        $fp=fopen($file,'w+');
        fputcsv($fp,$header);
        foreach($data as $k=>$v){
            fputcsv($fp,$v);
        }
        //上次到oss
        $fileType='order_csv';
        $uploadFileService = new UploadFileService();
        $uploadFileService->getUpdateFile($fileType);
        $uploadTime = time();
        $filePath = $uploadFileService->putFilePath(1, $fileType, $uploadTime, $fileNameNew);
        if (method_exists($uploadFileService->uploadFile, 'getFileSystem')) {

            app('log')->debug('上传order_csv的filePath:'.$filePath);

            $uploadFileService->uploadFile->getFileSystem()->put($filePath, file_get_contents($file));
        } else {
            app('filesystem')->put($filePath, file_get_contents($file));
        }
        $data = [
            'company_id' => 1,
            'file_name' => $fileNameNew,
            'file_size' => filesize($file), // $fileObject->getSize(),
            'handle_status' => 'wait', //等待处理
            'file_type' => $fileType,
            'handle_line_num' => 0,
            'created' => $uploadTime,
            'distributor_id' => 0,
            'left_job_num' => 1, //默认剩余一个待处理的子任务
        ];
        $data = $uploadFileService->entityRepository->create($data);
        //$uploadFileService->handleUploadFile($data, false);

        return true;
    }
    function doImportSplitCsvs()
    {

    }
}
