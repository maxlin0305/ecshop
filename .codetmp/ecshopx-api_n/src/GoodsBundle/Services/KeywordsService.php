<?php

namespace GoodsBundle\Services;

use GoodsBundle\Entities\Keywords;
use Dingo\Api\Exception\ResourceException;
use GoodsBundle\Entities\ItemsRelTags;

class KeywordsService
{
    private $entityRepository;
    private $itemsRelTags;

    /**
     *  構造函數.
     */
    public function __construct()
    {
        $this->entityRepository = app('registry')->getManager('default')->getRepository(Keywords::class);
        $this->itemsRelTags = app('registry')->getManager('default')->getRepository(ItemsRelTags::class);
    }

    public function deleteById($filter)
    {
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $lists = $this->itemsRelTags->lists($filter);
            if (isset($lists['list']) && $lists['list']) {
                $result = $this->itemsRelTags->deleteBy($filter);
            }
            $result = $this->entityRepository->deleteBy($filter);
            $conn->commit();
            return true;
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }
    }
    public function addKeywords($data)
    {
        if (isset($data['id'])) {
            $row = $this->entityRepository->getInfo(['id' => $data['id']]);
            if (!$row) {
                throw new ResourceException("記錄不存在");
            }
            return $this->updateOneBy(['id' => $data['id']], $data);
        }
        return $this->create($data);
    }

    // 如果可以直接調取Repositories中的方法，則直接調用
    public function __call($method, $parameters)
    {
        return $this->entityRepository->$method(...$parameters);
    }

    public function getByShop($filter)
    {
        $result = $this->lists($filter);
        if (!$result['total_count']) {
            $filter['distributor_id'] = 0; //取默認店鋪值
            $result = $this->lists($filter);
        }
        return $result;
    }
}
