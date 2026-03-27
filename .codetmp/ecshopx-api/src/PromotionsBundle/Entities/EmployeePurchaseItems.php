<?php

namespace PromotionsBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * EmployeePurchaseItems 员工内购商品表
 *
 * @ORM\Table(name="promotions_employee_purchase_items", options={"comment"="员工内购商品表"}, indexes={
 *    @ORM\Index(name="idx_company_id", columns={"company_id"}),
 *    @ORM\Index(name="idx_purchase_id", columns={"purchase_id"}),
 *    @ORM\Index(name="idx_item_id", columns={"item_id"}),
 * })
 * @ORM\Entity(repositoryClass="PromotionsBundle\Repositories\EmployeePurchaseItemsRepository")
 */
class EmployeePurchaseItems
{

    /**
     * @var integer
     * @ORM\Id
     * @ORM\Column(name="purchase_id", type="bigint", options={"comment":"员工内购活动ID"})
     */
    private $purchase_id;


    /**
     * @var integer
     * @ORM\Id
     * @ORM\Column(name="item_id", type="bigint", options={"comment":"关联id(商品ID，标签ID，品牌ID等)"})
     */
    private $item_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司ID"})
     */
    private $company_id;

    /**
     * @var string
     *  all 全部商品
     *  item 指定商品
     *  tag 标签
     *  category 商品主类目
     *  brand 品牌
     *
     * @ORM\Column(name="item_type", type="string", options={"comment":"活动商品类型: all:全部商品,item:指定商品,tag:标签,category:商品主类目,brand:品牌", "default":"all"})
     */
    private $item_type = 'all';

    /**
     * @var int
     *
     * 每人额度，以分为单位
     *
     * @ORM\Column(name="limit_fee", type="integer", options={"unsigned":true, "comment":"每人额度，以分为单位"})
     */
    private $limit_fee = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="limit_num", type="integer", options={"comment":"每人限购", "default":0})
     */
    private $limit_num = 0;

    /**
     * @var \DateTime $created
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="integer")
     */
    protected $created;

    /**
     * @var \DateTime $updated
     *
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $updated;


    

    /**
     * Set purchaseId.
     *
     * @param int $purchaseId
     *
     * @return EmployeePurchaseItems
     */
    public function setPurchaseId($purchaseId)
    {
        $this->purchase_id = $purchaseId;

        return $this;
    }

    /**
     * Get purchaseId.
     *
     * @return int
     */
    public function getPurchaseId()
    {
        return $this->purchase_id;
    }

    /**
     * Set itemId.
     *
     * @param int $itemId
     *
     * @return EmployeePurchaseItems
     */
    public function setItemId($itemId)
    {
        $this->item_id = $itemId;

        return $this;
    }

    /**
     * Get itemId.
     *
     * @return int
     */
    public function getItemId()
    {
        return $this->item_id;
    }

    /**
     * Set companyId.
     *
     * @param int $companyId
     *
     * @return EmployeePurchaseItems
     */
    public function setCompanyId($companyId)
    {
        $this->company_id = $companyId;

        return $this;
    }

    /**
     * Get companyId.
     *
     * @return int
     */
    public function getCompanyId()
    {
        return $this->company_id;
    }

    /**
     * Set itemType.
     *
     * @param string $itemType
     *
     * @return EmployeePurchaseItems
     */
    public function setItemType($itemType)
    {
        $this->item_type = $itemType;

        return $this;
    }

    /**
     * Get itemType.
     *
     * @return string
     */
    public function getItemType()
    {
        return $this->item_type;
    }

    /**
     * Set limitFee.
     *
     * @param int $limitFee
     *
     * @return EmployeePurchaseItems
     */
    public function setLimitFee($limitFee)
    {
        $this->limit_fee = $limitFee;

        return $this;
    }

    /**
     * Get limitFee.
     *
     * @return int
     */
    public function getLimitFee()
    {
        return $this->limit_fee;
    }

    /**
     * Set limitNum.
     *
     * @param int $limitNum
     *
     * @return EmployeePurchaseItems
     */
    public function setLimitNum($limitNum)
    {
        $this->limit_num = $limitNum;

        return $this;
    }

    /**
     * Get limitNum.
     *
     * @return int
     */
    public function getLimitNum()
    {
        return $this->limit_num;
    }

    /**
     * Set created.
     *
     * @param int $created
     *
     * @return EmployeePurchaseItems
     */
    public function setCreated($created)
    {
        $this->created = $created;

        return $this;
    }

    /**
     * Get created.
     *
     * @return int
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * Set updated.
     *
     * @param int|null $updated
     *
     * @return EmployeePurchaseItems
     */
    public function setUpdated($updated = null)
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * Get updated.
     *
     * @return int|null
     */
    public function getUpdated()
    {
        return $this->updated;
    }
}
