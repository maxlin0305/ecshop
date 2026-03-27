<?php

namespace DistributionBundle\Http\Api\V1\Action;

use AdaPayBundle\Services\CorpMemberService;
use DistributionBundle\Services\DistributorTagsService;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;

use DistributionBundle\Services\DistributorService;
use DistributionBundle\Services\DistributeCountService;
use DistributionBundle\Services\DistributorItemsService;
use MerchantBundle\Services\MerchantService;
use OrdersBundle\Services\CompanyRelDadaService;
use ThirdPartyBundle\Services\DadaCentre\ShopService;
use WechatBundle\Services\WeappService;
use GoodsBundle\Services\ItemStoreService;
use DistributionBundle\Services\DistributorSalesmanService;
use DistributionBundle\Events\DistributorCreateEvent;
use DistributionBundle\Events\DistributorUpdateEvent;
use HfPayBundle\Services\HfpayLedgerConfigService;

use EspierBundle\Jobs\ExportFileJob;

use Dingo\Api\Exception\StoreResourceFailedException;
use Dingo\Api\Exception\ResourceException;
use AdaPayBundle\Services\MemberService as AdapayMemberService;
use CompanysBundle\Services\EmployeeService;
use GoodsBundle\Services\ItemsService;
use CompanysBundle\Ego\CompanysActivationEgo;
use GoodsBundle\Services\ItemsCategoryService;
use DistributionBundle\Services\PickupLocationService;
use DistributionBundle\Services\DistributorAftersalesAddressService;

use Exception;
use Swagger\Annotations as SWG;

class Distributor extends Controller
{
    /**
     * @SWG\Post(
     *     path="/distributor",
     *     summary="添加店铺",
     *     tags={"店铺"},
     *     description="添加店铺",
     *     operationId="createDistributor",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="shop_code", in="query", description="店铺号", type="string"),
     *     @SWG\Parameter( name="name", in="query", description="店铺名称", required=true, type="string"),
     *     @SWG\Parameter( name="contact", in="query", description="联系人姓名", required=true, type="string"),
     *     @SWG\Parameter( name="mobile", in="query", description="联系方式", required=true, type="string"),
     *     @SWG\Parameter( name="hour", in="query", description="经营时间", required=true, type="string"),
     *     @SWG\Parameter( name="is_ziti", in="query", description="是否支持自提", required=true, type="string"),
     *     @SWG\Parameter( name="is_delivery", in="query", description="是否快递", required=true, type="string"),
     *     @SWG\Parameter( name="auto_sync_goods", in="query", description="自动同步总部商品", type="string"),
     *     @SWG\Parameter( name="logo", in="query", description="logo", type="string"),
     *     @SWG\Parameter( name="banner", in="query", description="banner", type="string"),
     *     @SWG\Parameter( name="regionauth_id", in="query", description="所属区域", required=true, type="string"),
     *     @SWG\Parameter( name="lng", in="query", description="经度", required=true, type="string"),
     *     @SWG\Parameter( name="lat", in="query", description="纬度", required=true, type="string"),
     *     @SWG\Parameter( name="regions_id", in="query", description="区域编码", required=true, type="string"),
     *     @SWG\Parameter( name="regions", in="query", description="区域名称", required=true, type="string"),
     *     @SWG\Parameter( name="address", in="query", description="详细地址", required=true, type="string"),
     *     @SWG\Parameter( name="is_dada", in="query", description="是否开启达达同城配", type="string"),
     *     @SWG\Parameter( name="business", in="query", description="业务类型", type="string"),
     *     @SWG\Parameter( name="is_open", in="query", description="是否分账", type="boolean"),
     *     @SWG\Parameter( name="rate", in="query", description="平台服务费", type="string"),
     *     @SWG\Parameter( name="contract_phone", in="query", description="固定座机", type="string"),
     *     @SWG\Parameter( name="introduce", in="query", description="店铺介绍", type="string"),
     *     @SWG\Parameter( name="distribution_type", in="query", description="店铺类型", type="string"),
     *     @SWG\Parameter( name="merchant_id", in="query", description="所属商户", type="string"),
     *     @SWG\Parameter( name="offline_aftersales", in="query", description="本店订单到店售后", type="string"),
     *     @SWG\Parameter( name="offline_aftersales_distributor_id", in="query", description="本店订单到其他店铺售后", type="string"),
     *     @SWG\Parameter( name="offline_aftersales_other", in="query", description="其他店铺订单到本店售后", type="string"),
     *     @SWG\Parameter( name="offline_aftersales_address[name]", in="query", description="退货点名称", type="string"),
     *     @SWG\Parameter( name="offline_aftersales_address[province]", in="query", description="退货点省份", type="string"),
     *     @SWG\Parameter( name="offline_aftersales_address[city]", in="query", description="退货点城市", type="string"),
     *     @SWG\Parameter( name="offline_aftersales_address[area]", in="query", description="退货点区/县", type="string"),
     *     @SWG\Parameter( name="offline_aftersales_address[address]", in="query", description="退货点地址", type="string"),
     *     @SWG\Parameter( name="offline_aftersales_address[regions]", in="query", description="退货点省市区", type="string"),
     *     @SWG\Parameter( name="offline_aftersales_address[regions_id]", in="query", description="退货点省市区ID", type="string"),
     *     @SWG\Parameter( name="offline_aftersales_address[area_code]", in="query", description="联系电话区号", type="string"),
     *     @SWG\Parameter( name="offline_aftersales_address[mobile]", in="query", description="货点联系电话", type="string"),
     *     @SWG\Parameter( name="offline_aftersales_address[hours]", in="query", description="退货点营业时间", type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="distributor_id", type="string", example="86", description="店铺id"),
     *                  @SWG\Property( property="shop_id", type="string", example="0", description="门店id"),
     *                  @SWG\Property( property="is_distributor", type="string", example="true", description="是否是主店铺"),
     *                  @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                  @SWG\Property( property="mobile", type="string", example="13712345678", description="手机号"),
     *                  @SWG\Property( property="address", type="string", example="上海市徐汇区宜山路七0一弄23", description="地址"),
     *                  @SWG\Property( property="name", type="string", example="测试x", description="名称"),
     *                  @SWG\Property( property="auto_sync_goods", type="string", example="false", description="自动同步总部商品"),
     *                  @SWG\Property( property="logo", type="string", example="null", description="店铺logo"),
     *                  @SWG\Property( property="contract_phone", type="string", example="0", description="联系电话"),
     *                  @SWG\Property( property="banner", type="string", example="null", description="店铺banner"),
     *                  @SWG\Property( property="contact", type="string", example="测试x", description="联系人"),
     *                  @SWG\Property( property="is_valid", type="string", example="true", description="是否有效"),
     *                  @SWG\Property( property="lng", type="string", example="121.417663", description="地图纬度"),
     *                  @SWG\Property( property="lat", type="string", example="31.17429", description="地图经度"),
     *                  @SWG\Property( property="child_count", type="string", example="0", description=""),
     *                  @SWG\Property( property="is_default", type="string", example="0", description="是否默认"),
     *                  @SWG\Property( property="is_audit_goods", type="string", example="false", description="是否审核店铺商品"),
     *                  @SWG\Property( property="is_ziti", type="string", example="false", description="是否支持自提"),
     *                  @SWG\Property( property="regions_id", type="array",
     *                      @SWG\Items( type="string", example="310000", description=""),
     *                  ),
     *                  @SWG\Property( property="regions", type="array",
     *                      @SWG\Items( type="string", example="上海市", description=""),
     *                  ),
     *                  @SWG\Property( property="is_domestic", type="string", example="1", description="是否是中国国内门店 1:国内(包含港澳台),2:非国内"),
     *                  @SWG\Property( property="is_direct_store", type="string", example="1", description="是否为直营店 1:直营店,2:非直营店"),
     *                  @SWG\Property( property="province", type="string", example="上海市", description="省"),
     *                  @SWG\Property( property="is_delivery", type="string", example="true", description="是否支持配送"),
     *                  @SWG\Property( property="city", type="string", example="上海市", description="市"),
     *                  @SWG\Property( property="area", type="string", example="徐汇区", description="区"),
     *                  @SWG\Property( property="hour", type="string", example="08:00-21:00", description="营业时间，格式11:11-12:12"),
     *                  @SWG\Property( property="created", type="string", example="1613981654", description=""),
     *                  @SWG\Property( property="updated", type="string", example="1613981654", description="修改时间"),
     *                  @SWG\Property( property="shop_code", type="string", example="153456", description="店铺号"),
     *                  @SWG\Property( property="wechat_work_department_id", type="string", example="0", description="企业微信的部门ID"),
     *                  @SWG\Property( property="distributor_self", type="string", example="0", description="是否是总店配置"),
     *                  @SWG\Property( property="regionauth_id", type="string", example="2", description="地区id"),
     *                  @SWG\Property( property="is_open", type="string", example="false", description=""),
     *                  @SWG\Property( property="rate", type="string", example="null", description=""),
     *                  @SWG\Property( property="is_dada", type="string", example="0", description="是否开启达达同城配"),
     *                  @SWG\Property( property="business", type="string", example="1", description="业务类型"),
     *                  @SWG\Property( property="introduce", type="string", example="1", description="店铺介绍"),
     *                  @SWG\Property( property="distribution_type", type="string", example="1", description="店铺类型"),
     *                  @SWG\Property( property="merchant_id", type="string", example="1", description="所属商户"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/DistributionErrorRespones") ) )
     * )
     */
    public function createDistributor(Request $request)
    {
        $distributorService = new DistributorService();
        $companyId = app('auth')->user()->get('company_id');
        $merchantId = app('auth')->user()->get('merchant_id');
        $operatorType = app('auth')->user()->get('operator_type');
        $regions = [
            0 => 'province',
            1 => 'city',
            2 => 'area',
        ];
        $params = $request->all('name', 'address', 'mobile', 'regions_id', 'regions', 'shop_id', 'contact', 'lng', 'lat', 'hour', 'logo', 'banner', 'auto_sync_goods', 'is_audit_goods', 'is_ziti', 'is_delivery', 'shop_code', 'distributor_self', 'regionauth_id', 'is_open', 'rate', 'is_dada', 'business', 'contract_phone', 'introduce','merchant_id','distribution_type', 'is_require_subdistrict', 'is_require_building', 'pickup_location', 'offline_aftersales', 'offline_aftersales_distributor_id', 'offline_aftersales_other', 'offline_aftersales_address');
        if(empty($params['distribution_type'])){
            $params['merchant_id'] = 0;
        }
        if($operatorType == 'merchant'){
            $params['merchant_id'] = $merchantId;
            $params['distribution_type'] = '1';
        }
        if (!empty($params['is_dada']) && $params['is_dada'] == 'false') {
            $params['is_dada'] = '';
        }

        if (empty($params['regionauth_id'])) {
            $params['regionauth_id'] = 0;
        }

        $params['offline_aftersales_self'] = false;
        if (isset($params['offline_aftersales'])) {
            $params['offline_aftersales'] = (!$params['offline_aftersales'] || $params['offline_aftersales'] === 'false') ? false : true;
            $params['offline_aftersales_self'] = $params['offline_aftersales']; //创建开启到店退货开关默认可以到本店退货
        }

        if (isset($params['offline_aftersales_other'])) {
            $params['offline_aftersales_other'] = (!$params['offline_aftersales_other'] || $params['offline_aftersales_other'] === 'false') ? false : true;
        }

        $rules = [
            'name' => 'required|max:255',
            'contact' => 'required|max:255',
            'mobile' => 'required|mobile',
            'hour' => 'required|max:150',
            'is_ziti' => 'required',
            // 'is_delivery' => 'required',
            // 'auto_sync_goods' => 'required',
            #  'regionauth_id' => 'required',
            'lng' => 'required|max:255',
            'lat' => 'required|max:255',
            'regions_id' => 'required',
            'regions' => 'required',
            'address' => 'required|max:255',
            'introduce' => 'max:1000',
            'distribution_type' => 'required',
            'merchant_id' => 'required_if:distribution_type,1',
            'contract_phone' => 'min:9'
        ];
        $msg = [
            'name.required' => '店铺名称必填',
            'name.max' => '店铺名称最大长度255',
            'contact.required' => '联系人姓名必填',
            'contact.max' => '联系人姓名最大长度255',
            'mobile.required' => '联系方式必填',
            'mobile.mobile' => '联系方式格式错误',
            'hour.required' => '经营时间必填',
            'hour.max' => '经营时间最大长度150',
            'is_ziti.required' => '是否支持自提必填',
            // 'is_delivery.required' => '是否快递必填',
            // 'auto_sync_goods.required' => '自动上架商品必填',
            #   'regionauth_id.required' => '所属区域必填',
            'lng.required' => '经度必填',
            'lng.max' => '经度最大长度255',
            'lat.required' => '纬度必填',
            'lat.max' => '纬度最大长度255',
            'regions_id.required' => '区域必填',
            'regions.required' => '区域必填',
            'address.required' => '详细地址必填',
            'address.max' => '详细地址最大长度255',
            'introduce.max' => '店铺介绍最大长度1000',
            'distribution_type.required' => '店铺类型必填',
            'merchant_id.*' => '所属商户必填',
            'contract_phone.min' => '请填写正确格式的固定座机',
        ];
        ## 没有开启达达同城配时店铺编号必填; 开启达达同城配时用户填写了店铺编号用用户填写的，没有填写店铺编号使用达达创建的
        if (empty($params['is_dada'])) {
            $params['is_dada'] = 0;
            $rules['shop_code'] = 'required';
            $msg['shop_code.required'] = '店铺编号必填';
        }
        if ($params['offline_aftersales_self'] || $params['offline_aftersales_other']) {
            $rules['offline_aftersales_address.name'] = 'required|max:255';
            $rules['offline_aftersales_address.regions'] = 'required';
            $rules['offline_aftersales_address.regions_id'] = 'required';
            $rules['offline_aftersales_address.address'] = 'required|max:255';
            $rules['offline_aftersales_address.mobile'] = 'required';
            $rules['offline_aftersales_address.hours'] = 'required';
            $msg['offline_aftersales_address.name.required'] = '退货点名称必填';
            $msg['offline_aftersales_address.regions.required'] = '省市区必填';
            $msg['offline_aftersales_address.regions_id.required'] = '省市区必填';
            $msg['offline_aftersales_address.address.required'] = '退货点地址必填';
            $msg['offline_aftersales_address.mobile.required'] = '退货点联系电话必填';
            $msg['offline_aftersales_address.hours.required'] = '退货点营业时间必填';
        }
        $validator = app('validator')->make($params, $rules, $msg);
        if ($validator->fails()) {
            $errorsMsg = $validator->errors()->toArray();
            $errmsg = current($errorsMsg)[0];
            throw new ResourceException($errmsg);
        }

        ## 店铺编号唯一
        if (!empty($params['shop_code'])) {
            if (!preg_match('/^[A-Za-z0-9-]+$/', $params['shop_code'])) {
                throw new StoreResourceFailedException("请填写正确格式的店铺编号");
            }

            $exist = $distributorService->count(['shop_code' => $params['shop_code'], 'is_valid|neq' => 'delete', 'company_id' => $companyId]);
            if ($exist) {
                throw new Exception("店铺号已存在");
            }
        }
        ## 店铺名称是否存在
        $exist = $distributorService->count(['name' => $params['name'], 'is_valid|neq' => 'delete', 'company_id' => $companyId]);
        if ($exist) {
            throw new Exception("店铺名称已存在");
        }
        ## 手机号是否存在
//        $exist = $distributorService->count(['mobile' => $params['mobile'], 'is_valid|neq'=>'delete', 'company_id' => $companyId]);
//        if ($exist) {
//            throw new Exception("店铺手机号已存在");
//        }
        if (!empty($params['contract_phone'])) {
//            if (!preg_match('/^\d{3,4}-?\d{7,8}$/', $params['contract_phone'])) {
//                throw new StoreResourceFailedException("请填写正确格式的固定座机");
//            }
        }

        if (isset($params['distributor_self']) && $params['distributor_self'] == '1') {
            $params['is_valid'] = 'true';

            //是否有自营店铺信息设置
            $result = $distributorService->getDistributorSelf($companyId);
            if ($result) {
                throw new StoreResourceFailedException("总部自提点只能添加一个");
            }
        }

        if (app('auth')->user()->get('operator_type') == 'distributor') {
            unset($params['is_audit_goods']);
            unset($params['auto_sync_goods']);
        }
        if (isset($params['regions_id']) && isset($params['regions'])) {
            foreach ($params['regions'] as $k => $value) {
                $params[$regions[$k]] = $value;
            }
        }

        $distributorIds = $request->get('distributorIds');
        if ($distributorIds) {
            throw new Exception("您没有添加店铺的权限", 400500);
        }

        if (isset($params['is_ziti'])) {
            $params['is_ziti'] = (!$params['is_ziti'] || $params['is_ziti'] === 'false') ? false : true;
        }
        if (isset($params['is_delivery'])) {
            $params['is_delivery'] = (!$params['is_delivery'] || $params['is_delivery'] === 'false') ? false : true;
        } else {
            $params['is_delivery'] = true;
        }
        if (isset($params['is_ziti'], $params['is_delivery']) && !$params['is_ziti'] && !$params['is_delivery']) {
            throw new Exception("自提和快递配送至少开启一项", 400500);
        }
        if (isset($params['auto_sync_goods'])) {
            $params['auto_sync_goods'] = (!$params['auto_sync_goods'] || $params['auto_sync_goods'] === 'false') ? false : true;
        } else {
            $params['auto_sync_goods'] = false;
        }
        if (isset($params['is_audit_goods'])) {
            $params['is_audit_goods'] = (!$params['is_audit_goods'] || $params['is_audit_goods'] === 'false') ? false : true;
        }
        if (isset($params['is_require_subdistrict'])) {
            $params['is_require_subdistrict'] = (!$params['is_require_subdistrict'] || $params['is_require_subdistrict'] === 'false') ? false : true;
        }
        if (isset($params['is_require_building'])) {
            $params['is_require_building'] = (!$params['is_require_building'] || $params['is_require_building'] === 'false') ? false : true;
        }

        $params['company_id'] = $companyId;

        //分账配置判断
        if (isset($params['is_open']) && $params['is_open'] == 'true') {
            $hfpayLedgerConfigService = new HfpayLedgerConfigService();
            $ledgerConfig = $hfpayLedgerConfigService->getLedgerConfig(['company_id' => $companyId]);
            if (empty($ledgerConfig) || $ledgerConfig['is_open'] == 'false') {
                throw new Exception("请在分账及结算-基础配置中开启分账");
            }
            $params['rate'] = $params['rate'] ? bcmul($params['rate'], 100) : '0';
            if ($params['rate'] < 0) {
                throw new Exception("服务费率不能小于0");
            }
            if ($params['rate'] > 3000) {
                throw new Exception("服务费率不得超过30");
            }
        }
        $params['dada_shop_create'] = 0;
        if (!empty($params['is_dada'])) {
            $companyRelDadaService = new CompanyRelDadaService();
            $companyRelData = $companyRelDadaService->getInfo(['company_id' => $companyId]);
            if (empty($companyRelData['is_open'])) {
                throw new Exception("该商户未开启达达同城配");
            }

            if (!($params['business'] ?? null)) {
                throw new Exception('请选择业务类型');
            }

            $shopService = new ShopService();
            $shopParam = [];
            $shopParam[] = $params;
            $dadaResult = $shopService->createShop($companyId, $shopParam);
            $originShopId = $dadaResult['successList'][0]['originShopId'];
            $params['shop_code'] = $originShopId;
            $params['dada_shop_create'] = 1;
        }

        if (isset($params['offline_aftersales_distributor_id']) && $params['offline_aftersales_distributor_id']) {
            if ($distributorService->count(['merchant_id|neq' => $params['merchant_id'], 'distributor_id' => $params['offline_aftersales_distributor_id']])) {
                throw new ResourceException('售后门店只能选择同一商户下的其他门店');
            }
        }

        $result = $distributorService->createDistributor($params);

        // 关联自提点
        if (isset($params['pickup_location']) && $params['pickup_location']) {
            $pickupLocationService = new PickupLocationService();
            if (is_array($params['pickup_location'])) {
                foreach ($params['pickup_location'] as $pickupLocationId) {
                    $pickupLocationService->relDistributor($companyId, 0, $pickupLocationId, $result['distributor_id']);
                }
            } else {
                $pickupLocationService->relDistributor($companyId, 0, $params['pickup_location'], $result['distributor_id']);
            }
        }

        if (isset($params['offline_aftersales_address']) && $params['offline_aftersales_address']) {
            $aftersalesAddress = $params['offline_aftersales_address'];
            if (isset($aftersalesAddress['name'], $aftersalesAddress['address'], $aftersalesAddress['mobile']) && $aftersalesAddress['name'] && $aftersalesAddress['address'] && $aftersalesAddress['mobile']) {
                if (isset($aftersalesAddress['area_code']) && $aftersalesAddress['area_code']) {
                    $aftersalesAddress['mobile'] = $aftersalesAddress['area_code'].'-'.$aftersalesAddress['mobile'];
                }
                $data = [
                    'distributor_id' => $result['distributor_id'],
                    'name' => $aftersalesAddress['name'],
                    'regions_id' => json_encode($aftersalesAddress['regions_id'] ?? []),
                    'regions' => json_encode($aftersalesAddress['regions'] ?? []),
                    'address' => $aftersalesAddress['address'],
                    'company_id' => $companyId,
                    'mobile' => $aftersalesAddress['mobile'],
                    'hours' => $aftersalesAddress['hours'],
                    'merchant_id' => $merchantId,
                    'return_type' => 'offline',
                    'province' => '',
                    'city' => '',
                    'area' => '',
                ];
                foreach (($aftersalesAddress['regions'] ?? []) as $k => $value) {
                    $data[$regions[$k]] = $value;
                }
                $distributorAftersalesAddressService = new DistributorAftersalesAddressService();
                $distributorAftersalesAddressService->setDistributorAfterSalesAddress($data);
            }
        }

        return $this->response->array($result);
    }

    /**
     * @SWG\Put(
     *     path="/distributor/{distributor_id}",
     *     summary="更新店铺",
     *     tags={"店铺"},
     *     description="更新店铺",
     *     operationId="updateDistributor",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="shop_code", in="query", description="店铺号", type="string"),
     *     @SWG\Parameter( name="name", in="query", description="店铺名称", required=true, type="string"),
     *     @SWG\Parameter( name="contact", in="query", description="联系人姓名", required=true, type="string"),
     *     @SWG\Parameter( name="mobile", in="query", description="联系方式", required=true, type="string"),
     *     @SWG\Parameter( name="hour", in="query", description="经营时间", required=true, type="string"),
     *     @SWG\Parameter( name="is_ziti", in="query", description="是否支持自提", required=true, type="string"),
     *     @SWG\Parameter( name="is_delivery", in="query", description="是否快递", required=true, type="string"),
     *     @SWG\Parameter( name="auto_sync_goods", in="query", description="自动同步总部商品", required=true, type="string"),
     *     @SWG\Parameter( name="logo", in="query", description="logo", type="string"),
     *     @SWG\Parameter( name="banner", in="query", description="banner", type="string"),
     *     @SWG\Parameter( name="regionauth_id", in="query", description="所属区域", required=true, type="string"),
     *     @SWG\Parameter( name="lng", in="query", description="经度", required=true, type="string"),
     *     @SWG\Parameter( name="lat", in="query", description="纬度", required=true, type="string"),
     *     @SWG\Parameter( name="regions_id", in="query", description="区域编码", required=true, type="string"),
     *     @SWG\Parameter( name="regions", in="query", description="区域名称", required=true, type="string"),
     *     @SWG\Parameter( name="address", in="query", description="详细地址", required=true, type="string"),
     *     @SWG\Parameter( name="is_dada", in="query", description="是否开启达达同城配", type="string"),
     *     @SWG\Parameter( name="business", in="query", description="业务类型", type="string"),
     *     @SWG\Parameter( name="is_open", in="query", description="是否分账", type="boolean"),
     *     @SWG\Parameter( name="rate", in="query", description="平台服务费", type="string"),
     *     @SWG\Parameter( name="contract_phone", in="query", description="固定座机", type="string"),
     *     @SWG\Parameter( name="introduce", in="query", description="店铺介绍", type="string"),
     *     @SWG\Parameter( name="distribution_type", in="query", description="店铺类型", type="string"),
     *     @SWG\Parameter( name="merchant_id", in="query", description="所属商户", type="string"),
     *     @SWG\Parameter( name="offline_aftersales", in="query", description="本店订单到店售后", type="string"),
     *     @SWG\Parameter( name="offline_aftersales_distributor_id", in="query", description="本店订单到其他店铺售后", type="string"),
     *     @SWG\Parameter( name="offline_aftersales_other", in="query", description="其他店铺订单到本店售后", type="string"),
     *     @SWG\Parameter( name="offline_aftersales_address[name]", in="query", description="退货点名称", type="string"),
     *     @SWG\Parameter( name="offline_aftersales_address[province]", in="query", description="退货点省份", type="string"),
     *     @SWG\Parameter( name="offline_aftersales_address[city]", in="query", description="退货点城市", type="string"),
     *     @SWG\Parameter( name="offline_aftersales_address[area]", in="query", description="退货点区/县", type="string"),
     *     @SWG\Parameter( name="offline_aftersales_address[address]", in="query", description="退货点地址", type="string"),
     *     @SWG\Parameter( name="offline_aftersales_address[regions]", in="query", description="退货点省市区", type="string"),
     *     @SWG\Parameter( name="offline_aftersales_address[regions_id]", in="query", description="退货点省市区ID", type="string"),
     *     @SWG\Parameter( name="offline_aftersales_address[area_code]", in="query", description="联系电话区号", type="string"),
     *     @SWG\Parameter( name="offline_aftersales_address[mobile]", in="query", description="货点联系电话", type="string"),
     *     @SWG\Parameter( name="offline_aftersales_address[hours]", in="query", description="退货点营业时间", type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="distributor_id", type="string", example="119", description="店铺id"),
     *                  @SWG\Property( property="shop_id", type="string", example="0", description="门店id"),
     *                  @SWG\Property( property="is_distributor", type="string", example="true", description="是否是主店铺"),
     *                  @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                  @SWG\Property( property="mobile", type="string", example="13712345678", description="手机号"),
     *                  @SWG\Property( property="address", type="string", example="上海市虹口区横浜路131号附近", description="具体地址"),
     *                  @SWG\Property( property="name", type="string", example="xxx", description="名称"),
     *                  @SWG\Property( property="auto_sync_goods", type="string", example="1", description="自动同步总部商品"),
     *                  @SWG\Property( property="logo", type="string", example="null", description="店铺logo"),
     *                  @SWG\Property( property="contract_phone", type="string", example="0", description="固定座机"),
     *                  @SWG\Property( property="banner", type="string", example="null", description="店铺banner"),
     *                  @SWG\Property( property="contact", type="string", example="测试王1", description="联系人"),
     *                  @SWG\Property( property="is_valid", type="string", example="true", description="是否有效"),
     *                  @SWG\Property( property="lng", type="string", example="121.478942", description="地图纬度"),
     *                  @SWG\Property( property="lat", type="string", example="31.26418", description="地图经度"),
     *                  @SWG\Property( property="child_count", type="string", example="0", description=""),
     *                  @SWG\Property( property="is_default", type="string", example="0", description="是否默认"),
     *                  @SWG\Property( property="is_audit_goods", type="string", example="true", description="是否审核店铺商品"),
     *                  @SWG\Property( property="is_ziti", type="string", example="false", description="是否支持自提"),
     *                  @SWG\Property( property="regions_id", type="array",
     *                      @SWG\Items( type="string", example="310000", description=""),
     *                  ),
     *                  @SWG\Property( property="regions", type="array",
     *                      @SWG\Items( type="string", example="上海市", description=""),
     *                  ),
     *                  @SWG\Property( property="is_domestic", type="string", example="1", description="是否是中国国内门店 1:国内(包含港澳台),2:非国内"),
     *                  @SWG\Property( property="is_direct_store", type="string", example="1", description="是否为直营店 1:直营店,2:非直营店"),
     *                  @SWG\Property( property="province", type="string", example="上海市", description="省"),
     *                  @SWG\Property( property="is_delivery", type="string", example="true", description="是否支持配送"),
     *                  @SWG\Property( property="city", type="string", example="上海市", description="市"),
     *                  @SWG\Property( property="area", type="string", example="黄浦区", description="区"),
     *                  @SWG\Property( property="hour", type="string", example="08:00-21:00", description="营业时间，格式11:11-12:12"),
     *                  @SWG\Property( property="created", type="string", example="1612407584", description=""),
     *                  @SWG\Property( property="updated", type="string", example="1613977288", description="修改时间"),
     *                  @SWG\Property( property="shop_code", type="string", example="1111111", description="店铺号"),
     *                  @SWG\Property( property="wechat_work_department_id", type="string", example="1", description="企业微信的部门ID"),
     *                  @SWG\Property( property="distributor_self", type="string", example="0", description="是否是总店配置"),
     *                  @SWG\Property( property="regionauth_id", type="string", example="1", description="地区id"),
     *                  @SWG\Property( property="is_open", type="string", example="false", description=""),
     *                  @SWG\Property( property="rate", type="string", example="null", description=""),
     *                  @SWG\Property( property="is_dada", type="string", example="0", description="是否开启达达同城配"),
     *                  @SWG\Property( property="business", type="string", example="1", description="业务类型"),
     *                  @SWG\Property( property="introduce", type="string", example="1", description="店铺介绍"),
     *                  @SWG\Property( property="distribution_type", type="string", example="1", description="店铺类型"),
     *                  @SWG\Property( property="merchant_id", type="string", example="1", description="所属商户"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/DistributionErrorRespones") ) )
     * )
     */
    public function updateDistributor($distributor_id, Request $request)
    {
        $regions = [
            0 => 'province',
            1 => 'city',
            2 => 'area',
        ];
        $params = $request->all('name', 'address', 'mobile', 'regions_id', 'regions', 'contact', 'shop_id', 'is_ziti', 'lng', 'lat', 'hour', 'logo', 'banner', 'auto_sync_goods', 'is_audit_goods', 'is_delivery', 'shop_code', 'distributor_self', 'regionauth_id', 'is_open', 'rate', 'is_dada', 'business', 'is_valid', 'contract_phone', 'introduce','merchant_id','distribution_type', 'is_require_subdistrict', 'is_require_building', 'offline_aftersales', 'offline_aftersales_distributor_id', 'offline_aftersales_other', 'offline_aftersales_address');
        $merchantId = app('auth')->user()->get('merchant_id');
        $operatorType = app('auth')->user()->get('operator_type');
        if($operatorType == 'merchant'){
            $params['merchant_id'] = $merchantId;
            $params['distribution_type'] = '1';
        }
        $reviewResult = $request->input('review_result', '');
        $params['distributor_id'] = $distributor_id;
        if (!empty($params['is_dada']) && $params['is_dada'] == 'false') {
            $params['is_dada'] = '';
        }
        if (empty($params['regionauth_id'])) {
            $params['regionauth_id'] = 0;
        }

        if (isset($params['offline_aftersales'])) {
            $params['offline_aftersales'] = (!$params['offline_aftersales'] || $params['offline_aftersales'] === 'false') ? false : true;
        }

        $params['offline_aftersales_self'] = false;
        if (isset($params['offline_aftersales_distributor_id'])) {
            $params['offline_aftersales_self'] = in_array($distributor_id, $params['offline_aftersales_distributor_id']);
        }

        if (isset($params['offline_aftersales_other'])) {
            $params['offline_aftersales_other'] = (!$params['offline_aftersales_other'] || $params['offline_aftersales_other'] === 'false') ? false : true;
        }

        // 脱敏的字段
        $datapassBlockCol = [];
        if (!empty($params['shop_code'])) {
            $rules = [
                'name' => 'required|max:255',
                'contact' => 'required|max:255',
                'mobile' => 'required|mobile',
                'hour' => 'required|max:150',
                'is_ziti' => 'required',
                // 'is_delivery' => 'required',
                // 'auto_sync_goods' => 'required',
                #       'regionauth_id' => 'required',
                'lng' => 'required|max:255',
                'lat' => 'required|max:255',
                'regions_id' => 'required',
                'regions' => 'required',
                'address' => 'required|max:255',
                'introduce' => 'max:1000',
                'distribution_type' => 'required',
                'merchant_id' => 'required_if:distribution_type,1',
                'contract_phone' => 'min:9',
            ];
            $msg = [
                'name.required' => '店铺名称必填',
                'name.max' => '店铺名称最大长度255',
                'contact.required' => '联系人姓名必填',
                'contact.max' => '联系人姓名最大长度255',
                'mobile.required' => '联系方式必填',
                'mobile.mobile' => '联系方式格式错误',
                'hour.required' => '经营时间必填',
                'hour.max' => '经营时间最大长度150',
                'is_ziti.required' => '是否支持自提必填',
                // 'is_delivery.required' => '是否快递必填',
                // 'auto_sync_goods.required' => '自动上架商品必填',
                #     'regionauth_id.required' => '所属区域必填',
                'lng.required' => '经度必填',
                'lng.max' => '经度最大长度255',
                'lat.required' => '纬度必填',
                'lat.max' => '纬度最大长度255',
                'regions_id.required' => '区域必填',
                'regions.required' => '区域必填',
                'address.required' => '详细地址必填',
                'address.max' => '详细地址最大长度255',
                'introduce.max' => '店铺介绍最大长度1000',
                'distribution_type.required' => '店铺类型必填',
                'merchant_id.*' => '所属商户必填',
                'contract_phone.min' => '请填写正确格式的固定座机',
            ];
            if ($params['offline_aftersales_self'] || $params['offline_aftersales_other']) {
                $rules['offline_aftersales_address.name'] = 'required|max:255';
                $rules['offline_aftersales_address.regions'] = 'required';
                $rules['offline_aftersales_address.regions_id'] = 'required';
                $rules['offline_aftersales_address.address'] = 'required|max:255';
                $rules['offline_aftersales_address.mobile'] = 'required';
                $rules['offline_aftersales_address.hours'] = 'required';
                $msg['offline_aftersales_address.name.required'] = '退货点名称必填';
                $msg['offline_aftersales_address.regions.required'] = '省市区必填';
                $msg['offline_aftersales_address.regions_id.required'] = '省市区必填';
                $msg['offline_aftersales_address.address.required'] = '退货点地址必填';
                $msg['offline_aftersales_address.mobile.required'] = '退货点联系电话必填';
                $msg['offline_aftersales_address.hours.required'] = '退货点营业时间必填';
            }
            $validator = app('validator')->make($params, $rules, $msg);
            if ($validator->fails()) {
                $errorsMsg = $validator->errors()->toArray();
                $errmsg = current($errorsMsg)[0];
                throw new ResourceException($errmsg);
            }
            if (isset($params['mobile']) && strstr($params['mobile'], '*')) {
                $datapassBlockCol[] = 'mobile';
                unset($params['mobile'], $rules['mobile']);
            }
            if (isset($params['contact']) && strstr($params['contact'], '*')) {
                $datapassBlockCol[] = 'contact';
                unset($params['contact'], $rules['contact']);
            }
            $validator = app('validator')->make($params, $rules, $msg);
            if ($validator->fails()) {
                $errorsMsg = $validator->errors()->toArray();
                $errmsg = current($errorsMsg)[0];
                throw new ResourceException($errmsg);
            }
            if (!preg_match('/^[A-Za-z0-9-]+$/', $params['shop_code'])) {
                throw new StoreResourceFailedException("请填写正确格式的店铺号");
            }
            // 不明确先暂时改成限制九位数
//            if (!empty($params['contract_phone'])) {
//                if (!preg_match('/^\d{3,4}-?\d{7,8}$/', $params['contract_phone'])) {
//                    throw new StoreResourceFailedException("请填写正确格式的固定座机");
//                }
//            }
        }
        if (app('auth')->user()->get('operator_type') == 'distributor') {
            unset($params['is_audit_goods']);
            unset($params['auto_sync_goods']);
        }

        $distributorIds = $request->get('distributorIds');
        if ($distributorIds && !in_array($distributor_id, $distributorIds)) {
            throw new Exception("您没有此操作的权限", 400500);
        }

        $rules = [
            'distributor_id' => ['required|integer|min:1', '请确定需要更新的经销商'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new StoreResourceFailedException($error);
        }

        if (isset($params['is_ziti'])) {
            $params['is_ziti'] = (!$params['is_ziti'] || $params['is_ziti'] === 'false') ? false : true;
        }
        if (isset($params['is_delivery'])) {
            $params['is_delivery'] = (!$params['is_delivery'] || $params['is_delivery'] === 'false') ? false : true;
        }
        if (isset($params['is_ziti'], $params['is_delivery']) && !$params['is_ziti'] && !$params['is_delivery']) {
            throw new Exception("自提和快递配送至少开启一项", 400500);
        }
        if (isset($params['auto_sync_goods'])) {
            $params['auto_sync_goods'] = (!$params['auto_sync_goods'] || $params['auto_sync_goods'] === 'false') ? false : true;
        }
        if (isset($params['is_audit_goods'])) {
            $params['is_audit_goods'] = (!$params['is_audit_goods'] || $params['is_audit_goods'] === 'false') ? false : true;
        }
        if (isset($params['is_require_subdistrict'])) {
            $params['is_require_subdistrict'] = (!$params['is_require_subdistrict'] || $params['is_require_subdistrict'] === 'false') ? false : true;
        }
        if (isset($params['is_require_building'])) {
            $params['is_require_building'] = (!$params['is_require_building'] || $params['is_require_building'] === 'false') ? false : true;
        }

        if (isset($params['regions_id']) && isset($params['regions'])) {
            foreach ($params['regions'] as $k => $value) {
                $params[$regions[$k]] = $value;
            }
        }

        if (isset($params['distributor_self']) && $params['distributor_self'] == '1') {
            $params['is_valid'] = 'true';
        }

        //店铺入驻审核结果
        if ($reviewResult) {
            if ($reviewResult === 'true') {
                $params['is_valid'] = 'true';
            } else {
                $params['is_valid'] = 'false';
            }
            $params['review_status'] = 1;
        }

        if (isset($params['regions']) && count($params['regions']) == 2) {
            $params['area'] = '';
        }

        $companyId = app('auth')->user()->get('company_id');
        $params['company_id'] = $companyId;

        //分账配置判断
        if (isset($params['is_open']) && $params['is_open'] == 'true') {
            $hfpayLedgerConfigService = new HfpayLedgerConfigService();
            $ledgerConfig = $hfpayLedgerConfigService->getLedgerConfig(['company_id' => $companyId]);
            if (empty($ledgerConfig) || $ledgerConfig['is_open'] == 'false') {
                throw new Exception("请在分账及结算-基础配置中开启分账");
            }
            $params['rate'] = !empty($params['rate']) ? bcmul($params['rate'], 100) : '0';
            if ($params['rate'] < 0) {
                throw new Exception("服务费率不能小于0");
            }
            if ($params['rate'] > 3000) {
                throw new Exception("服务费率不得超过30");
            }
        }
        $distributorService = new DistributorService();
        $distributorInfo = $distributorService->getData(['distributor_id' => $distributor_id, 'company_id' => $companyId]);
        if ($datapassBlockCol) {
            foreach ($datapassBlockCol as $col) {
                $params[$col] = $distributorInfo[$col];
            }
        }
        $companyRelDadaService = new CompanyRelDadaService();
        $dadaInfo = $companyRelDadaService->getInfo(['company_id' => $companyId]);
        if (!empty($params['is_dada'])) {
            if (empty($dadaInfo['is_open'])) {
                throw new Exception("该商户未开启达达同城配");
            }
        }
        if (!empty($params['shop_code'])) {
            ## 手机号是否存在
//            $disInfo = $distributorService->getData(['mobile' => $params['mobile'], 'company_id' => $companyId]);
//            if ($disInfo && $disInfo['distributor_id'] != $distributor_id && $disInfo['is_valid'] != 'delete') {
//                throw new Exception("修改的店铺手机号已存在");
//            }

            ## 店铺名称是否存在
            $disInfo = $distributorService->getData(['name' => $params['name'], 'company_id' => $companyId]);
            if ($disInfo && $disInfo['distributor_id'] != $distributor_id && $disInfo['is_valid'] != 'delete') {
                throw new Exception("修改的店铺名称已存在");
            }
            $params['shop_code'] = $distributorInfo['shop_code'];
            if (empty($params['is_dada'])) {
                $params['is_dada'] = 0;
                $params['business'] = $distributorInfo['business'];
            }
            if (!empty($params['is_dada']) && empty($distributorInfo['dada_shop_create'])) {
                if (!($params['business'] ?? null)) {
                    throw new Exception('请选择业务类型');
                }
                $shopService = new ShopService();
                $shopParams = [];
                $shopParams[] = $params;
                $dadaResult = $shopService->createShop($companyId, $shopParams);
                $params['dada_shop_create'] = 1;
            } else {
                if (!empty($dadaInfo['is_open']) && !empty($distributorInfo['dada_shop_create'])) {
                    if (!($params['business'] ?? null)) {
                        throw new Exception('请选择业务类型');
                    }
                    $shopService = new ShopService();
                    $shopService->updateShop($companyId, $params);
                }
            }
        }

        if (isset($params['offline_aftersales_distributor_id']) && $params['offline_aftersales_distributor_id']) {
            if (!isset($params['merchant_id'])) {
                $params['merchant_id'] = $distributorInfo['merchant_id'];
            }
            if ($distributorService->count(['merchant_id|neq' => $params['merchant_id'], 'distributor_id' => $params['offline_aftersales_distributor_id']])) {
                throw new ResourceException('售后门店只能选择同一商户下的其他门店');
            }
        }

        $result = $distributorService->updateDistributor($distributor_id, $params);

        if (isset($params['offline_aftersales_address']) && $params['offline_aftersales_address']) {
            $aftersalesAddress = $params['offline_aftersales_address'];
            if (isset($aftersalesAddress['name'], $aftersalesAddress['address'], $aftersalesAddress['mobile']) && $aftersalesAddress['name'] && $aftersalesAddress['address'] && $aftersalesAddress['mobile']) {
                if (isset($aftersalesAddress['area_code']) && $aftersalesAddress['area_code']) {
                    $aftersalesAddress['mobile'] = $aftersalesAddress['area_code'].'-'.$aftersalesAddress['mobile'];
                }
                $data = [
                    'distributor_id' => $result['distributor_id'],
                    'name' => $aftersalesAddress['name'],
                    'regions_id' => json_encode($aftersalesAddress['regions_id'] ?? []),
                    'regions' => json_encode($aftersalesAddress['regions'] ?? []),
                    'address' => $aftersalesAddress['address'],
                    'company_id' => $companyId,
                    'mobile' => $aftersalesAddress['mobile'],
                    'hours' => $aftersalesAddress['hours'] ?? '',
                    'merchant_id' => $merchantId,
                    'return_type' => 'offline',
                    'province' => '',
                    'city' => '',
                    'area' => '',
                ];
                foreach (($aftersalesAddress['regions'] ?? []) as $k => $value) {
                    $data[$regions[$k]] = $value;
                }

                $distributorAftersalesAddressService = new DistributorAftersalesAddressService();
                $filter = [
                    'company_id' => $companyId,
                    'distributor_id' => $result['distributor_id'],
                    'return_type' => 'offline',
                ];
                $exist = $distributorAftersalesAddressService->getInfo($filter);
                if (!$exist) {
                    $distributorAftersalesAddressService->setDistributorAfterSalesAddress($data);
                } else {
                    $distributorAftersalesAddressService->updateDistributorAfterSalesAddress($filter, $data);
                }
            }
        }

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/distributors",
     *     summary="获取经销商列表",
     *     tags={"店铺"},
     *     description="获取经销商列表",
     *     operationId="getDistributorList",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="pageSize", in="query", description="每页的数量", required=true, type="string"),
     *     @SWG\Parameter( name="page", in="query", description="分页页数", required=true, type="string"),
     *     @SWG\Parameter( name="name", in="query", description="经销商姓名", required=false, type="string"),
     *     @SWG\Parameter( name="shop_code", in="query", description="店铺编号", required=false, type="string"),
     *     @SWG\Parameter( name="mobile", in="query", description="经销商手机号", required=false, type="string"),
     *     @SWG\Parameter( name="is_app", in="query", description="是否店务端app", required=false, type="string"),
     *     @SWG\Parameter( name="distribution_type", in="query", description="店铺类型:0自营;1加盟", required=false, type="string"),
     *     @SWG\Parameter( name="merchant_id", in="query", description="所属商家", required=false, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="total_count", type="string", example="36", description=""),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="distributor_id", type="string", example="85", description="分销商id"),
     *                          @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                          @SWG\Property( property="mobile", type="string", example="18964058319", description="手机号"),
     *                          @SWG\Property( property="address", type="string", example="宜山路700号", description="地址"),
     *                          @SWG\Property( property="name", type="string", example="普天科创产业园", description="名称"),
     *                          @SWG\Property( property="created", type="string", example="1596433779", description=""),
     *                          @SWG\Property( property="updated", type="string", example="1596433779", description="修改时间"),
     *                          @SWG\Property( property="is_valid", type="string", example="true", description="店铺是否有效"),
     *                          @SWG\Property( property="province", type="string", example="上海市", description="省 "),
     *                          @SWG\Property( property="city", type="string", example="上海市", description="市"),
     *                          @SWG\Property( property="area", type="string", example="徐汇", description="区"),
     *                          @SWG\Property( property="regions_id", type="array",
     *                              @SWG\Items( type="string", example="310000", description=""),
     *                          ),
     *                          @SWG\Property( property="regions", type="array",
     *                              @SWG\Items( type="string", example="上海市", description=""),
     *                          ),
     *                          @SWG\Property( property="contact", type="string", example="lijian", description="联系人"),
     *                          @SWG\Property( property="child_count", type="string", example="0", description=""),
     *                          @SWG\Property( property="shop_id", type="string", example="0", description="门店id"),
     *                          @SWG\Property( property="is_default", type="string", example="false", description=""),
     *                          @SWG\Property( property="is_ziti", type="string", example="false", description="是否支持自提"),
     *                          @SWG\Property( property="lng", type="string", example="121.417559", description="地图纬度"),
     *                          @SWG\Property( property="lat", type="string", example="31.176522", description="地图经度"),
     *                          @SWG\Property( property="hour", type="string", example="08:00-21:00", description="营业时间，格式11:11-12:12"),
     *                          @SWG\Property( property="auto_sync_goods", type="string", example="false", description="自动同步总部商品"),
     *                          @SWG\Property( property="logo", type="string", example="null", description="店铺logo"),
     *                          @SWG\Property( property="banner", type="string", example="null", description="店铺banner"),
     *                          @SWG\Property( property="is_audit_goods", type="string", example="false", description="是否审核店铺商品"),
     *                          @SWG\Property( property="is_delivery", type="string", example="true", description="是否支持配送"),
     *                          @SWG\Property( property="shop_code", type="string", example="gys001", description="店铺号"),
     *                          @SWG\Property( property="review_status", type="string", example="0", description="入驻审核状态，0未审核，1已审核"),
     *                          @SWG\Property( property="source_from", type="string", example="1", description="店铺来源，1管理端添加，2小程序申请入驻"),
     *                          @SWG\Property( property="distributor_self", type="string", example="0", description="是否是总店配置"),
     *                          @SWG\Property( property="is_distributor", type="string", example="true", description="是否是主店铺"),
     *                          @SWG\Property( property="contract_phone", type="string", example="18964058319", description="联系电话"),
     *                          @SWG\Property( property="is_domestic", type="string", example="1", description="是否是中国国内门店 1:国内(包含港澳台),2:非国内"),
     *                          @SWG\Property( property="is_direct_store", type="string", example="1", description="是否为直营店 1:直营店,2:非直营店"),
     *                          @SWG\Property( property="wechat_work_department_id", type="string", example="0", description="企业微信的部门ID"),
     *                          @SWG\Property( property="regionauth_id", type="string", example="0", description="地区id"),
     *                          @SWG\Property( property="is_open", type="string", example="false", description=""),
     *                          @SWG\Property( property="rate", type="string", example="", description="货币汇率(与人民币)"),
     *                          @SWG\Property( property="store_address", type="string", example="上海市徐汇宜山路700号", description=""),
     *                          @SWG\Property( property="store_name", type="string", example="普天科创产业园", description="店铺名称"),
     *                          @SWG\Property( property="phone", type="string", example="18964058319", description=""),
     *                          @SWG\Property( property="distance_show", type="string", example="", description=""),
     *                          @SWG\Property( property="distance_unit", type="string", example="", description=""),
     *                          @SWG\Property( property="is_openAccount", type="boolean", example="true", description="店铺是否已经开户"),
     *                          @SWG\Property( property="tagList", type="array",
     *                              @SWG\Items( type="string", example="undefined", description=""),
     *                          ),
     *                          @SWG\Property( property="link", type="string", example="pages/index?dtid=85", description=""),
     *                       ),
     *                  ),
     *                  @SWG\Property( property="tagList", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="distributor_id", type="string", example="62", description="分销商id"),
     *                          @SWG\Property( property="tag_id", type="string", example="1", description="标签id"),
     *                          @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                          @SWG\Property( property="tag_name", type="string", example="测试标签", description="标签名称"),
     *                          @SWG\Property( property="tag_color", type="string", example="#ff1939", description="标签颜色"),
     *                          @SWG\Property( property="font_color", type="string", example="rgba(255, 255, 255, 1)", description="字体颜色"),
     *                          @SWG\Property( property="description", type="string", example="测试标签说明", description="描述"),
     *                          @SWG\Property( property="tag_icon", type="string", example="null", description="标签icon"),
     *                          @SWG\Property( property="front_show", type="string", example="1", description="前台是否显示 0 否 1 是"),
     *                          @SWG\Property( property="created", type="string", example="1571129662", description=""),
     *                          @SWG\Property( property="updated", type="string", example="1571130101", description="修改时间"),
     *                          @SWG\Property( property="merchant_name", type="string", example="1", description="所属商家"),
     *                          @SWG\Property( property="distribution_type", type="string", example="1", description="店铺类型")
     *                       ),
     *                  ),
     *                  @SWG\Property( property="distributor_self", type="string", example="51", description="是否是总店配置"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/DistributionErrorRespones") ) )
     *  )
     * )
     */
    public function getDistributorList(Request $request)
    {
        $page = $request->get('page', 1);
        $pageSize = $request->get('pageSize', 1000);

        $companyId = app('auth')->user()->get('company_id');
        $merchantId = app('auth')->user()->get('merchant_id');
        $operatorType = app('auth')->user()->get('operator_type');
        if ($operatorType == 'merchant') {
            $filter['merchant_id'] = $merchantId; // 商户端只能获取商户的店铺
        }
        $staffRegionAuthDistributorIds = app('auth')->user()->get('distributorIds');

        $unbound = $request->get('unbound', false);
        $unbound && $filter['wechat_work_department_id'] = 0;

        $filter['company_id'] = $companyId;
        $filter['distributor_self'] = 0;

        if ($request->get('distributor_id') && !$request->get('is_all', false)) {
            $filter['distributor_id'] = $request->get('distributor_id');
        } elseif ($request->get('distributorIds')) {
            $filter['distributor_id'] = $request->get('distributorIds');
        }

        if ($request->input('is_valid')) {
            $filter['is_valid'] = $request->input('is_valid');
        }

        if ($request->input('name')) {
            $filter['name|contains'] = $request->input('name');
        }
        if ($request->input('shop_code')) {
            $filter['shop_code|contains'] = $request->input('shop_code');
        }

        if ($request->input('province')) {
            $filter['province|contains'] = $request->input('province');
        }
        if ($request->input('city')) {
            $filter['city|contains'] = $request->input('city');
        }
        if ($request->input('area')) {
            $filter['area|contains'] = str_replace("区", "", $request->input('area'));
        }

        if ($request->input('mobile')) {
            $filter['mobile'] = $request->input('mobile');
        }
        if ($request->input('merchant_name')) {
            $filter['merchant.merchant_name|like'] = $request->input('merchant_name');
        }
        $distributionType = $request->input('distribution_type');
        if (!is_null($distributionType) && $distributionType != '') {
            $filter['distribution_type'] = $request->input('distribution_type');
        }
        if ($operatorType == 'merchant' || $operatorType == 'distributor') {
            unset($filter['distribution_type']);
        }
        $distributorTagsService = new DistributorTagsService();
        if ($request->input('tag_id')) {
            $tagFilter = ['company_id' => $filter['company_id'], 'tag_id' => $request->input('tag_id')];
            if (isset($filter['distributor_id']) && $filter['distributor_id']) {
                $tagFilter['distributor_id'] = $filter['distributor_id'];
            }
            $distributorIds = $distributorTagsService->getDistributorIdsByTagids($tagFilter);
            if (!$distributorIds) {
                $result['list'] = [];
                $result['total_count'] = 0;
                return $this->response->array($result);
            }
            $filter['distributor_id'] = $distributorIds;
        }
        // 如果是员工，且是区域管理员，覆盖店铺id
        if ($operatorType == 'staff' && $staffRegionAuthDistributorIds) {
            if (isset($filter['distributor_id']) && $filter['distributor_id'] && is_array($filter['distributor_id'])) {
                $filter['distributor_id'] = array_intersect($filter['distributor_id'], $staffRegionAuthDistributorIds);
            } elseif (!isset($filter['distributor_id']) || !$filter['distributor_id']) {
                $filter['distributor_id'] = $staffRegionAuthDistributorIds;
            }
        }
        $distributorService = new DistributorService();
        $data = $distributorService->lists($filter, ["created" => "DESC"], $pageSize, $page);
        $data['tagList'] = [];
        // 是否有权限查看加密数据
        $datapassBlock = $request->get('x-datapass-block', 0);
        $data['datapass_block'] = $datapassBlock;
        if ($data['list']) {
            //获取商品标签
            $distributorIds = array_column($data['list'], 'distributor_id');
            $tagFilter = [
                'distributor_id' => $distributorIds,
                'company_id' => $filter['company_id'],
            ];
            $tagList = $distributorTagsService->getDistributorRelTagList($tagFilter);
            $tagNewList = [];
            foreach ($tagList as $tag) {
                $newTags[$tag['distributor_id']][] = $tag;
                $tagNewList[$tag['tag_id']] = $tag;
            }

            //获取店铺开户状态
            $distributor_ids = array_column($data['list'], 'distributor_id');
            $adapayMemberService = new AdapayMemberService();
            $adaPayFilter = [
                'company_id' => $filter['company_id'],
                'operator_type' => 'distributor',
                'operator_id' => $distributor_ids,
                'audit_state' => 'E',
            ];
            $adapayMembers = [];
            $rs = $adapayMemberService->lists($adaPayFilter, '*', 1, -1, ['id' => 'ASC']);
            foreach ($rs['list'] as $v) {
                $adapayMembers[$v['operator_type']][$v['operator_id']] = $v;
            }
            foreach ($data['list'] as &$value) {
                $value['tagList'] = $newTags[$value['distributor_id']] ?? [];
                $value['link'] = 'pages/index?dtid=' . $value['distributor_id'];
                $value['is_openAccount'] = isset($adapayMembers['distributor'][$value['distributor_id']]) ?? false;
                if ($datapassBlock) {
                    $value['mobile'] = data_masking('mobile', (string) $value['mobile']);
                    $value['contact'] = data_masking('truename', (string) $value['contact']);
                }
            }
            $data['tagList'] = array_values($tagNewList);

            // 添加卡券信息
            $distributorService->appendCouponList((int)$companyId, $data['list']);
        }

        //是否有自营店铺信息设置
        $data['distributor_self'] = $distributorService->getDistributorSelf($companyId);

        $company = (new CompanysActivationEgo())->check($companyId);

        // 总店信息
        if ($company['product_model'] != 'platform' && $request->get('is_app', 0)) {
            foreach ($data['list'] as &$v) {
                $v['is_center'] = false;
            }

            if (isset($filter["distributor_id"])) {
                if (!is_array($filter["distributor_id"])) {
                    $filter["distributor_id"] = [$filter["distributor_id"]];
                }
                if (!in_array('0', $filter['distributor_id'])) {
                    return $this->response->array($data);
                }
            }

            if ($page == 1) {
                // 追加总店的简单信息
                $selfInfo = $distributorService->getDistributorSelfSimpleInfo($companyId);
                $selfInfo['is_center'] = true;
                array_unshift($data['list'], $selfInfo);
                $data['total_count'] += 1;
            }
        }

        return $this->response->array($data);
    }

    /**
     * @SWG\Get(
     *     path="/distributor/count/{distributorId}",
     *     summary="获取经销商统计",
     *     tags={"店铺"},
     *     description="获取经销商统计",
     *     operationId="getDistributorCount",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property(property="cashWithdrawalRebate", type="stirng", example="可提现佣金 单位为分"),
     *                 @SWG\Property(property="freezeCashWithdrawalRebate", type="stirng", example="申请提现佣金，冻结提现佣金"),
     *                 @SWG\Property(property="itemTotalPrice", type="stirng", example="经销商品总金额"),
     *                 @SWG\Property(property="noCloseRebate", type="stirng", example="未结算佣金"),
     *                 @SWG\Property(property="rebateTotal", type="stirng", example="经销佣金总金额"),
     *             )
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/DistributionErrorRespones") ) )
     * )
     */
    public function getDistributorCount($distributorId, Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        if (!$distributorId) {
            throw new ResourceException('请指定需要获取的经销商');
        }

        $distributorService = new DistributorService();
        $filter['company_id'] = $companyId;

        $data = array();
        $filter['distributor_id'] = $distributorId;

        $result = $distributorService->getInfo($filter);
        if ($result) {
            $distributeCountService = new DistributeCountService();
            $data = $distributeCountService->getDistributorCount($companyId, $distributorId);
        }
        return $this->response->array($data);
    }

    /**
     * @SWG\Post(
     *     path="/distributor/items",
     *     summary="添加经销商关联商品",
     *     tags={"店铺"},
     *     description="添加经销商关联商品",
     *     operationId="saveDistributorItems",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="distributor_ids", in="query", description="店铺id集合", required=true, type="string"),
     *     @SWG\Parameter( name="item_ids", in="query", description="商品id集合", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="status", type="stirng"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/DistributionErrorRespones") ) )
     * )
     */
    public function saveDistributorItems(Request $request)
    {
        $params = $request->all('distributor_ids', 'item_ids', 'is_can_sale');

        $rules = [
            'distributor_ids' => ['required', '请选择需要同步的店铺'],
            'item_ids' => ['required', '请选择需要同步的商品']
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new StoreResourceFailedException($error);
        }

        $companyId = app('auth')->user()->get('company_id');

        $distributorService = new DistributorService();
        $distributorItemsService = new DistributorItemsService();
        if (!is_array($params['distributor_ids']) && $params['distributor_ids'] == '_all') {
            $list = $distributorService->getLists(['company_id' => $companyId, 'is_valid|neq' => 'delete'], 'distributor_id');
            $params['distributor_ids'] = array_column($list, 'distributor_id');
        }
        foreach ($params['distributor_ids'] as $distributorId) {
            $filter = [
                'company_id' => $companyId,
                'distributor_id' => $distributorId,
            ];
            $result = $distributorService->getInfo($filter);
            if (!$result || $result['is_valid'] == 'delete') {
                continue;
            }

            $createData = [
                'company_id' => $companyId,
                'distributor_id' => $distributorId,
                'item_ids' => $params['item_ids'],
                'is_can_sale' => $params['is_can_sale'] ?? false,
            ];
            $distributorItemsService->createDistributorItems($createData);
        }
        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Delete(
     *     path="/distributor/items",
     *     summary="删除经销商商品",
     *     tags={"店铺"},
     *     description="删除经销商商品",
     *     operationId="delDistributorItems",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="distributor_id", in="query", description="店铺ID", required=true, type="string"),
     *     @SWG\Parameter( name="item_ids", in="query", description="商品id集合", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="status", type="stirng"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/DistributionErrorRespones") ) )
     * )
     */
    public function delDistributorItems(Request $request)
    {
        $params = $request->all('distributor_id', 'item_ids');

        $rules = [
            'distributor_id' => ['required', '请选择需要删除的店铺'],
            'item_ids' => ['required', '请选择需要删除的商品']
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new StoreResourceFailedException($error);
        }

        $companyId = app('auth')->user()->get('company_id');

        $filter = [
            'company_id' => $companyId,
            'distributor_id' => $params['distributor_id'],
            'default_item_id' => $params['item_ids'],
        ];
        $distributorItemsService = new DistributorItemsService();
        $distributorItemsService->deleteBy($filter);
        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Get(
     *     path="/distributor/items",
     *     summary="获取经销商关联商品列表",
     *     tags={"店铺"},
     *     description="获取经销商关联商品列表",
     *     operationId="getDistributorItems",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="pageSize", in="query", description="每页的数量", required=true, type="string"),
     *     @SWG\Parameter( name="page", in="query", description="分页页数", required=true, type="string"),
     *     @SWG\Parameter( name="distributor_id", in="query", description="经销商ID", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="total_count", type="string", example="169", description=""),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="item_id", type="string", example="5031", description="商品id"),
     *                          @SWG\Property( property="item_type", type="string", example="normal", description="商品类型，services：服务商品，normal: 普通商品"),
     *                          @SWG\Property( property="is_can_sale", type="string", example="true", description="是否在本店可售"),
     *                          @SWG\Property( property="item_name", type="string", example="dermGO SENSITIVE敏感肌改善抗衰精华30ml", description="商品名称"),
     *                          @SWG\Property( property="store", type="string", example="100", description="库存"),
     *                          @SWG\Property( property="price", type="string", example="52803", description="商品价格"),
     *                          @SWG\Property( property="is_total_store", type="string", example="true", description="是否为总部库存"),
     *                          @SWG\Property( property="approve_status", type="string", example="onsale", description="商品状态 onsale 前台可销售，offline_sale前端不展示，instock 不可销售"),
     *                       ),
     *                  ),
     *                  @SWG\Property( property="warning_store", type="string", example="5", description=""),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/DistributionErrorRespones") ) )
     * )
     */
    public function getDistributorItems(Request $request)
    {
        $distributorId = $request->get('distributor_id') ?: 0;
        $data = ['list' => [], 'total_count' => 0];
        if ($distributorId) {
            $params = $request->all('pageSize', 'page', 'is_sku', 'item_id', 'is_can_sale', 'is_warning', 'store_lt', 'store_gt', 'category', 'is_gift', 'brand_id');
            $companyId = app('auth')->user()->get('company_id');

            $filter['company_id'] = $companyId;
            if (!isset($params['is_sku']) || $params['is_sku'] === 'false') {
                $filter['is_default'] = true;
            }

            $filter['distributor_id'] = $distributorId;

            if ($request->input('keywords')) {
                $filter['item_name|contains'] = $request->input('keywords');
            }

            if ($request->input('item_bn')) {
                $itemsService = new ItemsService();
                $itemList = $itemsService->getItemsLists(['company_id' => $companyId, 'item_bn' => $request->input('item_bn')], 'item_id,default_item_id');
                if (!$itemList) {
                    $result['list'] = [];
                    $result['total_count'] = 0;
                    return $this->response->array($result);
                }
                $filter['default_item_id'] = array_column($itemList, 'default_item_id');
            }

            if ($request->input('barcode')) {
                $filter['barcode'] = $request->input('barcode');
            }
            $itemStoreService = new ItemStoreService();
            $warningStore = $itemStoreService->getWarningStore($companyId, $distributorId);
            if (isset($params['is_warning']) && $params['is_warning'] == 'true') {
                $filter['store|lte'] = $warningStore;
            }

            if (isset($params['item_id']) && $params['item_id']) {
                $filter['default_item_id'] = $params['item_id'];
            }

            if ($params['is_can_sale'] === 'false') {
                $filter['is_can_sale'] = false;
            } elseif ($params['is_can_sale'] === 'true') {
                $filter['is_can_sale'] = true;
            }

            if ($params['store_gt'] ?? 0) {
                $filter["store|gt"] = intval($params['store_gt']);
            }

            if ($params['store_lt'] ?? 0) {
                $filter["store|lt"] = intval($params['store_lt']);
            }

            if ($params['brand_id'] ?? 0) {
                $filter["brand_id"] = $params['brand_id'];
            }

            if (isset($params['is_gift'])) {
                $filter['is_gift'] = ($params['is_gift'] == 'true') ? 1 : 0;
            }

            $itemsCategoryService = new ItemsCategoryService();
            if (isset($params['category']) && $params['category']) {
                $itemIds = $itemsCategoryService->getItemIdsByCatId($params['category'], $companyId);
                if (!$itemIds) {
                    $result['list'] = [];
                    $result['total_count'] = 0;
                    return $this->response->array($result);
                }

                if (isset($params['item_id'])) {
                    $filter['item_id'] = array_intersect((array)$params['item_id'], $itemIds);
                } else {
                    $filter['item_id'] = $itemIds;
                }
            }

            $filter['item_type'] = 'normal';

            $distributorItemsService = new DistributorItemsService();
            $pageSize = $params['pageSize'] ?: -1;
            $page = $params['page'] ?: 1;
            $data = $distributorItemsService->getDistributorRelItemList($filter, $pageSize, $params['page'], ['item_id' => 'desc'], false);
            $data['warning_store'] = $warningStore;

            // 判断是查询单个商品sku
            if (empty($params['item_id']) and !empty($data['list'])) {
                $suk_where['company_id'] = $companyId;
                $suk_where['distributor_id'] = $distributorId;
                // 查询库存
                foreach ($data['list'] as &$v) {
                    $suk_where['default_item_id'] = $v['item_id'];
                    $suk_data = $distributorItemsService->getList($suk_where, 'item_id,store', 1, -1);
                    $v['distributor_store'] = (int)($v['distributor_store'] ?? -1); // 获取店铺库存，如果为-1则表示不存在店铺不存在该商品
                    $v['item_store'] = (int)$v['store']; // 获取商品本身的库存
                    $v['sku_store_array'] = array_column($suk_data, "store", "item_id"); // 获取sku列表
                    $v['store'] = (int)array_sum(array_values($v['sku_store_array'])); // 获取该商品下的总库存库存

                    $categoryInfo = $itemsCategoryService->getInfoById($v['item_main_cat_id']);
                    $v['itemMainCatName'] = $categoryInfo['category_name'] ?? '';

                    $cat_arr = [];
                    foreach (($v['item_cat_id'] ?? []) as $cid) {
                        $cat_info = $itemsCategoryService->getInfoById($cid);
                        if ($cat_info) {
                            $cat_arr[] = '['.$cat_info['category_name'].']';
                        }
                    }
                    $v['itemCatName'] = $cat_arr;
                }
            }
        }
        return $this->response->array($data);
    }

    /**
     * @SWG\Get(
     *     path="/distributor/items/export",
     *     summary="导出经销商关联商品列表",
     *     tags={"店铺"},
     *     description="导出经销商关联商品列表",
     *     operationId="exportDistributorItems",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="distributor_id", in="query", description="经销商ID", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="list", type="stirng"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/DistributionErrorRespones") ) )
     * )
     */
    public function exportDistributorItems(Request $request)
    {
        $distributorId = $request->get('distributor_id') ?: 0;
        if (empty($distributorId)) {
            throw new ResourceException('请选择店铺');
        }
        $params = $request->all('pageSize', 'page', 'is_sku', 'item_id', 'is_can_sale', 'is_warning', 'goods_ids');
        $companyId = app('auth')->user()->get('company_id');

        if (!empty($params['goods_ids'])) {
            $filter['default_item_id'] = $params['goods_ids'];
        }

        $filter['company_id'] = $companyId;
        $filter['distributor_id'] = $distributorId;

        if ($request->input('keywords')) {
            $filter['item_name|contains'] = $request->input('keywords');
        }

        if ($params['is_can_sale'] === 'false') {
            $filter['is_can_sale'] = false;
        } elseif ($params['is_can_sale'] === 'true') {
            $filter['is_can_sale'] = true;
        }

        $filter['item_type'] = 'normal';

        // 存储导出操作账号者
        $operator_id = app('auth')->user()->get('operator_id');
        $gotoJob = (new ExportFileJob('distributor_items', $companyId, $filter, $operator_id))->onQueue('slow');
        app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($gotoJob);
        $result['status'] = true;

        return response()->json($result);
    }

    /**
     * @SWG\Get(
     *     path="/onecode/wxacode",
     *     summary="获取经销商小程序码",
     *     tags={"店铺"},
     *     description="获取经销商小程序码",
     *     operationId="getWxaDistributorCodeStream",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", type="string" ),
     *     @SWG\Parameter( name="distributor_id", in="query", description="经销商id", type="integer" ),
     *     @SWG\Parameter( name="codetype", in="query", description="小程序码类型id", type="integer" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="base64Image", type="string", example="data:image/jpg;base64,/9j/4AAQSkZJRgABAgD/9k=", description="图片信息"),
     *                  @SWG\Property( property="tempname", type="string", example="yykweishop", description=""),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/DistributionErrorRespones") ) )
     * )
     */
    public function getWxaDistributorCodeStream(request $request)
    {
        $params = $request->input();
        $validator = app('validator')->make($params, [
            'distributor_id' => 'required|min:1',
            // 'wxaappid' => 'required|min:1',
        ]);
        if ($validator->fails()) {
            throw new ResourceException('获取小程序码参数出错，请检查.', $validator->errors());
        }

        $codetype = $request->get('codetype', 'index');

        $templateName = '';
        if (isset($params['template_name']) && $params['template_name']) {
            $templateName = $params['template_name'];
        }

        $weappService = new WeappService();
        $companyId = app('auth')->user()->get('company_id');
        $wxaappid = $weappService->getWxappidByTemplateName($companyId, $templateName);
        if (!$wxaappid) {
            throw new ResourceException('没有开通此小程序，不能下载.', $validator->errors());
        }
        $distributorService = new DistributorService();
        $result = $distributorService->getWxaDistributorCodeStream($wxaappid, $params['distributor_id'], 1, $codetype);
        $result['tempname'] = $templateName;
        // 获取店铺码的url
        $urlInfo = $distributorService->getWxaDistributorCodeUrl((int)$companyId, $wxaappid, $params["distributor_id"], $codetype);
        $result["url"] = $urlInfo["url"] ?? "";
        return $this->response->array($result);
    }

    /**
     * @SWG\Put(
     *     path="/distributors/item",
     *     summary="配置经销商商品库存",
     *     tags={"店铺"},
     *     description="配置经销商商品库存",
     *     operationId="updateDistributorItem",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="distributor_id", in="query", description="经销商id集合", required=true, type="string"),
     *     @SWG\Parameter( name="item_id", in="query", description="商品id集合", required=true, type="string"),
     *     @SWG\Parameter( name="store", in="query", description="库存", required=true, type="string"),
     *     @SWG\Parameter( name="is_total_store", in="query", description="是否是总库存", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="string", example="true", description=""),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/DistributionErrorRespones") ) )
     * )
     */

    public function updateDistributorItem(request $request)
    {
        $params = $request->all('distributor_id', 'item_id', 'goods_id', 'is_can_sale', 'store', 'price', 'is_total_store', 'is_default');

        $params['company_id'] = app('auth')->user()->get('company_id');

        $distributorItemsService = new DistributorItemsService();
        $distributorItemsService->updateDistributorItem($params);
        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Post(
     *     path="/distributor/default",
     *     summary="设置默认门店",
     *     tags={"店铺"},
     *     description="设置默认门店",
     *     operationId="defaultSetDistributor",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", type="string", ),
     *     @SWG\Parameter( name="distributor_id", in="query", description="店铺id", required=true, type="integer", ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="string", example="true", description="自行更改字段描述"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/DistributionErrorRespones") ) )
     * )
     */
    public function defaultSetDistributor(request $request)
    {
        $distributorId = $request->input('distributor_id');
        if (!$distributorId) {
            return $this->response->error('店铺必选！', 411);
        }
        $companyId = app('auth')->user()->get('company_id');
        $distributorService = new DistributorService();
        $result = $distributorService->setDefaultDistributor($companyId, $distributorId);

        return $this->response->array(['status' => $result]);
    }


    /**
     * @SWG\Post(
     *     path="/distributor/salesman",
     *     summary="新增店铺导购员",
     *     tags={"店铺"},
     *     description="新增店铺导购员",
     *     operationId="addSalesman",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="mobile", in="query", description="手机号", required=true, type="string"),
     *     @SWG\Parameter( name="name", in="query", description="姓名", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="mobile", type="integer", example="13918087333"),
     *                     @SWG\Property(property="salesman_name", type="integer", example="张三"),
     *                     @SWG\Property(property="user_id", type="integer", example="23"),
     *                     @SWG\Property(property="is_valid", type="string", example="true"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构")
     * )
     */
    public function addSalesman(request $request)
    {
        $params = $request->all('mobile', 'salesman_name', 'distributor_id');

        $rules = [
            'mobile' => ['required', '请填写手机号'],
            'salesman_name' => ['required', '请填写导购员姓名'],
            'distributor_id' => ['required', '请选择导购员所属店铺'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new StoreResourceFailedException($error);
        }

        $distributorSalesmanService = new DistributorSalesmanService();
        $companyId = app('auth')->user()->get('company_id');
        $data = [
            'mobile' => trim($request->input('mobile')),
            'salesman_name' => trim($request->input('salesman_name')),
            'distributor_id' => $request->input('distributor_id'),
            'company_id' => $companyId,
        ];
        $result = $distributorSalesmanService->createSalesman($data);
        return $this->response->array($result);
    }

    /**
     * @SWG\Put(
     *     path="/distributor/salesman/{salesmanId}",
     *     summary="更新店铺导购员",
     *     tags={"店铺"},
     *     description="更新店铺导购员",
     *     operationId="updateSalesman",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="mobile", in="query", description="手机号", required=false, type="string"),
     *     @SWG\Parameter( name="salesman_name", in="query", description="姓名", required=false, type="string"),
     *     @SWG\Parameter( name="distributor_id", in="query", description="店铺ID", required=false, type="string"),
     *     @SWG\Parameter( name="is_valid", in="query", description="是否有效", required=false, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="status", type="string", example="true"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构")
     * )
     */
    public function updateSalesman($salesmanId, request $request)
    {
        $distributorSalesmanService = new DistributorSalesmanService();

        $data = [];
        if (trim($request->input('mobile'))) {
            $data['mobile'] = trim($request->input('mobile'));
        }
        if (trim($request->input('salesman_name'))) {
            $data['salesman_name'] = trim($request->input('salesman_name'));
        }
        if (trim($request->input('distributor_id'))) {
            $data['distributor_id'] = trim($request->input('distributor_id'));
        }
        if (trim($request->input('is_valid'))) {
            $data['is_valid'] = trim($request->input('is_valid'));
        }

        if ($data) {
            $companyId = app('auth')->user()->get('company_id');
            $result = $distributorSalesmanService->updateSalesman(['salesman_id' => $salesmanId, 'company_id' => $companyId], $data);
        }
        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Get(
     *     path="/distributor/salesmans",
     *     summary="获取店铺导购员列表",
     *     tags={"店铺"},
     *     description="获取店铺导购员列表",
     *     operationId="getSalesmanList",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="pageSize", in="query", description="每页的数量", required=true, type="string"),
     *     @SWG\Parameter( name="page", in="query", description="分页页数", required=true, type="string"),
     *     @SWG\Parameter( name="mobile", in="query", description="手机号", required=false, type="string"),
     *     @SWG\Parameter( name="salesman_name", in="query", description="姓名", required=false, type="string"),
     *     @SWG\Parameter( name="distributor_id", in="query", description="店铺ID", required=false, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="mobile", type="string", example="13918099999"),
     *                     @SWG\Property(property="salesman_name", type="string", example="吴七"),
     *                     @SWG\Property(property="distributor_name", type="string", example="烤鸭桂林路店"),
     *                     @SWG\Property(property="distributor_id", type="string", example="777"),
     *                     @SWG\Property(property="salesman_id", type="string", example="124"),
     *                     @SWG\Property(property="company_id", type="string", example="655"),
     *                     @SWG\Property(property="is_valid", type="string", example="true"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构")
     * )
     */
    public function getSalesmanList(request $request)
    {
        $distributorSalesmanService = new DistributorSalesmanService();
        $companyId = app('auth')->user()->get('company_id');

        $filter = [];
        $filter['company_id'] = $companyId;
        if (trim($request->input('distributor_id'))) {
            $filter['distributor_id'] = trim($request->input('distributor_id'));
        }
        if (trim($request->input('salesman_name'))) {
            $filter['salesman_name'] = trim($request->input('salesman_name'));
        }
        if (trim($request->input('mobile'))) {
            $filter['mobile'] = trim($request->input('mobile'));
        }

        $filter['is_valid|neq'] = 'delete';
        $list = $distributorSalesmanService->getSalesmanList($filter, trim($request->input('page')), trim($request->input('pageSize')));

        return $this->response->array($list);
    }

    /**
     * @SWG\Definition(
     *     definition="openAccount",
     *     type="object",
     *                   @SWG\Property(property="id", type="string", description="企业ID"),
     *                   @SWG\Property(property="member_id", type="string", description="用户ID"),
     *                   @SWG\Property(property="user_name", type="string", description="企业名称/用户名"),
     *                   @SWG\Property(property="prov_code", type="string", description="省份编码"),
     *                   @SWG\Property(property="area_code", type="string", description="地区编码"),
     *                   @SWG\Property(property="area", type="string", description="省市地区"),
     *                   @SWG\Property(property="cert_id", type="string", description="法人身份证/用户身份证"),
     *                   @SWG\Property(property="social_credit_code_expires", type="string", description="统一社会信用证有效期(1121)"),
     *                   @SWG\Property(property="business_scope", type="string", description="经营范围"),
     *                   @SWG\Property(property="legal_person", type="string", description="法人姓名"),
     *                   @SWG\Property(property="legal_cert_id", type="string", description="法人身份证号码"),
     *                   @SWG\Property(property="legal_cert_id_expires", type="string", description="法人身份证有效期(20220112)"),
     *                   @SWG\Property(property="tel_no", type="string", description="法人手机号/个人手机号"),
     *                   @SWG\Property(property="address", type="string", description="企业地址"),
     *                   @SWG\Property(property="bank_code", type="string", description="银行代码"),
     *                   @SWG\Property(property="bank_name", type="string", description="银行名称"),
     *                   @SWG\Property(property="bank_acct_type", type="string", description="银行账户类型：1-对公；2-对私"),
     *                   @SWG\Property(property="card_no", type="string", description="银行卡号"),
     *                   @SWG\Property(property="zip_code", type="string", description="邮编"),
     *                   @SWG\Property(property="member_type", type="string", description="账户类型"),
     *                   @SWG\Property(property="div_fee_mode", type="string", description="分账扣费方式"),
     *                   @SWG\Property(property="split_ledger_info", type="object", description="分账比例",
     *                       @SWG\Property(property="adapay_fee_mode", type="string", description="手续费扣费方式"),
     *                       @SWG\Property(property="headquarters_proportion", type="string", description="分账总部占比"),
     *                       @SWG\Property(property="distributor_proportion", type="string", description="分账店铺占比"),
     *                       @SWG\Property(property="dealer_proportion", type="string", description="分账经销商占比"),
     *                   ),
     *                   @SWG\Property(property="card_name", type="string", description="银行卡对应的户名，若银行账户类型是对公，必须与企业名称一致"),
     *                   @SWG\Property(property="bank_card_name", type="string", description="银行卡对应的户名，若银行账户类型是对公，必须与企业名称一致"),
     *                   @SWG\Property( property="bank_tel_no", description="银行预留手机号", type="string"),
     *                   @SWG\Property( property="bank_cert_id", description="开户证件号", type="string"),
     *                   @SWG\Property( property="bank_card_id", description="银行卡号", type="string"),
     *                   @SWG\Property( property="basinInfo", description="基本信息", type="array",
     *                       @SWG\Items(
     *                         @SWG\Property( property="name", description="店铺名/企业名称", type="string"),
     *                         @SWG\Property( property="contact", description="联系人", type="string"),
     *                         @SWG\Property( property="area", description="地区", type="string"),
     *                         @SWG\Property( property="email", description="企业邮箱", type="string"),
     *                         @SWG\Property( property="tel_no", description="企业电话", type="string"),
     *                         @SWG\Property( property="hour", description="营业时间", type="string"),
     *                         @SWG\Property( property="is_ziti", description="是否支持自提", type="string"),
     *                         @SWG\Property( property="auto_sync_goods", description="自动同步总部商品", type="string"),
     *                         @SWG\Property( property="is_delivery", description="支持快递", type="string"),
     *                         @SWG\Property( property="is_dada", description="同城配送", type="string"),
     *                       )
     *                   ),
     *                   @SWG\Property(property="attach_file", type="string", description="附件"),
     *                       @SWG\Property(property="disabled_type", type="string", description="可编辑状态：user 用户信息不可编辑，all 所有字段不可编辑"),
     *             ),
     * )
     */


    /**
     * @SWG\Get(
     *     path="/distributors/info",
     *     summary="获取指定店铺信息或者默认店铺信息",
     *     tags={"店铺"},
     *     description="获取指定店铺信息或者默认店铺信息",
     *     operationId="getDistributorInfo",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="distributor_id", in="query", description="店铺id", required=false, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="datapass_block", type="string", example="1", description="数据是否经过脱敏 1已经脱敏/0未脱敏"),
     *                  @SWG\Property( property="distributor_id", type="string", example="85", description="分销商id"),
     *                  @SWG\Property( property="shop_id", type="string", example="0", description="门店id"),
     *                  @SWG\Property( property="is_distributor", type="string", example="true", description="是否是主店铺"),
     *                  @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                  @SWG\Property( property="mobile", type="string", example="18964058319", description="手机号"),
     *                  @SWG\Property( property="address", type="string", example="宜山路700号", description="地址"),
     *                  @SWG\Property( property="name", type="string", example="普天科创产业园", description="名称"),
     *                  @SWG\Property( property="auto_sync_goods", type="string", example="false", description="自动同步总部商品"),
     *                  @SWG\Property( property="logo", type="string", example="null", description="店铺logo"),
     *                  @SWG\Property( property="contract_phone", type="string", example="18964058319", description="联系电话"),
     *                  @SWG\Property( property="banner", type="string", example="null", description="店铺banner"),
     *                  @SWG\Property( property="contact", type="string", example="lijian", description="联系人"),
     *                  @SWG\Property( property="is_valid", type="string", example="true", description="是否有效"),
     *                  @SWG\Property( property="lng", type="string", example="121.417559", description="地图纬度"),
     *                  @SWG\Property( property="lat", type="string", example="31.176522", description="地图经度"),
     *                  @SWG\Property( property="child_count", type="string", example="0", description=""),
     *                  @SWG\Property( property="is_default", type="string", example="0", description="是否默认"),
     *                  @SWG\Property( property="is_audit_goods", type="string", example="false", description="是否审核店铺商品"),
     *                  @SWG\Property( property="is_ziti", type="string", example="false", description="是否支持自提"),
     *                  @SWG\Property( property="regions_id", type="array",
     *                      @SWG\Items( type="string", example="310000", description=""),
     *                  ),
     *                  @SWG\Property( property="regions", type="array",
     *                      @SWG\Items( type="string", example="上海市", description=""),
     *                  ),
     *                  @SWG\Property( property="is_domestic", type="string", example="1", description="是否是中国国内门店 1:国内(包含港澳台),2:非国内"),
     *                  @SWG\Property( property="is_direct_store", type="string", example="1", description="是否为直营店 1:直营店,2:非直营店"),
     *                  @SWG\Property( property="province", type="string", example="上海市", description="省"),
     *                  @SWG\Property( property="is_delivery", type="string", example="true", description="是否支持配送"),
     *                  @SWG\Property( property="city", type="string", example="上海市", description="市"),
     *                  @SWG\Property( property="area", type="string", example="徐汇", description="区"),
     *                  @SWG\Property( property="hour", type="string", example="08:00-21:00", description="营业时间，格式11:11-12:12"),
     *                  @SWG\Property( property="created", type="string", example="1596433779", description=""),
     *                  @SWG\Property( property="updated", type="string", example="1596433779", description="修改时间"),
     *                  @SWG\Property( property="shop_code", type="string", example="gys001", description="店铺号"),
     *                  @SWG\Property( property="wechat_work_department_id", type="string", example="0", description="企业微信的部门ID"),
     *                  @SWG\Property( property="distributor_self", type="string", example="0", description="是否是总店配置"),
     *                  @SWG\Property( property="regionauth_id", type="string", example="0", description="地区id"),
     *                  @SWG\Property( property="is_open", type="string", example="false", description="是否开启"),
     *                  @SWG\Property( property="rate", type="string", example="", description="货币汇率(与人民币)"),
     *                  @SWG\Property( property="store_address", type="string", example="上海市徐汇宜山路700号", description=""),
     *                  @SWG\Property( property="store_name", type="string", example="普天科创产业园", description="店铺名称"),
     *                  @SWG\Property( property="phone", type="string", example="18964058319", description=""),
     *                  @SWG\Property( property="company_dada_open", type="boolean", example="true", description="商户是否开启达达同城配"),
     *                  @SWG\Property( property="business_list", type="array",
     *                      @SWG\Items( type="string", example="食品小吃", description=""),
     *                  ),
     *                  @SWG\Property( property="dealer", type="object",description="关联经销商",
     *                     @SWG\Property( property="operator_id", type="string", example="85", description="经销商销商id"),
     *                     @SWG\Property( property="username", type="string", example="经销商", description="名称"),
     *                  ),
     *                  @SWG\Property(property="split_ledger_info", type="string", description="分账比例",
     *                       @SWG\Property(property="adapay_fee_mode", type="string", description="手续费扣费方式"),
     *                       @SWG\Property(property="headquarters_proportion", type="string", description="分账总部占比"),
     *                       @SWG\Property(property="distributor_proportion", type="string", description="分账店铺占比"),
     *                       @SWG\Property(property="dealer_proportion", type="string", description="分账经销商占比"),
     *                   ),
     *                   @SWG\Property(property="adapayMemberInfo", description="开户信息", type="array", @SWG\Items(
     *                        ref="#/definitions/openAccount"
     *                   )),
     *                  @SWG\Property( property="is_openAccount", type="boolean", example="true", description="店铺是否已经开户"),
     *                  @SWG\Property( property="is_rel_dealer", type="boolean", example="true", description="店铺是否已经关联经销商"),
     *                  @SWG\Property( property="qqmapimg", type="string", example="true", description="腾讯地图图片"),
     *
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/DistributionErrorRespones") ) )
     *  )
     * )
     */
    public function getDistributorInfo(Request $request)
    {
        $user = app('auth')->user();
        $filter['distributor_id'] = $request->get('distributor_id', 0);
        $filter['company_id'] = $user->get('company_id');

        $operatorType = $user->get('operator_type');
        if ($operatorType == 'staff') {
            $distributors = $user->get('distributor_ids');
            if (!is_array($distributors)) {
                $distributors = json_decode($distributors, true);
            }
            $distributorIds = array_column($distributors, 'distributor_id');
            $distributorIds = array_filter($distributorIds, function ($id) {
                return $id > 0;
            });

            if ($distributorIds && !in_array($filter['distributor_id'], $distributorIds)) {
                $filter['distributor_id'] = current($distributorIds);
            }
        }

        $distributorService = new DistributorService();
        $result = $distributorService->getInfo($filter);
        if (!$result) {
            throw new ResourceException('请选择店铺');
        }
        $shopService = new ShopService();
        $businessList = $shopService->getBusinessList();
        $result['business_list'] = $businessList;
        $companyRelDadaService = new CompanyRelDadaService();
        $dadaInfo = $companyRelDadaService->getInfo(['company_id' => $filter['company_id']]);
        $result['company_dada_open'] = $dadaInfo['is_open'] ?? false;
        $result['regionauth_id'] = empty($result['regionauth_id']) ? '' : $result['regionauth_id'];
        $adapayMemberService = new AdapayMemberService();
        $adapayMemberInfo = $adapayMemberService->getMemberInfo(['operator_type' => 'distributor', 'operator_id' => $filter['distributor_id'], 'company_id' => $filter['company_id']]);
        $audit_state = $adapayMemberInfo['audit_state'] ?? 0;
        $result['is_openAccount'] = false;
//        if($audit_state == 'B') $result['is_openAccount'] = true;//店铺端判断是否开户成功
        if ($audit_state == 'C') {
            $result['is_openAccount'] = true;
        }//店铺端判断是否开户成功
//        if($audit_state == 'D') $result['is_openAccount'] = true;//店铺端判断是否开户成功
        if (isset($adapayMemberInfo['is_update']) && $adapayMemberInfo['is_update']) {
            $corpMemberService = new CorpMemberService();
            $adapayMemberInfo = $corpMemberService->waitDataTranf($adapayMemberInfo);
        }
        //拆分错误信息
        $audit_desc = $adapayMemberInfo['audit_desc'] ?? '';
        if ($audit_desc) {
            $audit_desc = explode('###', $audit_desc);
            $adapayMemberInfo['audit_desc_1'] = $audit_desc[0] ?? '';
            $adapayMemberInfo['audit_desc_2'] = $audit_desc[1] ?? '';
        }


        $result['adapayMemberInfo'] = $adapayMemberInfo;

        $latlng = ($result['lat'] ?? '39.908739') . ',' . ($result['lng'] ?? '116.397513');
        $result['qqmapimg'] = 'http://apis.map.qq.com/ws/staticmap/v2/?'
            . 'key=' . config('common.qqmap_key')
            . '&size=500x249'
            . '&zoom=16'
            . '&center=' . $latlng
            . '&markers=color:blue|label:A|' . $latlng;

        //获取绑定经销商
        if ($result['dealer_id'] ?? 0) {
            $employeeService = new EmployeeService();
            $dealer = $employeeService->getInfoStaff($result['dealer_id'], $filter['company_id']);
            $result['dealer'] = ['operator_id' => $dealer['operator_id'], 'username' => $dealer['username']];
            $result['is_rel_dealer'] = true;
        } else {
            $result['is_rel_dealer'] = false;
        }
        // 是否有权限查看加密数据
        $datapassBlock = $request->get('x-datapass-block', 0);
        $result['datapass_block'] = $datapassBlock;
        if ($datapassBlock) {
            $result['mobile'] = data_masking('mobile', (string) $result['mobile']);
            $result['contact'] = data_masking('truename', (string) $result['contact']);
            // $result['store_address'] = data_masking('detailedaddress', (string) $result['store_address']);
            $newAdapayMemberInfo = $adapayMemberInfo;
            unset($newAdapayMemberInfo['basicInfo']);
            if ($newAdapayMemberInfo) {
                $result['adapayMemberInfo']['user_name'] = data_masking('truename', (string) $newAdapayMemberInfo['user_name']);
                $result['adapayMemberInfo']['tel_no'] = data_masking('mobile', (string) $newAdapayMemberInfo['tel_no']);
                $result['adapayMemberInfo']['cert_id'] = data_masking('idcard', (string) $newAdapayMemberInfo['cert_id']);
                $result['adapayMemberInfo']['bank_card_name'] = data_masking('truename', (string) $newAdapayMemberInfo['bank_card_name']);
                $result['adapayMemberInfo']['bank_tel_no'] = data_masking('mobile', (string) $newAdapayMemberInfo['bank_tel_no']);
                $result['adapayMemberInfo']['bank_card_id'] = data_masking('bankcard', (string) $newAdapayMemberInfo['bank_card_id']);
                $result['adapayMemberInfo']['bank_cert_id'] = data_masking('idcard', (string) $newAdapayMemberInfo['bank_cert_id']);
                if ($newAdapayMemberInfo['member_type'] == 'corp') {
                    $result['adapayMemberInfo']['legal_person'] = data_masking('truename', (string) $newAdapayMemberInfo['legal_person']);
                    $result['adapayMemberInfo']['legal_cert_id'] = data_masking('idcard', (string) $newAdapayMemberInfo['legal_cert_id']);
                    $result['adapayMemberInfo']['card_no'] = data_masking('bankcard', (string) $newAdapayMemberInfo['card_no']);
                    $result['adapayMemberInfo']['legal_mp'] = data_masking('mobile', (string) $newAdapayMemberInfo['legal_mp']);
                    $result['adapayMemberInfo']['tel_no'] = data_masking('mobile', (string) $newAdapayMemberInfo['tel_no']);
                }
            }
        }
        $result['datapass_block'] = $datapassBlock ? 1 : 0;
        $result['merchant_name'] = '';
        if(!empty($result['merchant_id'])){
            $merchantService= new MerchantService();
            $merchantInfo=$merchantService->getInfo(['id'=>$result['merchant_id'],'company_id'=>$result['company_id']]);
            $result['merchant_name']=$merchantInfo['merchant_name'] ?? '';
        }

        $filter = [
            'distributor_id' => $result['distributor_id'],
            'return_type' => 'offline',
        ];
        $distributorAftersalesAddressService = new DistributorAftersalesAddressService();
        $result['offline_aftersales_address'] = $distributorAftersalesAddressService->getInfo($filter);
        if ($result['offline_aftersales_address']) {
            if ($result['offline_aftersales_address']['regions_id']) {
                $result['offline_aftersales_address']['regions_id'] = json_decode($result['offline_aftersales_address']['regions_id'], true);
            }
            if ($result['offline_aftersales_address']['regions']) {
                $result['offline_aftersales_address']['regions'] = json_decode($result['offline_aftersales_address']['regions'], true);
            }
        }

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/distributor/easylist",
     *     summary="获取店铺的简易基础信息列表",
     *     tags={"店铺"},
     *     description="获取店铺的简易基础信息列表",
     *     operationId="getEasyList",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="name", in="query", description="店铺名称", required=false, type="string"),
     *     @SWG\Parameter( name="distributor_id", in="query", description="店铺id", required=false, type="string"),
     *     @SWG\Parameter( name="is_valid", in="query", description="是否有效", required=false, type="string"),
     *     @SWG\Parameter( name="province", in="query", description="省份", required=false, type="string"),
     *     @SWG\Parameter( name="city", in="query", description="城市", required=false, type="string"),
     *     @SWG\Parameter( name="area", in="query", description="地区", required=false, type="string"),
     *     @SWG\Parameter( name="mobile", in="query", description="管理员手机", required=false, type="string"),
     *     @SWG\Parameter( name="tag_id", in="query", description="标签id", required=false, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="distributor_id", type="stirng"),
     *                     @SWG\Property(property="name", type="stirng"),
     *                     @SWG\Property(property="address", type="stirng"),
     *                     @SWG\Property(property="mobile", type="stirng"),
     *                     @SWG\Property(property="shop_id", type="stirng"),
     *                     @SWG\Property(property="store_name", type="stirng"),
     *                     @SWG\Property(property="store_address", type="stirng"),
     *                     @SWG\Property(property="hour", type="stirng"),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/DistributionErrorRespones") ) )
     *  )
     * )
     */
    public function getEasyList(Request $request)
    {
        $page = $request->get('page', 1);
        $pageSize = $request->get('pageSize', 1000);

        $companyId = app('auth')->user()->get('company_id');
        $filter['company_id'] = $companyId;
        $operatorType = app('auth')->user()->get('operator_type');
        $merchantId = app('auth')->user()->get('merchant_id');
        if ($operatorType == 'merchant') {
            $filter['merchant_id'] = $merchantId;
        }
        if ($request->get('distributor_id') && !$request->get('is_all', false)) {
            $filter['distributor_id'] = (array)$request->get('distributor_id');
        } elseif ($request->get('distributorIds')) {
            $filter['distributor_id'] = (array)$request->get('distributorIds');
        }

        if ($request->input('is_valid')) {
            $filter['is_valid'] = $request->input('is_valid');
        }

        if ($request->input('name')) {
            $filter['name|contains'] = $request->input('name');
        }

        if ($request->input('province')) {
            $filter['province'] = $request->input('province');
        }
        if ($request->input('city')) {
            $filter['city'] = $request->input('city');
        }
        if ($request->input('area')) {
            $filter['area'] = $request->input('area');
        }

        if ($request->input('mobile')) {
            $filter['mobile'] = $request->input('mobile');
        }

        $distributorTagsService = new DistributorTagsService();
        if ($request->input('tag_id')) {
            $tagFilter = ['company_id' => $filter['company_id'], 'tag_id' => $request->input('tag_id')];
            if (isset($filter['distributor_id']) && $filter['distributor_id']) {
                $tagFilter['distributor_id'] = $filter['distributor_id'];
            }
            $distributorIds = $distributorTagsService->getDistributorIdsByTagids($tagFilter);
            if (!$distributorIds) {
                $result['list'] = [];
                $result['total_count'] = 0;
                return $this->response->array($result);
            }
            $filter['distributor_id'] = ($filter['distributor_id'] ?? 0) ? array_merge($filter['distributor_id'], (array)$distributorIds) : (array)$distributorIds;
        }

        $distributorService = new DistributorService();
        $result = $distributorService->getDistributorEasylists($filter, $page, $pageSize, ["created" => "DESC"]);
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/distributors/setdistance",
     *     summary="设置默认距离",
     *     tags={"店铺"},
     *     description="设置默认距离",
     *     operationId="setDistance",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="distributor_id[]", in="query", description="店铺ID", required=false, type="string"),
     *     @SWG\Parameter( name="distance", in="query", description="距离", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="distance", type="string", example="999998", description="自行更改字段描述"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/DistributionErrorRespones") ) )
     *  )
     * )
     */
    public function setDistance(Request $request)
    {
        $distance = $request->get('distance', 0);
        $companyId = app('auth')->user()->get('company_id');

        $distributorId = $request->get('distributor_id');
        // if (!$distributorId) {
        //     throw new ResourceException('请至少选择一个店铺');
        // }

        $distributorService = new DistributorService();

        // $filter['company_id'] = $companyId;
        // $filter['distributor_id'] = $distributorId;
        // $operatorType = app('auth')->user()->get('operator_type');
        // switch ($operatorType) {
        //     case 'admin':
        //     case 'staff':
        //     $filter['distribution_type'] = 1;
        //     $msg = '只能修改设置自营店铺的配送范围';
        //     break;
        //     case 'distributor':
        //     case 'merchant':
        //     $filter['distribution_type'] = 0;
        //     $msg = '只能修改设置入驻店铺的配送范围';
        //     break;
        // }
        // if ($distributorService->count($filter)) {
        //     throw new ResourceException($msg);
        // }

        $result = $distributorService->setDistanceRedis($companyId, $distributorId, $distance);
        return $this->response->array(['distance' => $distance]);
    }

    /**
     * @SWG\Get(
     *     path="/distributors/getdistance",
     *     summary="获取距离",
     *     tags={"店铺"},
     *     description="获取距离",
     *     operationId="getDistance",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="distributor_id", in="query", description="店铺ID", required=false, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="distance", type="string", example="999999", description=""),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/DistributionErrorRespones") ) )
     *  )
     * )
     */
    public function getDistance(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');

        $distributorId = $request->get('distributor_id');
        // if (!$distributorId) {
        //     throw new ResourceException('店铺ID必填');
        // }

        // if (is_array($distributorId) && count($distributorId) > 1) {
        //     throw new ResourceException('只能查看单个店铺的配送距离');
        // }

        $distributorService = new DistributorService();
        $result = $distributorService->getDistanceRedis($companyId, $distributorId);
        if (!$result) {
            $result = 0;
        }
        return $this->response->array(['distance' => $result]);
    }

    /**
     * @SWG\Get(
     *     path="/distributors/aftersales",
     *     summary="获取可退货店铺列表",
     *     tags={"店铺"},
     *     description="获取可退货店铺列表",
     *     operationId="getOtherOfflineAftersalesDistributor",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="distributor_id", in="query", description="当前店铺ID", required=true, type="string"),
     *     @SWG\Parameter( name="is_selected", in="query", description="只看选中的店铺", required=false, type="string"),
     *     @SWG\Parameter( name="distributor_name", in="query", description="搜索店铺名称", required=false, type="string"),
     *     @SWG\Parameter( name="pageSize", in="query", description="每页的数量", required=true, type="string"),
     *     @SWG\Parameter( name="page", in="query", description="分页页数", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *              @SWG\Property( property="total_count", type="string", example="169", description=""),
     *              @SWG\Property( property="list", type="array",
     *                  @SWG\Items( type="object",
     *                      @SWG\Property( property="distributor_id", type="string", example="85", description="分销商id"),
     *                      @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                  )
     *              ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/DistributionErrorRespones") ) )
     *  )
     * )
     */
    public function getOtherOfflineAftersalesDistributor(Request $request) {
        $companyId = app('auth')->user()->get('company_id');
        $distributorId = $request->get('distributor_id', 0);
        $merchantId = $request->get('merchant_id', 0);
        $isSelected = $request->get('is_selected', 0);
        $distributorName = $request->get('distributor_name');
        $page = $request->get('page', 1);
        $pageSize = $request->get('pageSize', 10);

        $distributorService = new DistributorService();
        if ($distributorId > 0) {
            $distributor = $distributorService->getInfoSimple(['company_id' => $companyId, 'distributor_id' => $distributorId]);
            if (!$distributor) {
                throw new ResourceException('店铺ID错误');
            }
            $merchantId = $distributor['merchant_id'];
        }

        $filter = [
            'company_id' => $companyId,
            'is_valid' => 'true',
            'offline_aftersales_other' => 1,
            'distributor_id|neq' => $distributorId,
            'merchant_id' => $merchantId, //只能选同一个商户下的店铺
        ];

        if ($distributorId > 0) {
            if ($isSelected === '1' || $isSelected === 'true') {
                $filter['distributor_id|in'] = $distributor['offline_aftersales_distributor_id'];
            }
        }

        if ($distributorName) {
            $filter['name|contains'] = $distributorName;
        }
        $result = $distributorService->lists($filter, ['created' => 'DESC'], $pageSize, $page);
        if ($distributorId > 0 && $page == 1) {
            if ($isSelected === '1' || $isSelected === 'true') {
                if ($distributor['offline_aftersales_self']) {
                    array_unshift($result['list'], $distributor);
                    $result['total_count'] += 1;
                }
            } else {
                array_unshift($result['list'], $distributor);
                $result['total_count'] += 1;
            }
        }
        return $this->response->array($result);
    }
}
