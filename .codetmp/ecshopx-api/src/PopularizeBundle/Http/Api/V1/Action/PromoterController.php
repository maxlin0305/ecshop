<?php

namespace PopularizeBundle\Http\Api\V1\Action;

use EspierBundle\Jobs\ExportFileJob;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;
use PopularizeBundle\Services\PromoterService;
use PopularizeBundle\Services\PromoterCountService;
use Dingo\Api\Exception\ResourceException;
use MembersBundle\Services\MemberService;

class PromoterController extends Controller
{
    /**
     * @SWG\Get(
     *     path="/popularize/promoter/list",
     *     summary="获取推广员列表",
     *     tags={"分销推广"},
     *     description="获取推广员列表",
     *     operationId="getPromoterList",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="page", in="query", description="分页", required=true, type="string"),
     *     @SWG\Parameter( name="pageSize", in="query", description="页码", required=true, type="string"),
     *     @SWG\Parameter( name="mobile", in="query", description="通过手机号搜索", required=false, type="string"),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(
     *            @SWG\Property(property="data", type="object", description="数据集合",
     *               @SWG\Property(property="total_count", type="integer", example="147", description="总记录条数"),
     *               @SWG\Property(property="list", type="array", description="数据列表",
     *                 @SWG\Items(
     *                           @SWG\Property(property="id", type="string", example="5", description="ID"),
     *                           @SWG\Property(property="promoter_id", type="string", example="5", description=""),
     *                           @SWG\Property(property="company_id", type="string", example="1", description="企业ID"),
     *                           @SWG\Property(property="user_id", type="string", example="20110", description="会员ID"),
     *                           @SWG\Property(property="shop_name", type="string", example="", description="推广员自定义店铺名称"),
     *                           @SWG\Property(property="alipay_name", type="string", example="", description="推广员提现的支付宝姓名"),
     *                           @SWG\Property(property="shop_pic", type="string", example="", description="推广店铺封面"),
     *                           @SWG\Property(property="brief", type="string", example="", description="推广店铺描述"),
     *                           @SWG\Property(property="alipay_account", type="string", example="", description="推广员提现的支付宝账号"),
     *                           @SWG\Property(property="pid", type="integer", example="0", description="上级会员ID"),
     *                           @SWG\Property(property="shop_status", type="integer", example="1", description="开店状态 0 未开店 1已开店 2申请中 3禁用 4申请审核拒绝 "),
     *                           @SWG\Property(property="reason", type="string", example="", description="审核拒绝原因"),
     *                           @SWG\Property(property="pmobile", type="string", example="", description="上级手机号"),
     *                           @SWG\Property(property="grade_level", type="integer", example="1", description="推广员等级"),
     *                           @SWG\Property(property="is_promoter", type="integer", example="1", description="是否为推广员"),
     *                           @SWG\Property(property="disabled", type="integer", example="0", description="是否有效"),
     *                           @SWG\Property(property="is_buy", type="integer", example="0", description="是否有购买记录"),
     *                           @SWG\Property(property="created", type="integer", example="1593669929", description=""),
     *                           @SWG\Property(property="children_count", type="integer", example="0", description=""),
     *                           @SWG\Property(property="bind_date", type="string", example="2020-07-02", description=""),
     *                           @SWG\Property(property="itemTotalPrice", type="integer", example="0", description=""),
     *                           @SWG\Property(property="rebateTotal", type="integer", example="0", description=""),
     *                           @SWG\Property(property="noCloseRebate", type="integer", example="0", description=""),
     *                           @SWG\Property(property="cashWithdrawalRebate", type="integer", example="0", description=""),
     *                           @SWG\Property(property="freezeCashWithdrawalRebate", type="integer", example="0", description=""),
     *                           @SWG\Property(property="rechargeRebate", type="integer", example="0", description=""),
     *                           @SWG\Property(property="payedRebate", type="integer", example="0", description=""),
     *                           @SWG\Property(property="rechargePoint", type="integer", example="0", description="充值返佣积分"),
     *                           @SWG\Property(property="cashWithdrawalPoint", type="integer", example="0", description="已结算积分"),
     *                           @SWG\Property(property="noClosePoint", type="integer", example="0", description="未结算佣金积分"),
     *                           @SWG\Property(property="pointTotal", type="integer", example="0", description="积分总额"),
     *                 ),
     *               ),
     *            ),
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromoterErrorRespones") ) )
     * )
     */
    public function getPromoterList(Request $request)
    {
        $promoterService = new PromoterService();

        $companyId = app('auth')->user()->get('company_id');

        if ($request->input('mobile', null)) {
            $filter['mobile'] = $request->input('mobile');
        }
        if ($request->input('username', null)) {
            $filter['username'] = $request->input('username');
        }
        $store_status = $request->input('store_status');
        if (!is_null($store_status) && $store_status != '') {
            $filter['shop_status'] = $request->input('store_status');
        }

        if ($request->input('time_start_begin')) {
            $filter['created|>='] = $request->input('time_start_begin');
            $filter['created|<='] = $request->input('time_start_end');
        }

        $filter['company_id'] = $companyId;

        $page = $request->input('page', 1);
        $limit = $request->input('pageSize', 20);

        $data = $promoterService->getPromoterList($filter, $page, $limit);
        // 是否有权限查看加密数据
        $datapassBlock = $request->get('x-datapass-block', 0);
        $data['datapass_block'] = $datapassBlock;
        if ($data['total_count'] > 0) {
            $promoterCountService = new PromoterCountService();

            $userIdList = array_column($data['list'], 'user_id');
            $promoterIndex = $promoterCountService->getPromoterIndexCount($companyId, $userIdList);
            foreach ($data['list'] as $k => $row) {
                $temp = $promoterIndex[$row['user_id']] ?? [];
                if (empty($temp)) {
                    $temp = [
                        'itemTotalPrice' => 0,
                        'rebateTotal' => 0,
                        'noCloseRebate' => 0,
                        'cashWithdrawalRebate' => 0,
                        'freezeCashWithdrawalRebate' => 0,
                        'rechargeRebate' => 0,
                        'payedRebate' => 0,
                        'rechargePoint' => 0,
                        'cashWithdrawalPoint' => 0,
                        'noClosePoint' => 0,
                        'pointTotal' => 0,
                    ];
                }

                $data['list'][$k] = array_merge($data['list'][$k], $temp);
                if ($datapassBlock) {
                    isset($row['mobile']) and $data['list'][$k]['mobile'] = data_masking('mobile', (string) $row['mobile']);
                    $data['list'][$k]['pmobile'] = data_masking('mobile', (string) $row['pmobile']);
                    isset($row['username']) and $data['list'][$k]['username'] = data_masking('truename', (string) $row['username']);
                }
            }
        }
        return $this->response->array($data);
    }

    /**
     * @SWG\Get(
     *     path="/popularize/promoter/children",
     *     summary="获取推广员直属下级列表",
     *     tags={"分销推广"},
     *     description="获取推广员直属下级列表",
     *     operationId="getPromoterchildrenList",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="page", in="query", description="分页", required=true, type="string"),
     *     @SWG\Parameter( name="pageSize", in="query", description="页码", required=true, type="string"),
     *     @SWG\Parameter( name="promoter_id", in="query", description="推广员id", required=true, type="string"),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(
     *            @SWG\Property(property="data", type="object", description="数据集合",
     *               @SWG\Property(property="total_count", type="integer", example="2", description="总记录条数"),
     *               @SWG\Property(property="list", type="array", description="数据列表",
     *                 @SWG\Items(
     *                           @SWG\Property(property="relationship_depth", type="integer", example="1", description=""),
     *                           @SWG\Property(property="promoter_id", type="integer", example="36", description=""),
     *                           @SWG\Property(property="grade_level", type="integer", example="2", description=""),
     *                           @SWG\Property(property="company_id", type="integer", example="1", description=""),
     *                           @SWG\Property(property="shop_status", type="integer", example="0", description=""),
     *                           @SWG\Property(property="user_id", type="integer", example="20236", description=""),
     *                           @SWG\Property(property="pmobile", type="string", example="15755777778", description=""),
     *                           @SWG\Property(property="created", type="integer", example="1598493367", description=""),
     *                           @SWG\Property(property="disabled", type="integer", example="0", description=""),
     *                           @SWG\Property(property="pid", type="integer", example="20", description=""),
     *                           @SWG\Property(property="is_buy", type="integer", example="0", description=""),
     *                           @SWG\Property(property="is_promoter", type="integer", example="1", description=""),
     *                           @SWG\Property(property="children_count", type="integer", example="1", description=""),
     *                           @SWG\Property(property="bind_date", type="string", example="2020-08-27", description=""),
     *                           @SWG\Property(property="itemTotalPrice", type="integer", example="0", description=""),
     *                           @SWG\Property(property="rebateTotal", type="integer", example="0", description=""),
     *                           @SWG\Property(property="noCloseRebate", type="integer", example="0", description=""),
     *                           @SWG\Property(property="cashWithdrawalRebate", type="integer", example="0", description=""),
     *                           @SWG\Property(property="freezeCashWithdrawalRebate", type="integer", example="0", description=""),
     *                           @SWG\Property(property="rechargeRebate", type="integer", example="0", description=""),
     *                           @SWG\Property(property="payedRebate", type="integer", example="0", description=""),
     *                 ),
     *               ),
     *            ),
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromoterErrorRespones") ) )
     * )
     */
    public function getPromoterchildrenList(Request $request)
    {
        $promoterService = new PromoterService();

        $companyId = app('auth')->user()->get('company_id');
        $filter['company_id'] = $companyId;

        $dataPassBlock = $request->get('x-datapass-block');

        $page = $request->input('page', 1);
        $limit = $request->input('pageSize', 20);

        $id = $request->input('promoter_id', 1);
        $filter['promoter_id'] = $id;

        $data = $promoterService->getPromoterchildrenList($filter, 1, $page, $limit);
        if ($data['total_count'] > 0) {
            $promoterCountService = new PromoterCountService();
            foreach ($data['list'] as $k => $row) {
                $count = $promoterCountService->getPromoterCount($companyId, $row['user_id']);
                $data['list'][$k] = array_merge($data['list'][$k], $count);
            }

            foreach ($data['list'] as $k => $row) {
                if ($dataPassBlock) {
                    $data['list'][$k]['mobile'] = data_masking('mobile', (string)$row['mobile']);
                    $data['list'][$k]['pmobile'] = data_masking('mobile', (string)$row['pmobile']);
                    $data['list'][$k]['username'] = data_masking('truename', (string)$row['username']);
                    $data['list'][$k]['nickname'] = data_masking('truename', (string)$row['nickname']);
                }
            }
        }
        return $this->response->array($data);
    }

    /**
     * @SWG\Put(
     *     path="/popularize/promoter/shop",
     *     summary="对推广员的店铺状态进行更新",
     *     tags={"分销推广"},
     *     description="对推广员的店铺状态进行更新",
     *     operationId="updatePromoterShop",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="shop_status", in="query", description="0 未开店 1已开店 2申请中 3禁用 4申请审核拒绝", required=true, type="string"),
     *     @SWG\Parameter( name="user_id", in="query", description="当前推广员的user_id", required=true, type="string"),
     *     @SWG\Parameter( name="reason", in="query", description="拒绝原因", required=false, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                   @SWG\Property(property="status", type="string"),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromoterErrorRespones") ) )
     * )
     */
    public function updatePromoterShop(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');

        $promoterService = new PromoterService();
        $userId = $request->input('user_id');
        if (!$userId) {
            throw new ResourceException('参数错误');
        }

        $reason = $request->input('reason', null);

        $status = $request->input('status', 0);
        $data = $promoterService->updateShopStatus($companyId, $userId, $status, $reason);
        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Put(
     *     path="/popularize/promoter/disabled",
     *     summary="禁用（冻结）/ 激活 （解冻）推广员 ",
     *     tags={"分销推广"},
     *     description="禁用（冻结）/ 激活 （解冻）推广员 ",
     *     operationId="updatePromoterDisabled",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="active", in="query", description="激活状态 true 激活 false 禁用", required=true, type="string"),
     *     @SWG\Parameter( name="user_id", in="query", description="当前推广员的user_id", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                    @SWG\Property(property="status", type="string"),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromoterErrorRespones") ) )
     * )
     */
    public function updatePromoterDisabled(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');

        $promoterService = new PromoterService();
        $userId = $request->input('user_id');
        if (!$userId) {
            throw new ResourceException('参数错误');
        }

        $userInfo = $promoterService->getInfoByUserId($userId);
        if (!$userInfo || !$userInfo['is_promoter'] || $userInfo['company_id'] != $companyId) {
            throw new ResourceException('无效的推广员');
        }

        $active = $request->input('active', 'true');
        $active = ($active == 'true') ? 0 : 1;
        $data = $promoterService->updateByUserId($userId, ['disabled' => $active]);

        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Put(
     *     path="/popularize/promoter/grade",
     *     summary="推广员等级调整",
     *     tags={"分销推广"},
     *     description="推广员等级调整，推广员等级数据可以通过推广员 /popularize/promoter/config（GET请求方式）接口获取",
     *     operationId="updatePromoterDisabled",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="grade_level", in="query", description="推广员等级id 目前支持 1， 2， 3 三级", required=true, type="string"),
     *     @SWG\Parameter( name="user_id", in="query", description="当前推广员的user_id", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                   @SWG\Property(property="status", type="string"),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromoterErrorRespones") ) )
     * )
     */
    public function updatePromoterGrade(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');

        $promoterService = new PromoterService();
        $userId = $request->input('user_id');
        if (!$userId) {
            throw new ResourceException('参数错误');
        }

        $userInfo = $promoterService->getInfoByUserId($userId);
        if (!$userInfo || !$userInfo['is_promoter'] || $userInfo['company_id'] != $companyId) {
            throw new ResourceException('无效的推广员');
        }

        $gradeLevel = $request->input('grade_level');
        $data = $promoterService->updateByUserId($userId, ['grade_level' => intval($gradeLevel)]);

        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Put(
     *     path="/popularize/promoter/remove",
     *     summary="调整推广员上下级关系 （调整到顶级）",
     *     tags={"分销推广"},
     *     description="调整推广员上下级关系，不要将当前推广员调整到自己的下级",
     *     operationId="updatePromoterDisabled",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="user_id", in="query", description="当前推广员的user_id", required=true, type="string"),
     *     @SWG\Parameter( name="new_user_id", in="query", description="要调整到的推广员user_id，如果要调整到顶级，则当前值为 0即可", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                   @SWG\Property(property="status", type="string"),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromoterErrorRespones") ) )
     * )
     */
    public function updatePromoterRemove(Request $request)
    {
        $promoterService = new PromoterService();

        $userId = $request->input('user_id');
        if (!$userId) {
            throw new ResourceException('参数错误');
        }

        $companyId = app('auth')->user()->get('company_id');

        $newUserId = $request->input('new_user_id');
        $data = $promoterService->relRemove($companyId, $userId, $newUserId);
        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Put(
     *     path="/popularize/promoter/add",
     *     summary="指定会员成为顶级推广员",
     *     tags={"分销推广"},
     *     description="指定会员成为顶级推广员",
     *     operationId="addPromoter",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="mobile", in="query", description="当前推广员会员手机号", required=false, type="string"),
     *     @SWG\Parameter( name="user_id", in="query", description="当前推广员会员id", required=false, type="integer"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                    @SWG\Property(property="status", type="string"),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromoterErrorRespones") ) )
     * )
     */
    public function addPromoter(Request $request)
    {
        $promoterService = new PromoterService();
        $companyId = app('auth')->user()->get('company_id');

        if ($request->input('mobile')) {
            $memberService = new MemberService();
            $userId = $memberService->getUserIdByMobile($request->input('mobile'), $companyId);
            if (!$userId) {
                throw new ResourceException('当前手机号还不是会员');
            }
        } else {
            $userId = $request->input('user_id');
            if (!$userId) {
                throw new ResourceException('参数错误');
            }
        }

        // 后台手动添加推广员
        // 强制当前会员成为推广员
        $data = $promoterService->changePromoter($companyId, $userId, true);
        if (isset($data['list'][0]['pid']) && $data['list'][0]['pid']) {
            // 将当前推广员移动到顶级
            $promoterService->relRemove($companyId, $userId, 0);
        }
        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Get(
     *     path="/popularize/promoter/export",
     *     summary="导出推广员列表",
     *     tags={"分销推广"},
     *     description="导出推广员列表",
     *     operationId="exportPromoterList",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="page", in="query", description="分页", required=true, type="string"),
     *     @SWG\Parameter( name="pageSize", in="query", description="页码", required=true, type="string"),
     *     @SWG\Parameter( name="mobile", in="query", description="通过手机号搜索", required=false, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                   @SWG\Property(property="status", type="string"),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromoterErrorRespones") ) )
     * )
     */
    public function exportPromoterList(Request $request)
    {
        $authdata = app('auth')->user()->get();
        $inputData = $request->input();

        //存储导出操作账号者
        $operator_id = app('auth')->user()->get('operator_id');
        $params['company_id'] = app('auth')->user()->get('company_id');
        if ($inputData['mobile'] ?? '') {
            $params['mobile'] = $inputData['mobile'] ?? '';
        }
        if ($inputData['username'] ?? '') {
            $params['username'] = $inputData['username'] ?? '';
        }
        // 是否有权限查看加密数据
        $params['datapass_block'] = $request->get('x-datapass-block');
        $gotoJob = (new ExportFileJob('popularize', $authdata['company_id'], $params, $operator_id))->onQueue('slow');
        app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($gotoJob);
        $result['status'] = true;
        return response()->json($result);
    }
}
