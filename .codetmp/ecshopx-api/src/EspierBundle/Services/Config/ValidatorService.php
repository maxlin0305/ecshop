<?php

namespace EspierBundle\Services\Config;

use Carbon\Carbon;
use Dingo\Api\Exception\ResourceException;

class ValidatorService extends ConfigRequestFieldsService
{
    /**
     * 參數驗證
     * @param int $companyId 公司id
     * @param int $moduleType 模塊類型
     * @param array $formData 表單數據
     * @param bool $lazy 是否是懶惰模式，true為懶惰模式(如果formData中不存在驗證的字段，就不驗證)，false為非懶惰模式
     * @return array 返回驗證的所有字段信息
     */
    public function check(int $companyId, int $moduleType, array $formData, bool $lazy = false, int $distributorId = 0): array
    {
        // 驗證的規則
        $rules = [];
        // 驗證出錯時根據規則返回的錯誤信息
        $messages = [];
        // 獲取字段列表
        $fields = $this->getListAndHandleSettingFormat($companyId, $moduleType, $distributorId);
        // 判斷是否是懶惰模式
        if ($lazy) {
            foreach ($fields as $field => $info) {
                if (!isset($formData[$field])) {
                    unset($fields[$field]);
                }
            }
        }
        // 遍曆數據
        foreach ($fields as $field => &$info) {
            // 獲取字段名字
            $name = (string)($info["name"] ?? "");
            // 獲取必填驗證的錯誤信息
            $errorRequiredMessage = (string)($info["required_message"] ?? "");
            // 獲取條件驗證的錯誤信息（如果為空則去獲取必填驗證的錯誤信息）
            $errorValidateMessage = (string)($info["validate_message"] ?? "");
            if (empty($errorValidateMessage)) {
                $errorValidateMessage = $errorRequiredMessage;
            }
            // 判斷字段驗證是否被開啟
            if (!isset($info["is_open"]) || !$info["is_open"]) {
                continue;
            }
            // 判斷字段是否可編輯
            if (!isset($info["is_edit"]) || !$info["is_edit"]) {
                continue;
                throw new ResourceException(sprintf("操作失敗！%s無法被修改！", $name));
            }
            // 判斷字段是否是必填
            /*if (isset($info["is_required"]) && $info["is_required"]) {
                $rules[$field][] = "required";
                $messages[sprintf("%s.required", $field)] = $errorRequiredMessage;
            }*/
            // 獲取字段類型
            $fieldType = (int)($info["field_type"] ?? 0);
            // 根據字段類型來做區分
            switch ($fieldType) {
                // 判斷日期
                case ConfigRequestFieldsService::FIELD_TYPE_DATE:
                    $rules[$field][] = function ($attribute, $value, $fail) use ($name) {
                        try {
                            new Carbon($value);
                            return true;
                        } catch (\Exception $exception) {
                            return $fail(sprintf("%s的日期格式有誤！", $name));
                        }
                    };
                    break;
                // 判斷數字
                case ConfigRequestFieldsService::FIELD_TYPE_NUMBER:
                    // 獲取取值範圍的列表數據
                    $rageData = (array)($info["range"] ?? []);
                    $rules[$field][] = function ($attribute, $value, $fail) use ($rageData, $errorValidateMessage) {
                        if (!is_numeric($value)) {
                            return $fail($errorValidateMessage);
                        }
                        // 最小值
                        $start = $rageData["start"] ?? null;
                        // 最大值
                        $end = $rageData["end"] ?? null;
                        if (is_null($start) && !is_null($end)) {
                            // 隻有最大值
                            if ($value > $end) {
                                return $fail(sprintf("請輸入小於等於%d的數字", $end));
                            }
                            return true;
                        }
                        if (is_null($end) && !is_null($start)) {
                            // 隻有最小值
                            if ($value < $start) {
                                return $fail(sprintf("請輸入大於等於%d的數字", $start));
                            }
                            return true;
                        }

                        if ($value > $end || $value < $start) {
                            return $fail(sprintf("請輸入%d~%d範圍內的數組", $start, $end));
                        }
                        return true;
                    };
                    break;
                // 單選下拉框
                case ConfigRequestFieldsService::FIELD_TYPE_RADIO:
                    // 獲取單選項的列表數據
                    $select = (array)($info["select"] ?? []);
                    $rules[$field][] = function ($attribute, $value, $fail) use ($select, $errorValidateMessage, &$info) {
                        // 這裏是兼容app版本，老app版本用的id作為驗證字段
                        if (isset($select[$value])) {
                            return true;
                        }
                        // 兼容老數據用中文的方式來驗證字段是否存在
//                        if (in_array($value, $select, true)) {
//                            return true;
//                        }
                        return $fail(sprintf("%s有誤！不存在該選項！", $info["name"] ?? ""));
                    };
                    break;
                // 複選框
                case ConfigRequestFieldsService::FIELD_TYPE_CHECKBOX:
                    // 獲取複選框的列表數據
                    $checkbox = (array)($info["checkbox"] ?? []);
                    $rules[$field][] = function ($attribute, $value, $fail) use ($checkbox, $errorValidateMessage, &$info) {
                        $names = array_column($checkbox, null, "name");
                        if (is_array($value)) {
                            // 如果value是數組
                            foreach ($value as $item) {
                                // 如果value下麵的子元素扔是數組，則獲取子數組中的name來判斷是否存在這個多選項
                                if (is_array($item)) {
                                    $itemValue = (string)($item["name"] ?? "");
                                } else {
                                    $itemValue = (string)$item;
                                }
                                if (!isset($names[$itemValue])) {
                                    return $fail(sprintf("%s有誤！不存在該選項！", $info["name"] ?? ""));
                                }
                            }
                        } else {
                            // value是值就直接判斷
                            $value = (string)$value;
                            if (!isset($names[$value])) {
                                return $fail(sprintf("%s有誤！不存在該選項！", $info["name"] ?? ""));
                            }
                        }
                        return true;
                    };
                    break;
                // 手機號驗證
                case ConfigRequestFieldsService::FIELD_TYPE_MOBILE:
                    $rules[$field][] = function ($attribute, $value, $fail) use ($name) {
                        // 新手機號驗證
                        if (!preg_match('/^1[3456789]{1}[0-9]{9}$/', $value)) {
                            return $fail("請輸入合法的手機號");
                        }
                        return true;
                    };
                    break;
            }
        }
        // 參數驗證
        $validator = app("validator")->make($formData, $rules, $messages);
        if ($validator->fails()) {
            throw new ResourceException($validator->errors()->first());
        }
        return $fields;
    }
}
