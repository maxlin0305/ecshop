<?php

namespace PromotionsBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * EmployeePurchaseRelorder 员工内购订单关联表
 *
 * @ORM\Table(name="promotions_employee_purchase_relorder", options={"comment"="员工内购订单关联表"}, indexes={
 *    @ORM\Index(name="idx_company_id", columns={"company_id"}),
 *    @ORM\Index(name="idx_purchase_id", columns={"purchase_id"}),
 *    @ORM\Index(name="idx_order_id", columns={"order_id"}),
 * })
 * @ORM\Entity(repositoryClass="PromotionsBundle\Repositories\EmployeePurchaseRelorderRepository")
 */
class EmployeePurchaseRelorder
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="bigint", options={"comment":"id"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司ID"})
     */
    private $company_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="purchase_id", type="bigint", options={"comment":"员工内购活动ID"})
     */
    private $purchase_id;

    /**
     * @var string
     *
     * @ORM\Column(name="order_id", type="string", nullable=true, length=100, options={"comment":"订单编号"})
     */
    private $order_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="order_item_id", type="bigint", length=64, options={"comment":"订单中的商品ID"})
     */
    private $order_item_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="user_id", type="bigint", options={"comment":"会员ID"})
     */
    private $user_id;

    /**
     * @var integer
     * 
     * @ORM\Column(name="purchase_item_id", type="bigint", options={"comment":"员工内购商品id(商品ID，标签ID，品牌ID、商品主类目ID)"})
     */
    private $purchase_item_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="num", type="integer", options={"unsigned":true, "comment":"购买商品数量"})
     */
    private $num;

    /**
     * @var integer
     *
     * @ORM\Column(name="fee", type="integer", options={"unsigned":true, "comment":"金额，以分为单位"})
     */
    private $fee;

    /**
     * @var string
     *
     * @ORM\Column(name="redis_key", type="string", options={"comment":"redis存储的key"})
     */
    private $redis_key;

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
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set companyId.
     *
     * @param int $companyId
     *
     * @return EmployeePurchaseRelorder
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
     * Set purchaseId.
     *
     * @param int $purchaseId
     *
     * @return EmployeePurchaseRelorder
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
     * Set orderId.
     *
     * @param string|null $orderId
     *
     * @return EmployeePurchaseRelorder
     */
    public function setOrderId($orderId = null)
    {
        $this->order_id = $orderId;

        return $this;
    }

    /**
     * Get orderId.
     *
     * @return string|null
     */
    public function getOrderId()
    {
        return $this->order_id;
    }

    /**
     * Set orderItemId.
     *
     * @param int $orderItemId
     *
     * @return EmployeePurchaseRelorder
     */
    public function setOrderItemId($orderItemId)
    {
        $this->order_item_id = $orderItemId;

        return $this;
    }

    /**
     * Get orderItemId.
     *
     * @return int
     */
    public function getOrderItemId()
    {
        return $this->order_item_id;
    }

    /**
     * Set userId.
     *
     * @param int $userId
     *
     * @return EmployeePurchaseRelorder
     */
    public function setUserId($userId)
    {
        $this->user_id = $userId;

        return $this;
    }

    /**
     * Get userId.
     *
     * @return int
     */
    public function getUserId()
    {
        return $this->user_id;
    }

    /**
     * Set purchaseItemId.
     *
     * @param int $purchaseItemId
     *
     * @return EmployeePurchaseRelorder
     */
    public function setPurchaseItemId($purchaseItemId)
    {
        $this->purchase_item_id = $purchaseItemId;

        return $this;
    }

    /**
     * Get purchaseItemId.
     *
     * @return int
     */
    public function getPurchaseItemId()
    {
        return $this->purchase_item_id;
    }

    /**
     * Set num.
     *
     * @param int $num
     *
     * @return EmployeePurchaseRelorder
     */
    public function setNum($num)
    {
        $this->num = $num;

        return $this;
    }

    /**
     * Get num.
     *
     * @return int
     */
    public function getNum()
    {
        return $this->num;
    }

    /**
     * Set fee.
     *
     * @param int $fee
     *
     * @return EmployeePurchaseRelorder
     */
    public function setFee($fee)
    {
        $this->fee = $fee;

        return $this;
    }

    /**
     * Get fee.
     *
     * @return int
     */
    public function getFee()
    {
        return $this->fee;
    }

    /**
     * Set redisKey.
     *
     * @param string $redisKey
     *
     * @return EmployeePurchaseRelorder
     */
    public function setRedisKey($redisKey)
    {
        $this->redis_key = $redisKey;

        return $this;
    }

    /**
     * Get redisKey.
     *
     * @return string
     */
    public function getRedisKey()
    {
        return $this->redis_key;
    }

    /**
     * Set created.
     *
     * @param int $created
     *
     * @return EmployeePurchaseRelorder
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
     * @return EmployeePurchaseRelorder
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
