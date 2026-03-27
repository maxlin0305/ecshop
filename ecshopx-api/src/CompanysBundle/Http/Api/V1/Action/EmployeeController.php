<?php

namespace CompanysBundle\Http\Api\V1\Action;

use AdaPayBundle\Services\MemberService;
use App\Http\Controllers\Controller as BaseController;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use CompanysBundle\Services\EmployeeService;
use CompanysBundle\Services\OperatorsService;

use Dingo\Api\Exception\StoreResourceFailedException;

class EmployeeController extends BaseController
{
    /** @var $employeeService */
    private $employeeService;

    /**
     * @param employeeService  $employeeService
     */
    public function __construct(EmployeeService $employeeService)
    {
        $this->employeeService = new $employeeService();
    }

    /**
     * @SWG\Post(
     *     path="/account/management",
     *     summary="创建企业员工",
     *     tags={"企业"},
     *     description="创建企业员工",
     *     operationId="createData",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string",
     *         required=true,
     *     ),
     *     @SWG\Parameter(
     *         name="mobile",
     *         in="query",
     *         description="手机号码",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="username",
     *         in="query",
     *         description="员工姓名",
     *         type="integer",
     *     ),
     *     @SWG\Parameter(
     *         name="login_name",
     *         in="query",
     *         description="用户名",
     *         type="integer",
     *     ),
     *     @SWG\Parameter(
     *         name="head_portrait",
     *         in="query",
     *         description="员工头像",
     *         type="integer",
     *     ),
     *     @SWG\Parameter(
     *         name="distributor_ids",
     *         in="query",
     *         description="distributor_ids",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="password",
     *         in="query",
     *         description="登录密码",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="role_id",
     *         in="query",
     *         description="角色",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="operator_id",
     *         in="query",
     *         description="operator_id",
     *         type="string",
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                     @SWG\Property(property="operator_id", type="integer"),
     *                     @SWG\Property(property="company_id", type="integer", example="1"),
     *                     @SWG\Property(property="mobile", type="integer", example=""),
     *                     @SWG\Property(property="username", type="string", example=""),
     *                     @SWG\Property(property="login_name", type="string", example=""),
     *                     @SWG\Property(property="head_portrait", type="string", example="1"),
     *                     @SWG\Property(property="password", type="string", example="1"),
     *                     @SWG\Property(property="eid", type="string", example="1"),
     *                     @SWG\Property(property="passport_uid", type="string", example="1"),
     *                     @SWG\Property(property="operator_type", type="string", example="1"),
     *                     @SWG\Property(property="shop_ids", type="string", example="[]"),
     *                     @SWG\Property(property="distributor_ids", type="string", example="[]"),
     *                     @SWG\Property(property="role_id", type="integer", example="1"),
     *                     @SWG\Property(property="regionauth_id", type="integer", example="1"),
     *                     @SWG\Property(property="lastlogintime", type="string", example="1"),
     *                     @SWG\Property(property="created", type="string", example="1"),
     *                     @SWG\Property(property="updated", type="string", example="1"),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */
    public function createData(Request $request)
    {
        $params = $request->all("login_name", "mobile", "username", "head_portrait", "password", "role_id", 'distributor_ids', 'shop_ids', 'operator_type', 'regionauth_id', 'contact');
        $merchantId = app('auth')->user()->get('merchant_id');
        $operatorType = app('auth')->user()->get('operator_type');
        if ($operatorType == 'merchant') {
            $params['merchant_id'] = $merchantId;
        }

        $rules = [
            'mobile' => ['required', '手机号必填'],
            'password' => ['required', '登录密码必填'],
            'operator_type' => ['required|in:staff,merchant,distributor,dealer,merchant', '账号类型错误'],
            // 'distributor_ids' => ['required', '必须添加所属店铺'],
            // 'regionauth_id'   => ['required|integer|min:0', '区域必填'],
        ];
        if ($params['operator_type'] == 'dealer') {
            unset($rules['distributor_ids']);
            $rules['contact'] = ['required', '联系人姓名必填'];

            $memberService = new MemberService();
            $operator = $memberService->getOperator();
            if ($operator['operator_type'] == 'dealer') {
                $params['dealer_parent_id'] = $operator['operator_id'];
                $params['is_dealer_main'] = 0;
            } else {
                $params['is_dealer_main'] = 1;
            }
        }
        $error = validator_params($params, $rules);
        if ($error) {
            throw new StoreResourceFailedException($error);
        }
        // 平台后台账号和商户账号可以添加店铺端账号相关判断
        $operatorsService = new OperatorsService();
        if (($params['operator_type'] == 'distributor')) {
            if (in_array($operatorType, ['admin', 'staff', 'merchant'])) {
                // if (!$params['distributor_ids'] || !is_array($params['distributor_ids'])) {
                //     throw new StoreResourceFailedException('至少添加关联一个店铺！');
                // }
                if ($params['distributor_ids']) {
                    foreach($params['distributor_ids'] as $v) {
                        $filter['distributor_ids|contains'] = '"distributor_id":"'.$v['distributor_id'].'"';
                        $filter['is_distributor_main'] = 1;
                        $operatorInfo = $operatorsService->lists($filter);
                        if ($operatorInfo['total_count'] >= 1) {
                            throw new StoreResourceFailedException('店铺【'.$v['name'].'】已经有超级管理员！');
                        }
                    }
                }
                $params['is_distributor_main'] = 1; // 平台后台账号和商户账号只能添加店铺超级管理员,不能给店铺添加普通店铺账号，入口做限制。
                unset($params['role_id']);
            }
            // 店铺管理员添加店铺员工账号判断
            if (in_array($operatorType, ['distributor'])) {
                if (!isset($params['distributor_ids']) || (count($params['distributor_ids']) != 1)) {
                    throw new StoreResourceFailedException('必须关联一个店铺！');
                }
                if (!isset($params['role_id']) || (count($params['role_id']) < 1)) {
                    throw new StoreResourceFailedException('至少关联一个角色！');
                }
                $operator = $operatorsService->getInfo(['operator_id' => app('auth')->user()->get('operator_id')]);
                $params['merchant_id'] = $operator['merchant_id'];
            }
        }

        if (!isset($params['regionauth_id'])) {
            $params['regionauth_id'] = 0;
        }
        if (!ismobile($params['mobile'])) {
            throw new StoreResourceFailedException("请填写正确的手机号");
        }
        $params['company_id'] = app('auth')->user()->get('company_id');
        $params['operator_id'] = app('auth')->user()->get('operator_id');

        $result = $this->employeeService->createOperatorStaff($params);

        return $this->response->array($result);
    }

    /**
     * @SWG\Patch(
     *     path="/account/management/{operator_id}",
     *     summary="更改企业员工信息",
     *     tags={"企业"},
     *     description="更改企业员工信息",
     *     operationId="updateData",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string",
     *         required=true,
     *     ),
      *     @SWG\Parameter(
     *         name="mobile",
     *         in="query",
     *         description="手机号码",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="username",
     *         in="query",
     *         description="员工姓名",
     *         type="integer",
     *     ),
     *     @SWG\Parameter(
     *         name="login_name",
     *         in="query",
     *         description="用户名",
     *         type="integer",
     *     ),
     *     @SWG\Parameter(
     *         name="head_portrait",
     *         in="query",
     *         description="员工头像",
     *         type="integer",
     *     ),
     *     @SWG\Parameter(
     *         name="distributor_ids",
     *         in="query",
     *         description="distributor_ids",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="password",
     *         in="query",
     *         description="登录密码",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="role_id",
     *         in="query",
     *         description="角色",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="operator_id",
     *         in="query",
     *         description="operator_id",
     *         type="string",
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                     type="object",
     *                     @SWG\Property(property="operator_id", type="integer"),
     *                     @SWG\Property(property="company_id", type="integer", example="1"),
     *                     @SWG\Property(property="mobile", type="integer", example="1"),
     *                     @SWG\Property(property="username", type="string", example="1"),
     *                     @SWG\Property(property="head_portrait", type="string", example="1"),
     *                     @SWG\Property(property="password", type="string", example="1"),
     *                     @SWG\Property(property="role_id", type="integer", example="1"),
     *                     @SWG\Property(property="lastlogintime", type="string", example="1"),
     *                     @SWG\Property(property="created", type="string", example="1"),
     *                     @SWG\Property(property="updated", type="string", example="1"),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */
    public function updateData($operator_id, Request $request)
    {
        $params = $request->all("username", "mobile", "head_portrait", "password", "role_id", 'distributor_ids', 'shop_ids', 'operator_type', 'regionauth_id');
        $merchantId = app('auth')->user()->get('merchant_id');
        $operatorType = app('auth')->user()->get('operator_type');
        if ($operatorType == 'merchant') {
            $params['merchant_id'] = $merchantId;
        }
        // $rules = [
        //     'regionauth_id' => ['required|integer|min:0', '必须关联区域'],
        // ];
        // $error = validator_params($params, $rules);
        // if($error) {
        //     throw new StoreResourceFailedException($error);
        // }
        if (!isset($params['regionauth_id'])) {
            $params['regionauth_id'] = 0;
        }

        // if (!isset($params['distributor_ids'])) {
        //     throw new StoreResourceFailedException('必须添加所属店铺');
        // }

        // 平台后台账号和商户账号可以添加店铺端账号相关判断
        if (($params['operator_type'] == 'distributor')) {
            if (in_array($operatorType, ['admin', 'staff', 'merchant'])) {
                // if (!$params['distributor_ids'] || !is_array($params['distributor_ids'])) {
                //     throw new StoreResourceFailedException('至少关联一个店铺！');
                // }
                $operatorsService = new OperatorsService();
                if ($params['distributor_ids']) {
                    foreach($params['distributor_ids'] as $v) {
                        $checkFilter = [];
                        $checkFilter['distributor_ids|contains'] = '"distributor_id":"'.$v['distributor_id'].'"';
                        $checkFilter['is_distributor_main'] = 1;
                        $operatorInfo = $operatorsService->lists($checkFilter);
                        if (($operatorInfo['total_count'] >= 1) && ($operator_id != $operatorInfo['list'][0]['operator_id'])) {
                            throw new StoreResourceFailedException('店铺【'.$v['name'].'】已经有超级管理员！');
                        }
                    }
                }
                $params['is_distributor_main'] = 1; // 平台后台账号和商户账号只能添加店铺超级管理员,不能给店铺添加普通店铺账号，入口做限制。
            }
            // 店铺管理员添加店铺员工账号判断
            if (in_array($operatorType, ['distributor'])) {
                if (!isset($params['distributor_ids']) || (count($params['distributor_ids']) != 1)) {
                    throw new StoreResourceFailedException('必须关联一个店铺！');
                }
                if (!isset($params['role_id']) || (count($params['role_id']) < 1)) {
                    throw new StoreResourceFailedException('至少关联一个角色！');
                }
            }

        }


        $filter['company_id'] = app('auth')->user()->get('company_id');
        $filter['operator_id'] = $operator_id;
        $result = $this->employeeService->updateOperatorStaff($params, $filter);
        return $this->response->array($result);
    }

    /**
     * @SWG\Delete(
     *     path="/account/management/{operator_id}",
     *     summary="删除企业员工信息",
     *     tags={"企业"},
     *     description="删除企业员工信息",
     *     operationId="deleteData",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string",
     *         required=true,
     *     ),
     *     @SWG\Parameter(
     *         name="operator_id",
     *         in="query",
     *         description="id",
     *         type="string",
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="status", type="boolean"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */
    public function deleteData($operator_id, Request $request)
    {
        return []; // 不允许删除账号
        $params['operator_id'] = $operator_id;

        $rules = [
            'operator_id' => ['required', 'id必填'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new StoreResourceFailedException($error);
        }
        $company_id = app('auth')->user()->get('company_id');

        $result = $this->employeeService->deleteStaff($operator_id, $company_id);
        return $this->response->array(['status' => $result]);
    }

    /**
     * @SWG\Get(
     *     path="/account/management",
     *     summary="获取企业员工信息列表",
     *     tags={"企业"},
     *     description="获取企业员工信息列表",
     *     operationId="getListData",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string",
     *         required=true,
     *     ),
     *     @SWG\Parameter(
     *         name="operator_id",
     *         in="query",
     *         description="id",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="operator_type",
     *         in="query",
     *         description="操作员类型",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="mobile",
     *         in="query",
     *         description="手机号码",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="distributor_ids",
     *         in="query",
     *         description="distributor_ids",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="username",
     *         in="query",
     *         description="员工姓名",
     *         type="integer",
     *     ),
     *     @SWG\Parameter(
     *         name="role_id",
     *         in="query",
     *         description="角色",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="login_name",
     *         in="query",
     *         description="用户名",
     *         type="string",
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property(property="total_count", type="integer"),
     *                 @SWG\Property(property="list", type="array", @SWG\Items(
     *                     @SWG\Property(property="operator_id", type="integer", example="1"),
     *                     @SWG\Property(property="company_id", type="integer", example="1"),
     *                     @SWG\Property(property="mobile", type="integer", example="1"),
     *                     @SWG\Property(property="username", type="string", example="1"),
     *                     @SWG\Property(property="head_portrait", type="string", example="1"),
     *                     @SWG\Property(property="password", type="string", example="1"),
     *                     @SWG\Property(property="role_id", type="integer", example="1"),
     *                     @SWG\Property(property="eid", type="integer", example="1"),
     *                     @SWG\Property(property="passport_uid", type="integer", example="1"),
     *                     @SWG\Property(property="operator_type", type="integer", example="1"),
     *                     @SWG\Property(property="shop_ids", type="string", example="1"),
     *                     @SWG\Property(property="distributor_ids", type="string", example="1"),
     *                     @SWG\Property(property="regionauth_id", type="string", example="1"),
     *                     @SWG\Property(property="role_data", type="string", example="1"),
     *                     @SWG\Property(property="lastlogintime", type="string", example="1"),
     *                     @SWG\Property(property="created", type="string", example="1"),
     *                     @SWG\Property(property="updated", type="string", example="1"),
     *                 ),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */
    public function getListData(Request $request)
    {
        $params = $request->all('operator_id', 'mobile', 'username', 'role_id', 'page', 'pageSize', 'login_name');
        $merchantId = app('auth')->user()->get('merchant_id');
        $operatorType = app('auth')->user()->get('operator_type');
        if ($operatorType == 'merchant') {
            $filter['merchant_id'] = $merchantId;
        }
        $inputOperatorType = $request->input('operator_type');
        if (($inputOperatorType == 'distributor') && in_array($operatorType, ['admin', 'staff', 'merchant',])) {
            $filter['is_distributor_main'] = 1;
        }
        if ($params['operator_id']) {
            $filter['operator_id'] = (array)$params['operator_id'];
        }

        if ($params['mobile']) {
            $filter['mobile'] = $params['mobile'];
        }

        if ($params['username']) {
            $filter['username|contains'] = $params['username'];
        }

        if ($params['login_name']) {
            $filter['login_name'] = $params['login_name'];
        }

        if ($params['role_id']) {
            $filter['role_id'] = $params['role_id'];
        }

        $filter['operator_type'] = $inputOperatorType;
        $filter['company_id'] = app('auth')->user()->get('company_id');

        if (app('auth')->user()->get('distributor_id')) {
            $filter['distributor_ids|contains'] = '"distributor_id":"'.app('auth')->user()->get('distributor_id').'"';
        }

        $page = $params['page'] ?: 1;
        $pageSize = $params['pageSize'] ?: 20;
        $orderBy = ["created" => "DESC"];

        $result = $this->employeeService->getListStaff($filter, $page, $pageSize, $orderBy);
        // 是否有权限查看加密数据
        $datapassBlock = $request->get('x-datapass-block', 0);
        $result['datapass_block'] = $datapassBlock;
        if ($datapassBlock) {
            foreach ($result['list'] as $key => $value) {
                $result['list'][$key]['username'] = data_masking('truename', (string) $value['username']);
                $result['list'][$key]['mobile'] = data_masking('mobile', (string) $value['mobile']);
            }
        }
        return $this->response->array($result);
    }


    /**
     * @SWG\Get(
     *     path="/account/management/{operator_id}",
     *     summary="获取企业员工信息",
     *     tags={"企业"},
     *     description="获取企业员工信息",
     *     operationId="getInfoData",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string",
     *         required=true,
     *     ),
     *     @SWG\Parameter(
     *         name="operator_id",
     *         in="query",
     *         description="id",
     *         type="string",
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                     type="object",
     *                     @SWG\Property(property="operator_id", type="integer"),
     *                     @SWG\Property(property="company_id", type="integer", example="1"),
     *                     @SWG\Property(property="mobile", type="integer", example="1"),
     *                     @SWG\Property(property="username", type="string", example="1"),
     *                     @SWG\Property(property="head_portrait", type="string", example="1"),
     *                     @SWG\Property(property="password", type="string", example="1"),
     *                     @SWG\Property(property="role_id", type="integer", example="1"),
     *                     @SWG\Property(property="eid", type="integer", example="1"),
     *                     @SWG\Property(property="passport_uid", type="integer", example="1"),
     *                     @SWG\Property(property="operator_type", type="integer", example="1"),
     *                     @SWG\Property(property="shop_ids", type="string", example="1"),
     *                     @SWG\Property(property="distributor_ids", type="string", example="1"),
     *                     @SWG\Property(property="regionauth_id", type="string", example="1"),
     *                     @SWG\Property(property="role_data", type="string", example="1"),
     *                     @SWG\Property(property="lastlogintime", type="string", example="1"),
     *                     @SWG\Property(property="created", type="string", example="1"),
     *                     @SWG\Property(property="updated", type="string", example="1"),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */
    public function getInfoData($operator_id, Request $request)
    {
        $filter['operator_id'] = $operator_id;
        $rules = [
            'operator_id' => ['required', 'id必填'],
        ];
        $error = validator_params($filter, $rules);
        if ($error) {
            throw new StoreResourceFailedException($error);
        }
        $company_id = app('auth')->user()->get('company_id');

        $result = $this->employeeService->getInfoStaff($operator_id, $company_id);
        return $this->response->array($result);
    }
}
