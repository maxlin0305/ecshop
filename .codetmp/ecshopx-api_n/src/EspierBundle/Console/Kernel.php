<?php

namespace EspierBundle\Console;

use AdaPayBundle\Commands\AutoDrawCashCommand;
use AdaPayBundle\Commands\BankCodeCommand as AdaPayBankCodeCommand;
use AdaPayBundle\Commands\CatrgoryCommand as AdaPayCatrgoryCommand;
use AdaPayBundle\Commands\RegionCommand as AdaPayRegionCommand;
use AdaPayBundle\Services\AdapayDrawCashService;
use AdaPayBundle\Services\OpenAccountService;
use AftersalesBundle\Services\AftersalesRefundService;
use AftersalesBundle\Services\AftersalesService;
use AliyunsmsBundle\Commands\SceneInitialize;
use AliyunsmsBundle\Services\SignService;
use AliyunsmsBundle\Services\SmsService;
use AliyunsmsBundle\Services\TaskService;
use AliyunsmsBundle\Services\TemplateService;
use App\Console\Commands\EcPayConfigCommand;
use App\Console\Commands\KeyGenerateCommand;
use App\Console\Commands\StorageLinkCommand;
use CommunityBundle\Services\CommunityActivityService;
use CompanysBundle\Ego\ExtendDemoLisensCommand;
use CompanysBundle\Services\CompanysStatisticsService;
use CompanysBundle\Services\OperatorLogs\MysqlService;
use CompanysBundle\Services\OperatorLogsService;
use DataCubeBundle\Services\CompanyDataService;
use DataCubeBundle\Services\DistributorDataService;
use DataCubeBundle\Services\GoodsDataService;
use DataCubeBundle\Services\MerchantDataService;
use EspierBundle\Commands\CreateSystemTagCommand;
use EspierBundle\Commands\DingoapiCacheCommand;
use EspierBundle\Commands\HyperfTransferCommand;
use EspierBundle\Commands\InitConfigRequestFieldsCommand;
use EspierBundle\Commands\JsonToSwaggerCommand;
use EspierBundle\Commands\NginxCacheCommand;
use EspierBundle\Commands\OmsGetStockCommand;
use EspierBundle\Commands\OrderTipCommand;
use EspierBundle\Commands\SendOrderToOmsCommand;
use EspierBundle\Commands\SendOrderToSaasOmsCommand;
use EspierBundle\Commands\SystemAddressInitialization;
use EspierBundle\Commands\TransferEncrypt\Adapay as TransferEncryptAdapayCommand;
use EspierBundle\Commands\TransferEncrypt\Distribution as TransferEncryptDistributionCommand;
use EspierBundle\Commands\TransferEncrypt\Members as TransferEncryptMembersCommand;
use EspierBundle\Commands\TransferEncrypt\Operators as TransferEncryptOperatorsCommand;
use EspierBundle\Commands\TransferEncrypt\Orders as TransferEncryptOrdersCommand;
use EspierBundle\Commands\TransferEncrypt\Popularize as TransferEncryptPopularizeCommand;
use EspierBundle\Commands\TransferEncrypt\Rights as TransferEncryptRightsCommand;
use EspierBundle\Commands\TransferEncrypt\ShopSalesperson as TransferEncryptShopSalespersonCommand;
use EspierBundle\Commands\TransferEncrypt\UserDiscount as TransferEncryptUserDiscountCommand;
use EspierBundle\Services\ExportLogService;
use EspierBundle\Services\UploadFileService;
use HfPayBundle\Console\HfpayStatisticsInitCommand;
use HfPayBundle\Services\HfpayCashRecordService;
use HfPayBundle\Services\HfpayCompanyDayStatisticsService;
use HfPayBundle\Services\HfpayDistributorStatisticsDayService;
use HfPayBundle\Services\HfpayEnterapplyService;
use Illuminate\Console\Scheduling\Schedule;
use KaquanBundle\Services\UserDiscountService;
use Laravel\Lumen\Console\Kernel as ConsoleKernel;
use OrdersBundle\Console\PrinterOrderCommand;
use OrdersBundle\Services\OrderDivisionService;
use OrdersBundle\Services\OrderProfitService;
use OrdersBundle\Services\OrderProfitSharingService;
use OrdersBundle\Services\Orders\GroupsNormalOrderService;
use OrdersBundle\Services\Orders\GroupsServiceOrderService;
use OrdersBundle\Services\Orders\NormalOrderService;
use OrdersBundle\Services\Orders\PointsmallNormalOrderService;
use OrdersBundle\Services\Orders\SeckillNormalOrderService;
use OrdersBundle\Services\Rights\TimesCardService;
use OrdersBundle\Services\StatementsService;
use PaymentBundle\Services\Payments\AdaPaymentService;
use PaymentBundle\Services\Payments\WechatPayService;
use PointBundle\Services\PointMemberService;
use PopularizeBundle\Services\BrokerageService;
use PromotionsBundle\Services\PromotionActivity;
use PromotionsBundle\Services\PromotionGroupsTeamService;
use PromotionsBundle\Services\SpecificCrowdDiscountService;
use PromotionsBundle\Services\TurntableService;
use SuperAdminBundle\Console\UpdateAccountPasswordCommand;
use SuperAdminBundle\Console\UploadDealerMenuCommand;
use SuperAdminBundle\Console\UploadMenuCommand;
use SuperAdminBundle\Console\UploadMerchantMenuCommand;
use SystemLinkBundle\Console\CopyItems;
use SystemLinkBundle\Console\GetOrderInfoCommand;
use ThemeBundle\Services\PagesTemplateServices;
use WechatBundle\Console\ApplySetOrderPathInfo;
use YoushuBundle\Services\TaskService as YoushuTaskService;

class Kernel extends ConsoleKernel
{
    public function getArtisan()
    {
        return parent::getArtisan();
    }
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        SendOrderToSaasOmsCommand::class,
        OmsGetStockCommand::class,
        AutoDrawCashCommand::class,
        AdaPayCatrgoryCommand::class,
        AdaPayBankCodeCommand::class,
        AdaPayRegionCommand::class,
        UploadMenuCommand::class,
        UploadDealerMenuCommand::class,
        UploadMerchantMenuCommand::class,
        OrderTipCommand::class,
        UpdateAccountPasswordCommand::class,
        ExtendDemoLisensCommand::class,
        CreateSystemTagCommand::class,
        TransferEncryptAdapayCommand::class,
        TransferEncryptDistributionCommand::class,
        TransferEncryptMembersCommand::class,
        TransferEncryptOperatorsCommand::class,
        TransferEncryptOrdersCommand::class,
        TransferEncryptPopularizeCommand::class,
        TransferEncryptRightsCommand::class,
        TransferEncryptUserDiscountCommand::class,
        TransferEncryptShopSalespersonCommand::class,
        CopyItems::class,
        SystemAddressInitialization::class,
        SendOrderToOmsCommand::class,
        GetOrderInfoCommand::class,
        JsonToSwaggerCommand::class,
        InitConfigRequestFieldsCommand::class,
        HfpayStatisticsInitCommand::class,
        SceneInitialize::class,
        PrinterOrderCommand::class,
        StorageLinkCommand::class,
        KeyGenerateCommand::class,
        DingoapiCacheCommand::class,
        NginxCacheCommand::class,
        HyperfTransferCommand::class,
        ApplySetOrderPathInfo::class,
        EcPayConfigCommand::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->call(function () {
            // 实体订单取消订单
            $normalOrderService = new NormalOrderService();
            $normalOrderService->scheduleCancelOrders();
        })->everyMinute()->name('schedule_cancel_normals_orders')->withoutOverlapping();

        $schedule->call(function () {
            // 自动取消未支付实体拼团订单
            $groupsNormalOrderService = new GroupsNormalOrderService();
            $groupsNormalOrderService->scheduleCancelOrders();
        })->everyMinute();

        $schedule->call(function () {
            // 自动取消未支付服务类拼团订单
            $groupsServiceOrderService = new GroupsServiceOrderService();
            $groupsServiceOrderService->scheduleCancelOrders();
        })->everyMinute();

        $schedule->call(function () {
            // 自动取消已支付未成功拼团
            $promotionGroupsTeamService = new PromotionGroupsTeamService();
            $promotionGroupsTeamService->scheduleAutoCancelGroupOrders();
            $promotionGroupsTeamService->scheduleAutoDoneGroup();
            $promotionGroupsTeamService->scheduleNoStoreAutoDoneGroup();
        })->everyMinute();

        $schedule->call(function () {
            //自动取消未支付秒杀订单
            $seckillNormalOrderService = new SeckillNormalOrderService();
            $seckillNormalOrderService->scheduleCancelOrders();
        })->everyMinute();

        $schedule->call(function () {
            //自动取消未支付积分商城订单
            $pointsmallNormalOrderService = new PointsmallNormalOrderService();
            $pointsmallNormalOrderService->scheduleCancelOrders();
        })->everyMinute();

        $schedule->call(function () {
            //自动取消用户选择后过期的兑换券
            $userDiscountService = new UserDiscountService();
            $userDiscountService->scheduleCancelExCard();
        })->everyMinute();

        $schedule->call(function () {
            //自动过期定向促销
            $specificCrowdDiscountService = new SpecificCrowdDiscountService();
            $specificCrowdDiscountService->scheduleExpiredPromotion();
        })->everyFiveMinutes();

        $schedule->call(function () {
            //自动驳回售后
            $aftersalesService = new AftersalesService();
            $aftersalesService->scheduleAutoRefuse();
        })->everyMinute();

        $schedule->call(function () {
            // 定时结算分佣
            $brokerageService = new BrokerageService();
            $brokerageService->scheduleSettleRebate();
        })->everyMinute();

        $schedule->call(function () {
            //自动关闭订单售后
            $aftersalesService = new AftersalesService();
            $aftersalesService->scheduleAutoCloseOrderItemAftersales();
        })->everyMinute();

        $schedule->call(function () {
            // 定时结算导购佣金
            $orderProfitService = new OrderProfitService();
            $orderProfitService->scheduleSettleProfit();
        })->everyMinute();

        // $schedule->call(function () {
        //     // 员工内购，删除已失效的分享码
        //     $employeePurchaseActivityService = new EmployeePurchaseActivityService();
        //     $employeePurchaseActivityService->scheduleExpireSharecode();
        // })->everyMinute();

        $schedule->call(function () {
            //智能模板定时启用
            $pages_template_services = new PagesTemplateServices();
            $pages_template_services->timer();
            //订单分账
            $orderProfitSharingService = new OrderProfitSharingService();
            $orderProfitSharingService->lists();
        })->everyFiveMinutes();

        $schedule->call(function () {
            // 删除上传到期文件
            $uploadFileService = new UploadFileService();
            $uploadFileService->scheduleDeleteErrorFile();
        })->daily();

        $schedule->call(function () {
            // 自动关闭到期驳回售后
            $aftersalesService = new AftersalesService();
            $aftersalesService->scheduleAutoDoneAftersales();
        })->daily();

        $schedule->call(function () {
            //为会员增加积分
            $pointMemberService = new PointMemberService();
            $pointMemberService->scheduleSendMemberPoint();
        })->daily();

        $schedule->call(function () {
            // 各种定时统计
            $statisService = new CompanysStatisticsService();
            $statisService->scheduleRecordStatistics();
            $statisService->scheduleActiveArticleRecordStatistics();
            $statisService->scheduleCommissionRecordStatistics();
            $statisService->schedulePopularizeRecordStatistics();
            $statisService->scheduleGiveCouponsRecordStatistics();

            //生成结算单
            $statementsService = new StatementsService();
            $statementsService->scheduleGenerateStatements();
        })->dailyAt('1:00');

        $schedule->call(function () {
            // 营销活动
            $promotionActivity = new PromotionActivity();
            $promotionActivity->activityInvalid();
            $promotionActivity->scheduleTrigger();
        })->dailyAt('1:00');

        $schedule->call(function () {
            // 商家后台当日汇总统计
            $companyDataService = new CompanyDataService();
            $companyDataService->scheduleInitStatistic();
            //商户后台当日汇总统计
            $merchantDataService = new MerchantDataService();
            $merchantDataService->scheduleInitStatistic();
            //汇付店铺分账数据统计
            $hfpayDistributorStatisticsDayService = new HfpayDistributorStatisticsDayService();
            $hfpayDistributorStatisticsDayService->statistics();
            //汇付平台分账数据统计
            $hfpayCompanyDayStatisticsService = new HfpayCompanyDayStatisticsService();
            $hfpayCompanyDayStatisticsService->statistics();
        })->dailyAt('1:30');

        $schedule->call(function () {
            // 商品数据统计
            $goodsDataService = new GoodsDataService();
            $goodsDataService->scheduleInitStatistic();
        })->dailyAt('2:00');

        $schedule->call(function () {
            // 删除操作日志
            $operatorLogsService = new OperatorLogsService(new MysqlService());
            $operatorLogsService->scheduleDelOperatorLogs();
        })->dailyAt('2:30');

        $schedule->call(function () {
            // 店铺每日数据统计
            $goodsDataService = new DistributorDataService();
            $goodsDataService->scheduleInitStatistic();
        })->dailyAt('2:45');

        $schedule->call(function () {
            // 删除历史导出文件列表
            $exportLogService = new ExportLogService();
            $exportLogService->scheduleDeleteHistoryFile();
        })->dailyAt('3:00');

        // 银联商务支付，划付，上传文件
        $schedule->call(function () {
            $orderDivisionService = new OrderDivisionService();
            $orderDivisionService->scheduleTransferSftp();
        })->dailyAt('18:00');

        // 银联商务支付，划付重新提交，上传文件
        $schedule->call(function () {
            $orderDivisionService = new OrderDivisionService();
            $orderDivisionService->scheduleTransferResubmitSftp();
        })->dailyAt('18:00');

        // 银联商务支付，划付，处理回盘数据
        $schedule->call(function () {
            $orderDivisionService = new OrderDivisionService();
            $orderDivisionService->scheduleTransferDownloadSftp();
        })->dailyAt('3:00');

        // 退款
        $schedule->call(function () {
            $aftersalesRefundService = new AftersalesRefundService();
            $aftersalesRefundService->schedule_refund();

            $wechatPayService = new WechatPayService();
            $wechatPayService->scheduleQueryMerchantPayment();
        })->everyTenMinutes();

        $schedule->call(function () {
            // 会员升级
            $normalOrderService = new NormalOrderService();
            $normalOrderService->scheduleConsumptionOrders();
        })->dailyAt('3:30');

        $schedule->call(function () {
            // 实体订单完成订单
            $normalOrderService = new NormalOrderService();
            $normalOrderService->scheduleFinishOrders();
        })->dailyAt('3:00');

        $schedule->call(function () {
            //每天过期或失效用户权益
            $timesCardService = new TimesCardService();
            $timesCardService->scheduleUpdateRightStatus();
        })->dailyAt('3:30');

        //每天11点上传腾讯有数，微信数据
        $schedule->call(function () {
            $service = new YoushuTaskService();
            $service->addWxappVisitPage();
            $service->addWxappVisitDistribution();
        })->dailyAt('11:00');

        $schedule->call(function () {
            //汇付提现状态查询
            $hfpayCashRecordService = new HfpayCashRecordService();
            $hfpayCashRecordService->checkStatus();
        })->dailyAt('23:00');

        // 每天5点上传腾讯有数，订单汇总信息
        $schedule->call(function () {
            $service = new YoushuTaskService();
            $service->addOrderSum();
        })->dailyAt('5:00');

        $schedule->call(function () {
            // 大转盘活动结束时清空抽奖次数
            $turntableService = new TurntableService();
            $turntableService->scheduleClearTurntableTimesOver();
        })->dailyAt('3:30');

        $schedule->call(function () {
            // 定向促销 更新自然月的周期开始和结束时间
            $specificCrowdDiscountService = new SpecificCrowdDiscountService();
            $specificCrowdDiscountService->scheduleExpiredPromotionMonth();
        })->monthly();

        $schedule->call(function () {
            //店铺提现
            $hfpay_enterapply_service = new HfpayEnterapplyService();
            $hfpay_enterapply_service->distributorWithdraw();
        })->dailyAt('18:00');

        $schedule->call(function () {
            //定时查询adapay总商户提交证照审核状态
            $openAccountService = new OpenAccountService();
            $openAccountService->getSubmitLicenseStatus();
        })->everyTenMinutes();

        $schedule->call(function () {
            //adapay支付确认重试
            $openAccountService = new AdaPaymentService();
            $openAccountService->adaPayPaymentConfirmRetry();
        })->everyTenMinutes();

        $schedule->call(function () {
            //adapay自动提现，每分钟执行
            $adapayDrawCashService = new AdapayDrawCashService();
            $adapayDrawCashService->autoDrawCashQueue();
        })->everyMinute();

        $schedule->call(function () {
            //阿里云短信签名审核状态查询, 每分钟执行
            $signService = new SignService();
            $signService->queryAuditStatus();
        })->everyFiveMinutes();

        $schedule->call(function () {
            //阿里云短信模板审核状态查询, 每分钟执行
            $templateService = new TemplateService();
            $templateService->queryAuditStatus();
        })->everyFiveMinutes();

        $schedule->call(function () {
            //阿里云短信发送结果查询, 每分钟执行
            $smsService = new SmsService();
            $smsService->querySendDetail();
        })->everyMinute();

        $schedule->call(function () {
            //阿里云短信群发任务状态更新, 每10分钟执行
            $taskService = new TaskService();
            $taskService->updateStatus();
        })->everyMinute();

        $schedule->call(function () {
            //阿里云短信群发任务执行, 每分钟执行
            $taskService = new TaskService();
            $taskService->runTask();
        })->everyMinute();

        $schedule->call(function () {
            //活动时间截止时自动成团或解散, 每分钟执行
            $communityActivityService = new CommunityActivityService();
            $communityActivityService->scheduleAutoFinishActivity();
        })->everyMinute();
    }
}
