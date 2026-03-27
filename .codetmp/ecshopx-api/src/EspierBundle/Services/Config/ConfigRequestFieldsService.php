<?php

namespace EspierBundle\Services\Config;

use Carbon\Carbon;
use Dingo\Api\Exception\ResourceException;
use EspierBundle\Entities\ConfigRequestFields;
use EspierBundle\Repositories\ConfigRequestFieldsRepository;
use EspierBundle\Services\Cache\RedisCacheService;
use MembersBundle\Services\MemberRegSettingService;

class ConfigRequestFieldsService
{
    /**
     * 配置請求字段的資源庫
     * @var ConfigRequestFieldsRepository
     */
    private $configRequestFieldsRepository;

    /**
     * 模塊類型
     */
    public const MODULE_TYPE_MEMBER_INFO = 1;
    public const MODULE_TYPE_CHIEF_INFO = 2;
    public const MODULE_TYPE_MAP = [
        self::MODULE_TYPE_MEMBER_INFO => "會員個人信息",
        self::MODULE_TYPE_CHIEF_INFO => "社區團購團長信息"
    ];

    /**
     * 字段類型
     */
    public const FIELD_TYPE_TEXT = 1;
    public const FIELD_TYPE_NUMBER = 2;
    public const FIELD_TYPE_DATE = 3;
    public const FIELD_TYPE_RADIO = 4;
    public const FIELD_TYPE_CHECKBOX = 5;
    public const FIELD_TYPE_MOBILE = 6;
    public const FIELD_TYPE_IMAGE = 7;
    public const FIELD_TYPE_MAP = [
        self::FIELD_TYPE_TEXT => "文本",
        self::FIELD_TYPE_NUMBER => "數字",
        self::FIELD_TYPE_DATE => "日期",
        self::FIELD_TYPE_RADIO => "單選項",
        self::FIELD_TYPE_CHECKBOX => "多選項",
        self::FIELD_TYPE_MOBILE => "手機號",
        self::FIELD_TYPE_IMAGE => "圖片",
    ];
    /**
     * 每個字段類型所對應的元素值
     */
    public const FIELD_TYPE_ELEMENT_MAP = [
        self::FIELD_TYPE_TEXT => "input",
        self::FIELD_TYPE_NUMBER => "numeric",
        self::FIELD_TYPE_DATE => "date",
        self::FIELD_TYPE_RADIO => "select",
        self::FIELD_TYPE_CHECKBOX => "checkbox",
        self::FIELD_TYPE_MOBILE => "mobile",
        self::FIELD_TYPE_IMAGE => "image",
    ];

    public function __construct()
    {
        $this->configRequestFieldsRepository = app('registry')->getManager('default')->getRepository(ConfigRequestFields::class);
    }

    /**
     * 檢查是否存在label字段名
     * @param int $companyId 公司id
     * @param int $moduleType 模塊類型
     * @param string $label 字段名
     * @param int $neqId 排除自己的數據
     */
    protected function checkLabelExist(int $companyId, int $moduleType, string $label, int $neqId = 0, int $distributorId = 0)
    {
        $labelCount = $this->configRequestFieldsRepository->count([
            "company_id" => $companyId,
            "module_type" => $moduleType,
            "label" => $label,
            "id|neq" => $neqId,
            "distributor_id" => $distributorId,
        ]);
        if ($labelCount > 0) {
            throw new ResourceException(sprintf("操作失敗！該模塊下【%s】已存在！", $label));
        }
    }

    /**
     * 檢查是否存在label字段名
     * @param int $companyId 公司id
     * @param int $moduleType 模塊類型
     * @param string $key 字段名的key
     * @param int $neqId 排除自己的數據
     */
    protected function checkKeyExist(int $companyId, int $moduleType, string $key, int $neqId = 0, int $distributorId = 0)
    {
        $keyCount = $this->configRequestFieldsRepository->count([
            "company_id" => $companyId,
            "module_type" => $moduleType,
            "key_name" => $key,
            "id|neq" => $neqId,
            "distributor_id" => $distributorId,
        ]);
        if ($keyCount > 0) {
            throw new ResourceException(sprintf("操作失敗！該模塊下【%s】已存在！", $key));
        }
    }

    /**
     * 檢查是否存在必填切必須開啟的字段
     * @param array $filter 過濾條件
     * @return bool true為存在，false為不存在
     */
    public function checkIsNeedInit(array $filter): bool
    {
        // 企業id
        $companyId = (int)($filter["company_id"] ?? 0);
        if ($companyId <= 0) {
            return true;
        }
        // 模塊類型
        $moduleType = (int)($filter["module_type"] ?? 0);
        //店鋪id
        $distributorId = (int)($filter["distributor_id"] ?? 0);
        // 獲取必須開啟且必填的字段
        $mustStartAndRequiredFields = $this->getMustStartAndRequiredFieldsFromConfig($companyId, $moduleType);
        // 獲取默認的字段
        $defaultFields = $this->getDefaultFieldsFromConfig($companyId, $moduleType);
        // 合並字段集合
        $fields = array_merge($mustStartAndRequiredFields, array_keys($defaultFields));
        // 定義預期的字段數量
        $expectedCount = count($mustStartAndRequiredFields);
        foreach ($defaultFields as $field => $defaultField) {
            if (isset($defaultField[self::SWITCH_COLUMN_DESC_IS_OPEN]) && $defaultField[self::SWITCH_COLUMN_DESC_IS_OPEN] && !in_array($field, $mustStartAndRequiredFields, true)) {
                $expectedCount++;
            }
        }
        // 查詢當前的已開啟字段數量
        $count = $this->configRequestFieldsRepository->count([
            "company_id" => $companyId,
            "module_type" => $moduleType,
            "key_name" => $fields,
            "distributor_id" => $distributorId,
        ]);
        if ($count !== $expectedCount) {
            $this->init($companyId, $moduleType, $distributorId);
        }
        return true;
    }

    /**
     * 檢查該字段是否要必須開啟且必須是必填
     * @param int $companyId 公司id
     * @param int $moduleType 模塊類型
     * @param string $keyName 字段名的key
     */
    protected function checkKeyNameIsMustStartAndRequired(int $companyId, int $moduleType, string $keyName)
    {
        if (in_array($keyName, $this->getMustStartAndRequiredFieldsFromConfig($companyId, $moduleType), true)) {
            throw new ResourceException(sprintf("操作失敗！該模塊下【%s】必須開啟且必須是必填！", $keyName));
        }
    }

    /**
     * 檢查該字段是否是默認項
     * @param int $companyId 公司id
     * @param int $moduleType 模塊類型
     * @param string $keyName 字段名的key
     */
    protected function checkKeyNameIsDefault(int $companyId, int $moduleType, string $keyName)
    {
        $defaultFields = $this->getDefaultFieldsFromConfig($companyId, $moduleType);
        if (isset($defaultFields[$keyName])) {
            throw new ResourceException(sprintf("操作失敗！該模塊下【%s】是默認項，無法刪除！", $keyName));
        }
    }

    /**
     * 創建一條請求字段
     * @param int $companyId 公司id
     * @param int $moduleType 模塊類型
     * @param array $formData 表單數據
     * @return array 插入完成後的數據
     * @throws \Exception
     */
    public function create(int $companyId, int $moduleType, array $formData)
    {
        // 獲取字段內容
        $label = (string)($formData["label"] ?? "");
        // 唯一的key標識符，這裏有兩種情況，一種是自定義的，一種是預設的
        $keyName = (string)($formData["key_name"] ?? "");
        // 店鋪id
        $distributorId = (int)($formData["distributor_id"] ?? 0);
        // 標識符，表示是否是預設字段
        $isPreset = false;
        // 獲取默認字段
        $defaultFields = $this->getDefaultFieldsFromConfig($companyId, $moduleType);
        if (empty($keyName)) {
            // 如果key_name為空，則通過label值去匹配出key_name，也能匹配出是否是預設
            foreach ($defaultFields as $fieldKeyName => $info) {
                $fieldLabel = $info["name"] ?? "";
                // 如果匹配到了label，則獲取對應的key_name，並且不在需要自動生成key_name
                if ($label == $fieldLabel) {
                    $keyName = $fieldKeyName;
                    $isPreset = true;
                    break;
                }
            }
        } else {
            // 如果key_name存在，則判斷是否是預設
            if (isset($defaultFields[$keyName]["name"])) {
                $label = $defaultFields[$keyName]["name"]; // 覆蓋label
                $isPreset = true;
            }
        }
        // 判斷key_name是否存在
        if ($isPreset) {
            $this->checkKeyExist($companyId, $moduleType, $keyName, 0, $distributorId);
        }

        // 需要被創建的數據格式
        $createData = [
            "company_id" => $companyId,
            "distributor_id" => $distributorId,
            "module_type" => $moduleType,
            "label" => $label,
            "key_name" => $keyName,
            "is_preset" => (int)$isPreset,
            "is_open" => (int)($formData["is_open"] ?? false),
            "is_required" => (int)($formData["is_required"] ?? false),
            "is_edit" => (int)($formData["is_edit"] ?? false),
            "field_type" => (int)($formData["field_type"] ?? self::FIELD_TYPE_TEXT),
            "validate_condition" => "",
            "alert_required_message" => (string)($formData["alert_required_message"] ?? ""),
            "alert_validate_message" => (string)($formData["alert_validate_message"] ?? ""),
        ];
        $createData["validate_condition"] = json_encode($this->makeValidateCondition($createData["field_type"], $formData), JSON_UNESCAPED_UNICODE);
        // 判斷label是否重複
        $this->checkLabelExist($companyId, $moduleType, $createData["label"], 0, $distributorId);
        // 判斷是否要必填和開啟
        try {
            $this->checkKeyNameIsMustStartAndRequired($companyId, $createData["module_type"], $createData["key_name"]);
        } catch (\Exception $exception) {
            $createData["is_open"] = true;
            $createData["is_required"] = true;
        }
        // 開啟事務
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            // 創建
            $result = $this->configRequestFieldsRepository->create($createData);
            // 如果不是預設，就自動生成
            if (!$isPreset) {
                // 獲取主鍵id
                $id = (int)($result["id"]);
                $result["key_name"] = md5("config_request_field_". $id);
                $this->checkKeyExist($companyId, $moduleType, $result["key_name"], $id, $distributorId);
                // 根據id做md5加密
                $this->configRequestFieldsRepository->updateBy(["id" => $id], [
                    "key_name" => $result["key_name"]
                ]);
            }
            $conn->commit();
        } catch (\Exception $exception) {
            $conn->rollback();
            throw $exception;
        }
        // 創建數據
        (new RedisCacheService($companyId, sprintf("ConfigRequestFieldsNewSetting_%d_%d", $moduleType, $distributorId)))->delete();
        return $this->handleData($result);
    }


    /**
     * 返回的數據做統一處理
     * @param array $data
     * @return array
     */
    protected function handleData(array $data): array
    {
        // 獲取模塊類型描述
        if (isset($data["field_type"])) {
            $data["field_type_desc"] = self::FIELD_TYPE_MAP[$data["field_type"]] ?? "";
        }
        // 獲取在驗證時的條件數據
        if (isset($data["validate_condition"])) {
            $data["validate_condition"] = (array)jsonDecode($data["validate_condition"] ?? "");
        }
        $this->makeFieldTypeContent($data);
        // 新增時間的描述
        if (isset($data["created"])) {
            $data["created_desc"] = Carbon::createFromTimestamp($data["created"])->toDateTimeString();
        }
        // 更新時間的描述
        if (isset($data["updated"])) {
            $data["updated_desc"] = Carbon::createFromTimestamp($data["updated"])->toDateTimeString();
        }

        // 設置字段是否是必須開啟且必須填寫
        $data["is_must_start_required"] = 0;
        // 設置字段是否是默認字段
        $data["is_default"] = 0;
        // 模塊類型
        if (isset($data["module_type"])) {
            $data["module_type_desc"] = self::MODULE_TYPE_MAP[$data["module_type"]] ?? "";
            $keyName = (string)($data["key_name"] ?? "");
            if (in_array($keyName, $this->getMustStartAndRequiredFieldsFromConfig((int)$data["company_id"], (int)$data["module_type"]))) {
                $data["is_must_start_required"] = 1;
            }
            if (isset($this->getDefaultFieldsFromConfig((int)$data["company_id"], (int)$data["module_type"])[$keyName])) {
                $data["is_default"] = 1;
            }
        }
        // 將這些字段轉成int類型
        foreach (["field_type", "is_open", "is_required", "is_edit", "is_preset"] as $field) {
            if (isset($data[$field])) {
                $data[$field] = (int)$data[$field];
            }
        }
        // 強製轉換默認值
        if (isset($data["company_id"]) && isset($data["module_type"]) && isset($data["label"]) && isset($data["key_name"])) {
            $transformDefaultLabels = $this->getDefaultFieldsFromConfig((int)$data["company_id"], (int)$data["module_type"]);
            $data["label"] = $transformDefaultLabels[$data["key_name"]]["name"] ?? $data["label"];
        }
        return $data;
    }

    /**
     * 開關字段的映射表
     */
    public const SWITCH_COLUMN_DESC_IS_OPEN = "is_open";
    public const SWITCH_COLUMN_DESC_IS_REQUIRED = "is_required";
    public const SWITCH_COLUMN_DESC_IS_EDIT = "is_edit";
    public const SWITCH_COLUMN_DESC_IS_PRESET = "is_preset";
    public const SWITCH_COLUMN_IS_OPEN = 1; // 是否開啟
    public const SWITCH_COLUMN_IS_REQUIRED = 2; // 是否必填
    public const SWITCH_COLUMN_IS_EDIT = 3; // 是否可編輯
    public const SWITCH_COLUMN_IS_PRESET = 4; // 是否是預設字段
    public const SWITCH_COLUMN_MAP = [
        self::SWITCH_COLUMN_IS_OPEN => self::SWITCH_COLUMN_DESC_IS_OPEN,
        self::SWITCH_COLUMN_IS_REQUIRED => self::SWITCH_COLUMN_DESC_IS_REQUIRED,
        self::SWITCH_COLUMN_IS_EDIT => self::SWITCH_COLUMN_DESC_IS_EDIT,
        self::SWITCH_COLUMN_IS_PRESET => self::SWITCH_COLUMN_DESC_IS_PRESET,
    ];

    public const SWITCH_YES = 1; // 開啟
    public const SWITCH_NO = 0; // 關閉

    /**
     * 更新啟動狀態
     * @param int $companyId 公司id
     * @param int $id 主鍵id
     * @param int $switchColumnKey 開關列表的key名，取得是SWITCH_COLUMN_MAP的映射表的key
     * @param bool $isOpen true為開啟，false為關閉
     * @return bool true為操作成功
     */
    public function updateSwitch(int $companyId, int $id, int $switchColumnKey, bool $isOpen, int $distributorId = 0): bool
    {
        // 獲取詳情
        $info = $this->getInfo($companyId, ["id" => $id, "distributor_id" => $distributorId]);
        if (empty($info)) {
            throw new ResourceException("無法查詢到該數據");
        }
        // 判斷更新的字段是否在預設的枚舉裏
        if (!isset(self::SWITCH_COLUMN_MAP[$switchColumnKey])) {
            throw new ResourceException("操作失敗！更新的字段有誤！");
        }
        $infoKeyName = (string)($info["key_name"] ?? "");
        $moduleType = (int)($info["module_type"] ?? 0);
        // 如果更新的字段是【是否開啟】或【是否必填】且開關最終的值是關閉
        if (!$isOpen && ($switchColumnKey == self::SWITCH_COLUMN_IS_OPEN || $switchColumnKey == self::SWITCH_COLUMN_IS_REQUIRED)) {
            $this->checkKeyNameIsMustStartAndRequired($companyId, $moduleType, $infoKeyName);
        }
        // 需要更新的內容
        $saveData = [
            self::SWITCH_COLUMN_MAP[$switchColumnKey] => (int)$isOpen,
            self::SWITCH_COLUMN_DESC_IS_PRESET => 0, // 默認不是預設
        ];
        // 判斷是否是預設字段
        $fields = $this->getDefaultFieldsFromConfig($companyId, $moduleType);
        if (isset($fields[$infoKeyName])) {
            $saveData[self::SWITCH_COLUMN_DESC_IS_PRESET] = 1; // 設置為預設字段
        }
        // 更新字段狀態
        $this->configRequestFieldsRepository->updateBy(["company_id" => $companyId, "id" => $id], $saveData);
        (new RedisCacheService($companyId, sprintf("ConfigRequestFieldsNewSetting_%d_%d", $moduleType, $distributorId)))->delete();
        return true;
    }

    /**
     * 獲取分頁數據數據
     * @param int $companyId 公司id
     * @param array $filter 過濾條件
     * @param int $page 當前頁
     * @param int $pageSize 每頁數量
     * @param array $orderBy 排序方式
     * @param string $cols 查詢的列，默認是全部
     * @return array
     */
    public function paginate(int $companyId, array $filter, int $page = 1, int $pageSize = -1, array $orderBy = [], string $cols = "*"): array
    {
        $filter["company_id"] = $companyId;
        $data = $this->configRequestFieldsRepository->lists($filter, $cols, $page, $pageSize, $orderBy);
        if (isset($data["list"]) && is_array($data["list"])) {
            foreach ($data["list"] as &$item) {
                $item = $this->handleData($item);
            }
        }
        return $data;
    }

    /**
     * 獲取列表數據
     * @param int $companyId 公司id
     * @param array $filter 過濾條件
     * @param int $page 當前頁
     * @param int $pageSize 每頁數量
     * @param array $orderBy 排序方式
     * @param string $cols 查詢的列，默認是全部
     * @return array 結果
     */
    public function getList(int $companyId, array $filter, int $page = 1, int $pageSize = -1, array $orderBy = ["id" => "DESC"], string $cols = "*"): array
    {
        $filter["company_id"] = $companyId;
        $list = $this->configRequestFieldsRepository->getLists($filter, $cols, $page, $pageSize, $orderBy);
        foreach ($list as &$item) {
            $item = $this->handleData($item);
        }

        return $list;
    }

    /**
     * 獲取當前模塊下所有字段內容並處理成setting中的配置格式
     * @param int $companyId 企業id
     * @param int $moduleType 模塊類型
     * @return array 結果集
     */
    public function getListAndHandleSettingFormat(int $companyId, int $moduleType, int $distributorId = 0): array
    {
        return (new RedisCacheService($companyId, sprintf("ConfigRequestFieldsNewSetting_%d_%d", $moduleType, $distributorId)))
            ->getByPrevention(function () use ($companyId, $moduleType, $distributorId) {
                // 檢查是否已經初始化
                $this->checkIsNeedInit(["company_id" => $companyId, "module_type" => $moduleType, "distributor_id" => $distributorId]);
                // 獲取字段列表
                $list = $this->configRequestFieldsRepository->getLists([
                    "company_id" => $companyId,
                    "distributor_id" => $distributorId,
                    "module_type" => $moduleType,
                    self::SWITCH_COLUMN_DESC_IS_OPEN => self::SWITCH_YES,
                ], "*", 1, -1, ["id" => "DESC"]);
                // 轉格式
                $fields = (array)array_column($list, null, "key_name");

                $result = [];
                // 將數據庫中查詢出來的字段信息轉換成外部的統一格式
                foreach ($fields as $keyName => $item) {
                    // 設置企業id
                    $item["company_id"] = $companyId;
                    // 處理數據
                    $item = $this->handleData($item);
                    $data = [
                        "name" => (string)($item["label"] ?? ""),
                        "key" => $keyName,
                        "is_open" => (bool)($item["is_open"] ?? 0),
                        "is_required" => (bool)($item["is_required"] ?? 0),
                        "is_edit" => (bool)($item["is_edit"] ?? 0),
                        "is_default" => (bool)($item["is_preset"] ?? 0),
                        "element_type" => "",
                        "field_type" => (int)($item["field_type"] ?? self::FIELD_TYPE_TEXT),
                        "required_message" => (string)($item["alert_required_message"] ?? ""),
                        "validate_message" => (string)($item["alert_validate_message"] ?? ""),
                        //"items"        => [],
                        "range" => [], // 數字、日期的選擇範圍
                        "select" => [], // 單選項的選擇列表 key為索引枚舉值，value為枚舉值
                        "checkbox" => [], // 複選項的選擇列表，二維數組，每個數組下麵name為選項名字，ischecked為是否選中
                    ];
                    $data["element_type"] = self::FIELD_TYPE_ELEMENT_MAP[$data["field_type"]] ?? "input";
                    switch ($data["field_type"]) {
                        case self::FIELD_TYPE_NUMBER:
                        case self::FIELD_TYPE_DATE:
                            $data["range"] = (array)($item["range"] ?? []);
                            break;
                        case self::FIELD_TYPE_RADIO:
                            // $data["select"] = array_column((array)($item["radio_list"] ?? []), "value", "label");
                            $data["select"] = array_column((array)($item["radio_list"] ?? []), "label", "value");
                            break;
                        case self::FIELD_TYPE_CHECKBOX:
                            $item["radio_list"] = (array)($item["radio_list"] ?? []);
                            foreach ($item["radio_list"] as $datum) {
                                $data["checkbox"][] = [
                                    "name" => (string)($datum["label"] ?? ""),
                                    "ischecked" => (bool)($datum["is_checked"] ?? false)
                                ];
                            }
                            break;
                    }
                    $result[$keyName] = $data;
                }
                return $result;
            });
    }

    /**
     * 獲取單條數據
     * @param int $companyId 公司id
     * @param array $filter 過濾條件
     * @return array 結果
     */
    public function getInfo(int $companyId, array $filter): array
    {
        $list = $this->getList($companyId, $filter, 1, 1);
        return (array)array_shift($list);
    }

    /**
     * 更新數據
     * @param int $companyId 公司id
     * @param int $id 主鍵id
     * @param array $formData 表單數據
     * @return array
     */
    public function updateInfo(int $companyId, int $id, array $formData): array
    {
        $distributorId = (int)($formData["distributor_id"] ?? 0);
        $info = $this->getInfo($companyId, ["id" => $id, "distributor_id" => $distributorId]);
        if (empty($info)) {
            throw new ResourceException("操作失敗！不存在該數據！");
        }
        $updateData = [
            "label" => (string)($formData["label"] ?? ""),
            "field_type" => (int)($formData["field_type"] ?? self::FIELD_TYPE_TEXT),
            "validate_condition" => "",
        ];
        foreach (["alert_required_message", "alert_validate_message"] as $field) {
            if (isset($formData[$field])) {
                $updateData[$field] = $formData[$field];
            }
        }
        $updateData["validate_condition"] = json_encode($this->makeValidateCondition($updateData["field_type"], $formData), JSON_UNESCAPED_UNICODE);
        // 判斷label是否重複
        $this->checkLabelExist($companyId, (int)($info["module_type"] ?? 0), $updateData["label"], $id, $distributorId);
        // 更新數據
        $result = $this->configRequestFieldsRepository->updateOneBy(["company_id" => $companyId, "id" => $id], $updateData);
        (new RedisCacheService($companyId, sprintf("ConfigRequestFieldsNewSetting_%d_%d", (int)($info["module_type"] ?? 0), $distributorId)))->delete();
        return $this->handleData($result);
    }

    /**
     * 刪除請求字段
     * @param int $companyId 公司id
     * @param int $id 主鍵id
     * @return bool 操作的狀態
     */
    public function delete(int $companyId, int $id, int $distributorId = 0): bool
    {
        $info = $this->getInfo($companyId, ["id" => $id, "distributor_id" => $distributorId]);
        if (empty($info)) {
            return true;
        }
        $keyName = (string)($info["key_name"] ?? "");
        $moduleType = (int)($info["module_type"] ?? 0);
        $this->checkKeyNameIsDefault($companyId, $moduleType, $keyName);
        $this->configRequestFieldsRepository->deleteBy(["company_id" => $companyId, "id" => $id]);
        (new RedisCacheService($companyId, sprintf("ConfigRequestFieldsNewSetting_%d_%d", $moduleType, $distributorId)))->delete();
        return true;
    }

    /**
     * 獲取驗證的條件
     * @param int $fieldType
     * @param array $formData
     * @return array|null[]
     */
    protected function makeValidateCondition(int $fieldType, array $formData): array
    {
        switch ($fieldType) {
            case self::FIELD_TYPE_NUMBER:
            case self::FIELD_TYPE_DATE:
                $range = (array)($formData["range"] ?? []);
                $result = [];
                // 這裏做了一個新老版本的兼容，如果range是一個數組，則遍曆獲取裏麵的對象，如果range本身就是一個對象就直接獲取開始和結束
                if (!isset($range["start"]) && !isset($range["end"])) {
                    foreach ($range as $item) {
                        $result[] = [
                            "value" => sprintf("%s,%s", $item["start"] ?? null, $item["end"] ?? null),
                            "label" => "取值範圍",
                            "is_checked" => 0
                        ];
                    }
                } else {
                    $result[] = [
                        "value" => sprintf("%s,%s", $range["start"] ?? null, $range["end"] ?? null),
                        "label" => "取值範圍",
                        "is_checked" => 0
                    ];
                }
                return $result;
                break;
            case self::FIELD_TYPE_RADIO:
            case self::FIELD_TYPE_CHECKBOX:
                $radioList = (array)($formData["radio_list"] ?? []);
                foreach ($radioList as $key => &$item) {
                    // $item原本是由key、label和is_checked組成，但現在key用不到了，隻需要label和is_checked即可
                    if (!isset($item["label"]) || !isset($item["is_checked"])) {
                        throw new ResourceException("操作失敗！驗證的數據格式有誤！");
                    }
                    $item["value"] = $key;
                }
                return $radioList;
            default:
                return [];
        }
    }

    /**
     * 是makeValidateCondition的逆方法，將存進去的值轉成前端希望的數據格式
     * @param array $result 結果
     */
    protected function makeFieldTypeContent(array &$result)
    {
        // 初始化取值範圍
        if (!isset($result["range"])) {
            $result["range"] = [];
        }
        // 初始單選項列表
        if (!isset($result["radio_list"])) {
            $result["radio_list"] = [];
        }
        // 判斷驗證條件，如果不存在的話，就不繼續執行，直接返回
        if (!isset($result["validate_condition"])) {
            return;
        } else {
            if (is_string($result["validate_condition"])) {
                $validateCondition = jsonDecode($result["validate_condition"]);
            } else {
                $validateCondition = $result["validate_condition"];
            }
        }
        // 獲取字段類型
        $fieldType = (int)($result["field_type"] ?? 0);
        switch ($fieldType) {
            case self::FIELD_TYPE_NUMBER:
            case self::FIELD_TYPE_DATE:
                foreach ($validateCondition as $item) {
                    $value = (string)($item["value"] ?? "");
                    $valueArray = explode(",", $value);
                    $start = array_shift($valueArray);
                    $end = array_shift($valueArray);
                    // 目前暫時是一個取值範圍
                    //$result["validate_condition_range"][] = [
                    $result["range"] = [
                        "start" => is_numeric($start) ? $start : null,
                        "end" => is_numeric($end) ? $end : null,
                    ];
                }
                break;
            case self::FIELD_TYPE_RADIO:
            case self::FIELD_TYPE_CHECKBOX:
                foreach ($validateCondition as $key => $item) {
                    $result["radio_list"][] = [
                        "key" => (string)($item["key"] ?? ""),
                        "value" => (string)($item["value"] ?? $key),
                        "label" => (string)($item["label"] ?? ""),
                        "is_checked" => (int)($item["is_checked"] ?? 0),
                    ];
                }
                break;
        }
    }

    /**
     * 從配置文件中獲取必須請求的字段信息
     * @param int $companyId 企業
     * @param int $moduleType 模塊類型
     * @return array 字段信息
     */
    public function getMustStartAndRequiredFieldsFromConfig(int $companyId, int $moduleType): array
    {
        $fieldsString = config(sprintf("requestField.%d.must_start_required", $moduleType));
        return explode(",", $fieldsString);
    }

    /**
     * 從配置文件中獲取默認的請求字段信息
     * @param int $companyId 企業
     * @param int $moduleType 模塊類型
     * @return array 字段信息, key為key_name, value為字段的內容
     */
    public function getDefaultFieldsFromConfig(int $companyId, int $moduleType): array
    {
        return (array)config(sprintf("requestField.%d.default", $moduleType));
    }

    /**
     * 獲取默認的字段內容
     * @param int $companyId 企業id
     * @param int $moduleType 模塊類型
     * @return array
     */
    public function getDefaultFieldContent(int $companyId, int $moduleType): array
    {
        $formData = [];
        // 獲取默認字段
        $defaultFields = $this->getDefaultFieldsFromConfig($companyId, $moduleType);
        // 設置某些需要必須開啟且是必填的字段
        // 如果在setting中找不到，則直接去配置文件中的默認字段裏找
        $mustStartAndRequiredFields = $this->getMustStartAndRequiredFieldsFromConfig($companyId, $moduleType);
        foreach ($mustStartAndRequiredFields as $mustStartAndRequiredField) {
            $defaultFields[$mustStartAndRequiredField][self::SWITCH_COLUMN_DESC_IS_OPEN] = (bool)self::SWITCH_YES;
            $defaultFields[$mustStartAndRequiredField][self::SWITCH_COLUMN_DESC_IS_REQUIRED] = (bool)self::SWITCH_YES;
            $defaultFields[$mustStartAndRequiredField]["name"] = $defaultFields[$mustStartAndRequiredField]["name"] ?? $mustStartAndRequiredField;
            $defaultFields[$mustStartAndRequiredField]["element_type"] = $defaultFields[$mustStartAndRequiredField]["element_type"] ?? "input";
        }

        foreach ($defaultFields as $keyName => $info) {
            $formDatum = [
                "module_type" => $moduleType,
                "label" => (string)($info["name"] ?? ""),
                "key_name" => $keyName,
                "is_open" => (int)($info["is_open"] ?? self::SWITCH_YES),
                "is_required" => (int)($info["is_required"] ?? self::SWITCH_YES),
                "is_edit" => self::SWITCH_YES,
                "field_type" => self::FIELD_TYPE_TEXT,
                "alert_required_message" => (string)($info["prompt"] ?? ""),
                "range" => [],
                "radio_list" => [],
            ];

            // 提示信息
            if (empty($formDatum["alert_required_message"])) {
                $formDatum["alert_required_message"] = sprintf("請輸入您的%s", $formDatum["label"]);
            }

            // 元素類型
            $elementType = (string)($info["element_type"] ?? "input");

            // 如果是用戶注冊模塊
            if ($moduleType == self::MODULE_TYPE_MEMBER_INFO) {
                switch ($elementType) {
                    case "select": // 下拉框
                        switch ($keyName) {
                            case "sex": // 性別
                                $formDatum["radio_list"] = [
                                    ["value" => 0, "label" => "未知", "is_checked" => 0],
                                    ["value" => 1, "label" => "男", "is_checked" => 0],
                                    ["value" => 2, "label" => "女", "is_checked" => 0],
                                ];
                                $formDatum["field_type"] = self::FIELD_TYPE_RADIO;
                                break;
                            case "birthday": // 生日
                                $formDatum["range"][] = [
                                    "start" => null,
                                    "end" => null,
                                ];
                                $formDatum["field_type"] = self::FIELD_TYPE_DATE;
                                break;
                            default: // 其他
                                $items = (array)($info["items"] ?? []);
                                foreach ($items as $value => $label) {
                                    $formDatum["radio_list"][] = ["value" => $value, "label" => $label, "is_checked" => 0];
                                }
                                $formDatum["field_type"] = self::FIELD_TYPE_RADIO;
                                break;
                        }
                        break;
                    case "checkbox": // 複選框
                        $items = (array)($info["items"] ?? []);
                        foreach ($items as $value => $item) {
                            $formDatum["radio_list"][] = ["value" => $value, "label" => $item["name"], "is_checked" => (int)($item["ischecked"] ?? 0)];
                        }
                        $formDatum["field_type"] = self::FIELD_TYPE_CHECKBOX;
                        break;
                    case "mobile":
                        $formDatum["field_type"] = self::FIELD_TYPE_MOBILE;
                        break;
                }
            } elseif ($moduleType == self::MODULE_TYPE_CHIEF_INFO) {
                 switch ($elementType) {
                    case "mobile":
                        $formDatum["field_type"] = self::FIELD_TYPE_MOBILE;
                        break;
                }
            }

            $formData[$keyName] = $formDatum;
        }
        return $formData;
    }

    /**
     * 初始化數據
     * @param int $companyId 企業id
     * @param int $moduleType 模塊類型
     * @return bool true表示初始化成功，false表示初始化失敗可能是數據查詢不到
     */
    public function init(int $companyId, int $moduleType, int $distributorId = 0): bool
    {
        // 獲取默認的字段內容
        $formData = $this->getDefaultFieldContent($companyId, $moduleType);

        // 批量入庫，如果存在數據就做更新
        foreach ($formData as $formDatum) {
            try {
                $formDatum["distributor_id"] = $distributorId;
                $this->create($companyId, $moduleType, $formDatum);
            } catch (\Exception $exception) {
                $field = $this->configRequestFieldsRepository->getInfo(["company_id" => $companyId, "module_type" => $moduleType, "key_name" => $formDatum["key_name"]]);
                if (!empty($field)) {
                    try {
                        $this->updateInfo($companyId, $field["id"], $formDatum);
                        $this->updateSwitch($companyId, $field["id"], self::SWITCH_COLUMN_IS_OPEN, (bool)$formDatum["is_open"], $distributorId);
                        $this->updateSwitch($companyId, $field["id"], self::SWITCH_COLUMN_IS_REQUIRED, (bool)$formDatum["is_required"], $distributorId);
                        $this->updateSwitch($companyId, $field["id"], self::SWITCH_COLUMN_IS_EDIT, (bool)$formDatum["is_edit"], $distributorId);
                    } catch (\Throwable $exception) {
                        app('api.exception')->report($exception);
                    }
                }
            }
        }
        return true;
    }

    /**
     * 根據模塊類型做初始化
     * @param int $moduleType
     * @return bool
     */
    public function commandInitByModuleType(int $moduleType)
    {
        // 獲取默認的字段內容
        $formData = $this->getDefaultFieldContent(0, $moduleType);

        // 批量入庫，如果存在數據就做更新
        foreach ($formData as $formDatum) {
            try {
                $page = 1;
                $pageSize = 50;
                do {
                    $list = $this->configRequestFieldsRepository->getLists([
                        "module_type" => $moduleType,
                        "key_name" => $formDatum["key_name"]
                    ], "*", $page, $pageSize, ["id" => "DESC"]);
                    foreach ($list as $item) {
                        $companyId = (int)($item["company_id"] ?? 0);
                        $distributorId = (int)($item["distributor_id"] ?? 0);
                        $formDatum["distributor_id"] = $distributorId;
                        $id = (int)($item["id"] ?? 0);
                        $this->updateInfo($companyId, $id, $formDatum);
                        $this->updateSwitch($companyId, $id, self::SWITCH_COLUMN_IS_OPEN, (bool)$formDatum["is_open"], $distributorId);
                        $this->updateSwitch($companyId, $id, self::SWITCH_COLUMN_IS_REQUIRED, (bool)$formDatum["is_required"], $distributorId);
                        $this->updateSwitch($companyId, $id, self::SWITCH_COLUMN_IS_EDIT, (bool)$formDatum["is_edit"], $distributorId);
                    }
                    $page++;
                } while (count($list) === $pageSize);
            } catch (\Exception $exception) {
                echo sprintf("%s-%s-%s", $exception->getMessage(), $exception->getFile(), $exception->getLine()). PHP_EOL;
            }
        }
        return true;
    }

    /**
     * 緩存配置項
     */
    public const SETTING_SWITCH_FIRST_AUTH_FORCE_VALIDATION = "switch_first_auth_force_validation";
    // value為這個選項的默認值
    public const SETTING_MAP = [
        self::SETTING_SWITCH_FIRST_AUTH_FORCE_VALIDATION => 0
    ];

    /**
     * 更新配置項的內容
     * @param int $companyId 企業id
     * @param int $moduleType 模塊id
     * @param array $data 需要更新的數據
     */
    public function updateSetting(int $companyId, int $moduleType, array $data, int $distributorId = 0)
    {
        $cacheService = new RedisCacheService($companyId, sprintf("ConfigRequestFieldsSetting_%d_%d", $moduleType, $distributorId));
        foreach ($data as $key => $value) {
            if (!isset(self::SETTING_MAP[$key])) {
                continue;
            }
            $cacheService->hashSet([$key => $value]);
        }
    }

    /**
     * 獲取配置項的信息
     * @param int $companyId 企業id
     * @param int $moduleType 模塊id
     * @return array
     */
    public function getSetting(int $companyId, int $moduleType, int $distributorId = 0): array
    {
        $data = (new RedisCacheService($companyId, sprintf("ConfigRequestFieldsSetting_%d_%d", $moduleType, $distributorId)))->hashGet(null);
        // 填充選項
        foreach (self::SETTING_MAP as $key => $default) {
            if (!isset($data[$key])) {
                $data[$key] = $default;
            }
            // 值類型的轉換
            switch ($key) {
                case self::SETTING_SWITCH_FIRST_AUTH_FORCE_VALIDATION:
                    $data[$key] = (int)$data[$key];
                    break;
            }
        }
        return $data;
    }

    /**
     * 將數據庫從存儲的int類型的值做描述輸出
     * @param int $companyId 企業id
     * @param int $moduleType 模塊類型
     * @param array $dbData 表單數據
     * @param array $memberInfo 用戶信息
     */
    public function transformGetDescByValue(int $companyId, int $moduleType, array &$dbData, array &$memberInfo, int $distributorId = 0): void
    {
        if (empty($dbData)) {
            return;
        }

        // 獲取配置的請求字段和字段的枚舉值
        $fields = $this->getListAndHandleSettingFormat($companyId, $moduleType, $distributorId);

        // 遍曆從數據庫中獲取的數據參數
        foreach ($dbData as $key => $value) {
            // 不存在配置項中或者不存在字段類型，就直接跳過
            if (empty($fields[$key]) || empty($fields[$key]["field_type"])) {
                continue;
            }

            switch ($fields[$key]["field_type"]) {
                // 單選項類型的字段的值改為 枚舉值的描述內容
                case ConfigRequestFieldsService::FIELD_TYPE_RADIO:
                    $dbData[$key] = $fields[$key]["select"][$value] ?? null;
                    if(isset($memberInfo[$key])){
                        $memberInfo[$key] = $dbData[$key];
                    }
                    break;
            }
        }
    }

    /**
     * 將前端傳遞的表單值最終轉成int類型存入db中
     * @param int $companyId 企業id
     * @param int $moduleType 模塊類型
     * @param array $formData 表單數據
     */
    public function transformGetValueByDesc(int $companyId, int $moduleType, array &$formData, int $distributorId = 0): void
    {
        if (empty($formData)) {
            return;
        }

        // 獲取配置的請求字段和字段的枚舉值
        $fields = $this->getListAndHandleSettingFormat($companyId, $moduleType, $distributorId);

        // 遍曆表單數據
        foreach ($formData as $key => $value) {
            // 不存在配置項中或者不存在字段類型，就直接跳過
            if (empty($fields[$key]) || empty($fields[$key]["field_type"])) {
                continue;
            }

            switch ($fields[$key]["field_type"]) {
                // 如果字段類型是單選項，則需要保存int類型
                case ConfigRequestFieldsService::FIELD_TYPE_RADIO:
                    $select = (array)($fields[$key]["select"] ?? []);
                    foreach ($select as $indexValue => $desc) {
                        // 如果表單的值等於枚舉的描述值 或 表單的值等於枚舉的索引值，則將表單的結果值改為索引值
                        if ($value === $desc || $value === $indexValue) {
                            $formData[$key] = $indexValue;
                            break;
                        }
                    }
                    break;
            }
        }
    }
}
