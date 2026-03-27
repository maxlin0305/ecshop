<?php

namespace OrdersBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * OrdersDelivery  订单发货单表
 *
 * @ORM\Table(name="orders_delivery", options={"comment":"订单发货单表"},
 *     indexes={
 *         @ORM\Index(name="idx_company_id", columns={"company_id"}),
 *         @ORM\Index(name="idx_order_id", columns={"order_id"}),
 *     },
 * )
 * @ORM\Entity(repositoryClass="OrdersBundle\Repositories\OrdersDeliveryRepository")
 */

class OrdersDelivery
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="orders_delivery_id", type="bigint", options={"comment":"orders_delivery_id"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $orders_delivery_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司id"})
     */
    private $company_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="order_id", type="bigint", options={"comment":"订单id"})
     */
    private $order_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="user_id", type="bigint", options={"comment":"用户id"})
     */
    private $user_id;

    /**
     * @var string
     *
     * @ORM\Column(name="logistics_type", type="bigint", options={"comment":"物流类型[1:快递100,2:绿界物流]"})
     */
    private $logistics_type;
    /**
     * @var string
     *
     * @ORM\Column(name="delivery_corp", type="string", options={"comment":"快递公司"})
     */
    private $delivery_corp;

    /**
     * @var string
     *
     * @ORM\Column(name="delivery_corp_name", type="string", options={"comment":"快递公司名称"})
     */
    private $delivery_corp_name;

    /**
     * @var string
     *
     * @ORM\Column(name="delivery_code", type="string", options={"comment":"快递单号"})
     */
    private $delivery_code;

    /**
     * @var integer
     *
     * @ORM\Column(name="delivery_time", type="integer", options={"comment":"发货时间"})
     */
    private $delivery_time;

    /**
     * @var string
     *
     * @ORM\Column(name="delivery_corp_source", type="string", nullable=true, options={"comment":"快递代码来源"})
     */
    private $delivery_corp_source;

    /**
     * @var string
     *
     * @ORM\Column(name="receiver_mobile", type="string", nullable=true, options={"comment":"收货人手机号"})
     */
    private $receiver_mobile;

    /**
     * @var string
     *
     * @ORM\Column(name="package_type", type="string", nullable=true, options={"comment":"订单包裹类型 batch 整单发货  sep拆单发货"})
     */
    private $package_type;

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
     * Get ordersDeliveryId.
     *
     * @return int
     */
    public function getOrdersDeliveryId()
    {
        return $this->orders_delivery_id;
    }

    /**
     * Set companyId.
     *
     * @param int $companyId
     *
     * @return OrdersDelivery
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
     * Set orderId.
     *
     * @param int $orderId
     *
     * @return OrdersDelivery
     */
    public function setOrderId($orderId)
    {
        $this->order_id = $orderId;

        return $this;
    }

    /**
     * Get orderId.
     *
     * @return int
     */
    public function getOrderId()
    {
        return $this->order_id;
    }

    /**
     * Set deliveryCorp.
     *
     * @param string $deliveryCorp
     *
     * @return OrdersDelivery
     */
    public function setDeliveryCorp($deliveryCorp)
    {
        $this->delivery_corp = $deliveryCorp;

        return $this;
    }

    /**
     * Get deliveryCorp.
     *
     * @return string
     */
    public function getDeliveryCorp()
    {
        return $this->delivery_corp;
    }

    /**
     * Set deliveryCorpName.
     *
     * @param string $deliveryCorpName
     *
     * @return OrdersDelivery
     */
    public function setDeliveryCorpName($deliveryCorpName)
    {
        $this->delivery_corp_name = $deliveryCorpName;

        return $this;
    }

    /**
     * Get deliveryCorpName.
     *
     * @return string
     */
    public function getDeliveryCorpName()
    {
        return $this->delivery_corp_name;
    }

    /**
     * Set deliveryCode.
     *
     * @param string $deliveryCode
     *
     * @return OrdersDelivery
     */
    public function setDeliveryCode($deliveryCode)
    {
        $this->delivery_code = $deliveryCode;

        return $this;
    }

    /**
     * Get deliveryCode.
     *
     * @return string
     */
    public function getDeliveryCode()
    {
        return $this->delivery_code;
    }

    /**
     * Set deliveryTime.
     *
     * @param int $deliveryTime
     *
     * @return OrdersDelivery
     */
    public function setDeliveryTime($deliveryTime)
    {
        $this->delivery_time = $deliveryTime;

        return $this;
    }

    /**
     * Get deliveryTime.
     *
     * @return int
     */
    public function getDeliveryTime()
    {
        return $this->delivery_time;
    }

    /**
     * Set created.
     *
     * @param int $created
     *
     * @return OrdersDelivery
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
     * @return OrdersDelivery
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

    /**
     * Set deliveryCorpSource.
     *
     * @param string $deliveryCorpSource
     *
     * @return OrdersDelivery
     */
    public function setDeliveryCorpSource($deliveryCorpSource)
    {
        $this->delivery_corp_source = $deliveryCorpSource;

        return $this;
    }


    /**
     * Set deliveryCorpSource.
     *
     * @param string $deliveryCorpSource
     *
     * @return OrdersDelivery
     */
    public function setLogisticsType($logisticsType)
    {
        $this->logistics_type = $logisticsType;

        return $this;
    }

    /**
     * Get deliveryCorpSource.
     *
     * @return string
     */
    public function getlogisticsType()
    {
        return $this->logistics_type;
    }

    /**
     * Get deliveryCorpSource.
     *
     * @return int
     */
    public function getDeliveryCorpSource()
    {
        return $this->delivery_corp_source;
    }

    /**
     * Set receiverMobile.
     *
     * @param string|null $receiverMobile
     *
     * @return OrdersDelivery
     */
    public function setReceiverMobile($receiverMobile = null)
    {
        $this->receiver_mobile = $receiverMobile;

        return $this;
    }

    /**
     * Get receiverMobile.
     *
     * @return string|null
     */
    public function getReceiverMobile()
    {
        return $this->receiver_mobile;
    }

    /**
     * Set userId.
     *
     * @param int $userId
     *
     * @return OrdersDelivery
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
     * Set packageType.
     *
     * @param string|null $packageType
     *
     * @return OrdersDelivery
     */
    public function setPackageType($packageType = null)
    {
        $this->package_type = $packageType;

        return $this;
    }

    /**
     * Get packageType.
     *
     * @return string|null
     */
    public function getPackageType()
    {
        return $this->package_type;
    }
}
