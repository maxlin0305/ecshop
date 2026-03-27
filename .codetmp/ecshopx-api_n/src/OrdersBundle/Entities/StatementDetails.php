<?php

namespace OrdersBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * StatementDetails 结算单明细
 *
 * @ORM\Table(name="statement_details", options={"comment":"结算单明细"})
 * @ORM\Entity(repositoryClass="OrdersBundle\Repositories\StatementDetailsRepository")
 */
class StatementDetails
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="bigint", options={"comment":"ID"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司id"})
     */
    private $company_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="merchant_id", type="bigint", options={"comment":"商户id"})
     */
    private $merchant_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="distributor_id", type="bigint", options={"comment":"店铺id"})
     */
    private $distributor_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="statement_id", type="bigint", options={"comment":"结算单ID"})
     */
    private $statement_id;

    /**
     * @var string
     *
     * @ORM\Column(name="statement_no", type="string", length=20, options={"comment":"结算单号"})
     */
    private $statement_no;

    /**
     * @var integer
     *
     * @ORM\Column(name="order_id", type="bigint", length=64, options={"comment":"订单号"})
     */
    private $order_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="total_fee", type="integer", options={"comment":"实付金额，以分为单位"})
     */
    private $total_fee;

    /**
     * @var integer
     *
     * @ORM\Column(name="freight_fee", type="integer", options={"comment":"运费金额，以分为单位"})
     */
    private $freight_fee;

    /**
     * @var integer
     *
     * @ORM\Column(name="intra_city_freight_fee", type="integer", options={"comment":"同城配金额，以分为单位"})
     */
    private $intra_city_freight_fee;

    /**
     * @var integer
     *
     * @ORM\Column(name="rebate_fee", type="integer", options={"comment":"分销佣金，以分为单位"})
     */
    private $rebate_fee;

    /**
     * @var integer
     *
     * @ORM\Column(name="refund_fee", type="integer", options={"comment":"退款金额，以分为单位"})
     */
    private $refund_fee;

    /**
     * @var integer
     *
     * @ORM\Column(name="statement_fee", type="integer", options={"comment":"结算金额，以分为单位"})
     */
    private $statement_fee;

    /**
     * @var string
     *
     * @ORM\Column(name="pay_type", type="string", options={ "comment":"支付方式"})
     */
    private $pay_type;

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
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return StatementDetails
     */
    public function setCompanyId($companyId)
    {
        $this->company_id = $companyId;

        return $this;
    }

    /**
     * Get companyId
     *
     * @return integer
     */
    public function getCompanyId()
    {
        return $this->company_id;
    }

    /**
     * Set merchantId
     *
     * @param integer $merchantId
     *
     * @return StatementDetails
     */
    public function setMerchantId($merchantId)
    {
        $this->merchant_id = $merchantId;

        return $this;
    }

    /**
     * Get merchantId
     *
     * @return integer
     */
    public function getMerchantId()
    {
        return $this->merchant_id;
    }

    /**
     * Set distributorId
     *
     * @param integer $distributorId
     *
     * @return StatementDetails
     */
    public function setDistributorId($distributorId)
    {
        $this->distributor_id = $distributorId;

        return $this;
    }

    /**
     * Get distributorId
     *
     * @return integer
     */
    public function getDistributorId()
    {
        return $this->distributor_id;
    }

    /**
     * Set statementId
     *
     * @param string $statementId
     *
     * @return StatementDetails
     */
    public function setStatementId($statementId)
    {
        $this->statement_id = $statementId;

        return $this;
    }

    /**
     * Get statementId
     *
     * @return string
     */
    public function getStatementId()
    {
        return $this->statement_id;
    }

    /**
     * Set statementNo
     *
     * @param string $statementNo
     *
     * @return StatementDetails
     */
    public function setStatementNo($statementNo)
    {
        $this->statement_no = $statementNo;

        return $this;
    }

    /**
     * Get statementNo
     *
     * @return string
     */
    public function getStatementNo()
    {
        return $this->statement_no;
    }

    /**
     * Set orderId
     *
     * @param integer $orderId
     *
     * @return StatementDetails
     */
    public function setOrderId($orderId)
    {
        $this->order_id = $orderId;

        return $this;
    }

    /**
     * Get orderId
     *
     * @return integer
     */
    public function getOrderId()
    {
        return $this->order_id;
    }

    /**
     * Set totalFee
     *
     * @param integer $totalFee
     *
     * @return StatementDetails
     */
    public function setTotalFee($totalFee)
    {
        $this->total_fee = $totalFee;

        return $this;
    }

    /**
     * Get totalFee
     *
     * @return integer
     */
    public function getTotalFee()
    {
        return $this->total_fee;
    }

    /**
     * Set freightFee
     *
     * @param integer $freightFee
     *
     * @return StatementDetails
     */
    public function setFreightFee($freightFee)
    {
        $this->freight_fee = $freightFee;

        return $this;
    }

    /**
     * Get freightFee
     *
     * @return integer
     */
    public function getFreightFee()
    {
        return $this->freight_fee;
    }

    /**
     * Set intraCityFreightFee
     *
     * @param integer $intraCityFreightFee
     *
     * @return StatementDetails
     */
    public function setIntraCityFreightFee($intraCityFreightFee)
    {
        $this->intra_city_freight_fee = $intraCityFreightFee;

        return $this;
    }

    /**
     * Get intraCityFreightFee
     *
     * @return integer
     */
    public function getIntraCityFreightFee()
    {
        return $this->intra_city_freight_fee;
    }

    /**
     * Set rebateFee
     *
     * @param integer $rebateFee
     *
     * @return StatementDetails
     */
    public function setRebateFee($rebateFee)
    {
        $this->rebate_fee = $rebateFee;

        return $this;
    }

    /**
     * Get rebateFee
     *
     * @return integer
     */
    public function getRebateFee()
    {
        return $this->rebate_fee;
    }

    /**
     * Set refundFee
     *
     * @param integer $refundFee
     *
     * @return StatementDetails
     */
    public function setRefundFee($refundFee)
    {
        $this->refund_fee = $refundFee;

        return $this;
    }

    /**
     * Get refundFee
     *
     * @return integer
     */
    public function getRefundFee()
    {
        return $this->refund_fee;
    }

    /**
     * Set statementFee
     *
     * @param integer $statementFee
     *
     * @return StatementDetails
     */
    public function setStatementFee($statementFee)
    {
        $this->statement_fee = $statementFee;

        return $this;
    }

    /**
     * Get statementFee
     *
     * @return integer
     */
    public function getStatementFee()
    {
        return $this->statement_fee;
    }

    /**
     * Set payType
     *
     * @param integer $payType
     *
     * @return StatementDetails
     */
    public function setPayType($payType)
    {
        $this->pay_type = $payType;

        return $this;
    }

    /**
     * Get payType
     *
     * @return integer
     */
    public function getPayType()
    {
        return $this->pay_type;
    }

    /**
     * Set created.
     *
     * @param int $created
     *
     * @return StatementDetails
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
     * @return StatementDetails
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
