<?php

namespace WsugcBundle\Services;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use GuzzleHttp\Client as Client;

use OrdersBundle\Services\ShippingTemplatesService;
use KaquanBundle\Services\VipGradeService;
use KaquanBundle\Services\MemberCardService;
use PromotionsBundle\Services\MemberPriceService;
use GoodsBundle\Services\ItemsCategoryService;
use GoodsBundle\Services\ItemsService;
use GoodsBundle\Services\ItemsProfitService;
use GoodsBundle\Entities\ItemsCategory;
use GoodsBundle\Services\ItemsAttributesService;
use EspierBundle\Services\Export\Items as ItemExport;
use GoodsBundle\Services\ItemStoreService;


class MpsFeedUploadService
{
    const MEMBER_PRICE_KEY = '会员价'; //忽略的字段，不导入


    public $memberPriceHeaderReady = false; //会员价表头已经加载

    public $spu_bn = null; //商品编号

    public $itemName = null;

    public $defaultItemId = null;

    //图片轮播
    public $defaultItemPic = [];

    public $header = [
        'Unique_ID' => 'Unique_ID',
        'Title' => 'Title',
        'Description' => 'Description',
        'Price_including_taxes' => 'Price_including_taxes',
        'Barred_price' => 'Barred_price',
        'Category' => 'Category',
        'Subcategory1' => 'Subcategory1',
        'Subcategory2' => 'Subcategory2',
        'Product_URL' => 'Product_URL',
        'Image_URL' => 'Image_URL',
        'MPN' => 'MPN',
        'Brand' => 'Brand',
        'Delivery_costs' => 'Delivery_costs',
        'Delivery_time' => 'Delivery_time',
        'Delivery_Description' => 'Delivery_Description',
        'Quantity_in_stock' => 'Quantity_in_stock',
        'Warranty' => 'Warranty',
        'Size' => 'Size',
        'Colour' => 'Colour',
        'Material' => 'Material',
        'Gender' => 'Gender',
        'Currency' => 'Currency',
        'Parent_ID' => 'Parent_ID',
        'Image_Small_URL'  => 'Image_Small_URL',
        'Image_Medium_URL ' => 'Image_Medium_URL',
        'Image_Big_URL' => 'Image_Big_URL',
        'Variant_type' => 'Variant_type',
        'Product_Type' => 'Product_Type',
        'EAN' => 'EAN',
        'New' => 'New',
        'Availability_Date' => 'Availability_Date',
        'Family_Code' => 'Family_Code',
        'Subfamily_code' => 'Subfamily_code',
        'MOCACO' => 'MOCACO',
        'Future_Price' => 'Future_Price',
        'Cares' => 'Cares',
        'Join_Life' => 'Join_Life',
        'Stock_TAG' => 'Stock_TAG',
        'Product_TAG' => 'Product_TAG',
        'COLORCUT' => 'COLORCUT',
        'Garment_Detail' => 'Garment_Detail',
        'Section' => 'Section',
        'Categories' => 'Categories',
    ];

    function __construct()
    {
        $this->itemsCategoryServiceCommon = new ItemsCategoryService();
    }

    public $headerInfo = [
        // '串码' => ['size' => 128, 'remarks' => '不得重复', 'is_need' => true],
    ];

    public $isNeedCols = [
        //'开卡门店' => 'shop_name',
    ];
    public $allApproveStatus = [
        '前台可销售' => 'onsale',
        '可线下销售' => 'offline_sale',
        '不可销售' => 'instock',
        '前台仅展示' => 'only_show',
    ];
    protected function preRowHandle($column, $row)
    {
        $data = [];
        foreach ($column as $key => $col) {
            if (isset($row[$key])) {
                $data[$col] = trim($row[$key]);
            } else {
                $data[$col] = null;
            }
        }
        return $data;
    }
    /**
     * 验证上传的会员信息
     */
    public function check($fileObject)
    {
        $extension = $fileObject->getClientOriginalExtension();
        if ($extension != 'csv') {
            throw new BadRequestHttpException('串码信息上传只支持csv文件格式');
        }
    }

    public $tmpTarget = null;

    /**
     * getFilePath function
     *
     * @return void
     */
    public function getFilePath($filePath, $fileExt = '')
    {
        if (env('DISK_DRIVER') != 'local') {
            $url = $this->getFileSystem()->privateDownloadUrl($filePath);
            $client = new Client();
            $content = $client->get($url)->getBody()->getContents();
        } else {
            //本地用这个
            $content = file_get_contents(storage_path('uploads/' . $filePath));

            // $content = file_get_contents(storage_path('uploads/mps_feed/'.date('Y-m-d').'/'. 'lengow_dutticn.csv') );
            //print_r($content);exit;
        }

        //print_r($content);exit;

        $this->tmpTarget = tempnam('/tmp', 'import-file') . $fileExt;
        file_put_contents($this->tmpTarget, $content);

        return $this->tmpTarget;
    }

    public function finishHandle()
    {
        unlink($this->tmpTarget);
        return true;
    }


    public function getFileSystem()
    {
        return app('filesystem')->disk('import-file');
    }

    /**
     * 获取头部标题
     */
    public function getHeaderTitle()
    {
        //
        return ['all' => $this->header, 'is_need' => $this->isNeedCols, 'headerInfo' => $this->headerInfo];
    }

    private function validatorData($row)
    {
        $arr = ['item_name', 'price', 'templates_id'];
        $data = [];
        foreach ($arr as $column) {
            if (isset($row[$column])) {
                $data[$column] = trim($row[$column]);
            }
        }

        return $data;
    }
    public function handleRow($companyId, $row)
    {
        //app('log')->debug("\n _uploadItems handleRow =>:".json_encode($row, 256));
        //print 11;
        if (trim($row['Price_including_taxes']) == "") {
            //当前行 是 空的，离开
            // $this->spu_bn=$row['']
            return true;
        }
        //print_r($row);exit;
        $errormsg = '';
        $this->getFormatGoodsRowData($row, $errormsg);
        //支持导入更新商品数据
        $row['goods_id'] = false;
        $row['item_id']  = false;

        if ($row['item_bn'] ?? null) {
            $this->addBnToCache($row['item_bn']);
            
            $filter = ['item_bn' => $row['item_bn'], 'company_id' => $companyId];


            $itemsService = new ItemsService();
            $oldItemInfo = $itemsService->getItem($filter);
            if ($oldItemInfo) {
                $row['default_item_id'] = $oldItemInfo['default_item_id'];
                $row['goods_id'] = $oldItemInfo['goods_id'];
                $row['item_id'] = $oldItemInfo['item_id']; //如果存在，更新商品数据

                if ($row['distributor_id'] != $oldItemInfo['distributor_id']) {
                    throw new BadRequestHttpException('商品编码已存在其他店铺中，不能更新');
                }

                $this->updateGoods($companyId, $row, $oldItemInfo);
                return;
            }
            $this->createGoods($companyId, $row);
        } else {
            throw new BadRequestHttpException('格式化数据不完整：' . $errormsg);
        }
    }
    /**
     * 获取item_bn function
     *
     * @param [type] $row
     * @return void
     */
    function getFormatGoodsRowData(&$row, &$errormsg)
    {
        try {
            $unique_id = explode('-', $row['Unique_ID']);
            $newrow['item_bn']    = $unique_id[0];

            $row['Image_URL'] = str_replace('http:', 'https:', stripslashes($row['Image_URL']));
            $newrow['pics'] = str_replace(['|', 'http:'], [',', 'https:'], $row['Image_URL']);
            // $tmpic=explode(',',$newrow['pics']);
            // foreach($tmpic as $k=>$v){
            //     $file = @file_get_contents($v);
            //     if(!$file){
            //         unset($tmpic[$k]);
            //     }
            // }
            //扫描一下首图是白图，去掉
            //$newrow['pics']=implode(',',$tmpic);
            
            $row['COLORCUT'] = str_replace('http:', 'https:', $row['COLORCUT']);
            //app('log')->debug("\n getFormatGoodsRowData Image_URL newrowpics=>:" . var_export($newrow['pics'],true).'|行号：'.__LINE__);
            $newrow['item_name'] = $row['Title'];
            $newrow['price'] = $row['Price_including_taxes'];
            $newrow['market_price'] = $row['Barred_price'];
            $newrow['cost_price'] = 0;
            $newrow['item_unit'] = '';
            $newrow['intro'] = $row['Description']; //介绍
            //分类信息

            //库存不要同步
            //$newrow['store']=$row['Quantity_in_stock']??0;

            //分类
            //分类里放到的是category_code分割开的:123123,123213
            //取其中存在的一个三级分类，作为主类目，其他的，全部转换为三级分类
            //app('log')->debug("\n getFormatGoodsRowData 组织导入商品数据 allCategories=>:" . var_export($row['Categories'], true) . '|行号：' . __LINE__);

            //print_r($row);
            $allCategories = $this->getAllCategories($row['Categories']);


            $allCategoriesMain = $this->getAllCategories($row['Categories'],1,1);

            //app('log')->debug("\n getFormatGoodsRowData 组织导入商品数据allCategories =>:" . var_export($allCategories, true) . '|行号：' . __LINE__);

            $newrow['item_main_category'] = $allCategoriesMain[0]; //str_replace(' > ','->',$row['Categories']);
            //$newrow['item_main_category']=;//str_replace(' ','',$newrow['item_main_category']);
            //主类目
            //$tmpCategory=explode('->', $newrow['item_main_category']);
            $companyId = 1;
            // $this->itemsCategoryRepository = app('registry')->getManager('default')->getRepository(ItemsCategory::class);
            //$this->autoCreateThirdCategory($tmpCategory, $companyId);
            //分类
            $category_codes = explode('|', $row['Categories']);
            if(in_array('1866507', $category_codes)){
                $allCategories=array_merge($allCategories,['女士->本周新品']);
            }
            else if(in_array('1887002', $category_codes)){
                $allCategories=array_merge($allCategories,['女士->秋季折扣']);
            }
            else if(in_array('1866501', $category_codes)){
                $allCategories=array_merge($allCategories,['男士->本周新品']);
            }
            //$allCategoriesSecond = $this->getAllCategoriesSecond($row['Categories']);
           // if($allCategoriesSecond){
                //2个合并2022-11-15 23:28:17
               // $allCategories=array_merge($allCategories,$allCategoriesSecond);
            //}
            $newrow['item_category'] = implode('|', $allCategories); // $newrow['item_main_category'];//$tmpCategory[0];//str_replace(' > ','->',$row['Category']);

            //简介
            $newrow['brief'] = '';
            $newrow['videos'] = '';
            $newrow['distributor_id'] = 0;
            $newrow['item_params'] = '';
            $newrow['weight'] = 0;
            $newrow['barcode'] = '';
            //运费模板
            $newrow['templates_id'] = "包邮"; //运费模板固定1
            //品牌
            $newrow['goods_brand'] = 'Massimo Dutti';

            //性别
            $newrow['mps_gender'] = $row['Gender'];

            //分润信息，不分
            $newrow['is_profit'] = false;
            $newrow['profit_type'] = 0;

            //规格描述        
            $tmpSpec = [];
            if (trim($row['Colour'])) {
                $tmpSpec[] = '颜色:' . $row['Colour'];
                $newrow['mps_color'] = $row['Colour'];
            }
            if ($row['Size']) {
                $tmpSpec[] = '尺码:' . $row['Size'];
                $newrow['mps_size'] = $row['Size'];
            }

            $newrow['item_spec'] = implode('|', $tmpSpec);

            //前台不可销售，因为库存问题。
            $newrow['approve_status'] = '不可销售';

            //SPU-空行的那个读不到，通过拆分获取
            $newrow['mps_spu_bn'] = substr($newrow['item_bn'], 0, -5); //.'-'.$unique_id[1];

            //季节
            $newrow['mps_season'] = $unique_id[1];
            //材质
            $newrow['mps_material'] = $row['Material'];
            //注意事项
            $newrow['mps_cares'] = $row['Cares'];

            //new的意思
            $newrow['mps_new'] = $row['New'];

            //配送说明
            $newrow['mps_delivery_desc'] = $row['Delivery_Description'];

            //规格值图片
            $newrow['mps_colorcut']         = $row['COLORCUT'];
            $newrow['attribute_value_image'] = $row['COLORCUT'];
            //规格-关联商品图片-改成数组2022年09月30日16:37:57
            $newrow['spec_image'] = $newrow['pics'];

            //产品标签
            $newrow['mps_producttags']         = trim($row['Product_TAG']);

            $row = $newrow;
        } catch (\Exception $e) {
            //throw new BadRequestHttpException();
            // echo "MESSAGE = " . $e->getMessage() . " " . "STACK TRACE = " . $e->getTraceAsString()  ;
            // exit;
            $errormsg = '组织导入商品数据错误:' . $e->getMessage();
            //app('log')->debug("\n 组织导入商品数据错误 error =>:" . $e->getMessage() . '|行号：' . __LINE__);
        }
    }

    private function createGoods($companyId, $row)
    {
        $itemsService = new ItemsService();

        $validatorData = $this->validatorData($row);

        $rules = [
            'item_name' => ['required', '请填写商品名称'],
            'price' => ['required', '请填写价格'],
            // 'store' => ['required|integer|min:0|max:999999999', '库存为0-999999999的整数'],
            //'templates_id' => ['required', '请填写运费模板'],
        ];
        $errorMessage = validator_params($validatorData, $rules, false);
        /*         
        没有库存
        if (intval($row['store']) < 0 || intval($row['store']) > 999999999) {
            $errorMessage[] = '库存为0-999999999的整数';
        }
        if ($errorMessage) {
            $msg = implode(', ', $errorMessage);
            throw new BadRequestHttpException($msg);
        } */
        if ($errorMessage) {
            $msg = implode(', ', $errorMessage);
            throw new BadRequestHttpException($msg);
        } 
        $nospec = $row['item_spec'] ? 'false' : 'true';
        //表示为多规格，并且已经存储了默认商品，所以只需要新增当前商品数据，通用关联数据不需要更新，例如：商品关联的分类，关联的品牌等,
        //怎么处理的多规格，判断当前行的商品名称是否和上一个商品名称一致2022-08-23 22:34:11
        
        if($defaultItemId=$this->getMpsSpuBnItemIdFromCache($row['mps_spu_bn'])){
            //如果换成里有对应spu_bn的item_id,就不用再查询数据库
            $isCreateRelData = false;
            app('log')->debug("缓存里有对应的spu_bn的item_id：getMpsSpuBnItemIdFromCache:".$row['mps_spu_bn'].':'.$defaultItemId);

        }
        else{
            app('log')->debug("缓存里没有有对应的spu_bn的item_id：getMpsSpuBnItemIdFromCache:".$row['mps_spu_bn']);

            $filterExist = ['mps_spu_bn' => $row['mps_spu_bn'],'is_default'=>true, 'company_id' => $companyId];
            $itemsService = new ItemsService();
            $existItemInfo = $itemsService->getItem($filterExist);

            if(!$existItemInfo){
                $isCreateRelData = true;
                $defaultItemId = null;

                app('log')->debug("缓存里没有有对应的spu_bn的item_id，查询数据库：getMpsSpuBnItemIdFromCache:数据库里也没有，新商品:");

            }
            else{
                app('log')->debug("缓存里没有有对应的spu_bn的item_id，查询数据库：getMpsSpuBnItemIdFromCache:item_id:".$existItemInfo['item_id']);

                $isCreateRelData = false;
                $defaultItemId = $existItemInfo['item_id'];
                $this->addMpsSpuBnToCache($row['mps_spu_bn'],$defaultItemId);
            }
        }
   
  /*       if ($nospec == 'false' && $this->itemName && trim($row['item_name']) == $this->itemName) {
            $isCreateRelData = false;
            $defaultItemId = $this->defaultItemId;
        } else {
            $isCreateRelData = true;
            $defaultItemId = null;
        } */
        //这里逻辑有很大问题2022-10-27 17:59:23




        $itemsProfitService = new ItemsProfitService();

        $profitType = 0;
        $profitFee = 0;
        if (!in_array(intval($row['is_profit']), [0, 1])) {
            throw new BadRequestHttpException('是否支持分润参数错误');
        }
        $row['profit_type'] = intval($row['profit_type']);
        if ($row['profit_type'] >= 0) {
            if (!in_array($row['profit_type'], [$itemsProfitService::STATUS_PROFIT_DEFAULT, $itemsProfitService::STATUS_PROFIT_SCALE, $itemsProfitService::STATUS_PROFIT_FEE])) {
                throw new BadRequestHttpException('分润类型错误');
            }
            if (0 != $row['profit_type']) {
                if (!($row['profit'] ?? 0)) {
                    throw new BadRequestHttpException('拉新分润金额不能为空');
                }
                if (!($row['popularize_profit'] ?? 0)) {
                    throw new BadRequestHttpException('推广分润金额不能为空');
                }
                $profitType = $itemsProfitService::STATUS_PROFIT_SCALE == $row['profit_type'] ? $itemsProfitService::PROFIT_ITEM_PROFIT_SCALE : $itemsProfitService::PROFIT_ITEM_PROFIT_FEE;
                $profitFee = $itemsProfitService::STATUS_PROFIT_SCALE == $row['profit_type'] ? bcmul(bcdiv($row['popularize_profit'], 100, 4), $row['price'], 2) : $row['popularize_profit'];
            }
        }

        $mainCategory = $this->getItemMainCategoryId($companyId, $row); //获取主类目信息

        $isProfit = intval($row['is_profit']);
        $itemInfo = [
            'company_id' => $companyId,
            'item_name' => trim($row['item_name']),
            'brief' => trim($row['brief']),
            'templates_id' => $this->getTemplatesId($companyId, $row),
            'pics' => $row['pics'] ? explode(',', $row['pics']) : [],
            'videos' => $row['videos'],
            'nospec' => $nospec,
            'item_category' => $this->getItemCategoryNew($companyId, $row, false),
            'item_main_cat_id' => $mainCategory['category_id'],
            'brand_id' => $this->getBrandId($companyId, $row),
            'item_params' => $this->getItemParams($companyId, $row),
            'item_bn' => trim($row['item_bn']),
            'weight' => trim($row['weight']),
            'barcode' => trim($row['barcode']),
            'price' => $row['price'],
            'cost_price' => $row['cost_price'],
            'market_price' => $row['market_price'],
            'item_unit' => $row['item_unit'],
            'store' => 0, //$this->getStore(trim($row['item_bn'])),// $row['store'],
            'is_default' => $isCreateRelData,
            //'is_profit' => intval($row['is_profit']) ?? 0,
            'is_profit' => ($isProfit == 1) ? 'true' : 'false',
            'profit_type' => $profitType,
            'profit_fee' => $profitFee,
            'item_type' => 'normal',
            'sort' => 1,
            'approve_status' => 'onsale',
            'intro' => $row['intro'],
            'distributor_id' => $row['distributor_id'],
            'mps_spu_bn' => $row['mps_spu_bn'],
            'mps_material' => $row['mps_material'],
            'mps_cares' => $row['mps_cares'],
            'mps_season' => $row['mps_season'],
            'mps_new' => $row['mps_new'],
            'mps_delivery_desc' => $row['mps_delivery_desc'],
            'mps_color' => ($row['mps_color'] ?? ''),
            'mps_size' => ($row['mps_size'] ?? ''),
            'mps_gener' => ($row['mps_gener'] ?? ''),
            'mps_colorcut' => ($row['mps_colorcut'] ?? ''),
        ];

        // 商品上下架状态，默认为 不可销售
        if (isset($row['approve_status']) && isset($this->allApproveStatus[$row['approve_status']])) {
            $itemInfo['approve_status'] = $this->allApproveStatus[$row['approve_status']];
        }

        if ($nospec == 'false') {
            $specItem = [
                'item_bn' => trim($row['item_bn']),
                'weight' => $row['weight'],
                'barcode' => trim($row['barcode']),
                'price' => $row['price'],
                'cost_price' => $row['cost_price'],
                'market_price' => $row['market_price'],
                'item_unit' => $row['item_unit'],
                'store' => 0, //$row['store'],
                'is_default' => $isCreateRelData,
                'default_item_id' => $defaultItemId,
                'item_spec' => $this->getItemSpec($companyId, $row, $mainCategory),
                'approve_status' => $itemInfo['approve_status'],
            ];

            $specItems[] = $specItem;
            $itemInfo['spec_items'] = json_encode($specItems);
            //"item_spec":[{"spec_id":"2","spec_value_id":"299"},{"spec_id":"3","spec_value_id":"192"}]
            //规格图片2是颜色规格
            $spec_value_id = 0;
            foreach ($specItem['item_spec'] as $kk => $onespecItem) {
                if (isset($onespecItem['spec_id']) && $onespecItem['spec_id'] == '2') {
                    $spec_value_id = $onespecItem['spec_value_id'];
                    if ($spec_value_id) {
                        $itemInfo['spec_images'] = json_encode(
                            [
                                [
                                    'spec_value_id'  => $spec_value_id,
                                    'item_image_url' => $itemInfo['pics'],
                                    'attribute_value_image' => $row['attribute_value_image']
                                ]
                            ]
                        );
                        //print_r($itemInfo['spec_images']);exit;
                    }
                }
            }


            // if(isset($specItem['item_spec']['spec_id']) && $specItem['item_spec']['spec_id']=='2'){

            //     $spec_value_id=$specItem['spec_value_id'];
            //     if($spec_value_id){
            //         $itemInfo['spec_images'] = json_encode(
            //             [
            //                 [
            //                     'spec_value_id'  => $spec_value_id,
            //                     //
            //                     //'item_image_url' => [$row['spec_image']],
            //                     //已经是数组了2022-09-30 16:40:01
            //                     'item_image_url' => $itemInfo['pics'],

            //                     'attribute_value_image' => $row['attribute_value_image']
            //                 ]
            //             ]);
            //             print_r( $itemInfo);exit;

            //     }
            // }

        }



        if (isset($row['goods_id']) && $row['goods_id']) {
            $itemInfo['goods_id'] = $row['goods_id'];
        }

        if (isset($row['item_id']) && $row['item_id']) {
            $itemInfo['item_id'] = $row['item_id'];
        }

        if ($defaultItemId) {
            $itemInfo['default_item_id'] = $defaultItemId;
        }

        $itemProfitInfo = [];
        if ($profitType) {
            if ($itemsProfitService::STATUS_PROFIT_SCALE == $row['profit_type']) {
                //按比例
                $profitConfData = [
                    //'profit' => bcmul(bcdiv($row['profit'], 100, 4), bcmul($row['price'], 100)),
                    'profit' => $row['profit'],
                    //'popularize_profit' => bcmul(bcdiv($row['popularize_profit'], 100, 4), bcmul($row['price'], 100)),
                    'popularize_profit' => $row['popularize_profit'],
                ];
            } else {
                //按金额
                $profitConfData = [
                    'profit' => bcmul($row['profit'], 100),
                    'popularize_profit' => bcmul($row['popularize_profit'], 100),
                ];
            }
            $itemProfitInfo = [
                'company_id' => $companyId,
                'profit_type' => $row['profit_type'],
                'profit_conf' => $profitConfData,
            ];
        }

        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $result = $itemsService->addItems($itemInfo, $isCreateRelData);
            $itemId = $result['item_id'] ?? 0;
            if ($itemProfitInfo && $itemId) {
                $itemProfitInfo['item_id'] = $itemId;
                $itemsProfitService->deleteBy(['company_id' => $companyId, 'item_id' => $itemId]);
                $itemsProfitService->create($itemProfitInfo);
            }
            if ($isCreateRelData) {
                $this->defaultItemId = $result['item_id'];
                $this->itemName = trim($row['item_name']);
            }
            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
            app('log')->debug("\n _uploadItems error =>:" . $e->getMessage() . '|' . __LINE__);
            throw new BadRequestHttpException($e->getMessage());
        } catch (\Throwable $e) {
            $conn->rollback();
            app('log')->debug("\n _uploadItems error =>:" . $e->getMessage() . '|' . __LINE__);
            throw new \Exception($e->getMessage());
        }

        //保存商品的会员价，注意，这里面有事务，不能和上面的事务叠加
        $this->_saveMemberPrice($row, $result['item_id'], $companyId);
    }
    function getStore()
    {
    }
    private function updateGoods($companyId, $row, $oldItemInfo)
    {
        $itemsService = new ItemsService();
        $itemsProfitService = new ItemsProfitService();

        $itemId = $row['item_id'];
        $itemInfo = [
            'item_id' => $itemId,
            'goods_id' => $row['goods_id'],
            'company_id' => $companyId,
            'default_item_id' => $row['default_item_id'],
        ];
        $profitType = 0;
        $profitFee = 0;

        // 商品价格，用来计算分润
        $itemPrice = $oldItemInfo['price'];
        if (isset($row['price']) && $row['price']) {
            $itemPrice = bcmul($row['price'], 100);
        }

        //是否支持分润参数
        if (!empty($row['is_profit'])) {
            if (!in_array($row['is_profit'], ['0', '1'])) {
                throw new BadRequestHttpException('是否支持分润参数错误');
            }
            $itemInfo['is_profit'] = ($row['is_profit'] == '1') ? 'true' : 'false';

            if (!empty($row['profit_type'])) {
                $row['profit_type'] = intval($row['profit_type']);
                if (!in_array($row['profit_type'], [$itemsProfitService::STATUS_PROFIT_DEFAULT, $itemsProfitService::STATUS_PROFIT_SCALE, $itemsProfitService::STATUS_PROFIT_FEE])) {
                    throw new BadRequestHttpException('分润类型错误');
                }
                if (0 != $row['profit_type']) {
                    if (!($row['profit'] ?? 0)) {
                        throw new BadRequestHttpException('拉新分润金额不能为空');
                    }
                    if (!($row['popularize_profit'] ?? 0)) {
                        throw new BadRequestHttpException('推广分润金额不能为空');
                    }
                    $profitType = $itemsProfitService::STATUS_PROFIT_SCALE == $row['profit_type'] ? $itemsProfitService::PROFIT_ITEM_PROFIT_SCALE : $itemsProfitService::PROFIT_ITEM_PROFIT_FEE;
                    $profitFee = $itemsProfitService::STATUS_PROFIT_SCALE == $row['profit_type'] ? bcmul(bcdiv($row['popularize_profit'], 100, 4), $itemPrice, 2) : bcmul($row['popularize_profit'], 100);
                }
                $itemInfo['profit_type'] = $profitType;
                $itemInfo['profit_fee'] = $profitFee;
            }
        }

        //获取主类目信息
        $mainCategory = [];
        if ($row['item_main_category']) {
            $mainCategory = $this->getItemMainCategoryId($companyId, $row);
            $itemInfo['item_main_cat_id'] = $mainCategory['category_id'];
        }

        foreach ($row as $k => $v) {
            switch ($k) {
                case 'item_category':
                    if (!$v) break;
                    $itemInfo['item_category'] = $this->getItemCategoryNew($companyId, $row, false);
                    break;

                case 'templates_id':
                    if (!$v) break;
                    $itemInfo['templates_id'] = $this->getTemplatesId($companyId, $row);
                    break;

                case 'pics':
                    if (!$v) break;
                    $itemInfo['pics'] = $row['pics'] ? explode(',', $row['pics']) : [];
                    break;

                case 'goods_brand':
                    if (!$v) break;
                    $itemInfo['brand_id'] = $this->getBrandId($companyId, $row);
                    break;

                case 'item_spec':
                    //商品规格，必须和主类目一起导入
                    if (empty($v) or !$mainCategory) break;
                    $itemInfo['nospec'] = 'false';
                    $itemInfo['item_spec'] = $this->getItemSpec($companyId, $row, $mainCategory);

                    //规格图片
                    $spec_value_id = 0;
                    foreach ($itemInfo['item_spec'] as $kk => $specItem) {
                        if (isset($specItem['spec_id']) && $specItem['spec_id'] == '2') {
                            $spec_value_id = $specItem['spec_value_id'];
                            if ($spec_value_id) {
                                $itemInfo['spec_images'] =
                                    [
                                        [
                                            'spec_value_id'  => $spec_value_id,
                                            'item_image_url' => $itemInfo['pics'],
                                            'attribute_value_image' => $row['attribute_value_image']
                                        ]
                                    ];
                                //print_r($itemInfo['spec_images']);exit;
                            }
                        }
                    }


                    break;

                case 'item_params':
                    if (empty($v)) break;
                    $itemInfo['item_params'] = $this->getItemParams($companyId, $row);
                    break;

                case 'approve_status':
                    if (empty($v)) break;
                    if (!isset($this->allApproveStatus[$v])) {
                        throw new BadRequestHttpException('商品状态错误');
                    }
                    if ($oldItemInfo['store'] <= 0) {
                        //小于等于0，下架,且不是默认规格时，才下架2022-10-19 14:16:13
                        if ($oldItemInfo['item_id'] != $oldItemInfo['default_item_id']) {
                            $itemInfo['approve_status'] = 'instock';
                        } else {
                            // $itemInfo['approve_status'] ='onsale';

                        }
                    } else {
                        $itemInfo['approve_status'] = 'onsale'; // $this->allApproveStatus[$v];
                    }
                    break;

                case 'price':
                case 'cost_price':
                    if (!$v) break;
                    $itemInfo[$k] = bcmul($v, 100);
                    break;
                case 'market_price':
                    //大型bug,如果是空的话，就不会更新这个市场价格了！！
                    $itemInfo[$k] = bcmul($v, 100);
                    break;
                default:
                    if (empty($v)) break;
                    $itemInfo[$k] = trim($v);
            }
        }

        // app('log')->debug('导入updateGoods更新商品 itemInfo =>: '.json_encode($itemInfo, JSON_UNESCAPED_UNICODE));

        $itemProfitInfo = [];
        if ($profitType) {
            if ($itemsProfitService::STATUS_PROFIT_SCALE == $row['profit_type']) {
                //按比例
                $profitConfData = [
                    //'profit' => bcmul(bcdiv($row['profit'], 100, 4), $itemPrice, 2),
                    'profit' => $row['profit'],
                    //'popularize_profit' => bcmul(bcdiv($row['popularize_profit'], 100, 4), $itemPrice, 2),
                    'popularize_profit' => $row['popularize_profit'],
                ];
            } else {
                //按金额
                $profitConfData = [
                    'profit' => bcmul($row['profit'], 100),
                    'popularize_profit' => bcmul($row['popularize_profit'], 100),
                ];
            }
            $itemProfitInfo = [
                'company_id' => $companyId,
                'profit_type' => $row['profit_type'],
                'profit_conf' => $profitConfData,
            ];
        }

        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $this->_saveMemberPrice($row, $itemId, $companyId);

            $result = $itemsService->updateUploadItems($itemInfo);
            if ($itemProfitInfo) {
                $itemProfitInfo['item_id'] = $itemId;
                $itemsProfitService->deleteBy(['item_id' => $itemId, 'company_id' => $companyId]);
                $itemsProfitService->create($itemProfitInfo);
            }
            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
            app('log')->debug("\n _uploadItems error =>:" . $e->getMessage());
            throw new BadRequestHttpException($e->getMessage());
        } catch (\Throwable $e) {
            $conn->rollback();
            app('log')->debug("\n _uploadItems error =>:" . $e->getMessage());
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * 保存商品的会员价
     *
     * @param array $row
     * @param int $itemId
     * @param int $companyId
     * @return bool|void
     */
    private function _saveMemberPrice($row = [], $itemId = 0, $companyId = 0)
    {
        //mprice: {"5427":{"grade":{"4":"1","8":"2","26":"3","27":""},"vipGrade":{"1":"4","2":""}}}
        //"vipGrade_price1":60,"vipGrade_price2":50
        $memberPrice = [];
        $priceLabel = ['grade', 'vipGrade'];
        $priceValid = false; //是否存在有效的会员价格
        foreach ($row as $k => $v) {
            foreach ($priceLabel as $label) {
                if (!isset($memberPrice[$itemId][$label])) {
                    $memberPrice[$itemId][$label] = []; //初始化结构，防止报错
                }
                if (strstr($k, $label . '_price')) {
                    $gradeId = str_replace($label . '_price', '', $k);
                    $v = floatval($v);
                    if (!$v) {
                        $v = ''; //不合法的价格都设置成空
                    } else {
                        $priceValid = true;
                    }
                    $memberPrice[$itemId][$label][$gradeId] = $v;
                }
            }
        }

        //会员价必须一起更新，如果没有填写任何会员价，不做更新
        if ($priceValid === false) {
            return false;
        }

        try {
            $priceParams = [
                'item_id' => $itemId,
                'company_id' => $companyId,
                'mprice' => json_encode($memberPrice, 256),
            ];
            //app('log')->debug("\n _saveMemberPrice priceParams =>:".json_encode($priceParams, 256));
            $memberPriceService = new MemberPriceService();
            $memberPriceService->saveMemberPrice($priceParams);
        } catch (\Exception $e) {
            app('log')->debug("\n _saveMemberPrice error =>:" . $e->getMessage());
            throw new BadRequestHttpException($e->getMessage());
        }
    }

    /**
     * 通过运费模版名称，获取运费模版ID
     */
    private function getTemplatesId($companyId, $row)
    {
        if (!$row['templates_id']) {
            throw new BadRequestHttpException('请填写商品运费模版');
        }

        $shippingTemplatesService = new ShippingTemplatesService();
        $data = $shippingTemplatesService->getInfoByName($row['templates_id'], $companyId);
        if (!$data) {
            throw new BadRequestHttpException('填写的运费模版不存在');
        }

        return $data['template_id'];
    }

    function autoCreateThirdCategory($tmpCategory, $companyId)
    {
        $itemsCategoryService = new ItemsCategoryService();
        //主类目
        $parentCategoryId = 0;
        foreach ($tmpCategory as $kcat => $vcat) {
            $distributorId = 0;
            $tmpNewCategory = [];
            if ($kcat == 0) {
                // $tmpNewCategory['parent_id']=0;
            } else {
                $tmpNewCategory['parent_id'] = $parentCategoryId;
            }
            $tmpNewCategory['category_name'] = $vcat;
            $tmpNewCategory['is_main_category'] = 1;
            $tmpNewCategory['sort'] = 0;
            $uniqueName = $this->itemsCategoryRepository->getInfo(['category_name' => $tmpNewCategory['category_name'], 'parent_id' => ($tmpNewCategory['parent_id'] ?? 0), 'company_id' => $companyId, 'is_main_category' => $tmpNewCategory['is_main_category'], 'distributor_id' => $distributorId]);
            if (!$uniqueName) {
                //print_r($tmpNewCategory);exit;

                $parentCategoryInfo = $this->createCategory($tmpNewCategory);

                $parentCategoryId = $parentCategoryInfo['category_id'];
            } else {
                $parentCategoryId = $uniqueName['category_id'];
                //要更新code啊
                $this->updateCategory($tmpNewCategory);
//更新同code
$tmpNewCategorySameCode['category_code']=$tmpNewCategory['category_code'];
$tmpNewCategorySameCode['category_name']=$tmpNewCategory['category_name'];
$this->updateCategorySameCode($tmpNewCategorySameCode);
            }
            //最后一级绑定规格2022-08-23 20:11:03
            if ($kcat == count($tmpCategory) - 1) {
                app('log')->debug('最后一级绑定规格 =>:' . $parentCategoryId);
                //是最后一级分类
                $itemsCategoryService->updateOneBy(['category_id' => $parentCategoryId, 'company_id' => 1], ['goods_spec' => [3, 2]]);
            }
        }

        //
        //分类
        $parentCategoryId = 0;
        foreach ($tmpCategory as $kcat => $vcat) {
            $distributorId = 0;
            $tmpNewCategory = [];
            if ($kcat == 0) {
                //$tmpNewCategory['parent_id']=0;
            } else {
                $tmpNewCategory['parent_id'] = $parentCategoryId;
            }
            $tmpNewCategory['category_name'] = $vcat;
            $tmpNewCategory['is_main_category'] = 0;
            $tmpNewCategory['sort'] = 0;

            $uniqueName = $this->itemsCategoryRepository->getInfo(['category_name' => $tmpNewCategory['category_name'], 'parent_id' => ($tmpNewCategory['parent_id'] ?? 0), 'company_id' => $companyId, 'is_main_category' => $tmpNewCategory['is_main_category'], 'distributor_id' => $distributorId]);
            if (!$uniqueName) {
                //print_r($tmpNewCategory);exit;

                $parentCategoryInfo = $this->createCategory($tmpNewCategory);

                $parentCategoryId = $parentCategoryInfo['category_id'];
            } else {
                $parentCategoryId = $uniqueName['category_id'];
                //要更新code啊

                $this->updateCategory($tmpNewCategory);

                //更新同code
                $tmpNewCategorySameCode['category_code']=$tmpNewCategory['category_code'];
                $tmpNewCategorySameCode['category_name']=$tmpNewCategory['category_name'];
                $this->updateCategorySameCode($tmpNewCategorySameCode);

            }
        }
    }
    /**
     * 添加分类
     *
     * @return void
     */
    function createCategory($params)
    {
        $companyId = 1; // app('auth')->user()->get('company_id');
        $distributorId = 0; // app('auth')->user()->get('distributor_id');
        $itemsCategoryService = new ItemsCategoryService();
        return $result = $itemsCategoryService->createClassificationService($params, $companyId, $distributorId);
    }


    /**
     * 添加分类
     *
     * @return void
     */
    function updateCategory($params)
    {
        $itemsCategoryService = new ItemsCategoryService();
        return $result = $itemsCategoryService->itemsCategoryRepository->updateOneBy(['category_id' => $params['category_id']], ['category_code' => $params['category_code']]);
    }

    function updateCategorySameCode($params)
    {
        if( $params['category_code']){
        $itemsCategoryService = new ItemsCategoryService();
       // print_r($params);exit;
        return $result = $itemsCategoryService->itemsCategoryRepository->updateBy(['category_code' => $params['category_code']], ['category_name' => $params['category_name']]);}
    }

    /**
     * 获取商品主类目
     *
     * @param int $companyId
     * @param array $row
     */
    private function getItemMainCategoryId($companyId = 0, &$row = [])
    {
        $categoryInfo = [];
        $splitChar = '->';
        $mainCategory = $row['item_main_category'];
        if (!$mainCategory) {
            throw new BadRequestHttpException('请上传商品主类目');
        }
        $catNamesArr = explode($splitChar, $mainCategory);
        if (count($catNamesArr) != 3) {
            throw new BadRequestHttpException('上传商品主类目必须是三层级,' . $mainCategory);
        }

        $itemsCategoryService = new ItemsCategoryService();
        $lists = $itemsCategoryService->lists(['company_id' => $companyId, 'category_name' => $catNamesArr, 'is_main_category' => 1]);
        if ($lists['total_count'] <= 0) {
            throw new BadRequestHttpException('上传商品主类目不存在,' . $mainCategory);
        }

        $categoryName = array_column($lists['list'], 'category_name', 'category_id');
        foreach ($lists['list'] as $v) {
            if (!$v['path']) continue;
            $paths = explode(',', $v['path']);
            $pathName = [];
            foreach ($paths as $id) {
                if (!isset($categoryName[$id])) continue;
                $pathName[] = $categoryName[$id];
            }
            //根据路径判断，找到一样的为止
            if (implode($splitChar, $pathName) == $mainCategory) {
                $categoryInfo = $v;
                break;
            }
        }

        if (!$categoryInfo) {
            throw new BadRequestHttpException('无法识别的商品主类目,' . $mainCategory);
        }

        //array_multisort($lists['list'], SORT_ASC, array_column($lists['list'], 'category_level'));
        //$categoryInfo = end($lists['list']);
        return $categoryInfo;
    }

    /**
     * 获取商品分类，这个函数有bug，用 getItemCategoryNew 替代
     */
    private function getItemCategory($companyId, $row, $isMain = false)
    {
        if ($isMain) {
            $category = $row['item_main_category'];
        } else {
            $category = $row['item_category'];
        }

        if ($category) {
            $catNames = explode('|', $category);
        } else {
            if ($isMain) {
                throw new BadRequestHttpException('请上传商品主类目');
            } else {
                throw new BadRequestHttpException('请上传商品分类');
            }
        }

        $catNamesArr = array();
        foreach ($catNames as $catNameRow) {
            $catNamesArr = array_merge($catNamesArr, explode('->', $catNameRow));
        }

        $itemsCategoryService = new ItemsCategoryService();
        // 数据结构买办法判断获取的分类ID是否最子级分类，三级分类改造后在优化
        $lists = $itemsCategoryService->lists(['company_id' => $companyId, 'category_name' => $catNamesArr, 'is_main_category' => $isMain]);
        if ($lists['total_count'] <= 0) {
            if ($isMain) {
                throw new BadRequestHttpException('上传商品主类目参数有误');
            } else {
                throw new BadRequestHttpException('上传商品分类参数有误1');
            }
        }
        //主类目

        $parentIds = [];
        $pathArr = [];
        $path2Arr = [];
        foreach ($lists['list'] as $catRow) {
            if ($catRow['category_level'] != '3') {
                $parentIds[] = $catRow['category_id'];
            }
            if ($catRow['category_level'] == '3') {
                $pathArr[] = $catRow['path'];
            }
            if ($catRow['category_level'] == '2') {
                $path2Arr[] = $catRow['path'];
            }
        }
        if (!$parentIds) {
            if ($isMain) {
                throw new BadRequestHttpException('上传商品主类目参数有误');
            } else {
                throw new BadRequestHttpException('上传商品分类参数有误2');
            }
        }
        $catId = [];
        foreach ($lists['list'] as $catRow) {
            $parentArr = [];
            if ($catRow['category_level'] == '3') {
                $parentArr = explode(',', $catRow['path']);
                unset($parentArr[2]);
            } elseif ($catRow['category_level'] == '2') {
                $result = false;
                foreach ($pathArr as $v) {
                    $result = 0 === strpos($v, $catRow['path']) ? true : false;
                    if ($result) {
                        continue;
                    }
                }
                if ($result) {
                    continue;
                }
                $parentArr = explode(',', $catRow['path']);
                unset($parentArr[1]);
            } elseif ($catRow['category_level'] == '1') {
                $result = false;
                foreach ($pathArr as $v) {
                    $result = 0 === strpos($v, $catRow['path'] . ',') ? true : false;
                    if ($result) break;
                }
                if ($result) continue;
                foreach ($path2Arr as $v) {
                    $result = 0 === strpos($v, $catRow['path'] . ',') ? true : false;
                    if ($result) break;
                }
                if ($result) continue;
                $catId[] = $catRow['category_id'];
            }
            if ($parentArr && $parentArr == array_intersect($parentArr, $parentIds)) {
                $catId[] = $catRow['category_id'];
            }
        }
        if (!$catId) {
            if ($isMain) {
                throw new BadRequestHttpException('上传商品主类目参数有误');
            } else {
                throw new BadRequestHttpException('上传商品分类参数有误3');
            }
        }
        return $catId;
    }

    /**
     * 获取商品分类
     */
    public function getItemCategoryNew($companyId, $row)
    {
        if (!$row['item_category']) {
            throw new BadRequestHttpException('请上传商品分类');
        }

        $catId = [];
        $category = $row['item_category'];
        $catNames = explode('|', $category);
        //print_r($catNames);

        $catNamesArr = array();
        foreach ($catNames as $catNameRow) {
            $catNamesArr = array_merge($catNamesArr, explode('->', $catNameRow));
        }
        //print_r($catNamesArr);

        //用重名的怎么办？？
        $itemsCategoryService = new ItemsCategoryService();
        // 数据结构买办法判断获取的分类ID是否最子级分类，三级分类改造后在优化
        $filter = ['company_id' => $companyId, 'distributor_id' => $row['distributor_id'], 'category_name' => $catNamesArr, 'is_main_category' => 0];

        $lists = $itemsCategoryService->lists($filter);
        if ($lists['total_count'] <= 0) {
            throw new BadRequestHttpException('上传商品分类参数有误|' . var_export($catNamesArr, true));
        }

        // 服装->套装->连衣裙
        $catNamePath = [];
        $categoryName = array_column($lists['list'], 'category_name', 'category_id');
   

        foreach ($lists['list'] as $catRow) {
            $path = explode(',', $catRow['path']);
            //本周新品 2,4
            //本周新品 2,5,8
            foreach ($path as $categoryId) {
                if (!isset($categoryName[$categoryId])) {
                    //continue;
                    //找不到的分类，直接给个空应该就可以2022-11-16 16:52:11
                    // 导入商品有个bug，如果csv里商品分类的最后一级分类名称 同时存在 后台商品分类的某个二级分类或三级分类，导入后会关联错误的分类
                    $categoryName[$categoryId]='';
                }
                if (isset($catNamePath[$catRow['category_id']])) {
                    $catNamePath[$catRow['category_id']] .= '->' . $categoryName[$categoryId];//[2]=>'女士=>本周新品'
                } else {
                    $catNamePath[$catRow['category_id']] = $categoryName[$categoryId];//[2]=>'女士'
                }
            /*     if (!isset($categoryName[$categoryId])) {
                    continue;
                }
                if (isset($catNamePath[$catRow['category_id']])) {
                    $catNamePath[$catRow['category_id']] .= '->' . $categoryName[$categoryId];//[2]=>'女士=>本周新品'
                } else {
                    $catNamePath[$catRow['category_id']] = $categoryName[$categoryId];//[2]=>'女士'
                } */
            }
        }
        //app('log')->debug('_uploadItems catNamePath =>:'.json_encode($catNamePath, 256));
        //print_r($catNamePath);
        foreach ($catNamePath as $categoryId => $v) {
            if (in_array($v, $catNames)) {
                $catId[] = $categoryId;
            }
        }

        //app('log')->debug('_uploadItems catNamePath =>:'.json_encode($catNamePath, 256));
        //app('log')->debug('_uploadItems catId =>:'.json_encode($catId, 256));

        if (!$catId) {
            throw new BadRequestHttpException('上传商品分类参数有误5');
        }
        return $catId;
    }
    function getAllCategories($category_codes = "", $companyId = 1,$is_main_category=0)
    {
        $category_codes = explode('|', $category_codes);
        $filter['category_code'] = $category_codes;
        $filter['company_id'] = $companyId;
        $filter['is_main_category'] = $is_main_category;//0;
        if( $is_main_category){
            $filter['category_level'] =[3];// 主类目必须3级

        }
        else{
            $filter['category_level'] =[2,3];// 分类2级也可以
        }
        $lists = $this->itemsCategoryServiceCommon->lists($filter);
        //print_r($lists);exit;

        if ($lists['total_count'] <= 0) {
            //print_r($category_codes);exit;
            throw new BadRequestHttpException('Categories里的主类目都不存在:' . json_encode($category_codes, true));
        } else {
            $category_ids = [];
            $itemExport = new ItemExport();
            foreach ($lists['list'] as $k => $v) {
                $category_ids[] = $v['category_id'];
            }
            $item_category = $itemExport->getItemCategory($companyId, $category_ids, $is_main_category);
            //app('log')->debug("\n getFormatGoodsRowData getItemCategory item_category=>:" . var_export($item_category, true) . '|行号：' . __LINE__);

            return explode('|', $item_category);
        }
    }


    function getAllCategoriesSecond($category_codes = "", $companyId = 1)
    {
        $category_codes = explode('|', $category_codes);
        $filter['category_code'] = $category_codes;
        $filter['company_id'] = $companyId;
        $filter['is_main_category'] = 0;
        $filter['category_level'] =2;// [2,3];//2,3 都可以吗
        $lists = $this->itemsCategoryServiceCommon->lists($filter);
        //print_r($lists);exit;

        if ($lists['total_count'] <= 0) {
            //print_r($category_codes);exit;
            //throw new BadRequestHttpException('Categories里的主类目都不存在:' . json_encode($category_codes, true));
            return [];
        } else {
            $category_ids = [];
            $itemExport = new ItemExport();
            foreach ($lists['list'] as $k => $v) {
                $category_ids[] = $v['category_id'];
            }
            $item_category = $itemExport->getItemCategory($companyId, $category_ids, 0);
            //app('log')->debug("\n getFormatGoodsRowData getItemCategory item_category=>:" . var_export($item_category, true) . '|行号：' . __LINE__);

            return explode('|', $item_category);
        }
    }

    /**
     * 通过品牌名称获取品牌ID
     */
    private function getBrandId($companyId, $row)
    {
        $brandName = $row['goods_brand'] ?? "";
        $brandId = 0;
        if ($brandName) {
            $itemsAttributesService = new ItemsAttributesService();
            $data = $itemsAttributesService->getInfo(['company_id' => $companyId, 'distributor_id' => $row['distributor_id'], 'attribute_name' => $brandName, 'attribute_type' => 'brand']);
            if (!$data) {
                throw new BadRequestHttpException($brandName . ' 品牌名称不存在');
            }
            $brandId = $data['attribute_id'];
        }
        return $brandId;
    }

    /**
     * 获取商品参数
     *
     * item_params: 功效:美白提亮|性别:男性
     */
    private function getItemParams($companyId, $row)
    {
        $data = [];
        if ($row['item_params']) {
            $itemsAttributesService = new ItemsAttributesService();
            $itemParams = explode('|', $row['item_params']);
            foreach ($itemParams as $row) {
                $itemRow = explode(':', $row);
                $attributeNames[] = $itemRow[0];
                $attributeValues[] = $itemRow[1];
            }

            $attrList = $itemsAttributesService->lists(['company_id' => $companyId, 'attribute_name' => $attributeNames, 'attribute_type' => 'item_params']);
            if ($attrList['total_count'] > 0) {
                $attributeids = array_column($attrList['list'], 'attribute_id');
            } else {
                throw new BadRequestHttpException('商品参数不存在');
            }

            $attrValuesList = $itemsAttributesService->getAttrValuesListBy(['company_id' => $companyId, 'attribute_value' => $attributeValues, 'attribute_id' => $attributeids]);
            if ($attrValuesList['total_count'] > 0) {
                foreach ($attrValuesList['list'] as $row) {
                    $data[] = [
                        'attribute_id' => $row['attribute_id'],
                        'attribute_value_id' => $row['attribute_value_id']
                    ];
                }
            } else {
                throw new BadRequestHttpException('商品参数值不存在');
            }
        }

        return $data;
    }

    private function getItemSpec($companyId, $row, &$mainCategory = [])
    {
        $data = [];
        $specInfo = [];
        if ($row['item_spec']) {
            //根据主类目获取商品规格属性的排序
            $goodsSpecIds = $mainCategory['goods_spec'];
            $tmpSpec = $row['item_spec'];
            $itemsAttributesService = new ItemsAttributesService();
            $itemParams = explode('|', $row['item_spec']);
            foreach ($itemParams as $row) {
                $itemRow = explode(':', $row);
                if (empty($itemRow[0])) {
                    throw new BadRequestHttpException('商品规格解析错误');
                }
                if (empty($itemRow[1])) {
                    throw new BadRequestHttpException('商品规格值解析错误:' . var_export($tmpSpec, true));
                }
                $attributeNames[] = $itemRow[0];
                $attributeValues[] = $itemRow[1];
            }

            // $goodsSpecIds 只查询当前主类目关联的规格
            $filter = [
                'company_id' => $companyId, 'attribute_name' => $attributeNames,
                'attribute_id' => $goodsSpecIds, 'attribute_type' => 'item_spec'
            ];
            $attrList = $itemsAttributesService->lists($filter, 1, 100, ['is_image' => 'DESC', 'attribute_id' => 'ASC']);
            if ($attrList['total_count'] == count($attributeNames)) {
                $attributeids = array_column($attrList['list'], 'attribute_id');
            } else {
                throw new BadRequestHttpException('商品规格[' . implode(',', $attributeNames) . ']存在无效值');
                //$attributeids=[4,5];//
            }

            $attrValuesList = $itemsAttributesService->getAttrValuesListBy(['company_id' => $companyId, 'attribute_value' => $attributeValues, 'attribute_id' => $attributeids]);
            if ($attrValuesList['total_count'] == count($attributeValues)) {
                foreach ($attrValuesList['list'] as $row) {
                    $data[$row['attribute_id']] = [
                        'spec_id' => $row['attribute_id'],
                        'spec_value_id' => $row['attribute_value_id']
                    ];
                }
            } else {
                //print_r(json_encode(ini_get_all()));exit;
                throw new BadRequestHttpException('商品规格值[' . implode(',', $attributeValues) . ']无效,filter:' . var_export(['company_id' => $companyId, 'attribute_value' => $attributeValues, 'attribute_id' => $attributeids], true));
            }

            //排序，按ID升序，按图像规格倒序
            foreach ($attributeids as $specId) {
                if (isset($data[$specId])) {
                    $specInfo[] = $data[$specId];
                }
            }
            /*
            foreach ($goodsSpecIds as $specId) {
                if (isset($data[$specId])) {
                    $specInfo[] = $data[$specId];
                }
            }*/
        }

        /*
        usort($data, function($a, $b) {
            if($a['spec_id'] == $b['spec_id']) return 0;
            else return $a['spec_id'] > $b['spec_id'] ? 1 : -1;
        });
        */
        return $specInfo;
    }

    function autoCreateThirdCategoryFromCsv($tmpCategory, $tmpCategoryCode, $companyId = 1)
    {
        $this->itemsCategoryRepository = app('registry')->getManager('default')->getRepository(ItemsCategory::class);
        $itemsCategoryService = new ItemsCategoryService();
        //主类目
        $parentCategoryId = 0;
        foreach ($tmpCategory as $kcat => $vcat) {
            $distributorId = 0;
            $tmpNewCategory = [];
            if ($kcat == 0) {
                // $tmpNewCategory['parent_id']=0;
            } else {
                $tmpNewCategory['parent_id'] = $parentCategoryId;
            }
            if (isset($tmpCategoryCode[$kcat]) && trim($tmpCategoryCode[$kcat])) {
                $tmpNewCategory['category_code'] = trim($tmpCategoryCode[$kcat]);
            } else {
                $tmpNewCategory['category_code'] = '';
            }
            $tmpNewCategory['category_name'] = $vcat;
            $tmpNewCategory['is_main_category'] = 1;
            $tmpNewCategory['sort'] = 0;
            $uniqueName = $this->itemsCategoryRepository->getInfo(['category_name' => $tmpNewCategory['category_name'], 'parent_id' => ($tmpNewCategory['parent_id'] ?? 0), 'company_id' => $companyId, 'is_main_category' => $tmpNewCategory['is_main_category'], 'distributor_id' => $distributorId]);
            if (!$uniqueName) {
                //print_r($tmpNewCategory);exit;

                $parentCategoryInfo = $this->createCategory($tmpNewCategory);

                $parentCategoryId = $parentCategoryInfo['category_id'];
            } else {
                $parentCategoryId = $uniqueName['category_id'];
                //要更新code啊
                $tmpNewCategory['category_id'] = $parentCategoryId;
                $this->updateCategory($tmpNewCategory);

                //更新同code
                $tmpNewCategorySameCode['category_code']=$tmpNewCategory['category_code'];
                $tmpNewCategorySameCode['category_name']=$tmpNewCategory['category_name'];
                $this->updateCategorySameCode($tmpNewCategorySameCode);
            }
            //最后一级绑定规格2022-08-23 20:11:03
            if ($kcat == count($tmpCategory) - 1) {
                app('log')->debug('最后一级绑定规格 =>:' . $parentCategoryId);
                //是最后一级分类
                $itemsCategoryService->updateOneBy(['category_id' => $parentCategoryId, 'company_id' => 1], ['goods_spec' => [3, 2]]);
            }
        }

        //
        //分类
        $parentCategoryId = 0;
        foreach ($tmpCategory as $kcat => $vcat) {
            $distributorId = 0;
            $tmpNewCategory = [];
            if ($kcat == 0) {
                //$tmpNewCategory['parent_id']=0;
            } else {
                $tmpNewCategory['parent_id'] = $parentCategoryId;
            }
            if (isset($tmpCategoryCode[$kcat]) && trim($tmpCategoryCode[$kcat])) {
                $tmpNewCategory['category_code'] = trim($tmpCategoryCode[$kcat]);
            } else {
                $tmpNewCategory['category_code'] = '';
            }
            $tmpNewCategory['category_name'] = $vcat;
            $tmpNewCategory['is_main_category'] = 0;
            $tmpNewCategory['sort'] = 0;

            $uniqueName = $this->itemsCategoryRepository->getInfo(['category_name' => $tmpNewCategory['category_name'], 'parent_id' => ($tmpNewCategory['parent_id'] ?? 0), 'company_id' => $companyId, 'is_main_category' => $tmpNewCategory['is_main_category'], 'distributor_id' => $distributorId]);
            if (!$uniqueName) {
                //print_r($tmpNewCategory);exit;

                $parentCategoryInfo = $this->createCategory($tmpNewCategory);

                $parentCategoryId = $parentCategoryInfo['category_id'];
            } else {
                $parentCategoryId = $uniqueName['category_id'];
                //要更新code啊
                //要更新code啊
                $tmpNewCategory['category_id'] = $parentCategoryId;
                $this->updateCategory($tmpNewCategory);


                //更新同code
                $tmpNewCategorySameCode['category_code']=$tmpNewCategory['category_code'];
                $tmpNewCategorySameCode['category_name']=$tmpNewCategory['category_name'];
                $this->updateCategorySameCode($tmpNewCategorySameCode);
            }
        }
    }
    /**
     * 把bn加入当前的redis
     *
     * @param [type] $bn
     * @return void
     */
    function addBnToCache($bn = "")
    {
        //app('redis')->del($key);

        $key = 'msp_feed_import_bn';
        $fieldName = 'bn'; //date('Ymd');//日期
        $allExistBn = app('redis')->hget($key, $fieldName);
        $allExistBn = json_decode($allExistBn);
        if (!$allExistBn || !in_array($bn, $allExistBn)) {
            $allExistBn[] = $bn;
            app('log')->debug("mps导入商品把货号加入redis-addBnToCache =>:" . json_encode($allExistBn));
            app('redis')->hset($key, $fieldName, json_encode($allExistBn));
        }
    }

    /**
     * 把mps_spu_bn加入当前的redis
     *
     * @param [type] $bn
     * @return void
     */
    function addMpsSpuBnToCache($mps_spu_bn = "",$item_id="")
    {
        //app('redis')->del($key);
        $key = 'msp_feed_import_spu_bn';
        $fieldName = 'mps_spu_bn::'.$mps_spu_bn; //date('Ymd');//日期
        app('redis')->hset($key, $fieldName, $item_id);
        
    }
    /**
     * 从缓存里获取mps_spu_bn对应的item_id
     *
     * @param string $mps_spu_bn
     * @return void
     */
    function getMpsSpuBnItemIdFromCache($mps_spu_bn = ""){
        $key = 'msp_feed_import_spu_bn';
        $fieldName = 'mps_spu_bn::'.$mps_spu_bn; //date('Ymd');//日期
        return app('redis')->hget($key, $fieldName);
    }
    function delMpsSpuBnCache()
    {
        $key = 'msp_feed_import_spu_bn';
        app('redis')->del($key);
    }
    function delMpsBnCache()
    {
        $key = 'msp_feed_import_bn';
        app('redis')->del($key);
    }
    function unmarketNotInMpsFeed()
    {
        app('log')->debug('开始执行unmarketNotInMpsFeed');

        $itemService = new ItemsService();
        $rows = $itemService->itemsRepository->list([], [], -1, 1, ['item_id', 'item_bn', 'store']);
        //app('log')->debug("当前数据库里的商品=>:".json_encode($rows));
        //echo json_encode($rows);
        echo "数据库里SKU数量：" . count($rows['list']);
        $itemStoreService = new ItemStoreService();

        //历史产物，不需要了2022-11-15 17:51:44
        //foreach ($rows['list'] as $k => $v) {
            // app('log')->debug("更新库存到redis,货号:" . $v['item_bn']."|库存".(int)$v['store']);

            // error_log(date("Y-m-d H:i:s").",".$v['item_bn'].',数据库,unmarketNotInMpsFeed-full,'.(int)$v['store']."\n", 3, storage_path('logs/stock.csv'));
          //  $itemStoreService->saveItemStore($v['item_id'], (int)$v['store']);
       // }


        $allBn = array_column($rows['list'], 'item_bn');

        $allItemsIds = array_column($rows['list'], 'item_id');

        $bnToItemId = [];
        foreach ($allBn as $k => $v) {
            $bnToItemId[$v] = $allItemsIds[$k];
        }

        app('log')->debug("当前数据库里的SKU数量=>:" . count($allBn));

        app('log')->debug("当前数据库里的SKU-bn=>:" . json_encode($allBn));

        $key = 'msp_feed_import_bn';
        $fieldName = 'bn'; //date('Ymd');//日期
        $allMpsBn = app('redis')->hget($key, $fieldName);
        $allMpsBn = json_decode($allMpsBn);
        echo "feed里SKU数量：" . count($allMpsBn);
        $resultDiff =[];
        if(count($allMpsBn)>0){
            $resultDiff = array_diff($allBn, $allMpsBn ?? []);
        }
        app('log')->debug("需要下架的不在mpsfeed里的商品货号列表-function unmarketNotInMpsFeed=>:" . json_encode($resultDiff));
        $data['diff_count'] = count($resultDiff);
        $data['diff_bn'] = $resultDiff;

        $allItemBnWithKey = [];
        foreach ($resultDiff as $k => $v) {
            //库存设置为0
            app('log')->debug("库存设置为0的商品-function unmarketNotInMpsFeedUpdate=>:" . $bnToItemId[$v]);


            error_log(date("Y-m-d H:i:s").",".$v.',数据库,unmarketNotInMpsFeed-resultDiff,'.'0'."\n", 3, storage_path('logs/stock.csv'));

            //数据库库存
            $itemService->itemsRepository->updateBy(['item_id'=>$bnToItemId[$v]], ['store' => 0]);
            //redis库存

            error_log(date("Y-m-d H:i:s").",".$v.',redis,unmarketNotInMpsFeed-resultDiff,'.'0'."\n", 3, storage_path('logs/stock.csv'));

            $itemStoreService->saveItemStore($bnToItemId[$v], 0);
            $allItemBnWithKey[] = ['goods_id' => $bnToItemId[$v], 'item_bn' => $v];
        }
        //下架

        $exceptBns=[
            '0941195271203',
            '0941195271202',
            '0941195271204',
            '0951095480001',
            '0951095480002',
            '0951095480003',
            '0951095480004',
            '0903295225004',
            '0903295225003',
            '0903295225002',
            '0903295225001',
            '0902295225004',
            '0902295225003',
            '0902295225002',
            '0940095360502',
            '0940095360501',
            '0940095360504',
            '0940095360503',
            '0903495380002',
            '0903495380003',
            '0902095371203',
            '0902095371202',
            '0902095371204',

              //泳装
              '0515055042704',
'0515055042703',
'0515055042705',
'0515055042702',
'0515055042701',
'0505055142740',
'0505055142742',
'0505055142744',
'0505055142734',
'0505055142736',
'0505055142738',
'0662564580004',
'0662564580001',
'0662564580004',
'0662564580003',
'0662564580002',
'0491252950003',
'0491252950001',
'0491252950002',
'0491252950004'
        ];
        $rowsExceptBns = $itemService->itemsRepository->list(['item_bn'=>$exceptBns], [], -1, 1, ['item_id', 'item_bn', 'store']);
        app('log')->debug('自动下架商品 这几个特殊的bn里做下架处理:'.json_encode($rowsExceptBns));
        if($rowsExceptBns['list']??null){
            foreach($rowsExceptBns['list'] as $k=>$v){
                //
                $allItemBnWithKey[] = ['goods_id' => $v['item_id'], 'item_bn' => $v['item_bn']];
            }
        }

        $goodsItemsService = new ItemsService();
        $result = $goodsItemsService->updateItemOneStatus(1, $allItemBnWithKey, 'instock');
        app('log')->debug('下架不在feed里的商品-function unmarketNotInMpsFeed' . json_encode($allItemBnWithKey));

        echo json_encode($data);
    }
    /**
     * 默认货品上下架问题 function
     *
     * @return void
     */
    function scheduleApproveStatusDefaultItem()
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');
        $itemService = new ItemsService();
        //app('log')->debug('自动上下架商品开始-scheduleApproveStatusDefaultItem');
        $hasGoods = true;
        //app('log')->debug('自动上下架商品开始-scheduleApproveStatusDefaultItem-hasGoods' . $hasGoods);
        $page = 1;
        $limit = 200;
        $exceptBns=[
            '0941195271203',
            '0941195271202',
            '0941195271204',
            '0951095480001',
            '0951095480002',
            '0951095480003',
            '0951095480004',
            '0903295225004',
            '0903295225003',
            '0903295225002',
            '0903295225001',
            '0902295225004',
            '0902295225003',
            '0902295225002',
            '0940095360502',
            '0940095360501',
            '0940095360504',
            '0940095360503',
            '0903495380002',
            '0903495380003',
            '0902095371203',
            '0902095371202',
            '0902095371204',
                        //泳装

            '0515055042704',
'0515055042703',
'0515055042705',
'0515055042702',
'0515055042701',
'0505055142740',
'0505055142742',
'0505055142744',
'0505055142734',
'0505055142736',
'0505055142738',
'0662564580004',
'0662564580001',
'0662564580004',
'0662564580003',
'0662564580002',
'0491252950003',
'0491252950001',
'0491252950002',
'0491252950004'
        ];
        while ($hasGoods) {
            try {
                $last = [];

                $spuLists = $itemService->itemsRepository->list(['is_default' => 1], [], $limit, $page, ['item_id', 'item_bn', 'store']);

                $spuLists = $spuLists['list'] ?? [];
                //app('log')->debug('自动上下架商品开始-scheduleApproveStatusDefaultItem-spuLists 第'.$page.'页/共' . count($spuLists));

                if ($spuLists && count($spuLists) > 0) {
                    $page++;
                    $hasGoods = true;
                } else {
                    $hasGoods = false;
                    break;
                }
                //
                foreach ($spuLists as $k => $v) {
                    if(!in_array($v['item_bn'],$exceptBns)){
                        //如果其中有一个sku库存不是0，那就要上架本身+default_item_id。
                        $skuLists = $itemService->itemsRepository->list(['default_item_id' => $v['item_id']], [], -1, 1, ['item_id', 'item_bn', 'store']);
                        $skuLists = $skuLists['list'];

                        $allSkuStore = array_column($skuLists, 'store');
                        $allItemBnWithKey = []; //所有货号
                        foreach ($skuLists as $ksku => $vsku) {
                            $allItemBnWithKey[] = ['goods_id' => $vsku['item_id'], 'item_bn' => $vsku['item_bn']];
                        }
                        //如果所有sku库存都是0，下架所有
                        if (array_sum($allSkuStore) == 0) {
                            $result = $itemService->updateItemOneStatus(1, $allItemBnWithKey, 'instock');
                            $last['alldown'] = $allItemBnWithKey;
                        } else {
                            $needUp = [];
                            $needDow = [];
                            //遍历哪些需要上架，哪些需要下架
                            foreach ($skuLists as $ksku2 => $vsku2) {
                                if ($vsku2['store'] > 0) {
                                    $needUp[] = ['goods_id' => $vsku2['item_id'], 'item_bn' => $vsku2['item_bn']];
                                } else {
                                    $needDow[] = ['goods_id' => $vsku2['item_id'], 'item_bn' => $vsku2['item_bn']];
                                }
                            }
                            if ($needDow) {
                                //先下架
                                $result = $itemService->updateItemOneStatus(1, $needDow, 'instock');
                            }
                            //再上架2022-10-19 18:48:51
                            if ($needUp) {
                                //上架
                                $needUp[] = ['goods_id' => $v['item_id'], 'item_bn' => $v['item_bn']]; //spu的，即默认SKU的，也必须要上架，否则前台搜索不到。

                                //app('log')->debug('自动上下架商品 处理上架商品-scheduleApproveStatusDefaultItem-spuLists needUp' . json_encode($needUp));

                                $result = $itemService->updateItemOneStatus(1, $needUp, 'onsale');
                            }

                            $last['needUp'] = $needUp;
                            $last['needDow'] = $needDow;
                           // app('log')->debug('自动上下架商品1629 处理上架商品-scheduleApproveStatusDefaultItem-spuLists needUp' . json_encode($last));

                        }
                    }
                    else{
                       // app('log')->debug('自动上下架商品 处理结束-是排除在外的bn里，不做上下架处理:'.json_encode($exceptBns));
                    }

                }
               // app('log')->debug('自动上下架商品 处理结束-scheduleApproveStatusDefaultItem-spuLists 第'.($page-1).'页/共' . count($spuLists));

            } catch (\Exception $e) {
                //app('log')->debug('自动上下架商品scheduleApproveStatusDefaultItem-报错了' . $e->getMessage());

                $hasGoods = false;
            }
        }

        return ($last);
    }
}
