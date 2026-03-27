<?php

namespace GoodsBundle\Http\Api\V1\Action;

use App\Http\Controllers\Controller as BaseController;
use Dingo\Api\Exception\ResourceException;
use EspierBundle\Jobs\ExportFileJob;
use GoodsBundle\Services\ItemsCategoryService;
use GoodsBundle\Services\ItemsService;
use GoodsBundle\Services\ItemsTagsService;
use WechatBundle\Services\WeappService;
use DistributionBundle\Services\DistributorService;

use Illuminate\Http\Request;

class ExportItems extends BaseController
{
    public const TEMPLATE_NAME = 'yykweishop';

    /**
     * @SWG\Post(
     *     path="/goods/export",
     *     summary="导出商品信息",
     *     tags={"商品"},
     *     description="导出商品信息",
     *     operationId="syncBrand",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string" ),
     *     @SWG\Parameter( name="item_id", in="query", description="导出id，是个数组", type="string" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="string", example="true", description=""),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/GoodsErrorRespones") ) )
     * )
     */
    public function exportItemsData(Request $request)
    {
        $authdata = app('auth')->user()->get();
        $inputData = $request->input();

        /*        $itemService = new ItemsService();
                $params = $itemService->exportParams($inputData, app('auth')->user()->get('company_id'));
                if ($params === false) {
                    $result['list'] = [];
                    $result['total_count'] = 0;
                    return $this->response->array($result);
                }*/
        $params['company_id'] = app('auth')->user()->get('company_id');
        if (isset($inputData['item_name']) && $inputData['item_name']) {
            $params['item_name|contains'] = $request->input('item_name');
        }
        if (isset($inputData['consume_type']) && $inputData['consume_type']) {
            $params['consume_type'] = $request->input('consume_type');
        }
        if (isset($inputData['templates_id']) && $inputData['templates_id']) {
            $params['templates_id'] = $request->input('templates_id');
        }
        if (isset($inputData['regions_id']) && $inputData['regions_id']) {
            $params['regions_id'] = implode(',', $request->input('regions_id'));
        }
        if (isset($inputData['keywords']) && $inputData['keywords']) {
            $params['item_name|contains'] = trim($request->input('keywords'));
        }

        if (isset($inputData['nospec'])) {
            $params['nospec'] = $inputData['nospec'];
        }

        $distributorId = $request->get('distributor_id') ?: $request->input('distributor_id', 0);
        $params['distributor_id'] = $distributorId;

        if (isset($inputData['approve_status']) && $inputData['approve_status']) {
            if (in_array($request->input('approve_status'), ['processing', 'rejected'])) {
                $params['audit_status'] = $request->input('approve_status');
            } else {
                $params['approve_status'] = $request->input('approve_status');
            }
        }

        if (isset($inputData['rebate']) && in_array($inputData['rebate'], [1, 0,2,3])) {
            $params['rebate'] = $request->input('rebate');
        }
        if (isset($inputData['rebate_type']) && $inputData['rebate_type']) {
            $params['rebate_type'] = $request->input('rebate_type');
        }

        if (isset($inputData['item_id']) && $inputData['item_id']) {
            $params['item_id'] = $inputData['item_id'];
            if (isset($params['distributor_id']) && !$params['distributor_id']) {
                unset($params['distributor_id']);
            }
        }

        if (isset($inputData['main_cat_id']) && $inputData['main_cat_id']) {
            $itemsCategoryService = new ItemsCategoryService();
            $itemCategory = $itemsCategoryService->getMainCatChildIdsBy($inputData['main_cat_id'], $params['company_id']);
            $itemCategory[] = $inputData['main_cat_id'];
            $params['item_category'] = $itemCategory;
        }

        if (isset($inputData['category']) && $inputData['category']) {
            $itemsCategoryService = new ItemsCategoryService();
            $ids = $itemsCategoryService->getItemIdsByCatId($inputData['category'], $params['company_id']);
            if (!$ids) {
                $result['list'] = [];
                $result['total_count'] = 0;
                return $this->response->array($result);
            }

            if (isset($params['item_id'])) {
                $params['item_id'] = array_intersect($params['item_id'], $ids);
            } else {
                $params['item_id'] = $ids;
            }
        }

        $params['item_type'] = $request->input('item_type', 'services');

        if ($inputData['store_gt'] ?? 0) {
            $params["store|gt"] = intval($inputData['store_gt']);
        }

        if ($inputData['store_lt'] ?? 0) {
            $params["store|lt"] = intval($inputData['store_lt']);
        }

        if ($inputData['price_gt'] ?? 0) {
            $params["price|gt"] = bcmul($inputData['price_gt'], 100);
        }

        if ($inputData['price_lt'] ?? 0) {
            $params["price|lt"] = bcmul($inputData['price_lt'], 100);
        }

        if (isset($inputData['special_type']) && in_array($inputData['special_type'], ['normal', 'drug'])) {
            $params['special_type'] = $inputData['special_type'];
        }

        if (isset($inputData['tag_id']) && $inputData['tag_id']) {
            $itemsTagsService = new ItemsTagsService();
            $filter = ['company_id' => $params['company_id'], 'tag_id' => $inputData['tag_id']];
            if (isset($params['item_id']) && $params['item_id']) {
                $filter['item_id'] = $params['item_id'];
            }
            $itemIds = $itemsTagsService->getItemIdsByTagids($filter);
            if (!$itemIds) {
                $result['list'] = [];
                $result['total_count'] = 0;
                return $this->response->array($result);
            }
            $params['item_id'] = $itemIds;
        }

        if ($inputData['brand_id'] ?? 0) {
            $params["brand_id"] = $inputData['brand_id'];
        }

        $itemsService = new ItemsService();
        if (isset($inputData['item_bn']) && $inputData['item_bn']) {
            $params['item_bn'] = $inputData['item_bn'];
            $datalist = $itemsService->getItemsLists($params, 'default_item_id,item_id');
            if (!$datalist) {
                $result['list'] = [];
                $result['total_count'] = 0;
                return $this->response->array($result);
            }
            unset($params['item_bn']);
            $params['item_id'] = array_column($datalist, 'default_item_id');
        }

        if (isset($inputData['is_sku']) && $inputData['is_sku'] == 'true') {
            $params['isGetSkuList'] = true;
        } else {
            $params['isGetSkuList'] = false;
            // $params['is_default'] = true;
        }
        $exportType = 'items';
        if ($inputData['export_type'] ?? false) {
            $exportType = $inputData['export_type'];
        }
        //存储导出操作账号者
        $operator_id = app('auth')->user()->get('operator_id');

        $gotoJob = (new ExportFileJob($exportType, $authdata['company_id'], $params, $operator_id))->onQueue('slow');
        app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($gotoJob);
        $result['status'] = true;
        return response()->json($result);
    }

    /**
     * @SWG\Post(
     *     path="/goods/tag/export",
     *     summary="导出商品标签信息",
     *     tags={"商品"},
     *     description="导出商品标签信息",
     *     operationId="exportItemsTagData",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string" ),
     *     @SWG\Parameter( name="item_id", in="query", description="导出id，是个数组", type="string" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="string", example="true", description=""),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/GoodsErrorRespones") ) )
     * )
     */
    public function exportItemsTagData(Request $request)
    {
        $authdata = app('auth')->user()->get();
        $inputData = $request->input();

        $params['company_id'] = app('auth')->user()->get('company_id');
        if (isset($inputData['item_name']) && $inputData['item_name']) {
            $params['item_name|contains'] = $request->input('item_name');
        }
        if (isset($inputData['consume_type']) && $inputData['consume_type']) {
            $params['consume_type'] = $request->input('consume_type');
        }
        if (isset($inputData['templates_id']) && $inputData['templates_id']) {
            $params['templates_id'] = $request->input('templates_id');
        }
        if (isset($inputData['regions_id']) && $inputData['regions_id']) {
            $params['regions_id'] = implode(',', $request->input('regions_id'));
        }
        if (isset($inputData['keywords']) && $inputData['keywords']) {
//            unset($params['item_name']);
            $params['item_name|contains'] = trim($request->input('keywords'));
        }

        if (isset($inputData['nospec'])) {
            $params['nospec'] = $inputData['nospec'];
        }

        $distributorId = $request->get('distributor_id') ?: $request->input('distributor_id', 0);
        $params['distributor_id'] = $distributorId;

        if (isset($inputData['approve_status']) && $inputData['approve_status']) {
            if (in_array($request->input('approve_status'), ['processing', 'rejected'])) {
                $params['audit_status'] = $request->input('approve_status');
            } else {
                $params['approve_status'] = $request->input('approve_status');
            }
        }

        if (isset($inputData['rebate']) && in_array($inputData['rebate'], [1, 0,2,3])) {
            $params['rebate'] = $request->input('rebate');
        }
        if (isset($inputData['rebate_type']) && $inputData['rebate_type']) {
            $params['rebate_type'] = $request->input('rebate_type');
        }

        if (isset($inputData['item_id']) && $inputData['item_id']) {
            $params['item_id'] = $inputData['item_id'];
            if (!$params['distributor_id']) {
                unset($params['distributor_id']);
            }
        }

        if (isset($inputData['main_cat_id']) && $inputData['main_cat_id']) {
            $itemsCategoryService = new ItemsCategoryService();
            $itemCategory = $itemsCategoryService->getMainCatChildIdsBy($inputData['main_cat_id'], $params['company_id']);
            $itemCategory[] = $inputData['main_cat_id'];
            $params['item_category'] = $itemCategory;
        }

        if (isset($inputData['category']) && $inputData['category']) {
            $itemsCategoryService = new ItemsCategoryService();
            $ids = $itemsCategoryService->getItemIdsByCatId($inputData['category'], $params['company_id']);
            if (!$ids) {
                $result['list'] = [];
                $result['total_count'] = 0;
                return $this->response->array($result);
            }

            if (isset($params['item_id'])) {
                $params['item_id'] = array_intersect($params['item_id'], $ids);
            } else {
                $params['item_id'] = $ids;
            }
        }

        $params['item_type'] = $request->input('item_type', 'services');

        if ($inputData['store_gt'] ?? 0) {
            $params["store|gt"] = intval($inputData['store_gt']);
        }

        if ($inputData['store_lt'] ?? 0) {
            $params["store|lt"] = intval($inputData['store_lt']);
        }

        if ($inputData['price_gt'] ?? 0) {
            $params["price|gt"] = bcmul($inputData['price_gt'], 100);
        }

        if ($inputData['price_lt'] ?? 0) {
            $params["price|lt"] = bcmul($inputData['price_lt'], 100);
        }

        if (isset($inputData['special_type']) && in_array($inputData['special_type'], ['normal', 'drug'])) {
            $params['special_type'] = $inputData['special_type'];
        }

        if (isset($inputData['tag_id']) && $inputData['tag_id']) {
            $itemsTagsService = new ItemsTagsService();
            $filter = ['company_id' => $params['company_id'], 'tag_id' => $inputData['tag_id']];
            if (isset($params['item_id']) && $params['item_id']) {
                $filter['item_id'] = $params['item_id'];
            }
            $itemIds = $itemsTagsService->getItemIdsByTagids($filter);
            if (!$itemIds) {
                $result['list'] = [];
                $result['total_count'] = 0;
                return $this->response->array($result);
            }
            $params['item_id'] = $itemIds;
        }

        if ($inputData['brand_id'] ?? 0) {
            $params["brand_id"] = $inputData['brand_id'];
        }

        $itemsService = new ItemsService();
        if (isset($inputData['item_bn']) && $inputData['item_bn']) {
            $params['item_bn'] = $inputData['item_bn'];
            $datalist = $itemsService->getItemsLists($params, 'default_item_id,item_id');
            if (!$datalist) {
                $result['list'] = [];
                $result['total_count'] = 0;
                return $this->response->array($result);
            }
            unset($params['item_bn']);
            $params['item_id'] = array_column($datalist, 'default_item_id');
        }

        //存储导出操作账号者
        $operator_id = app('auth')->user()->get('operator_id');

        $gotoJob = (new ExportFileJob('normal_items_tag', $authdata['company_id'], $params, $operator_id))->onQueue('slow');
        app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($gotoJob);
        $result['status'] = true;
        return response()->json($result);
    }

    /**
     * @SWG\Post(
     *     path="/goods/code/export",
     *     summary="导出商品码",
     *     tags={"商品"},
     *     description="导出商品码，小程序码或H5二维码",
     *     operationId="exportItemsCodeData",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string" ),
     *     @SWG\Parameter( name="item_id", in="query", description="导出id，是个数组", type="string" ),
     *     @SWG\Parameter( name="export_type", in="query", description="导出类型 wxa:太阳码;h5:H5二维码;", type="string" ),
     *     @SWG\Parameter( name="source", in="query", description="来源 item:商品;distributor:店铺商品;", type="string" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="string", example="true", description=""),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/GoodsErrorRespones") ) )
     * )
     */
    public function exportItemsCodeData(Request $request)
    {
        $authdata = app('auth')->user()->get();
        $inputData = $request->input();
        $params['company_id'] = app('auth')->user()->get('company_id');
        // 判断是否跨境
        if ($request->input('type') !== null) {
            $params['type'] = $request->input('type');
        }
        if (isset($inputData['item_name']) && $inputData['item_name']) {
            $params['item_name|contains'] = $request->input('item_name');
        }
        if (isset($inputData['consume_type']) && $inputData['consume_type']) {
            $params['consume_type'] = $request->input('consume_type');
        }
        if (isset($inputData['templates_id']) && $inputData['templates_id']) {
            $params['templates_id'] = $request->input('templates_id');
        }
        if (isset($inputData['regions_id']) && $inputData['regions_id']) {
            $params['regions_id'] = implode(',', $request->input('regions_id'));
        }
        if (isset($inputData['keywords']) && $inputData['keywords']) {
            $params['item_name|contains'] = trim($request->input('keywords'));
        }

        if (isset($inputData['nospec'])) {
            $params['nospec'] = $inputData['nospec'];
        }

        if (isset($inputData['is_gift'])) {
            $params['is_gift'] = ($inputData['is_gift'] == 'true') ? true : false;
        }

        $distributorId = $request->get('distributor_id') ?: $request->input('distributor_id', 0);
        $params['distributor_id'] = $distributorId;
        if (isset($inputData['approve_status']) && $inputData['approve_status']) {
            if (in_array($request->input('approve_status'), ['processing', 'rejected'])) {
                $params['audit_status'] = $request->input('approve_status');
            } else {
                $params['approve_status'] = $request->input('approve_status');
            }
        }

        if (isset($inputData['rebate']) && in_array($inputData['rebate'], [1, 0,2,3])) {
            $params['rebate'] = $request->input('rebate');
        }
        if (isset($inputData['rebate_type']) && $inputData['rebate_type']) {
            $params['rebate_type'] = $request->input('rebate_type');
        }

        if (isset($inputData['item_id']) && $inputData['item_id']) {
            $params['item_id'] = $inputData['item_id'];
            if (!$params['distributor_id']) {
                unset($params['distributor_id']);
            }
        }
        $inputData['source'] ??= 'item';
        $params['source'] = $inputData['source'];
        if ($params['source'] == 'distributor' && !$distributorId) {
            $distributorList = (new DistributorService())->getValidDistributor($params['company_id']);
            if (!empty($distributorList)) {
                $params['distributor_id'] = array_column($distributorList, 'distributor_id');
            }
        } elseif ($distributorId != null) {
            $params['distributor_id'] = $distributorId;
        }

        if (isset($inputData['main_cat_id']) && $inputData['main_cat_id']) {
            $itemsCategoryService = new ItemsCategoryService();
            $itemCategory = $itemsCategoryService->getMainCatChildIdsBy($inputData['main_cat_id'], $params['company_id']);
            $itemCategory[] = $inputData['main_cat_id'];
            $params['item_category'] = $itemCategory;
        }

        if (isset($inputData['category']) && $inputData['category']) {
            $itemsCategoryService = new ItemsCategoryService();
            $ids = $itemsCategoryService->getItemIdsByCatId($inputData['category'], $params['company_id']);
            if (!$ids) {
                $result['list'] = [];
                $result['total_count'] = 0;
                return $this->response->array($result);
            }

            if (isset($params['item_id'])) {
                $params['item_id'] = array_intersect($params['item_id'], $ids);
            } else {
                $params['item_id'] = $ids;
            }
        }

        $params['item_type'] = $request->input('item_type', 'services');

        if ($inputData['store_gt'] ?? 0) {
            $params["store|gt"] = intval($inputData['store_gt']);
        }

        if ($inputData['store_lt'] ?? 0) {
            $params["store|lt"] = intval($inputData['store_lt']);
        }

        if ($inputData['price_gt'] ?? 0) {
            $params["price|gt"] = bcmul($inputData['price_gt'], 100);
        }

        if ($inputData['price_lt'] ?? 0) {
            $params["price|lt"] = bcmul($inputData['price_lt'], 100);
        }

        if (isset($inputData['special_type']) && in_array($inputData['special_type'], ['normal', 'drug'])) {
            $params['special_type'] = $inputData['special_type'];
        }

        if (isset($inputData['tag_id']) && $inputData['tag_id']) {
            $itemsTagsService = new ItemsTagsService();
            $filter = ['company_id' => $params['company_id'], 'tag_id' => $inputData['tag_id']];
            if (isset($params['item_id']) && $params['item_id']) {
                $filter['item_id'] = $params['item_id'];
            }
            $itemIds = $itemsTagsService->getItemIdsByTagids($filter);
            if (!$itemIds) {
                $result['list'] = [];
                $result['total_count'] = 0;
                return $this->response->array($result);
            }
            $params['item_id'] = $itemIds;
        }

        if ($inputData['brand_id'] ?? 0) {
            $params["brand_id"] = $inputData['brand_id'];
        }



        $itemsService = new ItemsService();
        if (isset($inputData['item_bn']) && $inputData['item_bn']) {
            $params['item_bn'] = $inputData['item_bn'];
            $datalist = $itemsService->getItemsLists($params, 'default_item_id,item_id');
            if (!$datalist) {
                $result['list'] = [];
                $result['total_count'] = 0;
                return $this->response->array($result);
            }
            unset($params['item_bn']);
            $params['item_id'] = array_column($datalist, 'default_item_id');
        }

        $params['is_default'] = true;
        $inputData['export_type'] ??= 'wxa';
        $params['export_type'] = $inputData['export_type'];
        if ($params['export_type'] == 'wxa') {
            $weappService = new WeappService();
            $wxaappid = $weappService->getWxappidByTemplateName($authdata['company_id'], self::TEMPLATE_NAME);
            if (!$wxaappid) {
                throw new ResourceException('没有开通此小程序，不能下载.');
            }
            $params['wxaappid'] = $wxaappid;
        }

        //存储导出操作账号者
        $operator_id = app('auth')->user()->get('operator_id');

        $gotoJob = (new ExportFileJob('itemcode', $authdata['company_id'], $params, $operator_id))->onQueue('slow');
        app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($gotoJob);
        $result['status'] = true;
        return response()->json($result);
    }
}
