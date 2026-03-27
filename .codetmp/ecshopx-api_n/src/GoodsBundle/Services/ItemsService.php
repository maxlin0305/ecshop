<?php

namespace GoodsBundle\Services;

use CrossBorderBundle\Entities\OriginCountry;
use CrossBorderBundle\Services\Taxstrategy as Strategy;
use DistributionBundle\Entities\Distributor;
use EspierBundle\Services\Upload\UploadService;
use EspierBundle\Services\UploadToken\UploadTokenAbstract;
use EspierBundle\Services\UploadTokenFactoryService;
use GoodsBundle\Entities\Items;
use GoodsBundle\Entities\ItemsBarcode;
use GoodsBundle\Entities\ItemRelAttributes;
use GoodsBundle\Events\ItemBatchEditStatusEvent;
use KaquanBundle\Entities\RelItems;

use Dingo\Api\Exception\ResourceException;
use GoodsBundle\Events\ItemCreateEvent;
use GoodsBundle\Events\ItemAddEvent;
use GoodsBundle\Events\ItemDeleteEvent;
use MerchantBundle\Services\MerchantService;
use WechatBundle\Services\OpenPlatform;
use OrdersBundle\Services\RightsService;
use OrdersBundle\Services\Rights\TimesCardService;
use DistributionBundle\Services\DistributorItemsService;
use DistributionBundle\Services\DistributorService;

use PromotionsBundle\Services\PromotionGroupsActivityService;
use PromotionsBundle\Services\PromotionItemTagService;

use CompanysBundle\Services\ArticleService;
use CompanysBundle\Services\SettingService as CompanysSettingService;

use WechatBundle\Services\Material as MaterialService;
use PromotionsBundle\Services\MemberPriceService;
use MembersBundle\Services\MemberService;
use KaquanBundle\Services\VipGradeService;
use KaquanBundle\Services\VipGradeOrderService;
use WechatBundle\Services\WeappService;
use OrdersBundle\Services\ShippingTemplatesService;

use PointBundle\Services\PointMemberRuleService;
use PromotionsBundle\Traits\CheckPromotionsValid;
use PromotionsBundle\Services\MarketingActivityService;
use KaquanBundle\Services\MemberCardService;

class ItemsService
{
    use CheckPromotionsValid;

    protected $itemsTypeClass = [
        'services' => \GoodsBundle\Services\Items\Services::class, // 服務類商品
        'normal' => \GoodsBundle\Services\Items\Normal::class, //普通實體商品
    ];

    protected $itemtypeObject;

    /**
     * @var \GoodsBundle\Repositories\ItemsRepository
     */
    public $itemsRepository;
    private $itemRelAttributesRepository;
    private $kaquanRelItem;
    private $distributorRepository;

    /**
     * ItemsService 構造函數.
     */
    public function __construct()
    {
        $this->itemsRepository = app('registry')->getManager('default')->getRepository(Items::class);
        $this->itemRelAttributesRepository = app('registry')->getManager('default')->getRepository(ItemRelAttributes::class);
        $this->kaquanRelItem = app('registry')->getManager('default')->getRepository(RelItems::class);
        $this->distributorRepository = app('registry')->getManager('default')->getRepository(Distributor::class);
    }

    /**
     * 保存商品
     */
    public function addItems($params, $isCreateRelData = true)
    {
        $params['item_type'] = $params['item_type'] ?? "services";
        $params['recommend_items'] = $params['recommend_items'] ?? [];
        $this->itemtypeObject = new $this->itemsTypeClass[$params['item_type']]();

        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {

            // 商品通用參數
            $data = $this->commonParams($params);
            //$updateItemInfo = [];
            $goodsId = 0;
            if (isset($params['goods_id']) && $params['goods_id']) {
                $goodsId = $params['goods_id'];//導入數據的時候，不執行 processUpdateItem
            }

            if (!$goodsId && isset($params['item_id']) && $params['item_id']) {
                $itemId = $params['item_id'];//更新的商品ID, 如果是多規格則為默認的商品id
                $updateItemInfo = $this->processUpdateItem($itemId, $params['company_id'], $params);
                $goodsId = $updateItemInfo['goods_id'];
            }

            //支持導入的 default_item_id
            $defaultItemId = $params['default_item_id'] ?? null;
            $itemPrices = [];
            // 如果是多規格
            if (isset($params['nospec']) && $params['nospec'] === 'false') {
                $specImages = [];
                if (isset($params['spec_images'])) {
                    $tempSpecImages = json_decode($params['spec_images'], true);
                    $specImages = array_column($tempSpecImages, 'item_image_url', 'spec_value_id');
                }
                $data['spec_images'] = $specImages;

                $specItems = json_decode($params['spec_items'], true);
                // 規格數據不能為空
                if (empty($specItems)) {
                    throw new ResourceException('請填寫正確的規格數據');
                }
                // 如果有外部的商品ID則表示為更新，否則為強製刷新
                $isForceCreate = (isset($params['item_id']) && $params['item_id']) ? false : true;
                $isMiniPrice = 0;
                foreach ($specItems as $row) {
                    $itemsResult = $this->createItems($data, $row, $isForceCreate);
                    $itemIds[] = $itemsResult['item_id'];
                    if ($isMiniPrice == 0) {
                        $isMiniPrice = $row['price'];
                    } elseif (!$defaultItemId && $isMiniPrice > $row['price']) {
                        $isMiniPrice = $row['price'];
                        $defaultItemId = $itemsResult['item_id'];
                    }

                    if (!$defaultItemId && in_array($row['approve_status'], ['onsale', 'only_show', 'offline_sale'])) {
                        $defaultItemId = $itemsResult['item_id'];
                    }
                    $itemPrices[$itemsResult['item_id']] = bcmul($row['price'], 100);


                    //觸發事件
                    $eventData = [
                        'item_id' => $itemsResult['item_id'],
                        'company_id' => $itemsResult['company_id']
                    ];
                    event(new ItemAddEvent($eventData));
                }
                // 如果沒有定義默認商品，則默認為第一個
                if (!$defaultItemId) {
                    $defaultItemId = $itemIds[0];
                }
            } else {
                $itemsResult = $this->createItems($data, $params);
                $itemIds[] = $itemsResult['item_id'];
                if (!$defaultItemId) {
                    $defaultItemId = $itemsResult['item_id'];
                }
                $itemPrices[$itemsResult['item_id']] = bcmul($params['price'], 100);
                //觸發事件
                $eventData = [
                    'item_id' => $itemsResult['item_id'],
                    'company_id' => $itemsResult['company_id']
                ];
                event(new ItemAddEvent($eventData));
            }

            if (!$goodsId) {
                $goodsId = $defaultItemId;
            }
            // 如果設置為贈品，則檢查是否有未完成的活動
            if ($data['is_gift'] == 'true') {
                $this->checkNotFinishedActivityValid($params['company_id'], $itemIds, [$goodsId]);
            }
            // 檢查商品價格是否大於活動的價格
            $this->checkItemPrice($params['company_id'], [$goodsId], $itemPrices);

            $this->itemsRepository->updateBy(['item_id' => $itemIds], ['default_item_id' => $defaultItemId, 'goods_id' => $goodsId]);
            $this->itemsRepository->updateBy(['default_item_id' => $defaultItemId, 'item_id|neq' => $defaultItemId], ['is_default' => 0]);
            $this->itemsRepository->updateBy(['item_id' => $defaultItemId], ['is_default' => 1]);

            if ($isCreateRelData) {
                // 默認商品關聯分類
                $this->itemsRelCats($params, $defaultItemId);
                // 關聯品牌
                $this->itemsRelBrand($params, $defaultItemId);
                // 關聯參數
                $this->itemsRelParams($params, $defaultItemId);
            }
            // 處理關聯商品
            $itemsRecommendService = new ItemsRecommendService();
            $itemsRecommendService->checkParams($defaultItemId, $params['recommend_items']);
            $itemsRecommendService->saveItemsRecommendData($params['company_id'], $defaultItemId, $params['recommend_items']);
            // 處理關聯商品 end
            event(new ItemCreateEvent($itemsResult, $itemIds));
            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
            throw new ResourceException($e->getMessage());
        } catch (\Throwable $e) {
            $conn->rollback();
            throw new \Exception($e->getMessage());
        }

        // 同步總部商品到門店
        $this->syncGoods($itemsResult['company_id'], $defaultItemId);

        return $itemsResult;
    }

    /**
     * 保存上傳的商品數據
     * @param array $params
     * @return
     * @throws \Exception
     */
    public function updateUploadItems($params = [])
    {
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $itemsAttributesService = new ItemsAttributesService();

            $itemData = $params;
            $goodsId = $params['goods_id'];
            $itemId = $params['item_id'];
            $companyId = $params['company_id'];
            $defaultItemId = $params['default_item_id'];

            // 商品主類目字段名切換
            if (isset($itemData['item_main_cat_id']) && $itemData['item_main_cat_id']) {
                $itemData['item_category'] = $itemData['item_main_cat_id'];
                unset($itemData['item_main_cat_id']);
            }

            unset($itemData['item_id']);

            if (isset($params['price']) && $params['price'] <= 0) {
                $item = $this->itemsRepository->get($itemId);
                if ($item && !$item['is_gift']) {
                    throw new ResourceException('非贈品商品銷售價必須大於0');
                }
            }

            $itemsResult = $this->itemsRepository->update($itemId, $itemData);

            // 更新庫存
            if (isset($params['store']) && $params['store']) {
                $itemStoreService = new ItemStoreService();
                $itemStoreService->saveItemStore($itemId, $params['store']);
            }

            // 檢查商品價格是否大於活動的價格
            if (isset($params['price']) && $params['price']) {
                $itemPrices[$itemId] = $params['price'];
                $this->checkItemPrice($companyId, [$goodsId], $itemPrices);
            }

            // 默認商品關聯分類
            if (isset($params['item_category']) && $params['item_category']) {
                // 刪除關聯分類
                $itemsService = new ItemsRelCatsService();
                $itemsService->deleteBy(['item_id' => $defaultItemId, 'company_id' => $companyId]);
                $this->itemsRelCats($params, $defaultItemId);
            }

            //刪除條碼
            if (isset($params['barcode']) && $params['barcode']) {
                $deleteFilter = ['item_id' => $itemId, 'company_id' => $companyId];
                $ItemsBarcode = app('registry')->getManager('default')->getRepository(ItemsBarcode::class);
                $ItemsBarcode->deleteBy($deleteFilter);
                $this->saveBarcode($itemId, $defaultItemId, $companyId, $params['barcode']);
            }

            // 如果品牌發生變化，要先刪除原有的品牌
            if (isset($params['brand_id']) && $params['brand_id']) {
                $deleteFilter = ['item_id' => $itemId, 'attribute_type' => 'brand', 'company_id' => $companyId];
                $this->itemRelAttributesRepository->deleteBy($deleteFilter);
                $this->itemsRelBrand($params, $defaultItemId);
            }

            // 關聯參數
            if (isset($params['item_params']) && $params['item_params']) {
                $this->itemRelAttributesRepository->deleteBy(['item_id' => $itemId, 'attribute_type' => 'item_params', 'company_id' => $companyId]);
                $this->itemsRelParams($params, $defaultItemId);
            }

            // 商品規格，必須和主類目一起導入
            if (isset($params['item_spec']) && $params['item_spec']) {
                $sort = 0;
                $tmpFilter = [
                    'company_id' => $companyId,
                    'item_id' => $itemId,
                    'attribute_type' => 'item_spec',
                ];
                $rs = $this->itemRelAttributesRepository->lists($tmpFilter);
                $itemAttrVal = array_column($rs['list'], null, 'attribute_id');
                //app('log')->debug('_uploadItems itemAttrVal =>:'.json_encode($itemAttrVal, 256));

                // 規格更新，先刪除原規格
                $this->itemRelAttributesRepository->deleteBy($tmpFilter);

                foreach ($params['item_spec'] as $row) {
                    $tmpValue = $itemsAttributesService->getAttrValue(['attribute_value_id' => $row['spec_value_id']]);
                    $tempSort = $tmpValue['attribute_sort'] ?? 0;
                    $attrData = [
                        'company_id' => $companyId,
                        'item_id' => $itemId,
                        'attribute_id' => $row['spec_id'],
                        'attribute_type' => 'item_spec',
                        'attribute_value_id' => $row['spec_value_id'],
                    ];
                    if (isset($itemAttrVal[$row['spec_id']])) {
                        unset($itemAttrVal[$row['spec_id']]['id']);
                        $attrData = array_merge($itemAttrVal[$row['spec_id']], $attrData);
                    } else {
                        $attrData['attribute_sort'] = $tempSort + $sort;
                        $attrData['image_url'] = '';
                        $attrData['custom_attribute_value'] = null;
                    }
                    //app('log')->debug('_uploadItems attrData =>:'.json_encode($attrData, 256));
                    $sort++;
                    $this->itemRelAttributesRepository->create($attrData);
                }
            }

            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
            throw new ResourceException($e->getMessage());
        } catch (\Throwable $e) {
            $conn->rollback();
            throw new \Exception($e->getMessage());
        }

        // 同步總部商品到門店
        $this->syncGoods($companyId, $defaultItemId);

        return $itemsResult;
    }

    private function processUpdateItem($itemId, $companyId, $params)
    {
        $updateIitemInfo = $this->itemsRepository->getInfo(['item_id' => $itemId, 'company_id' => $companyId]);
        if (!$updateIitemInfo) {
            throw new ResourceException('更新的商品無效');
        }

        $distributorId = $params['distributor_id'] ?? 0;
        if ($updateIitemInfo['distributor_id'] != $distributorId) {
            throw new ResourceException('更新的商品無效');
        }

        // 如果是多規格
        if (!$updateIitemInfo['nospec'] || $updateIitemInfo['nospec'] === 'false' || (isset($params['nospec']) && $params['nospec'] === 'false')) {
            $defaultItemId = $updateIitemInfo['default_item_id'];
            $data = $this->itemsRepository->list(['default_item_id' => $defaultItemId, 'company_id' => $companyId], [], -1);
            $specItems = json_decode($params['spec_items'], true);
            $newItemIds = array_column($specItems, 'item_id');
            $deleteIds = [];

            $itemStoreService = new ItemStoreService();
            $distributorDeleteIds = [];
            foreach ($data['list'] as $row) {
                // 如果數據庫中的商品不在新更新的數據中，則表示需要把數據庫中的刪除
                if (!in_array($row['item_id'], $newItemIds)) {
                    $deleteIds[] = $row['item_id'];
                    $itemStoreService->deleteItemStore($row['item_id']);
                    // 如果不是店鋪商品，那麼需要刪除關聯商品數據
                    if (!$row['distributor_id']) {
                        $distributorDeleteIds[] = $row['item_id'];
                    }
                }
            }

            // 刪除商品
            if ($deleteIds) {
                $deleteFilter = ['item_id' => $deleteIds, 'company_id' => $companyId];
                $this->itemsRepository->deleteBy($deleteFilter);

                $itemRelPointAccessService = new ItemRelPointAccessService();
                $itemRelPointAccessService->deleteBy($deleteFilter);

                //刪除條碼
                $ItemsBarcode = app('registry')->getManager('default')->getRepository(ItemsBarcode::class);
                $ItemsBarcode->deleteBy($deleteFilter);
            }

            if ($distributorDeleteIds) {
                $distributorItemsService = new DistributorItemsService();
                $distributorItemsService->deleteBy(['item_id' => $deleteIds, 'company_id' => $companyId]);
            }

            // 刪除關聯分類
            $itemsService = new ItemsRelCatsService();
            $itemsService->deleteBy(['item_id' => $deleteIds, 'company_id' => $companyId]);
        } else {
            $newItemIds = $itemId;
        }

        // 刪除品牌，商品參數，商品規格關聯數據
        $this->itemRelAttributesRepository->deleteBy(['item_id' => $newItemIds, 'company_id' => $companyId]);

        if (method_exists($this->itemtypeObject, 'deleteRelItemById')) {
            $this->itemtypeObject->deleteRelItemById($itemId);
        }

        return $updateIitemInfo;
    }

    /**
     * 保存商品關聯分類
     */
    private function itemsRelCats($params, $defaultItemId)
    {
        //保存商品分類
        if (isset($params['company_id']) && isset($params['item_category']) && $defaultItemId) {
            $catIds = is_array($params['item_category']) ? $params['item_category'] : [$params['item_category']];
            $itemId = [$defaultItemId];
            $itemsService = new ItemsRelCatsService();
            $result = $itemsService->setItemsCategory($params['company_id'], $itemId, $catIds);
        }
    }

    /**
     * 商品關聯品牌 如果為單規格關聯當前商品ID，多規格關聯默認商品ID
     */
    private function itemsRelBrand($params, $defaultItemId)
    {
        // 保存品牌
        if (isset($params['brand_id']) && trim($params['brand_id'])) {
            // 驗證品牌ID是否有效
            $brandData = [
                'company_id' => $params['company_id'],
                'item_id' => $defaultItemId,
                'attribute_id' => trim($params['brand_id']),
                'attribute_type' => 'brand',
            ];
            $this->itemRelAttributesRepository->create($brandData);
        }
    }

    /**
     * 商品關聯參數 如果為單規格關聯當前商品ID，多規格關聯默認商品ID
     */
    private function itemsRelParams($params, $defaultItemId)
    {
        // 保存參數
        if (isset($params['item_params']) && $params['item_params']) {
            $itemParams = $params['item_params'];
            foreach ($itemParams as $row) {
                $paramsData = [
                    'company_id' => $params['company_id'],
                    'item_id' => $defaultItemId,
                    'attribute_id' => $row['attribute_id'],
                    'attribute_type' => 'item_params',
                    'attribute_value_id' => $row['attribute_value_id'] ?? null,
                    'custom_attribute_value' => $row['attribute_value_name'] ?? null,
                ];
                $this->itemRelAttributesRepository->create($paramsData);
            }
        }
    }


    //  保存條形碼
    private function saveBarcode($item_id, $barcode_default_item_id, $company_id, $barcode)
    {
        $ItemsBarcode = app('registry')->getManager('default')->getRepository(ItemsBarcode::class);

        $del_where['item_id'] = $item_id;
        $del_where['company_id'] = $company_id;
        // 先清空原有的關聯條形碼
        if ($ItemsBarcode->deleteBy($del_where)) {
            if (empty($barcode)) {
                return false;
            }
            $barcode = str_replace('，', ',', $barcode);
            $barcode = explode(',', $barcode);
            $barcode = array_unique($barcode);
            $add_sql = '';
            foreach ($barcode as $k => $v) {
                // 判斷條形碼是否存在
                $count_where['company_id'] = $company_id;
                $count_where['barcode'] = $v;
                if ($ItemsBarcode->count($count_where)) {
                    throw new ResourceException('條形碼已經存在');
                }
                $add_sql .= "($item_id,$barcode_default_item_id,$company_id,'$v'),";
            }
            // 拚接批量插入sql進行批量插入
            $add_sql = substr($add_sql, 0, strlen($add_sql) - 1);
            $conn = app('registry')->getConnection('default');
            $sql = "INSERT INTO items_barcode  (`item_id`, `default_item_id`, `company_id`, `barcode`) VALUES" . $add_sql;
            $id = $conn->executeUpdate($sql);
        }
    }

    /**
     * 創建商品
     *
     * @param array $data 已定義的商品參數
     * @param array $params 前台傳入的商品參數
     */
    private function createItems($data, $params, $isForceCreate = false)
    {
        // 商品規格特有參數
        $data = $this->itemSpecParams($data, $params);
        if (isset($params['item_id']) && $params['item_id'] && !$isForceCreate) {
            $itemsResult = $this->itemsRepository->update($params['item_id'], $data);
            $barcode_item_id = $params['item_id'];
            $barcode_default_item_id = $itemsResult['default_item_id'];
        } else {
            $data['rebate_type'] = 'default';
            $data['rebate'] = 0;
            $itemsResult = $this->itemsRepository->create($data);
            $barcode_item_id = $itemsResult['item_id'];
            $barcode_default_item_id = $itemsResult['default_item_id'];
        }
        // 保存條形碼
        $this->saveBarcode($barcode_item_id, $barcode_default_item_id, $data['company_id'], $data['barcode']);
        // if ($data['store'] && $data['store'] > 0) {
        $itemStoreService = new ItemStoreService();
        $itemStoreService->saveItemStore($itemsResult['item_id'], $data['store']);
        // }

        // 保存參數
        if (isset($params['item_spec'])) {
            $sort = 0;
            foreach ($params['item_spec'] as $row) {
                $itemImageUrl = $data['spec_images'][$row['spec_value_id']] ?? '';
                $tempSort = $row['attribute_sort'] ?? 0;
                $paramsData = [
                    'company_id' => $data['company_id'],
                    'item_id' => $itemsResult['item_id'],
                    'attribute_id' => $row['spec_id'],
                    'attribute_sort' => $tempSort + $sort,
                    'attribute_type' => 'item_spec',
                    'image_url' => $itemImageUrl,
                    'attribute_value_id' => $row['spec_value_id'],
                    'custom_attribute_value' => $row['spec_custom_value_name'] ?? null,
                ];
                $sort++;
                $this->itemRelAttributesRepository->create($paramsData);
            }
        }

        //新增不同類型商品的特殊參數
        if (method_exists($this->itemtypeObject, 'createRelItem')) {
            $itemsResult = $this->itemtypeObject->createRelItem($itemsResult, $params);
        }

        // 如果是按商品獲取積分，則保存關聯數據
        if ('items' == $data['point_access']) {
            $itemRelPointAccessService = new ItemRelPointAccessService();
            $relPointData = [
                'company_id' => $data['company_id'],
                'item_id' => $itemsResult['item_id'],
                'point' => intval($params['point_num'] ?? 0),
            ];
            $itemRelPointAccessService->saveOneData($relPointData);
        }

        return $itemsResult;
    }

    private function commonParams($params)
    {
        $data = [
            'company_id' => $params['company_id'],
            'item_type' => $params['item_type'] ?? 'services',
            'consume_type' => $params['consume_type'] ?? "every",
            'item_name' => $params['item_name'],
            'item_unit' => $params['item_unit'] ?? '',
            'brief' => $params['brief'] ?? '',
            'sort' => $params['sort'] ?? 1,
            'templates_id' => $params['templates_id'] ?? null,
            'is_show_specimg' => ($params['is_show_specimg'] ?? false) == 'true' ? true : false,
            'pics' => $params['pics'] ?? [],
            'pics_create_qrcode' => $params['pics_create_qrcode'] ?? [],
            'video_type' => $params['video_type'] ?? 'local',
            'videos' => $params['videos'] ?? "",
            'intro' => $params['intro'] ?? '',
            'special_type' => $params['special_type'] ?? 'normal',
            'purchase_agreement' => $params['purchase_agreement'] ?? '',
            'enable_agreement' => ($params['enable_agreement'] ?? false) == 'true' ? true : false,
            'item_category' => $params['item_main_cat_id'] ?? '',
            'nospec' => $params['nospec'] ?? 'true',
            'item_address_city' => $params['item_address_city'] ?? '',
            'item_address_province' => $params['item_address_province'] ?? '',
            'date_type' => $params['date_type'] ?? "",
            'begin_date' => $params['begin_date'] ?? "",
            'end_date' => $params['end_date'] ?? "",
            'fixed_term' => $params['fixed_term'] ?? "",
            // 'sales' => $params['fixed_term'] ?? 0,
            'brand_id' => $params['brand_id'] ?? 0,
            'tax_rate' => $params['tax_rate'] ?? 13, //13%默認
            // 跨境參數
            'crossborder_tax_rate' => $params['crossborder_tax_rate'] ?? '',
            'origincountry_id' => $params['origincountry_id'] ?? 0,
            'taxstrategy_id' => $params['taxstrategy_id'] ?? 0,
            'taxation_num' => $params['taxation_num'] ?? 0,
            'type' => $params['type'] ?? '0',
            'tdk_content' => $params['tdk_content'] ?? '',

            'distributor_id' => ($params['distributor_id'] ?? 0) ? $params['distributor_id'] : 0,  //店鋪id
            'item_source' => ($params['item_source'] ?? '') ? ($params['item_source'] ?: 'mall') : 'mall',  //商品來源，mall:商城，distributor:店鋪自有
            'is_gift' => ($params['is_gift'] ?? false) == 'true' ? true : false,
            'is_profit' => ($params['is_profit'] ?? false) == 'true' ? true : false,
            'profit_type' => $params['profit_type'] ?? 0,
            'profit_fee' => $params['profit_fee'] ?? 0,
            'is_default' => $params['is_default'] ?? false,
            'default_item_id' => $params['default_item_id'] ?? null,
        ];
        ini_set('pcre.backtrack_limit', '-1');
        $imgpreg = '/<img(.*?)src="(.+?)".*?>/';
        preg_match_all($imgpreg, $data['intro'], $intro);
        $count = count($intro) - 1;
        $allImg = $intro[$count];
        $fileType = 'image';
        foreach ($allImg as $key => $value) {
            if (substr($value, 0, 5) == 'data:') {
                preg_match('/^(data:\s*image\/(\w+);base64,)/', $value, $res);
                $decode_value = base64_decode(str_replace($res[1], '', $value));
                $result = UploadTokenFactoryService::create($fileType)->upload($params['company_id'], '', '', $decode_value);
                $url = $result['token']['domain'] . $result['token']['key'];
                $data['intro'] = str_replace($value, $url, $data['intro']);
            }
        }
        // 如果是騰訊視頻，videos存儲騰訊視頻vid
        if ($data['video_type'] == 'tencent') {
            $data['videos'] = $params['tencent_vid'] ?? '';
        }

        if (!isset($params['item_id']) || !$params['item_id']) {
            $data['audit_status'] = 'approved';
        }
        if ($data['distributor_id']) {
            $distributorService = new DistributorService();
            $distributorInfo = $distributorService->getInfo(['distributor_id' => $data['distributor_id'], 'company_id' => $params['company_id']]);
            if ($distributorInfo && $distributorInfo['is_audit_goods']) {
                $data['audit_status'] = 'processing';
            }
            ## 店鋪所屬商戶是否開啟了審核狀態
            if (!empty($distributorInfo['merchant_id'])) {
                $merchantService = new MerchantService();
                $merchantInfo = $merchantService->getInfo(['id' => $distributorInfo['merchant_id']]);
                if (!empty($merchantInfo['audit_goods'])) {
                    $data['audit_status'] = 'processing';
                }
            }
        }

        if (isset($params['regions_id'])) {
            if (is_array($params['regions_id'])) {
                $data['regions_id'] = implode(',', $params['regions_id']);
            } else {
                $data['regions_id'] = $params['regions_id'];
            }
        }

        if (isset($params['place_origin'])) {
            $data['place_origin'] = $params['place_origin'];
        }
        if (isset($params['regions'])) {
            if (is_array($params['regions'])) {
                $data['regions'] = implode(',', $params['regions']);
            } else {
                $data['regions'] = $params['regions'];
            }
        }

        $data['is_show_specimg'] = ($data['is_show_specimg'] == 'true') ? true : false;
        // 積分獲取的配置
        $pointMemberRuleService = new PointMemberRuleService();
        $pointRule = $pointMemberRuleService->getPointRule($params['company_id']);
        $data['point_access'] = $pointRule['access'];

        return $data;
    }

    /**
     * 商品規格特有參數
     * 如商品價格等,根據不同規格有不同值
     */
    private function itemSpecParams($data, $params)
    {
        if (!in_array($params['approve_status'], ['onsale', 'offline_sale', 'instock', 'only_show'])) {
            throw new ResourceException('請選擇正確的商品狀態');
        }

        //修複更新商品報錯：請輸入正確的分銷傭金
        if (isset($params['rebate'])) {
            $params['rebate'] = floatval($params['rebate']);
        }

        $data['item_bn'] = $params['item_bn'] ?? '';
        $data['item_bn'] = trim($data['item_bn']);
        $data['weight'] = $params['weight'] ?? 0;
        $data['weight'] = floatval($data['weight']);
        if (isset($params['volume']) && $params['volume']) {
            $data['volume'] = floatval($params['volume']);
        }
        $data['barcode'] = $params['barcode'] ?? '';
        $data['barcode'] = trim($data['barcode']);
        $data['price'] = bcmul($params['price'], 100);
        if ($data['price'] <= 0 && !$data['is_gift']) {
            throw new ResourceException('非贈品商品銷售價必須大於0');
        }
        $data['cost_price'] = isset($params['cost_price']) ? bcmul($params['cost_price'], 100) : 0;
        $data['market_price'] = $params['market_price'] ? bcmul($params['market_price'], 100) : 0;
        $data['profit_fee'] = isset($params['profit_fee']) ? bcmul($params['profit_fee'], 100) : 0;

        $data['item_unit'] = $data['item_unit'] ?? "個";
        $data['store'] = isset($params['store']) ? intval($params['store']) : 0;
        $data['approve_status'] = $params['approve_status'];
        $data['is_default'] = isset($params['is_default']) ? $params['is_default'] : true;
        // 商品贈送積分
        $data['point'] = isset($params['point']) ? intval($params['point']) : 0;

        //不同商品類型的參數
        if (method_exists($this->itemtypeObject, 'preRelItemParams')) {
            $data = $this->itemtypeObject->preRelItemParams($data, $params);
        }

        $itemsCategoryService = new ItemsCategoryService();
        //檢測分類是否存在
        if ($params['item_category'] ?? []) {
            $CategoryId = (array)$params['item_category'];
            $list = $itemsCategoryService->lists(['category_id' => $CategoryId, 'is_main_category' => false]);
            $catlist = $list['list'];
            if (!$catlist || count($catlist) != count($CategoryId)) {
                throw new ResourceException('選中的分類不存在 或 錯誤');
            }
        }
        //檢測主類目實付存在
        if ($params['item_main_cat_id'] ?? 0) {
            $catInfo = $itemsCategoryService->getInfo(['category_id' => $params['item_main_cat_id'], 'is_main_category' => true]);
            if (!$catInfo) {
                throw new ResourceException('您選中的主類目不存在');
            }
        }

        //檢測品牌是否存在
        $itemsAttributesService = new ItemsAttributesService();
        if ($params['brand_id'] ?? 0) {
            $info = $itemsAttributesService->getInfo(['attribute_id' => $params['brand_id'], 'attribute_type' => 'brand']);
            if (!$info) {
                throw new ResourceException('您選中的品牌不存在');
            }
        }
        //檢測參數是否存在
        if ($params['item_params'] ?? []) {
            foreach ($params['item_params'] as $v) {
                if ($v['attribute_id'] ?? 0) {
                    $ids[$v['attribute_id']] = $v['attribute_id'];
                }
                if ($v['attribute_value_id'] ?? 0) {
                    $vids[$v['attribute_value_id']] = $v['attribute_value_id'];
                }
            }
            if ($ids ?? []) {
                $lists = $itemsAttributesService->lists(['attribute_id' => $ids, 'attribute_type' => 'item_params']);
                if (!($lists['list'] ?? []) || count($lists['list']) != count($ids)) {
                    throw new ResourceException('您選中的參數不存在');
                }
            }
            if ($vids ?? []) {
                $lists = $itemsAttributesService->itemsAttributeValuesRepository->lists(['attribute_value_id' => $vids]);
                if (!($lists['list'] ?? []) || count($lists['list']) != count($vids)) {
                    throw new ResourceException('您選中的參數值不存在');
                }
            }
        }
        //檢測規格是否存在
        if ($params['item_spec'] ?? []) {
            foreach ($params['item_spec'] as $v) {
                if ($v['spec_id'] ?? 0) {
                    $ids[$v['spec_id']] = $v['spec_id'];
                }
                if ($v['spec_value_id'] ?? 0) {
                    $vids[$v['spec_value_id']] = $v['spec_value_id'];
                }
            }
            if ($ids ?? []) {
                $lists = $itemsAttributesService->lists(['attribute_id' => $ids, 'attribute_type' => 'item_spec']);
                if (!($lists['list'] ?? []) || count($lists['list']) != count($ids)) {
                    throw new ResourceException('您選中的規格不存在');
                }
            }
            if ($vids ?? []) {
                $lists = $itemsAttributesService->itemsAttributeValuesRepository->lists(['attribute_value_id' => $vids]);
                if (!($lists['list'] ?? []) || count($lists['list']) != count($vids)) {
                    throw new ResourceException('您選中的規格值不存在');
                }
            }
        }
        //檢測運費模板是否存在
        if ($params['templates_id'] ?? 0) {
            $shippingTemplatesService = new ShippingTemplatesService();
            $info = $shippingTemplatesService->getInfo($params['templates_id'], $params['company_id']);
            if (!$info) {
                throw new ResourceException('您選中的運費模板不存在');
            }
        }
        return $data;
    }

    /**
     * 刪除商品
     *
     * @param array filter
     * @return bool
     */
    public function deleteItems($filter)
    {
        if (!isset($filter['item_id']) || !$filter['item_id']) {
            throw new ResourceException('商品id不能為空');
        }

        $itemsInfo = $this->itemsRepository->get($filter['item_id']);
        if ($filter['company_id'] != $itemsInfo['company_id']) {
            throw new ResourceException('刪除商品信息有誤');
        }
        if ($filter['distributor_id'] != $itemsInfo['distributor_id']) {
            throw new ResourceException('店鋪商品信息有誤，不可刪除');
        }

        // 如果是多規格
        if (!$itemsInfo['nospec'] || $itemsInfo['nospec'] === 'false') {
            $data = $this->itemsRepository->list(['default_item_id' => $itemsInfo['default_item_id'], 'company_id' => $itemsInfo['company_id']]);
            $itemIds = array_column($data['list'], 'item_id');
            $defaultItemId = $itemsInfo['default_item_id'];
        } else {
            $itemIds = [$itemsInfo['item_id']];
            $defaultItemId = $itemsInfo['item_id'];
        }

        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $itemStoreService = new ItemStoreService();
            foreach ($itemIds as $itemId) {
                $this->itemsRepository->delete($itemId);
                $itemStoreService->deleteItemStore($itemId);
                // 刪除品牌，商品參數，商品規格關聯數據
                $this->itemRelAttributesRepository->deleteBy(['item_id' => $itemId, 'company_id' => $filter['company_id']]);
            }

            $itemsRelCatsService = new ItemsRelCatsService();
            $itemsRelCatsService->deleteBy(['item_id' => $defaultItemId, 'company_id' => $filter['company_id']]);

            $itemtypeObject = new $this->itemsTypeClass[$itemsInfo['item_type']]();
            if (method_exists($itemtypeObject, 'deleteRelItemById')) {
                $itemtypeObject->deleteRelItemById($defaultItemId);
            }

            // 刪除店鋪關聯
            $distributorItemsService = new DistributorItemsService();
            $distributorItemsService->deleteBy(['default_item_id' => $defaultItemId, 'company_id' => $filter['company_id']]);

            // 刪除商品會員價
            $memberPriceService = new MemberPriceService();
            $memberPriceService->deleteMemberPrice(['item_id' => $itemIds, 'company_id' => $filter['company_id']]);

            //刪除條碼
            $ItemsBarcode = app('registry')->getManager('default')->getRepository(ItemsBarcode::class);
            $ItemsBarcode->deleteBy(['item_id' => $itemIds, 'company_id' => $filter['company_id']]);

            $conn->commit();

            //觸發事件
            $eventData = [
                'item_id' => $filter['item_id'],
                'company_id' => $filter['company_id'],
                'del_ids' => $itemIds,
                'item_info' => $itemsInfo
            ];
            event(new ItemDeleteEvent($eventData));

            return true;
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }
    }

    /**
     * 獲取單個商品信息，如果是多規格，也隻返回指定ID的信息
     */
    public function getItemsSkuDetail($itemId, $authorizerAppId = null)
    {
        $itemsInfo = $this->itemsRepository->get($itemId);
        if (!$itemsInfo) {
            throw new ResourceException('商品不存在');
        }
        // 如果是多規格
        if (!$itemsInfo['nospec'] || $itemsInfo['nospec'] === 'false') {
            //規格等數據
            $specAttrList = $this->itemRelAttributesRepository->lists(['item_id' => $itemId, 'attribute_type' => 'item_spec'], 1, -1);
            if ($specAttrList['list']) {
                foreach ($specAttrList['list'] as $specAttrRow) {
                    if ($specAttrRow['item_id'] == $itemId && $specAttrRow['image_url']) {
                        $itemsInfo['pics'] = $specAttrRow['image_url'];
                    }
                }
            }
        }

        $itemsInfo['item_type'] = $itemsInfo['item_type'] ?: 'services';
        $itemtypeObject = new $this->itemsTypeClass[$itemsInfo['item_type']]();
        $itemsInfo['type_labels'] = [];
        if ($itemsInfo['item_type'] == 'services') {
            $itemsInfo['type_labels'] = $itemtypeObject->listByItemId($itemsInfo['item_id']);
        }

        //置換微信視頻
        if (isset($itemsInfo['videos']) && $itemsInfo['videos'] && $authorizerAppId) {
            $itemsInfo = $this->getVideoPicUrl($itemsInfo, $authorizerAppId);
        } else {
            $itemsInfo['videos_url'] = '';
        }

        return $itemsInfo;
    }

    /**
     * 獲取多個商品的數據
     *
     * @param mixed $itemIds 商品id
     * @param integer $companyId 公司ID
     * @param string $fields
     * @param string $filterType
     * @return array
     */
    public function getItems($itemIds, $companyId, $fields = null, $filterType = 'item_id')
    {
        $page = 1;
        $pageSize = -1;//取出所有商品
        $filter = [$filterType => $itemIds, 'company_id' => $companyId];
        $items = $this->itemsRepository->list($filter, [], $pageSize, $page, $fields);
        return $items['list'];
    }

    /**
     * 獲取單個商品信息
     *
     * @param array $filter
     * @return array
     */
    public function getItem($filter = array())
    {
        $itemInfo = $this->itemsRepository->getInfo($filter);
        return $itemInfo;
    }

    /**
     * @param array $filter
     * @param int $page
     * @param int $pageSize 默認-1，取出所有商品
     * @param string $column 返回的字段，默認 item_id
     * @return array
     */
    public function getItemIds($filter = array(), $page = 1, $pageSize = -1, $column = 'item_id')
    {
        $items = $this->itemsRepository->list($filter, [], $pageSize, $page);
        if ($column) {
            return array_column($items['list'], $column);
        }
        return $items['list'];
    }

    /**
     * 獲取商品詳情
     *
     * @param inteter item_id 商品id
     * @param inteter limitItemIds 限定的商品ID
     * @return array
     */
    public function getItemsDetail($itemId, $authorizerAppId = null, $limitItemIds = array(), $companyId = null)
    {
        if ($limitItemIds && !in_array($itemId, $limitItemIds)) {
            $itemId = $limitItemIds[0];
        }

        $itemsInfo = $this->itemsRepository->get($itemId);
        if (!$itemsInfo || ($companyId && $itemsInfo['company_id'] != $companyId)) {
            return [];
        }

        if ($itemsInfo['regions_id']) {
            $itemsInfo['regions_id'] = explode(',', $itemsInfo['regions_id']);
        }
        if ($itemsInfo['regions']) {
            $itemsInfo['regions'] = explode(',', $itemsInfo['regions']);
        }
        if (!empty($itemsInfo['pics'])) {
            foreach ($itemsInfo['pics'] as $key => $value) {
                $pics_create_qrcode = $itemsInfo['pics_create_qrcode'][$key] ?? 'false';
                $itemsInfo['pics_create_qrcode'][$key] = $pics_create_qrcode == 'false' ? false : true;
            }
            $itemsInfo['pics_create_qrcode'] = array_values($itemsInfo['pics_create_qrcode']);//防止前端出錯
        } else {
            $itemsInfo['pics_create_qrcode'] = [];
        }
        $itemsInfo['item_type'] = $itemsInfo['item_type'] ?: 'services';
        $itemtypeObject = new $this->itemsTypeClass[$itemsInfo['item_type']]();
        $itemsInfo['type_labels'] = [];
        if ($itemsInfo['item_type'] == 'services') {
            $itemsInfo['type_labels'] = $itemtypeObject->listByItemId($itemsInfo['item_id']);
        } else {
            // 如果是多規格
            if (!$itemsInfo['nospec'] || $itemsInfo['nospec'] === 'false') {
                $filter['company_id'] = $itemsInfo['company_id'];
                if ($limitItemIds) {
                    $filter['item_id'] = $limitItemIds;
                } else {
                    $filter['default_item_id'] = $itemsInfo['default_item_id'];
                }
                // 獲取多規格的商品id
                $itemsList = $this->itemsRepository->list($filter, null, -1);
                $itemIds = array_column($itemsList['list'], 'item_id');
            } else {
                $itemIds = $itemId;
                $itemsList = array();
            }

            $itemsInfo = $this->__preGetItemRelAttr($itemsInfo, $itemIds, $itemsList);
            $itemsInfo['item_category'] = $this->getCategoryByItemId($itemsInfo['item_id'], $itemsInfo['company_id']);
        }

        // 商品主類目
        if ($itemsInfo['item_main_cat_id']) {
            $itemsCategoryService = new ItemsCategoryService();
            $itemsInfo['item_category_main'] = $itemsCategoryService->getCategoryPathById($itemsInfo['item_main_cat_id'], $itemsInfo['company_id'], true);
            if (!$itemsInfo['item_category_main']) {
                $itemsInfo['item_main_cat_id'] = '';
            }
        } else {
            $itemsInfo['item_category_main'] = [];
        }

        // 設置第一個商品分類信息
        if ($itemsInfo['item_category'] ?? false) {
            $itemsCategoryService = new ItemsCategoryService();
            $itemsInfo['item_category_info'] = $itemsCategoryService->getCategoryPathById($itemsInfo['item_category'][0], $itemsInfo['company_id']);
        } else {
            $itemsInfo['item_category_info'] = [];
        }

        if (isset($itemsInfo['intro'])) {
            $articleService = new ArticleService();
            $itemsInfo['intro'] = $articleService->proArticleContent($itemsInfo['intro'], $authorizerAppId);
        }

        //置換微信視頻
        if (isset($itemsInfo['videos']) && $itemsInfo['videos'] && $authorizerAppId && $itemsInfo['video_type'] != 'tencent') {
            $itemsInfo = $this->getVideoPicUrl($itemsInfo, $authorizerAppId);
        } else {
            $itemsInfo['videos_url'] = '';
        }
        if ($itemsInfo['video_type'] == 'tencent') {
            $itemsInfo['tencent_vid'] = $itemsInfo['videos'];
        }
        $itemsInfo['distributor_sale_status'] = true;
        if (in_array($itemsInfo['approve_status'], ['instock', 'only_show'])) {
            $itemsInfo['distributor_sale_status'] = false;
        }
        $itemsInfo['item_total_store'] = $itemsInfo['item_total_store'] ?? $itemsInfo['store'];
        $itemsInfo['item_total_sales'] = $itemsInfo['item_total_sales'] ?? $itemsInfo['sales'];

        $itemsInfo['distributor_info'] = [];
        if ($itemsInfo['distributor_id'] ?? 0) {
            $distributorService = new DistributorService();
            $itemsInfo['distributor_info'] = $distributorService->getInfo(['distributor_id' => $itemsInfo['distributor_id'], 'company_id' => $itemsInfo['company_id']]);
        }

        // 產地國家
        if (empty($itemsInfo['origincountry_id'])) {
            $itemsInfo['origincountry_name'] = '';
            $itemsInfo['origincountry_img_url'] = '';
        } else {
            $info = app('registry')->getManager('default')->getRepository(OriginCountry::class)->getInfoById($itemsInfo['origincountry_id']);
            $itemsInfo['origincountry_name'] = $info['origincountry_name'];
            $itemsInfo['origincountry_img_url'] = $info['origincountry_img_url'];
        }

        // 判斷是否是跨境商品
        if ($itemsInfo['type'] == 1) {
            $ItemTaxRateService = new ItemTaxRateService($companyId);
            $ItemTaxRate = $ItemTaxRateService->getItemTaxRate($itemsInfo['item_id']);

            $itemsInfo['cross_border_tax_rate'] = $ItemTaxRate['tax_rate'];
            $itemsInfo['cross_border_tax'] = bcdiv(bcmul($itemsInfo['price'], $itemsInfo['cross_border_tax_rate'], 0), 100, 0);

            // 稅費規則
            if (!empty($itemsInfo['taxstrategy_id'])) {
                $tax_strategy_filter['id'] = $itemsInfo['taxstrategy_id'];
                $tax_strategy_filter['company_id'] = $itemsInfo['company_id'];
                $Strategy = new Strategy();
                $tax_strategy_data = $Strategy->getInfo($tax_strategy_filter);
                $itemsInfo['tax_strategy'] = $tax_strategy_data['taxstrategy_content'];
            }
        } else {
            $itemsInfo['tax_rate'] = 0;
            $itemsInfo['cross_border_tax'] = 0;
        }

        // 運費模板名稱
        $itemsInfo['templates_name'] = '';
        if (!empty($itemsInfo['templates_id'])) {
            $shippingTemplatesService = new ShippingTemplatesService();
            $info = $shippingTemplatesService->getInfo($itemsInfo['templates_id'], $companyId);
            if ($info) {
                $itemsInfo['templates_name'] = $info['name'];
            }
        }

        // 提供格式化的數據供前端使用
        foreach ($itemsInfo['spec_items'] as $key => &$item) {
            usort($item['item_spec'], function($a, $b) {
                if($a['spec_id'] == $b['spec_id']) return 0;
                else return $a['spec_id'] > $b['spec_id'] ? 1 : -1;
            });

            $custom_spec_ids = [];
            $custom_spec_names = [];
            foreach ($item['item_spec'] as $item_spec) {
                $custom_spec_ids[] = $item_spec['spec_value_id'];
                $custom_spec_names[] = $item_spec['spec_value_name'];
            }
            $custom_spec_ids = implode('-', $custom_spec_ids);
            $custom_spec_names = implode('、', $custom_spec_names);
            $itemsInfo['spec_items'][$key]['custom_spec_id'] = $custom_spec_ids;
            $itemsInfo['spec_items'][$key]['custom_spec_name'] = $custom_spec_names;
        }

        return $itemsInfo;
    }

    /**
     * 更加商品ID獲取商品參數屬性數據
     */
    public function getItemParamsByItem($itemsInfo)
    {
        //獲取品牌，屬性參數
        $attrList = $this->itemRelAttributesRepository->lists(['item_id' => $itemsInfo['default_item_id'], 'attribute_type' => 'item_params'], 1, -1, ['attribute_sort' => 'asc']);
        $attrList = $attrList['list'];
        if ($attrList) {
            $itemsAttributesService = new ItemsAttributesService();
            $attrData = $itemsAttributesService->getItemsRelAttrValuesList($attrList);
            $itemsInfo = $this->__preGetItemParams($itemsInfo, $attrData);
        }
        return $itemsInfo;
    }

    /**
     * 商品詳情，商品關聯商品屬性處理結構
     */
    private function __preGetItemRelAttr($itemsInfo, $itemIds, $itemsList)
    {
        $itemsAttributesService = new ItemsAttributesService();
        $defaultItemId = $itemsInfo['default_item_id'] ?: $itemsInfo['item_id'];

        //獲取品牌，屬性參數
        $attrList = $this->itemRelAttributesRepository->lists(['item_id' => $defaultItemId], 1, -1, ['attribute_sort' => 'asc']);
        //規格等數據
        $specAttrList = $this->itemRelAttributesRepository->lists(['item_id' => $itemIds, 'attribute_type' => 'item_spec'], 1, -1, ['attribute_sort' => 'asc']);
        // 臨時
        $itemsInfo['spec_pics'] = [];
        if ($specAttrList['list']) {
            foreach ($specAttrList['list'] as $specAttrRow) {
                if ($specAttrRow['item_id'] == $itemsInfo['item_id'] && $specAttrRow['image_url']) {
                    $itemsInfo['spec_pics'] = $specAttrRow['image_url'];
                }
            }
        }

        $itemsInfo['item_params'] = [];
        $itemsInfo['item_spec_desc'] = [];
        $itemsInfo['spec_images'] = [];
        $itemsInfo['spec_items'] = [];

        $attrList = array_merge($attrList['list'], $specAttrList['list']);
        if ($attrList) {
            $attrData = $itemsAttributesService->getItemsRelAttrValuesList($attrList);

            $itemsInfo['attribute_ids'] = $attrData['attribute_ids'] ?? [];
            $itemsInfo['attr_values_custom'] = $attrData['attr_values_custom'] ?? [];

            $itemsInfo = $this->__preGetItemParams($itemsInfo, $attrData);

            $itemsInfo = $this->__preGetItemSpec($itemsInfo, $attrData, $itemsList);

            if (isset($attrData['brand'])) {
                $itemsInfo['brand_id'] = $attrData['brand']['brand_id'];
                $itemsInfo['goods_brand'] = $attrData['brand']['goods_brand'];
                $itemsInfo['brand_logo'] = $attrData['brand']['brand_logo'];
            }
        }
        return $itemsInfo;
    }

    /**
     * 商品詳情，商品規格結構
     */
    private function __preGetItemSpec($itemsInfo, $attrData, $itemsList)
    {
        // 積分獲取關聯數據
        $pointAccess = $this->__preGetItemRelPointAccess($itemsInfo, $itemsList);
        if (!isset($attrData['item_spec']) || !$attrData['item_spec']) {
            $pointAccess and $itemsInfo['point_num'] = $pointAccess['point'] ?? 0;
            return $itemsInfo;
        }
        $itemsInfo['item_spec_desc'] = $attrData['item_spec_desc'];
        $itemsInfo['spec_images'] = $attrData['spec_images'];
        $totalStore = 0;
        $totalSales = 0;
        $approveStatus = [];
        foreach ($itemsList['list'] as $itemRow) {
            $itemSpec = $attrData['item_spec'][$itemRow['item_id']];
            $tempItemSpec = [];
            foreach ($itemSpec as $itemSpecRow) {
                $tempItemSpec[] = $itemSpecRow;
            }

            $approveStatus[] = $itemRow['approve_status'];

            $itemsInfo['spec_items'][] = [
                'item_id' => $itemRow['item_id'],
                'price' => $itemRow['price'],
                'store' => $itemRow['store'],
                'cost_price' => $itemRow['cost_price'],
                'item_bn' => $itemRow['item_bn'],
                'barcode' => $itemRow['barcode'],
                'market_price' => $itemRow['market_price'],
                'item_unit' => $itemRow['item_unit'],
                'volume' => $itemRow['volume'],
                'approve_status' => $itemRow['approve_status'],
                'is_default' => $itemRow['is_default'],
                'weight' => $itemRow['weight'],
                'item_spec' => $tempItemSpec,
                'point_num' => $pointAccess[$itemRow['item_id']] ?? 0,
            ];

            $totalStore += $itemRow['store'];
            $totalSales += $itemRow['sales'];
        }

        if (in_array('onsale', $approveStatus)) {
            $itemsInfo['approve_status'] = 'onsale';
        } elseif (in_array('only_show', $approveStatus)) {
            $itemsInfo['approve_status'] = 'only_show';
        } elseif (in_array('offline_sale', $approveStatus)) {
            $itemsInfo['approve_status'] = 'offline_sale';
        } else {
            $itemsInfo['approve_status'] = 'instock';
        }

        $itemsInfo['item_total_store'] = $totalStore;
        $itemsInfo['item_total_sales'] = $totalSales;
        return $itemsInfo;
    }

    private function __preGetItemRelPointAccess($itemsInfo, $itemsList)
    {
        // 積分獲取的配置
        $pointMemberRuleService = new PointMemberRuleService();
        $pointRule = $pointMemberRuleService->getPointRule($itemsInfo['company_id']);
        if ($pointRule['access'] == 'order') {
            return false;
        }
        $itemRelPointAccessService = new ItemRelPointAccessService();
        if (!$itemsList) {
            $pointAccessInfo = $itemRelPointAccessService->getInfo(['company_id' => $itemsInfo['company_id'], 'item_id' => $itemsInfo['item_id']]);
            return $pointAccessInfo ?? false;
        } else {
            $item_ids = array_column($itemsList['list'], 'item_id');

            $pointAccessList = $itemRelPointAccessService->getLists(['company_id' => $itemsInfo['company_id'], 'item_id' => $item_ids]);
            $pointAccessList = array_column($pointAccessList, 'point', 'item_id');
            return $pointAccessList;
        }
    }

    /**
     * 商品詳情，商品參數結構
     */
    private function __preGetItemParams($itemsInfo, $attrData)
    {
        $itemsInfo['item_params'] = [];
        if (isset($attrData['item_params'])) {
            $itemsInfo['item_params'] = $attrData['item_params'];
        } else {
            if (($itemsInfo['goods_brand'] ?? '') && !isset($itemsInfo['brand_id'])) {
                $itemsInfo['item_params'][] = [
                    'attribute_name' => '品牌',
                    'attribute_value_name' => $itemsInfo['goods_brand'],
                ];
            }
            if (($itemsInfo['goods_color'] ?? '')) {
                $itemsInfo['item_params'][] = [
                    'attribute_name' => '顏色',
                    'attribute_value_name' => $itemsInfo['goods_color'],
                ];
            }
            if (($itemsInfo['goods_function'] ?? '')) {
                $itemsInfo['item_params'][] = [
                    'attribute_name' => '功能',
                    'attribute_value_name' => $itemsInfo['goods_function'],
                ];
            }
            if (($itemsInfo['goods_series'] ?? '')) {
                $itemsInfo['item_params'][] = [
                    'attribute_name' => '係列',
                    'attribute_value_name' => $itemsInfo['goods_series'],
                ];
            }
        }
        return $itemsInfo;
    }

    /**
     * 獲取商品參加拚團活動的詳情
     *
     * @param array $itemDetail 數據由this->getItemsDetail()返回
     */
    // public function getItemsGroupsDetail($itemDetail)
    // {
    //     if (!$itemDetail['nospec'] || $itemDetail['nospec'] === 'false') {
    //         $itemIds = array_column($itemDetail['spec_items'], 'item_id');
    //     } else {
    //         $itemIds = $itemDetail['item_id'];
    //     }

    //     // 判斷商品是否參加拚團活動，
    //     $promotionGroupsActivityService = new PromotionGroupsActivityService();
    //     $lists = $promotionGroupsActivityService->getIsHave($itemIds, time(), time());
    //     if (!$lists) {
    //         return $itemDetail;
    //     }

    //     //同一個商品在同一時間段隻能為同一個活動
    //     $groupActivityInfo = $lists[0];

    //     $itemDetail['item_activity_type'] = 'group';
    //     $itemDetail['group_activity'] = [
    //         'pics' => $groupActivityInfo['pics'],
    //         'item_name' => $groupActivityInfo['item_name'],
    //         'brief' => $groupActivityInfo['brief'],
    //         'price' => $groupActivityInfo['price'],
    //     ];

    //     // 判斷是否需要顯示拚團列表
    //     if (isset($result['group_activity']['rig_up']) && true == $result['group_activity']['rig_up']) {
    //         $promotionGroupsTeamService = new PromotionGroupsTeamService();
    //         $filter = [
    //             'p.act_id' => $result['group_activity']['groups_activity_id'],
    //             'p.company_id' => $result['group_activity']['company_id'],
    //             'p.team_status' => 1,
    //             'p.disabled' => false,
    //         ];
    //         $result['groups_list'] = $promotionGroupsTeamService->getGroupsTeamByItems($filter, 1, 4);
    //     }
    // }

    /** 獲取商品積分
     * @param $itemId 商品id
     * @param null
     * @return mixed
     */
    public function getItemsPoint($itemId)
    {
        $itemsInfo = $this->itemsRepository->get($itemId);
        return $itemsInfo['is_point'] ? $itemsInfo['point'] : false;
    }

    /** 獲取會員商品價格
     * @param $itemId 商品id
     * @param $userId 會員id
     * @param $companyId
     * @return mixed
     */
    public function getItemsMemberPrice($itemId, $userId, $companyId)
    {
        $itemsInfo = $this->itemsRepository->get($itemId);

        $memberPriceService = new MemberPriceService();
        $filter = ['company_id' => $companyId, 'item_id' => $itemId];
        $priceList = $memberPriceService->lists($filter);

        $memberService = new MemberService();
        $userGradeData = $memberService->getValidUserGradeUniqueByUserId($userId, $companyId);

        $discount = $userGradeData['discount'] ?? '';            //會員折扣參數
        $gradeId = $userGradeData['id'] ?? '';                   //會員等級id
        $lvType = $userGradeData['lv_type'] ?? 'normal';   //會員等級類型 vip、svip、normal

        $grade = ($lvType == 'normal') ? 'grade' : 'vipGrade';

        $newPrice = [];
        foreach ($priceList['list'] as $priceRow) {
            $memberPrice = json_decode($priceRow['mprice'], true);
            // 是否有設置會員自定義價格
            if (isset($memberPrice[$grade][$gradeId]) && intval($memberPrice[$grade][$gradeId]) > 0) {
                $newPrice[$priceRow['item_id']] = intval($memberPrice[$grade][$gradeId]);
            }
        }

        if (isset($newPrice[$itemId]) && $newPrice[$itemId] > 0) {
            $price = $newPrice[$itemId];
        } elseif ($discount > 0 && $discount != 100) {
            $price = $itemsInfo['price'] - bcmul($itemsInfo['price'], bcdiv($discount, 100, 2));
        } else {
            $price = $itemsInfo['price'];
        }

        return $price;
    }

    /**
     * 將商品詳情中的視頻轉為對應的URL地址
     */
    private function getVideoPicUrl($itemsInfo, $authorizerAppId)
    {
        if (preg_match('/(http:\/\/)|(https:\/\/)/i', $itemsInfo['videos'])) {
            $itemsInfo['videos_url'] = $itemsInfo['videos'];
        } else {
            $service = new MaterialService();
            $service = $service->application($authorizerAppId);
            $detail = $service->getMaterial($itemsInfo['videos']);
            if (isset($detail['down_url']) && $detail['down_url']) {
                $itemsInfo['videos_url'] = $detail['down_url'];
            } else {
                $itemsInfo['videos_url'] = '';
                $itemsInfo['videos'] = '';
            }
        }

        return $itemsInfo;
    }

    public function getCategoryByItemId($itemId, $companyId)
    {
        $itemsService = new ItemsRelCatsService();
        $filter['item_id'] = $itemId;
        $filter['company_id'] = $companyId;
        $data = $itemsService->lists($filter);
        if ($data['list']) {
            $catIds = array_column($data['list'], 'category_id');
            return $catIds;
        }
        return [];
    }

    /**
     * 更新銷量
     * @param $itemId 商品id
     * @param $sales 商品數量
     * @return mixed
     */
    public function incrSales($itemId, $sales)
    {
        return $this->itemsRepository->updateSales($itemId, $sales);
    }

    /**
     * 獲取商品列表
     *
     * @param array filter
     * @return array
     */
    public function getItemsList($filter, $page = 1, $pageSize = 2000, $orderBy = ['item_id' => 'DESC'])
    {
        $page = ($page < 1) ? 1 : $page;
        $pageSize = ($pageSize > 2000) ? 2000 : $pageSize;
        $itemsList = $this->itemsRepository->list($filter, $orderBy, $pageSize, $page);
        foreach ($itemsList['list'] as $key => &$v) {
            $v['item_cat_id'] = $this->getCategoryByItemId($v['item_id'], $v['company_id']);
            $v['item_type'] = $v['item_type'] ?: 'services';
            $itemtypeObject = new $this->itemsTypeClass[$v['item_type']]();
            if (isset($v['itemId']) && method_exists($itemtypeObject, 'listByItemId')) {
                $v['type_labels'] = $itemtypeObject->listByItemId($v['itemId'], $v);
            } else {
                $v['type_labels'] = [];
            }
        }
        return $itemsList;
    }

    /**
     * 商品sku列表，格式化為商品列表，商品包含sku格式
     */
    public function formatItemsList($list)
    {
        if (!$list) {
            return [];
        }

        $result = [];
        foreach ($list as $row) {
            $itemId = $row['default_item_id'] ?: $row['item_id'];

            if (!isset($result[$itemId])) {
                $row['item_id'] = $itemId;
                $result[$itemId] = $row;
            }

            // 如果為多規格
            if (!$row['nospec'] || $row['nospec'] === 'false') {
                $result[$itemId]['spec_items'][] = $row;
            }
        }

        $res = [];
        foreach ($result as $value) {
            $res[] = $value;
        }
        return $res;
    }

    /**
     * 實體類商品獲取sku
     */
    public function getSkuItemsList($filter, $page = 1, $pageSize = 2000, $orderBy = ['item_id' => 'DESC'])
    {
        $page = ($page < 1) ? 1 : $page;
        $pageSize = ($pageSize > 2000) ? 2000 : $pageSize;
        $itemsList = $this->itemsRepository->list($filter, $orderBy, $pageSize, $page);
        if ($itemsList['total_count'] <= 0) {
            return $itemsList;
        }

        $itemsList = $this->replaceSkuSpec($itemsList);
        return $itemsList;
    }

    public function replaceSkuSpec($itemsList)
    {
        $itemIds = array_column($itemsList['list'], 'item_id');
        // 規格等數據
        $attrList = $this->itemRelAttributesRepository->lists(['item_id' => $itemIds, 'attribute_type' => 'item_spec'], 1, -1, ['attribute_sort' => 'asc']);
        $attrData = [];
        if ($attrList) {
            $itemsAttributesService = new ItemsAttributesService();
            $attrData = $itemsAttributesService->getItemsRelAttrValuesList($attrList['list']);
        }

        foreach ($itemsList['list'] as &$itemRow) {
            $itemRow['item_type'] = $itemRow['item_type'] ?: 'services';
            $itemtypeObject = new $this->itemsTypeClass[$itemRow['item_type']]();
            if (isset($itemRow['itemId']) && method_exists($itemtypeObject, 'listByItemId')) {
                $itemRow['type_labels'] = $itemtypeObject->listByItemId($itemRow['itemId'], $itemRow);
            } else {
                $itemRow['type_labels'] = [];
            }

            if (!$itemRow['default_item_id']) {
                $itemRow['default_item_id'] = $itemRow['item_id'];
            }
            if (isset($attrData['item_spec']) && isset($attrData['item_spec'][$itemRow['item_id']])) {
                $itemSpecStr = [];
                foreach ($attrData['item_spec'][$itemRow['item_id']] as $row) {
                    if ($row['item_image_url']) {
                        //列表頁商品圖片被替換成了自定義規格圖片，應要求取消掉替換
                        //$itemRow['pics'] = $row['item_image_url'] ?: $itemRow['pics'];
                    }
                    $itemRow['item_spec'][] = $row;
                    $itemSpecStr[] = $row['spec_name'] . ':' . $row['spec_value_name'];
                }
                $itemRow['item_spec_desc'] = implode(',', $itemSpecStr);
            }
        }
        return $itemsList;
    }

    /**
     * 根據商品ID新增權益
     */
    public function addRightsByItemId($itemId, $userId, $companyId, $mobile, $rightsFrom = null, $num = 1)
    {
        $itemsInfo = $this->getItemsSkuDetail($itemId);
        //如果不是服務商品，那麼則不能新增權益
        if ($itemsInfo['item_type'] != 'services') {
            return true;
        }

        $rightsObj = new RightsService(new TimesCardService());
        //商品核銷類型為團購券
        if ($itemsInfo['consume_type'] == 'all') {
            if ($itemsInfo['date_type'] == 'DATE_TYPE_FIX_TIME_RANGE') {
                $start_time = $itemsInfo['begin_date'];
                $end_time = $itemsInfo['end_date'];
            }
            if ($itemsInfo['date_type'] == 'DATE_TYPE_FIX_TERM') {
                $start_time = strtotime(date('Y-m-d 00:00:00', time()));
                $end_time = strtotime(date('Y-m-d 23:59:59', $start_time + 86400 * $itemsInfo['fixed_term']));
            }
            $labelInfos = [];
            foreach ($itemsInfo['type_labels'] as $v) {
                $labelInfos[] = ['label_id' => $v['labelId'], 'label_name' => $v['labelName']];
            }
            $data = [
                'user_id' => $userId,
                'company_id' => $itemsInfo['company_id'],
                'rights_name' => $itemsInfo['item_name'],
                'rights_subname' => '',
                'total_num' => $num,
                'total_consum_num' => 0,
                'start_time' => $start_time,
                'end_time' => $end_time,
                'order_id' => 0,
                'can_reservation' => false,
                'label_infos' => $labelInfos,
                'rights_from' => $rightsFrom ?: '注冊贈送',
                'mobile' => $mobile,
                'is_not_limit_num' => 2,
            ];
            $rightsObj->addRights($companyId, $data);
        } elseif ($itemsInfo['consume_type'] == 'every') {
            foreach ($itemsInfo['type_labels'] as $v) {
                $start_time = strtotime(date('Y-m-d 00:00:00', time()));
                $end_time = strtotime(date('Y-m-d 23:59:59', $start_time + 86400 * $v['limitTime']));
                $data = [
                    'user_id' => $userId,
                    'company_id' => $v['companyId'],
                    'rights_name' => $itemsInfo['item_name'],
                    'rights_subname' => $v['labelName'],
                    'total_num' => $v['num'] * $num,
                    'total_consum_num' => 0,
                    'start_time' => $start_time,
                    'end_time' => $end_time,
                    'order_id' => 0,
                    'can_reservation' => true,
                    'label_infos' => [['label_id' => $v['labelId'], 'label_name' => $v['labelName']]],
                    'rights_from' => $rightsFrom ?: '注冊贈送',
                    'mobile' => $mobile,
                    'is_not_limit_num' => $v['isNotLimitNum'],
                ];
                $rightsObj->addRights($companyId, $data);
            }
            return true;
        }
    }

    /**
     * 修改商品運費模板
     *
     * @param array params 提交的商品數據
     * @return array
     */
    public function setItemsTemplate($params)
    {
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            foreach ($params['item_id'] as $v) {
                $itemsInfo = $this->itemsRepository->get($v);

                if ($params['company_id'] != $itemsInfo['company_id']) {
                    throw new ResourceException('請確認您的商品信息後再提交.');
                }

                $itemsResult = $this->itemsRepository->updateBy(['default_item_id' => $v], ['templates_id' => $params['templates_id']]);
            }

            $conn->commit();
            return $itemsResult;
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }
    }

    /**
     * 修改商品商品分類
     *
     * @param array params 提交的商品數據
     * @return array
     */
    public function setItemsCategory($params)
    {
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            foreach ($params['item_id'] as $v) {
                $itemsInfo = $this->itemsRepository->get($v);

                if ($params['company_id'] != $itemsInfo['company_id']) {
                    throw new ResourceException('請確認您的商品信息後再提交.');
                }

                $itemsResult = $this->itemsRepository->setCategoryId($v, $params['category_id']);
            }
            $conn->commit();
            return $itemsResult;
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }
    }

    /**
     * 商品排序
     * @param $params 查詢條件
     * @param $sort 排序編號
     * @return mixed
     */
    public function setItemsSort($filter, $sort)
    {
        $itemsInfo = $this->itemsRepository->get($filter['item_id']);
        if ($filter['company_id'] != $itemsInfo['company_id']) {
            throw new ResourceException('請確認您的商品信息後再提交.');
        }
        $itemsResult = $this->itemsRepository->updateSort($filter['item_id'], $sort);
        return $itemsResult;
    }

    // 獲取商品分銷碼
    public function getDistributionGoodsWxaCode($wxaappid, $itemId, $distributorId, $isBase64 = 0)
    {
        $openPlatform = new OpenPlatform();
        $app = $openPlatform->getAuthorizerApplication($wxaappid);
        try {
            $data['page'] = 'pages/item/espier-detail';
            $scene = 'id=' . $itemId . '&dtid=' . $distributorId;
            $wxaCode = $app->app_code->getUnlimit($scene, $data);
        } catch (\Exception $e) {
            $data['page'] = 'pages/goodsdetail';
            $scene = 'id=' . $itemId . '&dtid=' . $distributorId;
            $wxaCode = $app->app_code->getUnlimit($scene, $data);
        }
        if ($isBase64) {
            $base64 = 'data:image/jpg;base64,' . base64_encode($wxaCode);
            return ['base64Image' => $base64];
        } else {
            return $wxaCode;
        }
    }

    /**
     * 獲取商品分銷碼的二維碼url
     * @param int $companyId 企業id
     * @param string $wxaAppid 微信小程序的appid
     * @param int $itemId 商品id
     * @param int $distributorId 店鋪id
     * @return string[]
     */
    public function getDistributionGoodsWxaCodeUrl(int $companyId, string $wxaAppid, int $itemId, int $distributorId)
    {
        $qrCodeContent = $this->getDistributionGoodsWxaCode($wxaAppid, $itemId, $distributorId, 0);
        // 上傳文件
        $uploadService = new UploadService($companyId, UploadTokenFactoryService::create("image"));
        $url = $uploadService->upload($qrCodeContent, UploadTokenAbstract::GROUP_DISTRIBUTOR_ITEM_QR_CODE) ? $uploadService->getUrl() : "";
        // 返回參數
        return ["url" => $url];
    }


    public function getItemListData($filter, $page = 1, $pageSize = 100, $orderBy = ['item_id' => 'DESC'], $isShowItemParams = false)
    {
        $listData['total_count'] = 0;
        $listData['list'] = [];

        $itemIds = [];
        $tagItemIds = [];
        if (isset($filter['item_id']) && $filter['item_id']) {
            $itemIds = $filter['item_id'];
            $tagItemIds = $itemIds;
        }

        // 根據商品分類id，獲取到對應的商品ID
        if (isset($filter['category_id']) && $filter['category_id']) {
            $itemIds = $this->getItemIdsByCategoryId($filter, $itemIds);
            if ($itemIds == -1 || !$itemIds) {
                return $listData;
            }
            $tagItemIds = $itemIds;
            unset($filter['category_id']);
        }
        // 根據商品參數刷選商品ID
        if (isset($filter['item_params']) && $filter['item_params']) {
            $itemIds = $this->getItemIdsByItemParamsId($filter, $itemIds);
            if ($itemIds == -1) {
                return $listData;
            }
            unset($filter['item_params']);
        }

        if (isset($filter['tag_id']) && $filter['tag_id']) {
            $itemIds = $this->getItemsIdByTags($filter, $itemIds);
            if (!$itemIds) {
                return $listData;
            }
            unset($filter['tag_id']);
        }

        if ($itemIds) {
            $filter['item_id'] = $itemIds;
        }

        $distributorService = new DistributorService();
        if (isset($filter['distributor_id']) && $filter['distributor_id']) {
            ##失效店鋪返回空
            $distributorFilter = [
                'company_id' => $filter['company_id'],
                'is_valid' => 'true',
                'distributor_id' => $filter['distributor_id']
            ];
            $distributorInfo = $this->distributorRepository->getInfo($distributorFilter);
            if (empty($distributorInfo)) {
                $listData = [
                    'total_count' => 0,
                    'list' => []
                ];
            } else {
                $distributorItemsService = new DistributorItemsService();
                $listData = $distributorItemsService->getDistributorRelItemList($filter, $pageSize, $page, $orderBy, false);
            }
        } else {
            $newFilter = $this->_filter($filter);
            if (!isset($newFilter['distributor_id'])) {
                $distributorFilter = [
                    'company_id' => $filter['company_id'],
                    'is_valid' => 'true'
                ];
                $validDistributorList = $distributorService->getDistributorOriginalList($distributorFilter, 1, -1);
                $validDistributorIds = array_column($validDistributorList['list'], 'distributor_id');
                $newFilter['distributor_id'] = array_merge(['0'], $validDistributorIds);
            }
            $listData = $this->itemsRepository->list($newFilter, $orderBy, $pageSize, $page);
        }
        if (isset($listData['list'])) {
            $distributorIds = array_filter(array_unique(array_column($listData['list'], 'distributor_id')));
            $distributorList = [];
            if ($distributorIds) {
                $distributorFilter = [
                    'company_id' => $filter['company_id'],
                    'distributor_id' => $distributorIds
                ];
                $distributorTempList = $distributorService->getDistributorOriginalList($distributorFilter, 1, -1);
                $distributorList = array_column($distributorTempList['list'], null, 'distributor_id');
            }
            foreach ($listData['list'] as $k => &$v) {
                $v['distributor_info'] = $distributorList[$v['distributor_id']] ?? [];
            }

            $listData['list'] = array_values($listData['list']);
        }
        if ($isShowItemParams) {
            $catFilter = $this->_filter($filter);
            $categorys = $this->itemsRepository->countItemsMainCatIdBy($catFilter);
            if ($categorys) {
                $mainCategoryId = $categorys[0]['item_category'];
                $selectList = $this->getItemSelectList($mainCategoryId, $filter);
                $listData['item_params_list'] = $selectList['item_params_list'] ?? [];
                $listData['select_address_list'] = $selectList['select_address_list'] ?? [];
            } else {
                $listData['item_params_list'] = [];
                $listData['select_address_list'] = [];
            }
        }

        $tagFilter = [
            "item_id" => array_column($listData['list'], "item_id"),
            "front_show" => 1,
            "company_id" => $filter['company_id']
        ];
        $tagList = $this->getItemTagList($tagFilter, $tagItemIds);
        $listData['select_tags_list'] = $tagList['select_tags_list'] ?? [];
        if (!empty($listData['select_tags_list'])) {
            $listData['select_tags_list'] = array_values(array_column($tagList['select_tags_list'], null, 'tag_id'));
        }
        $brandList = $this->getItemBrandList($filter);
        $listData['brand_list'] = $brandList['brand_list'] ?? [];
        return $listData;
    }

    public function getItemsIdByTags($filter, $itemIds)
    {
        $itemsTagsService = new ItemsTagsService();
        $tagFilter = ['company_id' => $filter['company_id'], 'tag_id' => $filter['tag_id']];
        if ($itemIds) {
            $tagFilter['item_id'] = $itemIds;
        }
        $itemIds = $itemsTagsService->getItemIdsByTagids($tagFilter);
        return $itemIds;
    }

    /**
     * 返回參數商品篩選
     *
     * array([
     *  'attribute_name' => '係列',
     *  'attribute_id' => 1,
     *  'values' => [
     *      ['name'=>'美白', 'attribute_value_id'=>2],
     *      ['name'=>'美白2', 'attribute_value_id'=>23],
     *  ],
     * ])
     */
    public function getItemSelectList($mainCategoryId, $filter)
    {
        unset($filter['brand_id']);
        unset($filter['tag_id']);
        $filter = $this->_filter($filter);
        if (isset($filter['distributor_id']) && $filter['distributor_id']) {
            $distributorItemsService = new DistributorItemsService();
            $listData = $distributorItemsService->getDistributorRelItemList($filter, 1000, 1, [], false, ['item_id', 'item_type', 'default_item_id', 'item_address_province']);
        } else {
            $listData = $this->itemsRepository->list($filter, [], 1000, 1, ['item_id', 'item_type', 'default_item_id', 'item_address_province']);
        }

        if ($listData['total_count'] <= 0) {
            return [];
        }
        // 產品產地
        $itemSelectList = [];
        $itemAddressProvince = [];
        $itemIds = [];
        foreach ($listData['list'] as $row) {
            if ($row['item_address_province']) {
                $itemAddressProvince[$row['item_address_province']] = $row['item_address_province'];
            }
            $itemIds[] = $row['item_id'];
        }
        if ($itemAddressProvince) {
            $itemSelectList['select_address_list'] = array_keys($itemAddressProvince);
        }

        // 獲取分類關聯的參數
        $itemsCategoryService = new ItemsCategoryService();
        $catInfo = $itemsCategoryService->getInfo(['category_id' => $mainCategoryId, 'is_main_category' => true]);
        if (!$catInfo || !$catInfo['goods_params']) {
            return $itemSelectList;
        }

        $relAttrFilter['item_id'] = $itemIds;
        $relAttrFilter['attribute_id'] = $catInfo['goods_params'];
        $relAttrFilter['attribute_type'] = "item_params";
        $list = $this->itemRelAttributesRepository->getItemRelAttributeBy($relAttrFilter);
        if (!$list) {
            return $itemSelectList;
        }
        foreach ($list as $row) {
            $data[$row['attribute_id']][] = $row['attribute_value_id'];
            $attributeValueIds[] = $row['attribute_value_id'];
            $attributeIds[] = $row['attribute_id'];
        }
        $itemsAttributesService = new ItemsAttributesService();
        $itemSelectList['item_params_list'] = $itemsAttributesService->getAttrValuesList($attributeIds, $attributeValueIds, $data);
        return $itemSelectList;
    }

    /**
     * 獲取商品品牌列表
     * @param $filter
     * @return array
     */
    public function getItemBrandList($filter)
    {
        $itemsAttributesService = new ItemsAttributesService();
        return $itemsAttributesService->getBrandList($filter);
    }

    /**
     * 獲取商品標簽列表
     * @param $filter
     * @return array
     */
    public function getItemTagList($filter, $tagItemIds = null)
    {
//        // 商品id不存在或為空，就直接返回
//        if (empty($filter["item_id"])) {
//            return [];
//        }
        // 如果店鋪id為null的話就不作為過濾條件
        if (isset($filter["distributor_id"]) && is_null($filter["distributor_id"])) {
            unset($filter["distributor_id"]);
        }
        // 過濾出指定條件的標簽列表
        $tagList = (new ItemsTagsService())->getItemsRelTagList($filter);
        return [
            "select_tags_list" => $tagList
        ];
    }

    /**
     * 獲取商品列表頁會員價
     */
    public function getItemsListMemberPrice($itemList, $userId, $companyId)
    {
        $itemIds = array_column($itemList['list'], 'item_id');
        $memberPriceService = new MemberPriceService();
        $filter = ['company_id' => $companyId, 'item_id' => $itemIds];
        $priceList = $memberPriceService->lists($filter);

        if (isset($userId) && $userId > 0) {
            $memberService = new MemberService();
            $userGradeData = $memberService->getValidUserGradeUniqueByUserId($userId, $companyId);
            $discount = $userGradeData['discount'];            //會員折扣參數
            $gradeId = $userGradeData['id'];                   //會員等級id
            $gradeName = $userGradeData['name'];
            $lvType = $userGradeData['lv_type'] ?? 'normal';   //會員等級類型 vip、svip、normal
        } else {
            $memberCardService = new MemberCardService();
            $defaultGradeInfo = $memberCardService->getDefaultGradeByCompanyId($companyId);
            if (!$defaultGradeInfo) {
                return $itemList;
            }
            $discount = $defaultGradeInfo['privileges']['discount'];    //會員折扣參數
            $gradeId = $defaultGradeInfo['grade_id'];                   //會員等級id
            $gradeName = $defaultGradeInfo['grade_name'];
            $lvType = 'normal';
        }

        $grade = ($lvType == 'normal') ? 'grade' : 'vipGrade';

        $vipGradeService = new VipGradeService();
        $vipGradeList = $vipGradeService->lists(['company_id' => $companyId, 'is_disabled' => false]);

        $newPrice = [];
        $vipPrice = [];
        foreach ($priceList['list'] as $priceRow) {
            $memberPrice = json_decode($priceRow['mprice'], true);
            // 是否有設置會員自定義價格
            if (isset($memberPrice[$grade][$gradeId]) && intval($memberPrice[$grade][$gradeId]) > 0) {
                $newPrice[$priceRow['item_id']] = intval($memberPrice[$grade][$gradeId]);
            }

            foreach ($vipGradeList as $vipGrade) {
                // 是否有設置自定義VIP價格
                if (isset($memberPrice['vipGrade'][$vipGrade['vip_grade_id']]) && intval($memberPrice['vipGrade'][$vipGrade['vip_grade_id']]) > 0) {
                    $vipPrice[$priceRow['item_id']][$vipGrade['lv_type']] = intval($memberPrice['vipGrade'][$vipGrade['vip_grade_id']]);
                }
            }
        }

        foreach ($itemList['list'] as &$item) {
            $item['member_grade_name'] = $gradeName;
            $itemId = $item['item_id'];
            if (isset($newPrice[$itemId]) && $newPrice[$itemId] > 0) {
                $item['member_price'] = $newPrice[$itemId];
            } elseif ($discount >= 0 && $discount < 100) {
                $item['member_price'] = $item['price'] - bcmul($item['price'], bcdiv($discount, 100, 2));
            }

            foreach ($vipGradeList as $vipGrade) {
                $vipDiscount = $vipGrade['privileges']['discount'];
                if (isset($vipPrice[$itemId][$vipGrade['lv_type']]) && $vipPrice[$itemId][$vipGrade['lv_type']] > 0) {
                    $item[$vipGrade['lv_type'].'_price'] = $vipPrice[$itemId][$vipGrade['lv_type']];
                } elseif ($vipDiscount >= 0 && $vipDiscount < 100) {
                    $item[$vipGrade['lv_type'].'_price'] = $item['price'] - bcmul($item['price'], bcdiv($vipDiscount, 100, 2));
                }
            }
        }

        return $itemList;
    }

    /**
     * 獲取商品詳情的會員價
     */
    public function getItemsMemberPriceByUserId($itemDetail, $userId, $companyId)
    {
        // 如果商品是單規格
        if ($itemDetail['nospec']) {
            $itemIds = $itemDetail['item_id'];
        } else {
            $itemIds = array_column($itemDetail['spec_items'], 'item_id');
        }

        //獲取購物車需要計算會員價的商品的會員價
        $memberPriceService = new MemberPriceService();
        $filter = ['company_id' => $companyId, 'item_id' => $itemIds];
        $priceList = $memberPriceService->lists($filter);

        //獲取會員當前的等級
        $memberService = new MemberService();
        if ($userId) {
            $userGradeData = $memberService->getValidUserGradeUniqueByUserId($userId, $companyId);
            // 如果沒有會員卡
            if (!$userGradeData) {
                return $itemDetail;
            }
            $discount = $userGradeData['discount'];            //會員折扣參數
            $gradeId = $userGradeData['id'];                   //會員等級id
            $gradeName = $userGradeData['name'];
            $lvType = $userGradeData['lv_type'] ?? 'normal';   //會員等級類型 vip、svip、normal
        } else {
            $memberCardService = new MemberCardService();
            $defaultGradeInfo = $memberCardService->getDefaultGradeByCompanyId($companyId);
            if (!$defaultGradeInfo) {
                return $itemDetail;
            }
            $discount = $defaultGradeInfo['privileges']['discount'];    //會員折扣參數
            $gradeId = $defaultGradeInfo['grade_id'];                   //會員等級id
            $gradeName = $defaultGradeInfo['grade_name'];
            $lvType = 'normal';
        }

        //當前會員等級
        $itemDetail['member_grade_name'] = $gradeName;

        //$lvType 為normal 表示普通會員等級，值為vip或者svip表示為付費會員等級
        // 當前會員等級類型
        $grade = ($lvType == 'normal') ? 'grade' : 'vipGrade';

        // 如果當前會員等級為普通會員等級，那麼需要獲取付費會員等級的價格，引導用戶購買付費會員
        $vipGradeService = new VipGradeService();
        $vipGradeList = $vipGradeService->lists(['company_id' => $companyId, 'is_disabled' => false]);

        $newPrice = [];
        $vipPrice = [];
        foreach ($priceList['list'] as $priceRow) {
            $memberPrice = json_decode($priceRow['mprice'], true);
            // 是否有設置會員自定義價格
            if (isset($memberPrice[$grade][$gradeId]) && intval($memberPrice[$grade][$gradeId]) > 0) {
                $newPrice[$priceRow['item_id']] = intval($memberPrice[$grade][$gradeId]);
            }

            foreach ($vipGradeList as $vipGrade) {
                // 是否有設置自定義VIP價格
                if (isset($memberPrice['vipGrade'][$vipGrade['vip_grade_id']]) && intval($memberPrice['vipGrade'][$vipGrade['vip_grade_id']]) > 0) {
                    $vipPrice[$priceRow['item_id']][$vipGrade['lv_type']] = intval($memberPrice['vipGrade'][$vipGrade['vip_grade_id']]);
                }
            }
        }

        $itemDetail = $this->__replaceItemMemberPrice($itemDetail, $newPrice, $discount, $vipPrice, $vipGradeList);
        foreach (($itemDetail['spec_items'] ?? []) as $k => $specItem) {
            $itemDetail['spec_items'][$k] = $this->__replaceItemMemberPrice($specItem, $newPrice, $discount, $vipPrice, $vipGradeList);
        }
        // 會員價格不為空，重新計算稅費
        if (!empty($itemDetail['member_price']) and $itemDetail['type'] == 1) {
            $ItemTaxRateService = new ItemTaxRateService($companyId);
            $ItemTaxRate = $ItemTaxRateService->getItemTaxRate($itemDetail['item_id'], $itemDetail['member_price']);
            $itemDetail['cross_border_tax_rate'] = $ItemTaxRate['tax_rate'];
            $itemDetail['cross_border_tax'] = bcdiv(bcmul($itemDetail['price'], $itemDetail['cross_border_tax_rate'], 0), 100, 0);
        }

        return $itemDetail;
    }

    private function __replaceItemMemberPrice($item, $newPrice, $discount, $vipPrice, $vipGradeList)
    {
        $itemId = $item['item_id'];

        if (isset($newPrice[$itemId]) && $newPrice[$itemId] > 0) {
            $item['member_price'] = $newPrice[$itemId];
        } elseif ($discount >= 0 && $discount < 100) {
            $item['member_price'] = $item['price'] - bcmul($item['price'], bcdiv($discount, 100, 2));
        }

        foreach ($vipGradeList as $vipGrade) {
            $vipDiscount = $vipGrade['privileges']['discount'];
            if (isset($vipPrice[$itemId][$vipGrade['lv_type']]) && $vipPrice[$itemId][$vipGrade['lv_type']] > 0) {
                $item[$vipGrade['lv_type'].'_price'] = $vipPrice[$itemId][$vipGrade['lv_type']];
            } elseif ($vipDiscount >= 0 && $vipDiscount < 100) {
                $item[$vipGrade['lv_type'].'_price'] = $item['price'] - bcmul($item['price'], bcdiv($vipDiscount, 100, 2));
            }
        }

        return $item;
    }

    /**
     * 商品條件過濾
     */
    public function _filter($filter, $distributor = false)
    {
        $filterCols = ['item_id', 'is_point', 'approve_status', 'company_id', 'approve_status', 'item_type', 'is_default', 'regions_id', 'goods_id', 'distributor_id', 'brand_id', 'rebate', 'price', 'item_category', 'rebate_type', 'audit_status', 'type', 'item_name','item_bn', 'keywords', 'default_item_id', 'is_gift'];

        // 處理price|gte
        $_filterCols = [];
        foreach ($filter as $key => $value) {
            $list = explode('|', $key);
            if (count($list) > 1 && in_array($list[0], $filterCols)) {
                $_filterCols[$list[0]][$list[1]] = $value;
            }
        }

        foreach ($filterCols as $col) {
            $list = explode('|', $col);
            if (count($list) > 1 && !isset($filter[$list[0]])) {
                continue;
            }

            if (isset($filter[$col])) {
                $newfilter[$col] = $filter[$col];
            }
            // 處理price|gte
            if (isset($_filterCols[$col])) {
                foreach ($_filterCols[$col] as $key => $value) {
                    $newfilter[$col . '|' . $key] = $value;
                }
            }
        }

        if (isset($filter['item_id']) && empty($filter['item_id'])) {
            unset($newfilter['item_id']);
        }

        if (isset($filter['item_name']) && $filter['item_name']) {
            $newfilter['item_name|contains'] = $filter['item_name'];
            unset($newfilter['item_name']);
        }

        if (isset($filter['keywords']) && $filter['keywords']) {
            $newfilter['or'] = [
                'item_name|contains' => $filter['keywords'],
                'item_bn|contains' => $filter['item_bn'],
            ];
            unset($newfilter['keywords']);
        }

        return $newfilter;
    }

    /**
     * 根據商品參數刷選商品ID
     */
    public function getItemIdsByItemParamsId($filter, $itemIds = [])
    {
        $attributeValueIds = [];
        foreach ($filter['item_params'] as $row) {
            if ($row['attribute_value_id'] == 'all') {
                continue;
            }
            $attributeIds[$row['attribute_id']] = 1;
            $attributeValueIds[] = $row['attribute_value_id'];
        }

        if ($attributeValueIds) {
            $companyId = $filter['company_id'] ?? null;
            $ids = $this->itemRelAttributesRepository->getItemdsByAttrValIds($attributeValueIds, $attributeIds, $companyId);
            if ($ids) {
                if ($itemIds) {
                    $itemIds = array_intersect($itemIds, $ids);
                } else {
                    $itemIds = $ids;
                }
            } else {
                $itemIds = -1;
            }

            if (!$itemIds) {
                $itemIds = -1;
            }
        }

        return $itemIds;
    }

    /**
     * 根據商品分類獲取商品id集合，非主類目
     */
    public function getItemIdsByCategoryId($filter, $itemIds = [])
    {
        $itemsCategoryService = new ItemsCategoryService();
        $tmpItemIds = $itemsCategoryService->getItemIdsByCatId($filter['category_id'], $filter['company_id']);
        if (!$tmpItemIds) {
            return -1;
        }

        if ($itemIds) {
            $itemIds = array_intersect($itemIds, $tmpItemIds);
        } else {
            $itemIds = $tmpItemIds;
        }

        if (!$itemIds) {
            return -1;
        }

        return $itemIds;
    }

    public function getItemCount($filter)
    {
        return $this->itemsRepository->count($filter);
    }

    public function simpleUpdateBy($filter, $params)
    {
        return $this->itemsRepository->simpleUpdateBy($filter, $params);
    }

    /**
     * 從請求參數構造查詢條件
     * @param $inputData
     * @param $companyId
     * @return array | false
     */
    public function exportParams($inputData, $companyId)
    {
        $params = ['company_id' => $companyId];
        if (isset($inputData['item_name']) && $inputData['item_name']) {
            $params['item_name|contains'] = $inputData['item_name'];
        }
        if (isset($inputData['consume_type']) && $inputData['consume_type']) {
            $params['consume_type'] = $inputData['consume_type'];
        }
        if (isset($inputData['templates_id']) && $inputData['templates_id']) {
            $params['templates_id'] = $inputData['templates_id'];
        }
        if (isset($inputData['regions_id']) && $inputData['regions_id']) {
            $params['regions_id'] = implode(',', $inputData['regions_id']);
        }
        if (isset($inputData['keywords']) && $inputData['keywords']) {
            $params['item_name|contains'] = trim($inputData['keywords']);
        }

        if (isset($inputData['nospec'])) {
            $params['nospec'] = $inputData['nospec'];
        }

        if (isset($inputData['distributor_id']) && $inputData['distributor_id']) {
            $distributorId = $inputData['distributor_id'];
        } else {
            $distributorId = 0;
        }
        $params['distributor_id'] = $distributorId;

        if (isset($inputData['approve_status']) && $inputData['approve_status']) {
            if (in_array($inputData['approve_status'], ['processing', 'rejected'])) {
                $params['audit_status'] = $inputData['approve_status'];
            } else {
                $params['approve_status'] = $inputData['approve_status'];
            }
        }

        if (isset($inputData['rebate']) && in_array($inputData['rebate'], [1, 0,2,3])) {
            $params['rebate'] = $inputData['rebate'];
        }
        if (isset($inputData['rebate_type']) && $inputData['rebate_type']) {
            $params['rebate_type'] = $inputData['rebate_type'];
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
                return false;
            }

            if (isset($params['item_id'])) {
                $params['item_id'] = array_intersect($params['item_id'], $ids);
            } else {
                $params['item_id'] = $ids;
            }
        }

        $params['item_type'] = $inputData['item_type'] ?? 'services';

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
                return false;
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
                return false;
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
        return $params;
    }

    public function updateItemsStore($companyId, $params)
    {
        $itemStoreService = new ItemStoreService();
        foreach ((array)$params as $data) {
            if (isset($data['is_default']) && $data['is_default'] == 'true') {
                $filter['company_id'] = $companyId;
                $filter['default_item_id'] = $data['item_id'];
                $this->itemsRepository->updateBy($filter, ['store' => $data['store']]);
                $itemlist = $this->itemsRepository->getItemsLists($filter);
                foreach ($itemlist as $value) {
                    $itemStoreService->saveItemStore($value['item_id'], $data['store']);
                }
            } else {
                $this->itemsRepository->updateStore($data['item_id'], $data['store']);
                $itemStoreService->saveItemStore($data['item_id'], $data['store']);
            }
        }
        return true;
    }

    public function updateItemsStatus($companyId, $items, $status)
    {
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        foreach ((array)$items as $data) {
            $filter['company_id'] = $companyId;
            $filter['goods_id'] = $data['goods_id'];
            $this->itemsRepository->updateBy($filter, ['approve_status' => $status]);
            $eventData = [
                'company_id' => $companyId,
                'goods_id' => $data['goods_id'],
                'approve_status' => $status,
            ];
            event(new ItemBatchEditStatusEvent($eventData));
        }
        return true;
    }

    public function getItemsListActityTag($itemList, $companyId)
    {
        $goodsIds = array_column($itemList['list'], 'goods_id');
        if ($goodsIds) {
            $promotionItemTagService = new PromotionItemTagService();
            $list = $promotionItemTagService->getPromotions($itemList['list'], $companyId, ['activity_price' => 'desc']);
            foreach ($list as $value) {
                if ($value['is_all_items'] == '2') {//指定商品適用
                    $newTags[$value['goods_id']][$value['promotion_id']] = [
                        'promotion_id' => $value['promotion_id'],
                        'tag_type' => $value['tag_type'],
                        'activity_price' => $value['activity_price'],
                        'start_time' => $value['start_time'],
                        'end_time' => $value['end_time'],
                        'item_id' => $value['item_id'],
                    ];
                } else {//適用任意商品
                    $newTags['all'][$value['promotion_id']] = [
                        'promotion_id' => $value['promotion_id'],
                        'tag_type' => $value['tag_type'],
                        'activity_price' => $value['activity_price'],
                        'start_time' => $value['start_time'],
                        'end_time' => $value['end_time'],
                        'item_id' => $value['item_id'],
                    ];
                }
            }

            $allTagType = [
                'full_discount',
                'full_minus',
                'full_gift',
                'multi_buy',
                'self_select',
                'plus_price_buy',
                'member_preference',
            ];

            $marketingService = new MarketingActivityService();
            foreach ($itemList['list'] as &$items) {
                if (!isset($items['goods_id'])) {
                    continue;
                }
                $promotion_activity = $newTags[$items['goods_id']] ?? [];
                if (isset($newTags['all']) && $newTags['all']) {
                    $promotion_activity = array_merge($promotion_activity, $newTags['all']);
                }
                foreach ($promotion_activity as $data) {
                    $itemDistributorId = $items['distributor_id'] ?? 0;//商品所屬的店鋪
                    if (in_array($data['tag_type'], $allTagType)) {
                        $marketingActivity = $marketingService->getInfoById($data['promotion_id']);
                        if ($itemDistributorId > 0) {
                            if (!$marketingActivity) {
                                continue;
                            }
                            if (!isset($marketingActivity['shop_ids'])) {
                                continue;
                            }
                            //判斷是否在店鋪範圍內
                            $shopIds = array_filter($marketingActivity['shop_ids']);
                            if ($shopIds && !in_array($itemDistributorId, $shopIds)) {
                                continue;
                            }
                            //店鋪和平台的促銷各自獨立
                            if ($itemDistributorId != $marketingActivity['source_id']) {
                                continue;
                            }
                        } else {
                            if (!$marketingActivity) {
                                continue;
                            }
                            if (!isset($marketingActivity['shop_ids'])) {
                                continue;
                            }
                            // 判斷總店商品是否滿足促銷活動
                            $shopIds = $marketingActivity['shop_ids'];
                            $shopIdsKey = array_flip($shopIds);
                            if ($shopIds && !array_key_exists($itemDistributorId, $shopIdsKey)) {
                                continue;
                            }
                        }
                    }

                    $items['promotion_activity'][] = $data;
                    if (in_array($data['tag_type'], ['single_group', 'normal', 'limited_time_sale'])) {
                        $items['activity_price'] = $data['activity_price'];
                    }
                    $itemIds[] = $data['item_id'];
                }
            }
            // 解決代客下單是，商品設置了店鋪發貨，走的還是總部發貨邏輯 先注釋下麵的邏輯
            /*if ($itemIds ?? null) {
                $datalists = $this->itemsRepository->getItemsLists(['item_id' => $itemIds], 'market_price,price,goods_id');
                $datalists = array_column($datalists, null, 'goods_id');
                foreach ($itemList['list'] as $k => $items_info) {
                    if (isset($datalists[$items_info['goods_id']])) {
                        $itemList['list'][$k] =  array_merge($datalists[$items_info['goods_id']], $itemList['list'][$k]);
                    }
                }
            }*/
        }

        return $itemList;
    }

    public function getWxaItemCodeStream($companyId, $itemId, $isBase64 = 0)
    {
        $weappService = new WeappService();
        $wxaappid = $weappService->getWxappidByTemplateName($companyId);
        if (!$wxaappid) {
            return '';
        }
        $openPlatform = new OpenPlatform();
        $app = $openPlatform->getAuthorizerApplication($wxaappid);
        $data['page'] = 'pages/goodsdetail';
        $scene = 'id=' . $itemId;
        $wxaCode = $app->app_code->getUnlimit($scene, $data);
        if ($isBase64) {
            $base64 = 'data:image/jpg;base64,' . base64_encode($wxaCode);
            return ['base64Image' => $base64];
        } else {
            return $wxaCode;
        }
    }

    public function getItemsListBrandData($itemList, $companyId)
    {
        $brandId = array_column($itemList, 'brand_id');
        $itemsAttributesService = new ItemsAttributesService();
        $brandId = array_filter($brandId);
        if (!$brandId) {
            return $itemList;
        }
        $filter['attribute_id'] = $brandId;
        $filter['company_id'] = $companyId;
        $filter['attribute_type'] = 'brand';
        $brandlist = $itemsAttributesService->getLists($filter);
        if (!$brandlist) {
            return $itemList;
        }
        $brandlist = array_column($brandlist, null, 'attribute_id');
        foreach ($itemList as &$list) {
            $list['brand_logo'] = $brandlist[$list['brand_id']]['image_url'] ?? '';
            $list['brand_name'] = $brandlist[$list['brand_id']]['attribute_name'] ?? '';
        }
        return $itemList;
    }

    public function getGoodsByCoupon($company_id, $coupon_id, $pageSize = 1000, $page = 1)
    {
        $filter = [
            'company_id' => $company_id,
            'card_id' => $coupon_id
        ];
        $relItems = $this->kaquanRelItem->lists($filter, [], 1000, 1);
        if ($relItems) {
            $relItemsId = [];
            foreach ($relItems as $relItem) {
                if ($relItem['item_id']) {
                    array_push($relItemsId, $relItem['item_id']);
                }
            }
            $filter = [
                'company_id' => $company_id,
                'item_id' => $relItemsId
            ];
            $items = $this->itemsRepository->list($filter, [], $pageSize, $page);
        } else {
            //全場券
            $items = [
                'data' => [
                    'total_count' => 0,
                    'list' => []
                ]
            ];
        }

        return $items;
    }

    /**
     * 批量設置商品為贈品或非贈品，商品列表使用
     * @param $companyId
     * @param $goodsIds
     * @param $status
     * @return bool
     */
    public function batchUpdateItemGift($companyId, $goodsIds, $status)
    {
        try {
            $filter['company_id'] = $companyId;
            $filter['goods_id'] = $goodsIds;
            //設置為非贈品,非0商品不能修改
            if ($status == 'false') {
                $filter['price|gt'] = 0;
            }
            if ($status == 'false') {
                // 查詢所有item_id
                $skuLists = $this->getSkuItemsList($filter, 1, -1);
                $skuSelectLists = $this->getSkuItemsList(['company_id' => $companyId, 'goods_id' => $goodsIds], 1, -1);
                if (count($skuLists['list']) != count($skuSelectLists['list'])) {
                    throw new ResourceException('存在價格設置為0元的商品無法設置為非贈品，請檢查後再次提交');
                }
            }

            if ($status == 'true') {
                // 查詢所有item_id
                $skuLists = $this->getSkuItemsList($filter, 1, -1);
                if (!$skuLists['list']) {
                    return false;
                }
                $item_ids = array_column($skuLists['list'], 'item_id');
                // 去檢查是否有未結束的營銷活動
                $this->checkNotFinishedActivityValid($companyId, $item_ids, $goodsIds);
            }
            $data['is_gift'] = $status == 'true' ? 1 : 0;
            $this->updateBy($filter, $data);
        } catch (\Exception $e) {
            throw new ResourceException($e->getMessage());
        }
        return true;
    }


    /**
     * 檢查活動商品，是否存在贈品
     * @param $company_id string 企業ID
     * @param $item_ids array skuId
     * @return bool true:存在，false:不存在
     */
    public function __checkIsGiftItem($company_id, $item_ids)
    {
        $filter = [
            'company_id' => $company_id,
            'item_id' => $item_ids,
            'is_gift' => 1,
        ];
        $cols = 'item_id,is_gift';
        $item_lists = $this->getItemsLists($filter, $cols);
        if ($item_lists) {
            return true;
        }
        return false;
    }

    /**
     * 修改商品價格、庫存、上下架狀態
     * @param array $filter 更新條件
     * @param array $params 更新數據
     * @return
     */
    public function updateItemsPriceStoreStatus($filter, $params)
    {
        if (is_numeric($params['price'] ?? null)) {
            $itemInfo = $this->getInfo($filter);
            if ($params['price'] <= 0 && !$itemInfo['is_gift']) {
                throw new ResourceException('非贈品商品銷售價必須大於0');
            }
            $goodsIds[] = $itemInfo['goods_id'];
            $itemPrices[$filter['item_id']] = $params['price'];
            $this->checkItemPrice($filter['company_id'], $goodsIds, $itemPrices);
        }
        return $this->updateBy($filter, $params);
    }

    /**
     * 檢查會員是否可分享--管理端的商品分享設置
     * @param string $companyId 企業ID
     * @param string $userId 會員ID
     * @return bool
     */
    public function checkUserItemShare($companyId, $userId)
    {
        $result = ['status' => false];
        $companysSettingService = new CompanysSettingService();
        $itemShareSetting = $companysSettingService->getItemShareSetting($companyId);
        if ($itemShareSetting['is_open'] == false) {
            $result['status'] = true;
            return $result;
        }
        $memberService = new MemberService();
        $filter = [
            'company_id' => $companyId,
            'user_id' => $userId,
        ];
        $memberInfo = $memberService->getMemberInfo($filter, true);
        if (in_array($memberInfo['grade_id'], $itemShareSetting['valid_grade'])) {
            $result['status'] = true;
        }
        if ($result['status'] == true) {
            return $result;
        }
        //獲取付費會員卡信息
        $vipGradeService = new VipGradeOrderService();
        $vipgrade = $vipGradeService->userVipGradeGet($companyId, $userId, true);
        foreach ($vipgrade as $gradeType => $grade) {
            if (in_array($gradeType, $itemShareSetting['valid_grade'])) {
                $result['status'] = true;
                break;
            }
        }
        if ($result['status'] == false) {
            $result['msg'] = $itemShareSetting['msg'];
            $result['page'] = $itemShareSetting['page'];
        }
        return $result;
    }

    /**
     * 處理商品列表顯示價格
     * @param $result
     * @return mixed
     */
    public function dealListStore($result)
    {
        foreach ($result['list'] as $key => &$v) {
            $skuList = $this->itemsRepository->getLists(['company_id' => $v['company_id'], 'goods_id' => $v['goods_id']]);
            $v['store'] = 0;
            foreach ($skuList as $key => $value) {
                $v['store'] += $value['store'];
            }
        }
        return $result;
    }

    /**
     * 商品列表查詢的條件處理
     * @param  array $filter 查詢條件
     * @return array
     */
    public function __formateGetListFilter($filter)
    {
        $merchantService = new MerchantService();
        $filter = $merchantService->__formateFilter($filter);
        return $filter;
    }

    public function getDistributorSkuItemsList($filter, $page = 1, $pageSize = 2000, $orderBy = ['item_id' => 'DESC']) {

        $page = ($page < 1) ? 1 : $page;
        $pageSize = ($pageSize > 2000) ? 2000 : $pageSize;
        $distributorItemsService = new DistributorItemsService();
        $itemsList = $distributorItemsService->getDistributorSkuItemsList($filter, $page, $pageSize, $orderBy);
        if ($itemsList['total_count'] <= 0) {
            return $itemsList;
        }

        $itemsList = $this->replaceSkuSpec($itemsList);
        return $itemsList;
    }

    private function syncGoods($companyId, $itemId) {
        $distributorItemsService = new DistributorItemsService();
        $distributorService = new DistributorService();
        $list = $distributorService->getLists(['company_id' => $companyId, 'is_valid|neq' => 'delete', 'auto_sync_goods' => true], 'distributor_id');
        foreach ($list as $row) {
            $createData = [
                'company_id' => $companyId,
                'distributor_id' => $row['distributor_id'],
                'item_ids' => [$itemId],
                'is_can_sale' => false,
            ];
            $distributorItemsService->createDistributorItems($createData);
        }
    }

    /**
     * Dynamically call the KaquanService instance.
     *
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->itemsRepository->$method(...$parameters);
    }
}
