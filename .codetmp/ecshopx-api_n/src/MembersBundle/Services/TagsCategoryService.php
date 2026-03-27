<?php

namespace MembersBundle\Services;

use Dingo\Api\Exception\ResourceException;
use MembersBundle\Entities\TagsCategory;
use MembersBundle\Entities\MemberTags;

class TagsCategoryService
{
    private $entityRepository;

    public function __construct()
    {
        $this->entityRepository = app('registry')->getManager('default')->getRepository(TagsCategory::class);
    }

    // 如果可以直接调取Repositories中的方法，则直接调用
    public function __call($method, $parameters)
    {
        return $this->entityRepository->$method(...$parameters);
    }

    /**
     * 删除分类
     *
     * @param array filter
     * @return bool
     */
    public function deleteCategory($filter)
    {
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            // 判断是否为主类目
            $result = $this->entityRepository->deleteBy(['category_id' => $filter['category_id'], 'company_id' => $filter['company_id']]);
            if ($result) {
                $memberTags = app('registry')->getManager('default')->getRepository(MemberTags::class);
                $resultAll = $memberTags->getInfo(['category_id' => $filter['category_id'], 'company_id' => $filter['company_id']]);
                if ($resultAll) {
                    throw new ResourceException('删除失败,该分类下已有标签');
                }
            }
            if ($result) {
                $conn->commit();
                return true;
            } else {
                throw new ResourceException('删除失败');
            }
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }
    }

    public function saveCategory($params, $relLabelIds = [], $filter = [])
    {
        if ($filter) {
            $result = $this->entityRepository->updateOneBy($filter, $params);
        } else {
            $result = $this->entityRepository->create($params);
        }
        if ($relLabelIds) {
            $memberTags = app('registry')->getManager('default')->getRepository(MemberTags::class);
            $mbFilter = [
                'tag_id' => (array)$relLabelIds,
                'company_id' => $result['company_id'],
            ];
            $res = $memberTags->updateBy($mbFilter, ['category_id' => $result['category_id']]);
        }
        return $result;
    }
}
