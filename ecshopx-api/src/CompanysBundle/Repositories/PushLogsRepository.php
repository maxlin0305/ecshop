<?php

namespace CompanysBundle\Repositories;

use CompanysBundle\Entities\PushLogs;
use Doctrine\ORM\EntityRepository;

class PushLogsRepository extends EntityRepository
{
    public $table = "push_logs";
    public $cols = ['id','company_id','request_params','response_data','http_status_code','status','push_time','cost_time','retry_times','method', 'type', 'created', 'updated'];


    /**
     * 新增
     *
     * @param array $data
     */
    public function create($data)
    {
        $entity = new PushLogs();
        $entity = $this->setColumnNamesData($entity, $data);

        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();

        return $this->getColumnNamesData($entity);
    }

    /**
     * 统计数量
     */
    public function count($filter)
    {
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        $qb->select('count(*)')
            ->from($this->table);
        if ($filter) {
            $this->_filter($filter, $qb);
        }
        $count = $qb->execute()->fetchColumn();
        return intval($count);
    }


    /**
     * 筛选条件格式化
     *
     * @param $filter
     * @param $qb
     */
    private function _filter($filter, $qb)
    {
        foreach ($filter as $field => $value) {
            $list = explode('|', $field);
            if (count($list) > 1) {
                list($v, $k) = $list;
                if ($k == 'contains') {
                    $k = 'like';
                    $value = '%'.$value.'%';
                }
                $qb = $qb->andWhere($qb->expr()->$k($v, $qb->expr()->literal($value)));
                continue;
            } elseif (is_array($value)) {
                array_walk($value, function (&$colVal) use ($qb) {
                    $colVal = $qb->expr()->literal($colVal);
                });
                $qb = $qb->andWhere($qb->expr()->in($field, $value));
            } else {
                $qb = $qb->andWhere($qb->expr()->eq($field, $qb->expr()->literal($value)));
            }
        }
        return $qb;
    }


    /**
     * 根据条件获取列表数据
     *
     * @param $filter 更新的条件
     */
    public function lists($filter, $cols = '*', $page = 1, $pageSize = -1, $orderBy = array())
    {
        $result['total_count'] = $this->count($filter);
        if ($result['total_count'] > 0) {
            $conn = app('registry')->getConnection('default');
            $qb = $conn->createQueryBuilder()->select($cols)->from($this->table);
            $qb = $this->_filter($filter, $qb);
            if ($orderBy) {
                foreach ($orderBy as $filed => $val) {
                    $qb->orderBy($filed, $val);
                }
            }
            if ($pageSize > 0) {
                $qb->setFirstResult(($page - 1) * $pageSize)->setMaxResults($pageSize);
            }
            $lists = $qb->execute()->fetchAll();
        }
        $result['list'] = $lists ?? [];
        return $result;
    }


    /**
     * 根据主键获取数据
     *
     * @param $id
     */
    public function getInfoById($id)
    {
        $entity = $this->find($id);
        if (!$entity) {
            return [];
        }

        return $this->getColumnNamesData($entity);
    }


    public function updateById($id, $updateData)
    {
        $entity = $this->find($id);
        if (empty($entity)) {
            return [];
        }
        $entity = $this->setColumnNamesData($entity, $updateData);
        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();

        return $this->getColumnNamesData($entity);
    }

    /**
     * 设置entity数据，用于插入和更新操作
     * @param $entity
     * @param $data
     */
    private function setColumnNamesData($entity, $data)
    {
        if (isset($data["company_id"]) && $data["company_id"]) {
            $entity->setCompanyId($data["company_id"]);
        }
        if (isset($data["request_params"]) && $data["request_params"]) {
            $entity->setRequestParams($data["request_params"]);
        }
        if (isset($data["response_data"]) && $data["response_data"]) {
            $entity->setResponseData($data["response_data"]);
        }
        if (isset($data["http_status_code"]) && $data["http_status_code"]) {
            $entity->setHttpStatusCode($data["http_status_code"]);
        }
        if (isset($data["status"])) {
            $entity->setStatus($data["status"]);
        }
        if (isset($data["push_time"]) && $data["push_time"]) {
            $entity->setPushTime($data["push_time"]);
        }
        if (isset($data["cost_time"]) && $data["cost_time"]) {
            $entity->setCostTime($data["cost_time"]);
        }
        if (isset($data["retry_times"])) {
            $entity->setRetryTimes($data["retry_times"]);
        }
        if (isset($data["method"]) && $data["method"]) {
            $entity->setMethod($data["method"]);
        }
        if (isset($data["type"]) && $data["type"]) {
            $entity->setType($data["type"]);
        }
        if (isset($data["created"]) && $data["created"]) {
            $entity->setCreated($data["created"]);
        }
        if (isset($data["updated"]) && $data["updated"]) {
            $entity->setUpdated($data["updated"]);
        }
        if (isset($data["request_url"]) && $data["request_url"]) {
            $entity->setRequestUrl($data["request_url"]);
        }
        return $entity;
    }

    /**
     * 获取数据表字段数据
     *
     * @param entity
     */
    private function getColumnNamesData($entity)
    {
        return [
            'id' => $entity->getId(),
            'company_id' => $entity->getCompanyId(),
            'request_params' => $entity->getRequestParams(),
            'response_data' => $entity->getResponseData(),
            'http_status_code' => $entity->getHttpStatusCode(),
            'status' => $entity->getStatus(),
            'push_time' => $entity->getPushTime(),
            'cost_time' => $entity->getCostTime(),
            'retry_times' => $entity->getRetryTimes(),
            'method' => $entity->getMethod(),
            'type' => $entity->getType(),
            'created' => $entity->getCreated(),
            'updated' => $entity->getUpdated(),
            'request_url' => $entity->getRequestUrl(),
        ];
    }
}
