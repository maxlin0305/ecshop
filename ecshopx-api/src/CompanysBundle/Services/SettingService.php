<?php

namespace CompanysBundle\Services;

use CompanysBundle\Entities\Setting;

class SettingService
{
    private $entityRepository;

    public function __construct()
    {
        $this->entityRepository = app('registry')->getManager('default')->getRepository(Setting::class);
    }

    /**
     * Dynamically call the shopsservice instance.
     *
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->entityRepository->$method(...$parameters);
    }

    public function selfdeliveryAddressSave($companyId, $params)
    {
        $key = 'selfDeliveryAddress:' . $companyId;
        $params = json_encode($params);
        app('redis')->connection('companys')->set($key, $params);
        return true;
    }

    public function selfdeliveryAddressGet($companyId)
    {
        $key = 'selfDeliveryAddress:' . $companyId;
        $params = app('redis')->connection('companys')->get($key);
        $params = json_decode($params, 1);
        if ($params) {
            return $params;
        }
        return [];
    }

    /**
     * 获取会员白名单设置
     * @param $companyId :企业Id
     * @return array|mixed
     */
    public function getWhitelistSetting($companyId)
    {
        $key = 'WhitelistSetting:' . $companyId;
        $inputData = app('redis')->connection('companys')->get($key);
        $inputData = $inputData ? json_decode($inputData, true) : [];
        $inputData['whitelist_status'] = $inputData['whitelist_status'] ?? false;
        $inputData['whitelist_tips'] = $inputData['whitelist_tips'] ?? '登录失败，手机号不在白名单内！';
        return $inputData;
    }

    /**
     * 获取预售提货码状态
     * @param $companyId
     * @return array|mixed
     */
    public function presalePickupcodeGet($companyId)
    {
        $key = 'PresalePickupcodeSetting:' . $companyId;
        $data = app('redis')->connection('companys')->get($key);
        $data = $data ? json_decode($data, true) : ['pickupcode_status' => false];
        return $data;
    }


    /**
     * 获取前端店铺展示关闭状态
     * @param $companyId
     * @return array|mixed
     */
    public function getNostoresSetting($companyId)
    {
        $key = 'NostoresSetting:' . $companyId;
        $inputData = app('redis')->connection('companys')->get($key);
        $inputData = $inputData ? json_decode($inputData, true) : [];
        $inputData['nostores_status'] = ($inputData['nostores_status'] ?? 'false') === 'true' ? true : false;
        return $inputData;
    }

    /**
     * 设置前端店铺展示关闭状态
     * @param $companyId
     * @param $data
     * @return bool
     */
    public function setNostoresSetting($companyId, $data)
    {
        $key = 'NostoresSetting:' . $companyId;
        app('redis')->connection('companys')->set($key, json_encode($data));
        return true;
    }

    /**
     * 获取储值功能状态
     * @param $companyId
     * @return array|mixed
     */
    public function getRechargeSetting($companyId)
    {
        $key = 'PresaleRechargeSetting:' . $companyId;
        $inputData = app('redis')->connection('companys')->get($key);
        $inputData = $inputData ? json_decode($inputData, true) : ['recharge_status' => true];
        return $inputData;
    }

    /**
     * 设置储值功能状态
     * @param $companyId
     * @param $data
     * @return bool
     */
    public function setRechargeSetting($companyId, $inputdata)
    {
        $key = 'PresaleRechargeSetting:' . $companyId;
        if (isset($inputdata['recharge_status'])) {
            $data['recharge_status'] = ($inputdata['recharge_status'] == 'false') ? false : true;
            app('redis')->connection('companys')->set($key, json_encode($data));
        }
        return $data;
    }

    /**
     * 获取库存显示状态
     * @param $companyId
     * @return array|mixed
     */
    public function getItemStoreSetting($companyId)
    {
        $key = 'ItemStoreSetting:' . $companyId;
        $inputData = app('redis')->connection('companys')->get($key);
        $inputData = $inputData ? json_decode($inputData, true) : ['item_store_status' => true];
        return $inputData;
    }

    /**
     * 设置库存显示状态
     * @param $companyId
     * @param $data
     * @return bool
     */
    public function setItemStoreSetting($companyId, $inputdata)
    {
        $key = 'ItemStoreSetting:' . $companyId;
        if (isset($inputdata['item_store_status'])) {
            $data['item_store_status'] = ($inputdata['item_store_status'] == 'false') ? false : true;
            app('redis')->connection('companys')->set($key, json_encode($data));
        }
        return $data;
    }

    /**
     * 获取商品销量显示状态
     * @param $companyId
     * @return array|mixed
     */
    public function getItemSalesSetting($companyId)
    {
        $key = 'ItemSalesSetting:' . $companyId;
        $inputData = app('redis')->connection('companys')->get($key);
        $inputData = $inputData ? json_decode($inputData, true) : ['item_sales_status' => true];
        return $inputData;
    }

    /**
     * 设置商品销量显示状态
     * @param $companyId
     * @param $data
     * @return bool
     */
    public function setItemSalesSetting($companyId, $inputdata)
    {
        $key = 'ItemSalesSetting:' . $companyId;
        if (isset($inputdata['item_sales_status'])) {
            $data['item_sales_status'] = ($inputdata['item_sales_status'] == 'false') ? false : true;
            app('redis')->connection('companys')->set($key, json_encode($data));
        }
        return $data;
    }

    /**
     * 获取发票选项显示状态
     * @param $companyId
     * @return array|mixed
     */
    public function getInvoiceSetting($companyId)
    {
        $key = 'InvoiceSetting:' . $companyId;
        $inputData = app('redis')->connection('companys')->get($key);
        $inputData = $inputData ? json_decode($inputData, true) : ['invoice_status' => true];
        return $inputData;
    }

    /**
     * 设置发票选项显示状态
     * @param $companyId
     * @param $data
     * @return bool
     */
    public function setInvoiceSetting($companyId, $inputdata)
    {
        $key = 'InvoiceSetting:' . $companyId;
        if (isset($inputdata['invoice_status'])) {
            $data['invoice_status'] = ($inputdata['invoice_status'] == 'false') ? false : true;
            app('redis')->connection('companys')->set($key, json_encode($data));
        }
        return $data;
    }

    /**
     * 获取商品分享设置
     * @param $companyId  企业ID
     * @return array|mixed
     */
    public function getItemShareSetting($companyId)
    {
        $default = [
            'is_open' => 'false',
            'valid_grade' => [],
            'msg' => '',
            'page' => []
        ];
        $key = 'ItemShareSetting:' . $companyId;
        $inputData = app('redis')->connection('companys')->get($key);
        $inputData = $inputData ? json_decode($inputData, true) : $default;
        $inputData = array_merge($default, $inputData);
        $inputData['is_open'] = $inputData['is_open'] == 'false' ? false : true;
        return $inputData;
    }

    /**
     * 保存商品分享设置
     * @param $companyId  企业ID
     * @param $inputdata  保存数据
     * @return bool
     */
    public function setItemShareSetting($companyId, $inputdata)
    {
        $key = 'ItemShareSetting:' . $companyId;
        app('redis')->connection('companys')->set($key, json_encode($inputdata));
        return true;
    }

    /**
     * 获取小程序分享参数设置
     * @param $companyId  企业ID
     * @return array|mixed
     */
    public function getShareParametersSetting($companyId)
    {
        $key = 'ShareParametersSetting:' . $companyId;
        $inputData = app('redis')->connection('companys')->get($key);
        $inputData = $inputData ? json_decode($inputData, true) : ['distributor_param_status' => false];
        return $inputData;
    }

    /**
     * 保存小程序分享参数设置
     * @param $companyId  企业ID
     * @param $inputdata  保存数据
     * @return array
     */
    public function saveShareParametersSetting($companyId, $inputdata)
    {
        $key = 'ShareParametersSetting:' . $companyId;
        $data['distributor_param_status'] = ($inputdata['distributor_param_status'] == 'false') ? false : true;
        app('redis')->connection('companys')->set($key, json_encode($data));
        return $data;
    }

    public function getDianwuSetting($companyId)
    {
        $key = 'DianwuSetting:' . $companyId;
        $inputData = app('redis')->connection('companys')->get($key);
        $inputData = $inputData ? json_decode($inputData, true) : ['dianwu_show_status' => false];
        return $inputData;
    }

    public function saveDianwuSetting($companyId, $inputdata)
    {
        $key = 'DianwuSetting:' . $companyId;
        $data['dianwu_show_status'] = ($inputdata['dianwu_show_status'] == 'false') ? false : true;
        app('redis')->connection('companys')->set($key, json_encode($data));
        return $data;
    }

    public function getItemPriceSetting($companyId)
    {
        $key = 'ItemPriceSetting:' . $companyId;
        $data = app('redis')->connection('companys')->get($key);
        if ($data) {
            $data = json_decode($data, true);
        } else {
            $data['cart_page'] = [
                'market_price' => true,
            ];

            $data['order_page'] = [
                'market_price' => true,
            ];

            $data['item_page'] = [
                'market_price' => true,
                'member_price' => false,
                'svip_price' => false,
            ];
        }
        return $data;
    }

    public function saveItemPriceSetting($companyId, $inputdata)
    {
        $data = $this->getItemPriceSetting($companyId);
        foreach ($data as $key => $value) {
            if (isset($inputdata[$key])) {
                $data[$key] = array_merge($value, $inputdata[$key]);
            }
        }
        $key = 'ItemPriceSetting:' . $companyId;
        app('redis')->connection('companys')->set($key, json_encode($data));
        return $data;
    }
}
