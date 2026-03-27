<?php

namespace OpenapiBundle\Services\Member;

use KaquanBundle\Entities\MemberCardGrade;
use KaquanBundle\Services\MemberCardService;
use OpenapiBundle\Constants\CommonConstant;
use OpenapiBundle\Constants\ErrorCode;
use OpenapiBundle\Exceptions\ErrorException;
use OpenapiBundle\Services\BaseService;

class MemberCardGradeService extends BaseService
{
    public function getEntityClass(): string
    {
        return MemberCardGrade::class;
    }

    public function list(array $filter, int $page = 1, int $pageSize = CommonConstant::DEFAULT_PAGE_SIZE, array $orderBy = [], string $cols = "*", bool $needCountSql = true): array
    {
        $result = [
            "total_count" => $needCountSql ? $this->getRepository()->count($filter) : 0,
            "list" => $this->getRepository()->getList($cols, $filter, ($page - 1) * $pageSize, $pageSize, $orderBy),
        ];
        $this->handlerListReturnFormat($result, $page, $pageSize);
        return $result;
    }

    /**
     * 检查是否达到上限
     * @param int $companyId
     * @param array $list
     * @return $this
     */
    protected function checkUpperLimit(int $companyId, array &$list): self
    {
        $list = $this->getRepository()->getList("grade_id", ["company_id" => $companyId], 0, -1);
        if (count($list) >= 6) {
            throw new ErrorException(ErrorCode::MEMBER_GRADE_ERROR, "最多只能创建5个会员等级");
        }
        return $this;
    }

    /**
     * 创建会员卡等级
     * @param array $createData
     * @return array
     */
    public function create(array $createData): array
    {
        // 拼装数据
        $params = [
            // 企业id
            "company_id" => (int)$createData["company_id"],
            // 会员卡等级名称
            "grade_name" => (string)($createData["grade_name"] ?? ""),
            // 等级卡背景图
            "background_pic_url" => (string)($createData["background_pic_url"] ?? ""),
            // 外部唯一标识，外部调用方自定义的值
            "external_id" => (string)($createData["external_id"] ?? ""),
            // 会员权益
            "privileges" => [],
            // 升级条件
            "promotion_condition" => [],
            // 是否默认等级
            "default_grade" => $this->getRepository()->count(["company_id" => $createData["company_id"]]) === 0
        ];
        // 会员权益
        if (isset($createData["discount"]) && is_numeric($createData["discount"])) {
            $params["privileges"] = [
                "discount_desc" => $createData["discount"],
                "discount" => 100 - intval($createData["discount"] * 10)
            ];
        }
        // 升级条件
        if (isset($createData["total_consumption"]) && is_numeric($createData["total_consumption"])) {
            $params["promotion_condition"] = [
                "total_consumption" => $createData["total_consumption"],
            ];
        }
        $params["grade_id"] = 0;
        $params["company_id"] = (int)$createData["company_id"];
        $params["default_grade"] = MemberCardService::DEFAULT_GRADE_NO;

        // 数据验证
        $list = [];
        $this->checkUpperLimit($params["company_id"], $list);

        $newList = [];
        $this->getRepository()->update($params["company_id"], [$params], [], $newList);
        return (array)array_shift($newList);
    }

    /**
     * 更新会员卡等级
     * @param array $filter
     * @param array $updateData
     * @return array
     */
    public function updateDetail(array $filter, array $updateData): array
    {
        $info = $this->find($filter);
        if (empty($info)) {
            throw new ErrorException(ErrorCode::MEMBER_GRADE_NOT_FOUND);
        }
        $params = [];
        // 会员卡等级名称
        if (isset($updateData["grade_name"])) {
            $params["grade_name"] = (string)$updateData["grade_name"];
        }
        // 等级卡背景图
        if (isset($updateData["background_pic_url"])) {
            $params["background_pic_url"] = (string)$updateData["background_pic_url"];
        }
        // 等级卡背景图
        if (isset($updateData["external_id"])) {
            $params["external_id"] = (string)$updateData["external_id"];
        }
        // 会员权益
        if (isset($updateData["discount"]) && is_numeric($updateData["discount"])) {
            $params["privileges"] = [
                "discount_desc" => $updateData["discount"],
                "discount" => 100 - intval($updateData["discount"] * 10)
            ];
        }
        // 升级条件
        if (isset($updateData["total_consumption"]) && is_numeric($updateData["total_consumption"])) {
            $params["promotion_condition"] = [
                "total_consumption" => $updateData["total_consumption"],
            ];
        }
        if (empty($params)) {
            return [];
        }
        $params = array_merge($filter, $params);
        $newList = [];
        $this->getRepository()->update($params["company_id"], [$params], [], $newList);
        return (array)array_shift($newList);
    }

    /**
     * 删除会员卡等级
     * @param array $filter
     * @return int
     */
    public function delete(array $filter): int
    {
        // 删除时检查该会员等级下是否已经存在了用户，如果已经存在则如果做删除
        $result = (new MemberService())->list(["company_id" => (int)$filter["company_id"], "grade_id" => (int)$filter["grade_id"]], 1, 1, [], "*", false);
        if (!empty($result["list"])) {
            throw new ErrorException(ErrorCode::MEMBER_GRADE_DELETE_ERROR, "会员等级无法删除，该会员等级下扔存在关联的会员");
        }
        $this->getRepository()->update((int)$filter["company_id"], [], [(int)$filter["grade_id"]]);
        return 1;
    }
}
