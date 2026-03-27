<?php

namespace GoodsBundle\Services;

use GoodsBundle\Entities\ServiceLabels;
use Dingo\Api\Exception\ResourceException;

class ServiceLabelsService
{
    /** @var serviceLabelsRepository */
    private $serviceLabelsRepository;

    /**
     * ServiceLabelsService 構造函數.
     */
    public function __construct()
    {
        $this->serviceLabelsRepository = app('registry')->getManager('default')->getRepository(ServiceLabels::class);
    }

    /**
     * 添加會員數值屬性
     *
     * @param array params 會員數值屬性數據
     * @return array
     */
    public function createServiceLabels(array $params)
    {
        $data = [
            'label_name' => $params['label_name'],
            'label_price' => $params['label_price'],
            'label_desc' => $params['label_desc'],
            'service_type' => $params['service_type'],
            'company_id' => $params['company_id'],
        ];
        $rs = $this->serviceLabelsRepository->create($data);

        return $rs;
    }

    /**
     * 刪除會員數值屬性
     *
     * @param array filter
     * @return bool
     */
    public function deleteServiceLabels($filter)
    {
        $serviceLabelsInfo = $this->serviceLabelsRepository->get($filter['label_id']);

        if ($filter['company_id'] != $serviceLabelsInfo['company_id']) {
            throw new ResourceException('刪除會員數值屬性信息有誤.');
        }
        if (!$filter['label_id']) {
            throw new ResourceException('會員數值屬性id不能為空.');
        }

        return $this->serviceLabelsRepository->delete($filter['label_id']);
    }

    /**
     * 獲取會員數值屬性詳情
     *
     * @param inteter label_id 會員數值屬性id
     * @return array
     */
    public function getServiceLabelsDetail($label_id)
    {
        $serviceLabelsInfo = $this->serviceLabelsRepository->get($label_id);

        return $serviceLabelsInfo;
    }

    /**
     * 獲取會員數值屬性列表
     *
     * @param array filter
     * @return array
     */
    public function getServiceLabelsList($filter, $page, $pageSize, $orderBy = ['label_id' => 'DESC'])
    {
        $page = ($page < 1) ? 1 : $page;
        $pageSize = ($pageSize > 100) ? 100 : $pageSize;
        $pageSize = ($pageSize <= 0) ? 10 : $pageSize;
        $serviceLabelsList = $this->serviceLabelsRepository->list($filter, $orderBy, $pageSize, $page);

        return $serviceLabelsList;
    }

    /**
     * 修改會員數值屬性
     *
     * @param array params 提交的門店數據
     * @return array
     */
    public function updateServiceLabels($params)
    {
        $serviceLabelsInfo = $this->serviceLabelsRepository->get($params['label_id']);

        if ($params['company_id'] != $serviceLabelsInfo['company_id']) {
            throw new ResourceException('請確認您的會員數值屬性信息後再提交.');
        }
        $data = [
            'label_name' => $params['label_name'],
            'label_price' => $params['label_price'],
            'label_desc' => $params['label_desc'],
            'service_type' => $params['service_type'],
            'company_id' => $params['company_id'],
        ];

        $rs = $this->serviceLabelsRepository->update($params['label_id'], $data);

        return $rs;
    }
}
