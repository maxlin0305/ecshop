<?php

namespace GoodsBundle\Services;

use OrdersBundle\Entities\ShippingTemplates;
use KaquanBundle\Services\VipGradeService;
use KaquanBundle\Services\MemberCardService;
use PromotionsBundle\Services\MemberPriceService;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use GuzzleHttp\Client as Client;

class NormalGoodsUploadService
{
    public const MEMBER_PRICE_KEY = '會員價';//忽略的字段，不導入

    public $memberPriceHeaderReady = false;//會員價表頭已經加載

    public $itemName = null;

    public $defaultItemId = null;

    public $header = [
        '管理分類' => 'item_main_category',
        '商品名稱' => 'item_name',
        '商品編碼' => 'item_bn',
        '簡介' => 'brief',
        '商品價格' => 'price',
        '市場價' => 'market_price',
        '成本價' => 'cost_price',
        '會員價' => 'member_price',//會被替換
        '庫存' => 'store',
        '圖片' => 'pics',
        '視頻' => 'videos',
        '品牌' => 'goods_brand',
        '運費模板' => 'templates_id',
        '分類' => 'item_category',
        '重量' => 'weight',
        '條形碼' => 'barcode',
        '單位' => 'item_unit',
        '規格值' => 'item_spec',
        '參數值' => 'item_params',
        '是否支持分潤' => 'is_profit',
        '分潤類型' => 'profit_type',
        '拉新分潤' => 'profit',
        '推廣分潤' => 'popularize_profit',
        '商品狀態' => 'approve_status',
    ];

    public $headerInfo = [
        '管理分類' => ['size' => 255, 'remarks' => '類目名稱，一級類目->二級類目->三級類目', 'is_need' => true],
        '商品名稱' => ['size' => 255, 'remarks' => '', 'is_need' => true],
        '商品編碼' => ['size' => 32, 'remarks' => '', 'is_need' => false],
        '簡介' => ['size' => 20, 'remarks' => '', 'is_need' => false],
        '商品價格' => ['size' => 255, 'remarks' => '單位為(元)，最多兩位小數', 'is_need' => true],
        '市場價' => ['size' => 255, 'remarks' => '單位為(元)，最多兩位小數', 'is_need' => false],
        '成本價' => ['size' => 255, 'remarks' => '單位為(元)，最多兩位小數', 'is_need' => false],
        '會員價' => ['size' => 255, 'remarks' => '單位為(元)，最多兩位小數', 'is_need' => false],//會被替換
        '庫存' => ['size' => 255, 'remarks' => '庫存為0-999999999的整數', 'is_need' => true],
        '圖片' => ['size' => 255, 'remarks' => '多個圖片使用英文逗號隔開，最多上傳9個', 'is_need' => false],
        '視頻' => ['size' => 255, 'remarks' => '在視頻素材複製對應的ID', 'is_need' => false],
        '品牌' => ['size' => 255, 'remarks' => '已有的品牌名稱', 'is_need' => false],
        '運費模板' => ['size' => 255, 'remarks' => '運費模板名稱', 'is_need' => true],
        '分類' => ['size' => 255, 'remarks' => '分類名稱，一級分類->二級分類|一級分類->二級分類>三級分類 多個二級三級分類使用|隔開', 'is_need' => true],
        '重量' => ['size' => 255, 'remarks' => '商品重量，單位KG', 'is_need' => false],
        '條形碼' => ['size' => 255, 'remarks' => '條形碼', 'is_need' => false],
        '單位' => ['size' => 255, 'remarks' => '單位', 'is_need' => false],
        '規格值' => ['size' => 255, 'remarks' => '例如：顏色:紅色|尺碼:20cm，必須和管理分類一起導入', 'is_need' => false],
        '參數值' => ['size' => 255, 'remarks' => '例如：係列:生機展顏|功效:美白提亮', 'is_need' => false],
        '是否支持分潤' => ['size' => 255, 'remarks' => '是否支持: 0不支持分潤 1支持分潤', 'is_need' => false],
        '分潤類型' => ['size' => 255, 'remarks' => '分潤類型:0,1或2, 0默認分潤 1固定比例分潤 2固定金額分潤', 'is_need' => true],
        '拉新分潤' => ['size' => 255, 'remarks' => '1:按照比例分潤 1-100, 2:按照固定金額分潤(元)，最多兩位小數', 'is_need' => false],
        '推廣分潤' => ['size' => 255, 'remarks' => '1:按照比例分潤 1-100, 2:按照固定金額分潤(元)，最多兩位小數', 'is_need' => false],
        '商品狀態' => ['size' => 30, 'remarks' => '前台可銷售，前端不展示，不可銷售, 前台僅展示', 'is_need' => false],
    ];

    public $allApproveStatus = [
        '前台可銷售' => 'onsale',
        '前端不展示' => 'offline_sale',
        '不可銷售' => 'instock',
        '前台僅展示' => 'only_show',
    ];

    public $isNeedCols = [
        '管理分類' => 'item_main_category',
        '商品名稱' => 'item_name',
        '商品價格' => 'price',
        '庫存' => 'store',
        '運費模板' => 'templates_id',
        '分類' => 'item_category',
        '分潤類型' => 'profit_type',
    ];
    public $tmpTarget = null;

    /**
     * 驗證上傳的實體商品信息
     */
    public function check($fileObject)
    {
        $extension = $fileObject->getClientOriginalExtension();
        if ($extension != 'xlsx') {
            throw new BadRequestHttpException('實體商品信息上傳隻支持Excel文件格式(xlsx)');
        }
    }

    /**
     * getFilePath function
     *
     * @return string
     */
    public function getFilePath($filePath, $fileExt = '')
    {
        $url = $this->getFileSystem()->privateDownloadUrl($filePath);

        //兼容本地文件存儲
        if (strtolower(substr($url, 0, 4)) != 'http') {
            $url = storage_path('uploads') . '/' . $filePath;
            $content = file_get_contents($url);
        } else {
            $client = new Client();
            $content = $client->get($url)->getBody()->getContents();
        }

        $this->tmpTarget = tempnam('/tmp', 'import-file') . $fileExt;
        file_put_contents($this->tmpTarget, $content);

        return $this->tmpTarget;
    }

    public function getFileSystem()
    {
        return app('filesystem')->disk('import-file');
    }

    public function finishHandle()
    {
        unlink($this->tmpTarget);
        return true;
    }

    /**
     * 獲取頭部標題
     */
    public function getHeaderTitle($companyId = 0)
    {
        $this->addMemberPriceHeader($companyId);
        return ['all' => $this->header, 'is_need' => $this->isNeedCols, 'headerInfo' => $this->headerInfo];
    }

    /**
     * 增加支持會員價字段導入
     */
    public function addMemberPriceHeader($companyId = 0)
    {
        if ($this->memberPriceHeaderReady) {
            return true;
        }

        if (!$companyId) {
            return false;
        }

        //獲取VIP會員等級
        $vipGradeService = new VipGradeService();
        $vipGrade = $vipGradeService->lists(['company_id' => $companyId, 'is_disabled' => false]);
        if ($vipGrade) {
            $vipGrade = array_column($vipGrade, null, 'vip_grade_id');
        }

        //獲取普通會員等級
        $kaquanService = new MemberCardService();
        $userGrade = $kaquanService->getGradeListByCompanyId($companyId, false);
        if ($userGrade) {
            $userGrade = array_column($userGrade, null, 'grade_id');
        }

        $this->_setHeader($userGrade, $vipGrade);
        $this->_setHeaderInfo($userGrade, $vipGrade);

        $this->memberPriceHeaderReady = true;

        return true;
    }

    /**
     * 設置會員價導入頭信息
     *
     * @param string $memberPriceKey
     * @param array $userGrade
     * @param array $vipGrade
     */
    private function _setHeader($userGrade = [], $vipGrade = [])
    {
        $newHeader = [];
        foreach ($this->header as $k => $v) {
            if ($k != self::MEMBER_PRICE_KEY) {
                $newHeader[$k] = $v;
                continue;
            }

            foreach ($userGrade as $grade) {
                $newHeader[$grade['grade_name']] = 'grade_price' . $grade['grade_id'];
            }

            foreach ($vipGrade as $grade) {
                $newHeader[$grade['grade_name']] = 'vipGrade_price' . $grade['vip_grade_id'];
            }
        }

        $this->header = $newHeader;
    }

    /**
     * 設置會員價導入頭的字段說明
     *
     * @param string $memberPriceKey
     * @param array $userGrade
     * @param array $vipGrade
     */
    private function _setHeaderInfo($userGrade = [], $vipGrade = [])
    {
        //$dataFormat = ['size' => 255, 'remarks' => '單位為(元)，最多兩位小數', 'is_need' => false];
        $dataFormat = $this->headerInfo[self::MEMBER_PRICE_KEY];
        $newHeaderInfo = [];
        foreach ($this->headerInfo as $k => $v) {
            if ($k != self::MEMBER_PRICE_KEY) {
                $newHeaderInfo[$k] = $v;
                continue;
            }

            foreach ($userGrade as $grade) {
                $newHeaderInfo[$grade['grade_name']] = $dataFormat;
            }

            foreach ($vipGrade as $grade) {
                $newHeaderInfo[$grade['grade_name']] = $dataFormat;
            }
        }

        $this->headerInfo = $newHeaderInfo;
    }

    public function handleRow($companyId, $row)
    {
        //app('log')->debug("\n _uploadItems handleRow =>:".json_encode($row, 256));

        //支持導入更新商品數據
        $row['goods_id'] = false;
        $row['item_id'] = false;
        if ($row['item_bn']) {
            $filter = ['item_bn' => $row['item_bn'], 'company_id' => $companyId];
            $itemsService = new ItemsService();
            $oldItemInfo = $itemsService->getItem($filter);
            if ($oldItemInfo) {
                $row['default_item_id'] = $oldItemInfo['default_item_id'];
                $row['goods_id'] = $oldItemInfo['goods_id'];
                $row['item_id'] = $oldItemInfo['item_id'];//如果存在，更新商品數據

                if ($row['distributor_id'] != $oldItemInfo['distributor_id']) {
                    throw new BadRequestHttpException('商品編碼已存在其他店鋪中，不能更新');
                }

                $this->updateGoods($companyId, $row, $oldItemInfo);
                return;
            }
        }

        $this->createGoods($companyId, $row);
    }

    private function createGoods($companyId, $row)
    {
        $itemsService = new ItemsService();

        $validatorData = $this->validatorData($row);

        $rules = [
            'item_name' => ['required', '請填寫商品名稱'],
            'price' => ['required', '請填寫價格'],
            // 'store' => ['required|integer|min:0|max:999999999', '庫存為0-999999999的整數'],
            'templates_id' => ['required', '請填寫運費模板'],
        ];
        $errorMessage = validator_params($validatorData, $rules, false);
        if (intval($row['store']) < 0 || intval($row['store']) > 999999999) {
            $errorMessage[] = '庫存為0-999999999的整數';
        }
        if ($errorMessage) {
            $msg = implode(', ', $errorMessage);
            throw new BadRequestHttpException($msg);
        }

        $nospec = $row['item_spec'] ? 'false' : 'true';
        //表示為多規格，並且已經存儲了默認商品，所以隻需要新增當前商品數據，通用關聯數據不需要更新，例如：商品關聯的分類，關聯的品牌等
        if ($nospec == 'false' && $this->itemName && trim($row['item_name']) == $this->itemName) {
            $isCreateRelData = false;
            $defaultItemId = $this->defaultItemId;
        } else {
            $isCreateRelData = true;
            $defaultItemId = null;
        }
        $row['default_item_id'] = $defaultItemId;
        $itemsProfitService = new ItemsProfitService();

        $profitType = 0;
        $profitFee = 0;
        if (!in_array(intval($row['is_profit']), [0, 1])) {
            throw new BadRequestHttpException('是否支持分潤參數錯誤');
        }
        $row['profit_type'] = intval($row['profit_type']);
        if ($row['profit_type'] >= 0) {
            if (!in_array($row['profit_type'], [$itemsProfitService::STATUS_PROFIT_DEFAULT, $itemsProfitService::STATUS_PROFIT_SCALE, $itemsProfitService::STATUS_PROFIT_FEE])) {
                throw new BadRequestHttpException('分潤類型錯誤');
            }
            if (0 != $row['profit_type']) {
                if (!($row['profit'] ?? 0)) {
                    throw new BadRequestHttpException('拉新分潤金額不能為空');
                }
                if (!($row['popularize_profit'] ?? 0)) {
                    throw new BadRequestHttpException('推廣分潤金額不能為空');
                }
                $profitType = $itemsProfitService::STATUS_PROFIT_SCALE == $row['profit_type'] ? $itemsProfitService::PROFIT_ITEM_PROFIT_SCALE : $itemsProfitService::PROFIT_ITEM_PROFIT_FEE;
                $profitFee = $itemsProfitService::STATUS_PROFIT_SCALE == $row['profit_type'] ? bcmul(bcdiv($row['popularize_profit'], 100, 4), $row['price'], 2) : $row['popularize_profit'];
            }
        }

        $mainCategory = $this->getItemMainCategoryId($companyId, $row);//獲取主類目信息

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
            'store' => $row['store'],
            'is_default' => $isCreateRelData,
            //'is_profit' => intval($row['is_profit']) ?? 0,
            'is_profit' => ($isProfit == 1) ? 'true' : 'false',
            'profit_type' => $profitType,
            'profit_fee' => $profitFee,
            'item_type' => 'normal',
            'sort' => 1,
            'approve_status' => 'onsale',
            'intro' => '',
            'distributor_id' => $row['distributor_id'],
        ];

        // 商品上下架狀態，默認為 onsale
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
                'store' => $row['store'],
                'is_default' => $isCreateRelData,
                'default_item_id' => $defaultItemId,
                'item_spec' => $this->getItemSpec($companyId, $row, $mainCategory),
                'approve_status' => $itemInfo['approve_status'],
            ];
            $specItems[] = $specItem;
            $itemInfo['spec_items'] = json_encode($specItems);
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
                //按金額
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
            app('log')->debug("\n _uploadItems error =>:" . $e->getMessage());
            throw new BadRequestHttpException($e->getMessage());
        } catch (\Throwable $e) {
            $conn->rollback();
            app('log')->debug("\n _uploadItems error =>:" . $e->getMessage());
            throw new \Exception($e->getMessage());
        }

        //保存商品的會員價，注意，這裏麵有事務，不能和上麵的事務疊加
        $this->_saveMemberPrice($row, $result['item_id'], $companyId);
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

        // 商品價格，用來計算分潤
        $itemPrice = $oldItemInfo['price'];
        if (isset($row['price']) && $row['price']) {
            $itemPrice = bcmul($row['price'], 100);
        }

        //是否支持分潤參數
        if (!empty($row['is_profit'])) {
            if (!in_array($row['is_profit'], ['0', '1'])) {
                throw new BadRequestHttpException('是否支持分潤參數錯誤');
            }
            $itemInfo['is_profit'] = ($row['is_profit'] == '1') ? 'true' : 'false';

            if (!empty($row['profit_type'])) {
                $row['profit_type'] = intval($row['profit_type']);
                if (!in_array($row['profit_type'], [$itemsProfitService::STATUS_PROFIT_DEFAULT, $itemsProfitService::STATUS_PROFIT_SCALE, $itemsProfitService::STATUS_PROFIT_FEE])) {
                    throw new BadRequestHttpException('分潤類型錯誤');
                }
                if (0 != $row['profit_type']) {
                    if (!($row['profit'] ?? 0)) {
                        throw new BadRequestHttpException('拉新分潤金額不能為空');
                    }
                    if (!($row['popularize_profit'] ?? 0)) {
                        throw new BadRequestHttpException('推廣分潤金額不能為空');
                    }
                    $profitType = $itemsProfitService::STATUS_PROFIT_SCALE == $row['profit_type'] ? $itemsProfitService::PROFIT_ITEM_PROFIT_SCALE : $itemsProfitService::PROFIT_ITEM_PROFIT_FEE;
                    $profitFee = $itemsProfitService::STATUS_PROFIT_SCALE == $row['profit_type'] ? bcmul(bcdiv($row['popularize_profit'], 100, 4), $itemPrice, 2) : bcmul($row['popularize_profit'], 100);
                }
                $itemInfo['profit_type'] = $profitType;
                $itemInfo['profit_fee'] = $profitFee;
            }
        }

        //獲取主類目信息
        $mainCategory = [];
        if ($row['item_main_category']) {
            $mainCategory = $this->getItemMainCategoryId($companyId, $row);
            $itemInfo['item_main_cat_id'] = $mainCategory['category_id'];
        }

        foreach ($row as $k => $v) {
            switch ($k) {
                case 'item_category':
                    if (!$v) {
                        break;
                    }
                    $itemInfo['item_category'] = $this->getItemCategoryNew($companyId, $row, false);
                    break;

                case 'templates_id':
                    if (!$v) {
                        break;
                    }
                    $itemInfo['templates_id'] = $this->getTemplatesId($companyId, $row);
                    break;

                case 'pics':
                    if (!$v) {
                        break;
                    }
                    $itemInfo['pics'] = $row['pics'] ? explode(',', $row['pics']) : [];
                    break;

                case 'goods_brand':
                    if (!$v) {
                        break;
                    }
                    $itemInfo['brand_id'] = $this->getBrandId($companyId, $row);
                    break;

                case 'item_spec':
                    //商品規格，必須和主類目一起導入
                    if (empty($v) or !$mainCategory) {
                        break;
                    }
                    $itemInfo['nospec'] = 'false';
                    $itemInfo['item_spec'] = $this->getItemSpec($companyId, $row, $mainCategory);
                    break;

                case 'item_params':
                    if (empty($v)) {
                        break;
                    }
                    $itemInfo['item_params'] = $this->getItemParams($companyId, $row);
                    break;

                case 'approve_status':
                    if (empty($v)) {
                        break;
                    }
                    if (!isset($this->allApproveStatus[$v])) {
                        throw new BadRequestHttpException('商品狀態錯誤');
                    }
                    $itemInfo['approve_status'] = $this->allApproveStatus[$v];
                    break;

                case 'price':
                case 'cost_price':
                case 'market_price':
                    if (!$v) {
                        $v = 0;
                        // break;
                    }
                    $itemInfo[$k] = bcmul($v, 100);
                    break;

                default:
                    if (empty($v)) {
                        break;
                    }
                    $itemInfo[$k] = trim($v);
            }
        }

        //app('log')->debug('_uploadItems itemInfo =>: '.json_encode($itemInfo, 256));

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
                //按金額
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
     * 保存商品的會員價
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
        $priceValid = false;//是否存在有效的會員價格
        foreach ($row as $k => $v) {
            foreach ($priceLabel as $label) {
                if (!isset($memberPrice[$itemId][$label])) {
                    $memberPrice[$itemId][$label] = [];//初始化結構，防止報錯
                }
                if (strstr($k, $label . '_price')) {
                    $gradeId = str_replace($label . '_price', '', $k);
                    $v = floatval($v);
                    if (!$v) {
                        $v = '';//不合法的價格都設置成空
                    } else {
                        $priceValid = true;
                    }
                    $memberPrice[$itemId][$label][$gradeId] = $v;
                }
            }
        }

        //會員價必須一起更新，如果沒有填寫任何會員價，不做更新
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

    private function validatorData($row)
    {
        $arr = ['item_name', 'price', 'store', 'templates_id'];
        $data = [];
        foreach ($arr as $column) {
            if ($row[$column]) {
                $data[$column] = $row[$column];
            }
        }

        return $data;
    }

    /**
     * 通過運費模版名稱，獲取運費模版ID
     */
    private function getTemplatesId($companyId, $row)
    {
        if (!$row['templates_id']) {
            throw new BadRequestHttpException('請填寫商品運費模版');
        }

        $shippingTemplatesRepository = app('registry')->getManager('default')->getRepository(ShippingTemplates::class);
        $data = $shippingTemplatesRepository->getInfo(['name' => $row['templates_id'], 'company_id' => $companyId, 'distributor_id' => $row['distributor_id']]);
        if (!$data) {
            throw new BadRequestHttpException('填寫的運費模版不存在');
        }

        return $data['template_id'];
    }

    /**
     * 獲取商品主類目
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
            throw new BadRequestHttpException('請上傳管理分類');
        }
        $catNamesArr = explode($splitChar, $mainCategory);
        if (count($catNamesArr) != 3) {
            throw new BadRequestHttpException('上傳管理分類必須是三層級,' . $mainCategory);
        }

        $itemsCategoryService = new ItemsCategoryService();
        $lists = $itemsCategoryService->lists(['company_id' => $companyId, 'category_name' => $catNamesArr, 'is_main_category' => 1]);
        if ($lists['total_count'] <= 0) {
            throw new BadRequestHttpException('上傳管理分類不存在,' . $mainCategory);
        }

        $categoryName = array_column($lists['list'], 'category_name', 'category_id');
        foreach ($lists['list'] as $v) {
            if (!$v['path']) {
                continue;
            }
            $paths = explode(',', $v['path']);
            $pathName = [];
            foreach ($paths as $id) {
                if (!isset($categoryName[$id])) {
                    continue;
                }
                $pathName[] = $categoryName[$id];
            }
            //根據路徑判斷，找到一樣的為止
            if (implode($splitChar, $pathName) == $mainCategory) {
                $categoryInfo = $v;
                break;
            }
        }

        if (!$categoryInfo) {
            throw new BadRequestHttpException('無法識別的管理分類,' . $mainCategory);
        }

        //array_multisort($lists['list'], SORT_ASC, array_column($lists['list'], 'category_level'));
        //$categoryInfo = end($lists['list']);
        return $categoryInfo;
    }

    /**
     * 獲取商品分類，這個函數有bug，用 getItemCategoryNew 替代
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
                throw new BadRequestHttpException('請上傳管理分類');
            } else {
                throw new BadRequestHttpException('請上傳商品分類');
            }
        }

        $catNamesArr = array();
        foreach ($catNames as $catNameRow) {
            $catNamesArr = array_merge($catNamesArr, explode('->', $catNameRow));
        }

        $itemsCategoryService = new ItemsCategoryService();
        // 數據結構買辦法判斷獲取的分類ID是否最子級分類，三級分類改造後在優化
        $lists = $itemsCategoryService->lists(['company_id' => $companyId, 'category_name' => $catNamesArr, 'is_main_category' => $isMain]);
        if ($lists['total_count'] <= 0) {
            if ($isMain) {
                throw new BadRequestHttpException('上傳管理分類參數有誤');
            } else {
                throw new BadRequestHttpException('上傳商品分類參數有誤');
            }
        }
        //主類目

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
                throw new BadRequestHttpException('上傳管理分類參數有誤');
            } else {
                throw new BadRequestHttpException('上傳商品分類參數有誤');
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
                    if ($result) {
                        break;
                    }
                }
                if ($result) {
                    continue;
                }
                foreach ($path2Arr as $v) {
                    $result = 0 === strpos($v, $catRow['path'] . ',') ? true : false;
                    if ($result) {
                        break;
                    }
                }
                if ($result) {
                    continue;
                }
                $catId[] = $catRow['category_id'];
            }
            if ($parentArr && $parentArr == array_intersect($parentArr, $parentIds)) {
                $catId[] = $catRow['category_id'];
            }
        }
        if (!$catId) {
            if ($isMain) {
                throw new BadRequestHttpException('上傳管理分類參數有誤');
            } else {
                throw new BadRequestHttpException('上傳商品分類參數有誤');
            }
        }
        return $catId;
    }

    /**
     * 獲取商品分類
     */
    private function getItemCategoryNew($companyId, $row)
    {
        if (!$row['item_category']) {
            throw new BadRequestHttpException('請上傳商品分類');
        }

        $catId = [];
        $category = $row['item_category'];
        $catNames = explode('|', $category);

        $catNamesArr = array();
        foreach ($catNames as $catNameRow) {
            $catNamesArr = array_merge($catNamesArr, explode('->', $catNameRow));
        }

        $itemsCategoryService = new ItemsCategoryService();
        // 數據結構買辦法判斷獲取的分類ID是否最子級分類，三級分類改造後在優化
        $filter = ['company_id' => $companyId, 'distributor_id' => $row['distributor_id'], 'category_name' => $catNamesArr, 'is_main_category' => 0];
        $lists = $itemsCategoryService->lists($filter);
        if ($lists['total_count'] <= 0) {
            throw new BadRequestHttpException('上傳商品分類參數有誤');
        }

        // 服裝->套裝->連衣裙
        $catNamePath = [];
        $categoryName = array_column($lists['list'], 'category_name', 'category_id');
        foreach ($lists['list'] as $catRow) {
            $path = explode(',', $catRow['path']);
            foreach ($path as $categoryId) {
                if (!isset($categoryName[$categoryId])) {
                    continue;
                }
                if (isset($catNamePath[$catRow['category_id']])) {
                    $catNamePath[$catRow['category_id']] .= '->' . $categoryName[$categoryId];
                } else {
                    $catNamePath[$catRow['category_id']] = $categoryName[$categoryId];
                }
            }
        }

        foreach ($catNamePath as $categoryId => $v) {
            if (in_array($v, $catNames)) {
                $catId[] = $categoryId;
            }
        }

        //app('log')->debug('_uploadItems catNamePath =>:'.json_encode($catNamePath, 256));
        //app('log')->debug('_uploadItems catId =>:'.json_encode($catId, 256));

        if (!$catId) {
            throw new BadRequestHttpException('上傳商品分類參數有誤');
        }
        return $catId;
    }

    /**
     * 通過品牌名稱獲取品牌ID
     */
    private function getBrandId($companyId, $row)
    {
        $brandName = $row['goods_brand'] ?? "";
        $brandId = 0;
        if ($brandName) {
            $itemsAttributesService = new ItemsAttributesService();
            $data = $itemsAttributesService->getInfo(['company_id' => $companyId, 'distributor_id' => $row['distributor_id'], 'attribute_name' => $brandName, 'attribute_type' => 'brand']);
            if (!$data) {
                throw new BadRequestHttpException($brandName . ' 品牌名稱不存在');
            }
            $brandId = $data['attribute_id'];
        }
        return $brandId;
    }

    /**
     * 獲取商品參數
     *
     * item_params: 功效:美白提亮|性別:男性
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
                $attributeValues[$itemRow[0]] = $itemRow[1];
            }

            $attrList = $itemsAttributesService->lists(['company_id' => $companyId, 'attribute_name' => $attributeNames, 'attribute_type' => 'item_params']);
            if ($attrList['total_count'] > 0) {
                foreach ($attrList['list'] as $row) {
                    $attrValue = $itemsAttributesService->getAttrValue(['company_id' => $companyId, 'attribute_value' => $attributeValues[$row['attribute_name']], 'attribute_id' => $row['attribute_id']]);
                    if ($attrValue) {
                        $data[] = [
                            'attribute_id' => $attrValue['attribute_id'],
                            'attribute_value_id' => $attrValue['attribute_value_id']
                        ];
                    }
                }
            } else {
                throw new BadRequestHttpException('商品參數不存在');
            }
        }

        return $data;
    }

    private function getItemSpec($companyId, $item, &$mainCategory = [])
    {
        $data = [];
        $specInfo = [];
        if ($item['item_spec']) {
            //根據主類目獲取商品規格屬性的排序
            $goodsSpecIds = $mainCategory['goods_spec'];
            if (!is_array($goodsSpecIds)) {
                $goodsSpecIds = json_decode($goodsSpecIds, true);
            }

            $itemsAttributesService = new ItemsAttributesService();
            $itemParams = explode('|', $item['item_spec']);
            foreach ($itemParams as $row) {
                $itemRow = explode(':', $row);
                if (empty($itemRow[0])) {
                    throw new BadRequestHttpException('商品規格解析錯誤');
                }
                if (empty($itemRow[1])) {
                    throw new BadRequestHttpException('商品規格值解析錯誤');
                }
                $attributeNames[] = $itemRow[0];
                $attributeValues[] = $itemRow[1];
            }

            // $goodsSpecIds 隻查詢當前主類目關聯的規格
            $filter = [
                'company_id' => $companyId, 'attribute_name' => $attributeNames,
                'attribute_id' => $goodsSpecIds, 'attribute_type' => 'item_spec'
            ];
            $attrList = $itemsAttributesService->lists($filter, 1, 100, ['is_image' => 'DESC', 'attribute_id' => 'ASC']);
            if ($attrList['total_count'] == count($attributeNames)) {
                $attributeids = array_column($attrList['list'], 'attribute_id');
            } else {
                throw new BadRequestHttpException('商品規格[' . implode(',', $attributeNames) . ']存在無效值');
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
                throw new BadRequestHttpException('商品規格值[' . implode(',', $attributeValues) . ']無效');
            }

            //排序，按ID升序，按圖像規格倒序
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

            if (isset($item['default_item_id']) && $item['default_item_id']) {
                $conn = app('registry')->getConnection('default');
                $qb = $conn->createQueryBuilder();
                $exist = $qb->select('count(a.item_id)')
                    ->from('items_rel_attributes', 'a')
                    ->leftJoin('a', 'items', 'i', 'a.item_id = i.item_id and a.attribute_type = '.$qb->expr()->literal('item_spec'))
                    ->andWhere($qb->expr()->eq('i.default_item_id', $item['default_item_id']))
                    ->andWhere($qb->expr()->neq('i.item_bn', $qb->expr()->literal(trim($item['item_bn']))))
                    ->andWhere($qb->expr()->in('a.attribute_value_id', array_column($specInfo, 'spec_value_id')))
                    ->groupBy('a.item_id')
                    ->having('count(*) = '.count($specInfo))
                    ->execute()->fetchColumn();
                if ($exist) {
                    throw new BadRequestHttpException('相同規格值的商品已存在');
                }
            }
        }

        /*
        usort($data, function($a, $b) {
            if($a['spec_id'] == $b['spec_id']) return 0;
            else return $a['spec_id'] > $b['spec_id'] ? 1 : -1;
        });
        */

        return $specInfo;
    }

    public function getDemoData()
    {
        $data = [
            [
                'item_main_category' => 'HP->上衣->HP',
                'item_name' => '上衣外套',
                'item_bn' => 'S61401E3179BB6',
                'brief' => '1',
                'price' => '0.01',
                'market_price' => '0',
                'cost_price' => '0',
                'member_price' => '',
                'store' => '99',
                'pics' => '',
                'videos' => '',
                'goods_brand' => '雲店',
                'templates_id' => '0.01',
                'item_category' => '食品副食->鹹味食品->屏幕故障',
                'weight' => '0',
                'barcode' => '',
                'item_unit' => '',
                'item_spec' => '',
                'item_params' => '',
                'is_profit' => '1',
                'profit_type' => '0',
                'profit' => '',
                'popularize_profit' => '',
                'approve_status' => '不可銷售'
            ],
            [
                'item_main_category' => '新鮮零食->新品６折->休閑食品',
                'item_name' => '雲店特產',
                'item_bn' => 'S6139A88AB7181',
                'brief' => '多種糕點 設計師原創插畫包裝',
                'price' => '309',
                'market_price' => '599',
                'cost_price' => '100',
                'member_price' => '',
                'store' => '1000',
                'pics' => 'https://b-img-cdn.yuanyuanke.cn/image/21/2021/09/09/e256a607a5914062d406916bc22b1465BRHbeKZOlgTyIDnPmitVXJESiSXLCaDk,https://b-img-cdn.yuanyuanke.cn/image/21/2021/09/09/89c74bf83949f6f67e989f7f48aa12baezCydrvcrV2bSoxPK5YWlDLrxVSZQrsh,https://b-img-cdn.yuanyuanke.cn/image/21/2021/09/09/89f2b37de9f030ee4a39b57acb68e34bk6Gluazl1KoKiucCoXU2W9HvhAxzaGf9',
                'videos' => '',
                'goods_brand' => '雲店',
                'templates_id' => '快遞包郵',
                'item_category' => '新品6折->糕點麵包|新品6折->休閑食品',
                'weight' => '0',
                'barcode' => '',
                'item_unit' => '',
                'item_spec' => '口味:芒果味',
                'item_params' => '',
                'is_profit' => '1',
                'profit_type' => '0',
                'profit' => '',
                'popularize_profit' => '',
                'approve_status' => '前台可銷售'
            ],
            [
                'item_main_category' => '新鮮零食->新品６折->休閑食品',
                'item_name' => '雲店特產',
                'item_bn' => 'S6139A88AAE005',
                'brief' => '多種糕點 設計師原創插畫包裝',
                'price' => '309',
                'market_price' => '599',
                'cost_price' => '100',
                'member_price' => '',
                'store' => '1000',
                'pics' => 'https://b-img-cdn.yuanyuanke.cn/image/21/2021/09/09/e256a607a5914062d406916bc22b1465BRHbeKZOlgTyIDnPmitVXJESiSXLCaDk,https://b-img-cdn.yuanyuanke.cn/image/21/2021/09/09/89c74bf83949f6f67e989f7f48aa12baezCydrvcrV2bSoxPK5YWlDLrxVSZQrsh,https://b-img-cdn.yuanyuanke.cn/image/21/2021/09/09/89f2b37de9f030ee4a39b57acb68e34bk6Gluazl1KoKiucCoXU2W9HvhAxzaGf9',
                'videos' => '',
                'goods_brand' => '雲店',
                'templates_id' => '快遞包郵',
                'item_category' => '新品6折->糕點麵包|新品6折->休閑食品',
                'weight' => '0',
                'barcode' => '',
                'item_unit' => '',
                'item_spec' => '口味:巧克力味',
                'item_params' => '',
                'is_profit' => '1',
                'profit_type' => '0',
                'profit' => '',
                'popularize_profit' => '',
                'approve_status' => '前台可銷售'
            ],
            [
                'item_main_category' => '新鮮零食->新品６折->休閑食品',
                'item_name' => '雲店特產',
                'item_bn' => 'S6139A88AA1930',
                'brief' => '多種糕點 設計師原創插畫包裝',
                'price' => '309',
                'market_price' => '599',
                'cost_price' => '100',
                'member_price' => '',
                'store' => '1000',
                'pics' => 'https://b-img-cdn.yuanyuanke.cn/image/21/2021/09/09/e256a607a5914062d406916bc22b1465BRHbeKZOlgTyIDnPmitVXJESiSXLCaDk,https://b-img-cdn.yuanyuanke.cn/image/21/2021/09/09/89c74bf83949f6f67e989f7f48aa12baezCydrvcrV2bSoxPK5YWlDLrxVSZQrsh,https://b-img-cdn.yuanyuanke.cn/image/21/2021/09/09/89f2b37de9f030ee4a39b57acb68e34bk6Gluazl1KoKiucCoXU2W9HvhAxzaGf9',
                'videos' => '',
                'goods_brand' => '雲店',
                'templates_id' => '快遞包郵',
                'item_category' => '新品6折->糕點麵包|新品6折->休閑食品',
                'weight' => '0',
                'barcode' => '',
                'item_unit' => '',
                'item_spec' => '口味:燒烤味',
                'item_params' => '',
                'is_profit' => '1',
                'profit_type' => '0',
                'profit' => '',
                'popularize_profit' => '',
                'approve_status' => '前台可銷售'
            ],
            [
                'item_main_category' => '新鮮零食->新品６折->休閑食品',
                'item_name' => '雲店特產',
                'item_bn' => 'S6139A88A987F2',
                'brief' => '多種糕點 設計師原創插畫包裝',
                'price' => '309',
                'market_price' => '599',
                'cost_price' => '100',
                'member_price' => '',
                'store' => '1000',
                'pics' => 'https://b-img-cdn.yuanyuanke.cn/image/21/2021/09/09/e256a607a5914062d406916bc22b1465BRHbeKZOlgTyIDnPmitVXJESiSXLCaDk,https://b-img-cdn.yuanyuanke.cn/image/21/2021/09/09/89c74bf83949f6f67e989f7f48aa12baezCydrvcrV2bSoxPK5YWlDLrxVSZQrsh,https://b-img-cdn.yuanyuanke.cn/image/21/2021/09/09/89f2b37de9f030ee4a39b57acb68e34bk6Gluazl1KoKiucCoXU2W9HvhAxzaGf9',
                'videos' => '',
                'goods_brand' => '雲店',
                'templates_id' => '快遞包郵',
                'item_category' => '新品6折->糕點麵包|新品6折->休閑食品',
                'weight' => '0',
                'barcode' => '',
                'item_unit' => '',
                'item_spec' => '口味:香辣味',
                'item_params' => '',
                'is_profit' => '1',
                'profit_type' => '0',
                'profit' => '',
                'popularize_profit' => '',
                'approve_status' => '前台可銷售'
            ],
            [
                'item_main_category' => '新鮮零食->新品６折->休閑食品',
                'item_name' => '雲店特產',
                'item_bn' => 'S6139A88A8C356',
                'brief' => '多種糕點 設計師原創插畫包裝',
                'price' => '309',
                'market_price' => '599',
                'cost_price' => '100',
                'member_price' => '',
                'store' => '999',
                'pics' => 'https://b-img-cdn.yuanyuanke.cn/image/21/2021/09/09/e256a607a5914062d406916bc22b1465BRHbeKZOlgTyIDnPmitVXJESiSXLCaDk,https://b-img-cdn.yuanyuanke.cn/image/21/2021/09/09/89c74bf83949f6f67e989f7f48aa12baezCydrvcrV2bSoxPK5YWlDLrxVSZQrsh,https://b-img-cdn.yuanyuanke.cn/image/21/2021/09/09/89f2b37de9f030ee4a39b57acb68e34bk6Gluazl1KoKiucCoXU2W9HvhAxzaGf9',
                'videos' => '',
                'goods_brand' => '雲店',
                'templates_id' => '快遞包郵',
                'item_category' => '新品6折->糕點麵包|新品6折->休閑食品',
                'weight' => '0',
                'barcode' => '',
                'item_unit' => '',
                'item_spec' => '口味:混合口味',
                'item_params' => '',
                'is_profit' => '1',
                'profit_type' => '0',
                'profit' => '',
                'popularize_profit' => '',
                'approve_status' => '前台可銷售'
            ],
            [
                'item_main_category' => '新鮮零食->新品６折->休閑食品',
                'item_name' => '雲店特產',
                'item_bn' => 'S6139A88A7BF0F',
                'brief' => '多種糕點 設計師原創插畫包裝',
                'price' => '309',
                'market_price' => '599',
                'cost_price' => '100',
                'member_price' => '',
                'store' => '1000',
                'pics' => 'https://b-img-cdn.yuanyuanke.cn/image/21/2021/09/09/e256a607a5914062d406916bc22b1465BRHbeKZOlgTyIDnPmitVXJESiSXLCaDk,https://b-img-cdn.yuanyuanke.cn/image/21/2021/09/09/89c74bf83949f6f67e989f7f48aa12baezCydrvcrV2bSoxPK5YWlDLrxVSZQrsh,https://b-img-cdn.yuanyuanke.cn/image/21/2021/09/09/89f2b37de9f030ee4a39b57acb68e34bk6Gluazl1KoKiucCoXU2W9HvhAxzaGf9',
                'videos' => '',
                'goods_brand' => '雲店',
                'templates_id' => '快遞包郵',
                'item_category' => '新品6折->糕點麵包|新品6折->休閑食品',
                'weight' => '0',
                'barcode' => '',
                'item_unit' => '',
                'item_spec' => '口味:原味',
                'item_params' => '',
                'is_profit' => '1',
                'profit_type' => '0',
                'profit' => '',
                'popularize_profit' => '',
                'approve_status' => '前台可銷售'
            ],
            [],
            [
                'item_main_category' => '',
                'item_name' => '',
                'item_bn' => '',
                'brief' => '2為一條單規格商品，3-8為一條多規格商品  此文件為DOEM模擬數據，使用該模板請刪除本句，重新修改數據。'
            ]
        ];
        $header = array_flip($this->header);
        $column = [];
        foreach ($header as $key => $value) {
            $column[$key] = '';
        }
        $result = [];
        foreach ($data as $key1 => $value1) {
            $tmpData = $column;
            foreach ($value1 as $itemKey => $item) {
                $tmpData[$itemKey] = $item;
            }
            $result[] = array_values($tmpData);
        }
        return $result;
    }
}
