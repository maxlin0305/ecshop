<?php

namespace CompanysBundle\Repositories;

use Doctrine\ORM\EntityRepository;
use Doctrine\Common\Collections\Criteria;
use CompanysBundle\Entities\Operators;
use Dingo\Api\Exception\ResourceException;

class OperatorsRepository extends EntityRepository
{
    public $table = "operators";
    public function getOperatorByMobile($mobile, $operatorType)
    {
        $operator = app('registry')->getConnection('default')->fetchAssoc("select * from operators where mobile=? and operator_type=?", [fixedencrypt($mobile), $operatorType]);
        if ($operator) {
            $operator['mobile'] = fixeddecrypt($operator['mobile']);
        }
        return $operator;
    }

    public function create($params)
    {
        $params['contact'] = $params['contact'] ?? '';
        $operatorEntity = new Operators();
        $operator = $this->setOperatorData($operatorEntity, $params);

        $em = $this->getEntityManager();
        $em->persist($operator);
        $em->flush();

        $result = $this->getOperatorData($operator);

        return $result;
    }

    /**
     * 更新数据表字段数据
     *
     * @param $filter 更新的条件
     * @param $data 更新的内容
     */
    public function updateOneBy(array $filter, array $data)
    {
        $filter = $this->fixedencryptCol($filter);
        $entity = $this->findOneBy($filter);
        if (!$entity) {
            throw new ResourceException("未查询到更新数据");
        }

        $entity = $this->setOperatorData($entity, $data);

        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();

        return $this->getOperatorData($entity);
    }


    public function setOperatorData($operatorEntity, $data)
    {
        if (isset($data['login_name']) && $data['login_name']) {
            $operatorEntity->setLoginName($data['login_name']);
        }
        if (isset($data['mobile']) && $data['mobile']) {
            $operatorEntity->setMobile($data['mobile']);
        }
        if (isset($data['password']) && $data['password']) {
            $operatorEntity->setPassword($data['password']);
        }
        if (isset($data['eid']) && $data['eid']) {
            $operatorEntity->setEid($data['eid']);
        }
        if (isset($data['passport_uid']) && $data['passport_uid']) {
            $operatorEntity->setPassportUid($data['passport_uid']);
        }
        if (isset($data['operator_type']) && $data['operator_type']) {
            $operatorEntity->setOperatorType($data['operator_type']);
        }
        if (isset($data['shop_ids'])) {
            $operatorEntity->setShopIds(json_encode($data['shop_ids']));
        }
        if (isset($data['distributor_ids'])) {
            $operatorEntity->setDistributorIds(json_encode($data['distributor_ids']));
        }
        if (isset($data['company_id']) && $data['company_id']) {
            $operatorEntity->setCompanyId($data['company_id']);
        }
        //当前字段非必填
        if (isset($data["username"]) && $data["username"]) {
            $operatorEntity->setUsername($data["username"]);
        }
        //当前字段非必填
        if (isset($data["head_portrait"]) && $data["head_portrait"]) {
            $operatorEntity->setHeadPortrait($data["head_portrait"]);
        }
        if (isset($data['regionauth_id']) && $data['regionauth_id']) {
            $operatorEntity->setRegionauthId($data['regionauth_id']);
        }
        if (isset($data['contact'])) {
            $operatorEntity->setContact($data['contact']);
        }
        if (isset($data['split_ledger_info']) && $data['split_ledger_info']) {
            $operatorEntity->setSplitLedgerInfo($data['split_ledger_info']);
        }

        if (isset($data['is_disable'])) {
            $operatorEntity->setIsDisable($data['is_disable']);
        }

        if (isset($data['adapay_open_account_time']) && $data['adapay_open_account_time']) {
            $operatorEntity->setAdapayOpenAccountTime($data['adapay_open_account_time']);
        }

        if (isset($data['dealer_parent_id']) && $data['dealer_parent_id']) {
            $operatorEntity->setDealerParentId($data['dealer_parent_id']);
        }

        if (isset($data['is_dealer_main'])) {
            $operatorEntity->setIsDealerMain($data['is_dealer_main']);
        }
        if (isset($data['merchant_id'])) {
            $operatorEntity->setMerchantId($data['merchant_id']);
        }
        if (isset($data['is_merchant_main'])) {
            $operatorEntity->setIsMerchantMain($data['is_merchant_main']);
        }
        if (isset($data['is_distributor_main'])) {
            $operatorEntity->setIsDistributorMain($data['is_distributor_main']);
        }
        return $operatorEntity;
    }

    public function getOperatorData($operatorEntity)
    {
        return [
            'operator_id' => $operatorEntity->getOperatorId(),
            'mobile' => $operatorEntity->getMobile(),
            'login_name' => $operatorEntity->getLoginName(),
            'password' => $operatorEntity->getPassword(),
            'eid' => $operatorEntity->getEid(),
            'passport_uid' => $operatorEntity->getPassportUid(),
            'operator_type' => $operatorEntity->getOperatorType(),
            'shop_ids' => json_decode($operatorEntity->getShopIds(), true),
            'distributor_ids' => json_decode($operatorEntity->getDistributorIds(), true),
            'company_id' => $operatorEntity->getCompanyId(),
            'username' => $operatorEntity->getUsername(),
            'head_portrait' => $operatorEntity->getHeadPortrait(),
            'regionauth_id' => $operatorEntity->getRegionauthId(),
            'split_ledger_info' => $operatorEntity->getSplitLedgerInfo(),
            'contact' => $operatorEntity->getContact(),
            'is_disable' => $operatorEntity->getIsDisable(),
            'adapay_open_account_time' => $operatorEntity->getAdapayOpenAccountTime(),
            'dealer_parent_id' => $operatorEntity->getDealerParentId(),
            'is_dealer_main' => $operatorEntity->getIsDealerMain(),
            'created' => $operatorEntity->getCreated(),
            'updated' => $operatorEntity->getUpdated(),
            'merchant_id' => $operatorEntity->getMerchantId(),
            'is_merchant_main' => $operatorEntity->getIsMerchantMain(),
            'is_distributor_main' => $operatorEntity->getIsDistributorMain(),
        ];
    }

    public function deleteBy($filter)
    {
        $filter = $this->fixedencryptCol($filter);
        $entityList = $this->findBy($filter);
        if (!$entityList) {
            throw new \Exception("删除的数据不存在");
        }
        $em = $this->getEntityManager();
        foreach ($entityList as $entityProp) {
            $em->remove($entityProp);
            $em->flush();
        }
        return true;
    }

    public function getInfo($filter)
    {
        $filter = $this->fixedencryptCol($filter);
        $entity = $this->findOneBy($filter);
        if (!$entity) {
            return [];
        }
        return $this->getOperatorData($entity);
    }

    /**
     * 根据条件获取单条数据
     *
     * @param $filter 更新的条件
     */
    public function lists($filter, $orderBy = ["created" => "DESC"], $pageSize = 100, $page = 1)
    {
        $filter = $this->fixedencryptCol($filter);
        $criteria = Criteria::create();
        foreach ($filter as $field => $value) {
            $list = explode("|", $field);
            if (count($list) > 1) {
                list($v, $k) = $list;
                $criteria = $criteria->andWhere(Criteria::expr()->$k($v, $value));
                continue;
            } elseif (is_array($value)) {
                $criteria = $criteria->andWhere(Criteria::expr()->in($field, $value));
            } else {
                $criteria = $criteria->andWhere(Criteria::expr()->eq($field, $value));
            }
        }

        $total = $this->getEntityManager()
            ->getUnitOfWork()
            ->getEntityPersister($this->getEntityName())
            ->count($criteria);
        $res["total_count"] = intval($total);

        $lists = [];
        if ($res["total_count"]) {
            $criteria = $criteria->orderBy($orderBy)
                ->setFirstResult($pageSize * ($page - 1))
                ->setMaxResults($pageSize);
            $entityList = $this->matching($criteria);
            foreach ($entityList as $entity) {
                $lists[] = $this->getOperatorData($entity);
            }
        }

        $res["list"] = $lists;
        return $res;
    }

    /**
    * 对filter中的部分字段，加密处理
    * @param  [type] $filter [description]
    * @return [type]         [description]
    */
    private function fixedencryptCol($filter)
    {
        $fixedencryptCol = ['mobile', 'contact'];
        foreach ($fixedencryptCol as $col) {
            if (isset($filter[$col])) {
                $filter[$col] = fixedencrypt($filter[$col]);
            }
        }
        return $filter;
    }
}
