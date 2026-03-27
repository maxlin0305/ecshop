<?php

namespace OrdersBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * EcpayDeliveryInfo  绿界物流信息表
 *
 * @ORM\Table(name="ecpay_delivery_info", options={"comment":"绿界物流信息表"},
 *     indexes={
 *         @ORM\Index(name="idx_company_id", columns={"company_id"}),
 *         @ORM\Index(name="idx_order_id", columns={"order_id"}),
 *     },
 * )
 * @ORM\Entity(repositoryClass="OrdersBundle\Repositories\EcpayDeliveryInfoRepository")
 */

class EcpayDeliveryInfo
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
     * @ORM\Column(name="logistics_id", type="bigint", options={"comment":"物流交易編號"})
     */
    private $logistics_id;

    /**
     * @var string
     *
     * @ORM\Column(name="booking_note", type="string", options={"comment":"托運單號"})
     */
    private $booking_note;

    /**
     * @var string
     *
     * @ORM\Column(name="cvs_payment_no", type="string", options={"comment":"寄貨編號"})
     */
    private $cvs_payment_no;

    /**
     * @var string
     *
     * @ORM\Column(name="cvs_validation_no", type="string", options={"comment":"驗證碼"})
     */
    private $cvs_validation_no;

    /**
     * @var string
     *
     * @ORM\Column(name="goods_amount", type="string", options={"comment":"商品金額"})
     */
    private $goods_amount;

    /**
     * @var string
     *
     * @ORM\Column(name="logistics_type", type="string", options={"comment":"物流類型"})
     */
    private $logistics_type;

    /**
     * @var integer
     *
     * @ORM\Column(name="logistics_subtype", type="integer", options={"comment":"物流子類型"})
     */
    private $logistics_subtype;

    /**
     * @var string
     *
     * @ORM\Column(name="merchant_id", type="string", nullable=true, options={"comment":"廠商編號"})
     */
    private $merchant_id;

    /**
     * @var string
     *
     * @ORM\Column(name="merchant_tradeno", type="string", options={"comment":"廠商交易編號"})
     */
    private $merchant_tradeno;
    
    /**
     * @var string
     *
     * @ORM\Column(name="receiver_address", type="string", nullable=true, options={"comment":"收件人地址"})
     */
    private $receiver_address;
    
    /**
     * @var string
     *
     * @ORM\Column(name="receiver_mobile", type="string", nullable=true, options={"comment":"收件人手機"})
     */
    private $receiver_mobile;
    
    /**
     * @var string
     *
     * @ORM\Column(name="receiver_email", type="string", nullable=true, options={"comment":"收件人email"})
     */
    private $receiver_email;

    /**
     * @var string
     *
     * @ORM\Column(name="receiver_name", type="string", nullable=true, options={"comment":"收件人姓名"})
     */
    private $receiver_name;

    /**
     * @var string
     *
     * @ORM\Column(name="receiver_phone", type="string", nullable=true, options={"comment":"收件人電話"})
     */
    private $receiver_phone;

    /**
     * @var string
     *
     * @ORM\Column(name="rtn_code", type="string", nullable=true, options={"comment":"目前物流狀態"})
     */
    private $rtn_code;
    /**
     * @var string
     *
     * @ORM\Column(name="rtn_msg", type="string", nullable=true, options={"comment":"物流狀態說明"})
     */
    private $rtn_msg;
    /**
     * @var string
     *
     * @ORM\Column(name="update_status_date", type="string", nullable=true, options={"comment":"物流狀態更新時間"})
     */
    private $update_status_date;
    /**
     * @var \DateTime $created_at
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="integer")
     */
    protected $created_at;

    /**
     * @var \DateTime $updated_at
     *
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $updated_at;

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
     * Set logisticsId.
     *
     * @param string $logisticsId
     *
     * @return EcpayDeliveryInfo
     */
    public function setLogisticsId($logisticsId)
    {
        $this->logistics_id = $logisticsId;

        return $this;
    }

    /**
     * Get logisticsId.
     *
     * @return string
     */
    public function getLogisticsId()
    {
        return $this->logistics_id;
    }

    /**
     * Set bookingNote.
     *
     * @param string $bookingNote
     *
     * @return EcpayDeliveryInfo
     */
    public function setBookingNote($bookingNote)
    {
        $this->booking_note = $bookingNote;

        return $this;
    }

    /**
     * Get bookingNote.
     *
     * @return string
     */
    public function getBookingNote()
    {
        return $this->booking_note;
    }

    /**
     * Set cvsPaymentNo.
     *
     * @param string $cvsPaymentNo
     *
     * @return EcpayDeliveryInfo
     */
    public function setCvsPaymentNo($cvsPaymentNo)
    {
        $this->cvs_payment_no = $cvsPaymentNo;

        return $this;
    }

    /**
     * Get cvsPaymentNo.
     *
     * @return string
     */
    public function getCvsPaymentNo()
    {
        return $this->cvs_payment_no;
    }

    /**
     * Set cvsValidationNo.
     *
     * @param string $cvsValidationNo
     *
     * @return EcpayDeliveryInfo
     */
    public function setCvsValidationNo($cvsValidationNo)
    {
        $this->cvs_validation_no = $cvsValidationNo;

        return $this;
    }

    /**
     * Get cvsValidationNo.
     *
     * @return string
     */
    public function getCvsValidationNo()
    {
        return $this->cvs_validation_no;
    }


    /**
     * Set goodsAmount.
     *
     * @param string $goodsAmount
     *
     * @return EcpayDeliveryInfo
     */
    public function setGoodsAmount($goodsAmount)
    {
        $this->goods_amount = $goodsAmount;

        return $this;
    }

    /**
     * Get goodsAmount.
     *
     * @return string
     */
    public function getGoodsAmount()
    {
        return $this->goods_amount;
    }

    /**
     * Set logisticsType.
     *
     * @param string $logisticsType
     *
     * @return EcpayDeliveryInfo
     */
    public function setLogisticsType($logisticsType)
    {
        $this->logistics_type = $logisticsType;

        return $this;
    }

    /**
     * Get logisticsType.
     *
     * @return string
     */
    public function getLogisticsType()
    {
        return $this->logistics_type;
    }

    /**
     * Set logisticsSubtype.
     *
     * @param string $logisticsSubtype
     *
     * @return EcpayDeliveryInfo
     */
    public function setLogisticsSubtype($logisticsSubtype)
    {
        $this->logistics_subtype = $logisticsSubtype;

        return $this;
    }

    /**
     * Get logisticsSubtype.
     *
     * @return string
     */
    public function getLogisticsSubtype()
    {
        return $this->logistics_subtype;
    }

    /**
     * Set merchantId.
     *
     * @param string $merchantId
     *
     * @return EcpayDeliveryInfo
     */
    public function setMerchantId($merchantId)
    {
        $this->merchant_id = $merchantId;

        return $this;
    }

    /**
     * Get merchantId.
     *
     * @return string
     */
    public function getMerchantId()
    {
        return $this->merchant_id;
    }

    /**
     * Set merchantTradeno.
     *
     * @param string $merchantTradeno
     *
     * @return EcpayDeliveryInfo
     */
    public function setMerchantTradeno($merchantTradeno)
    {
        $this->merchant_tradeno = $merchantTradeno;

        return $this;
    }

    /**
     * Get merchantTradeno.
     *
     * @return string
     */
    public function getMerchantTradeno()
    {
        return $this->merchant_tradeno;
    }

    /**
     * Set receiverAddress.
     *
     * @param string $receiverAddress
     *
     * @return EcpayDeliveryInfo
     */
    public function setReceiverAddress($receiverAddress)
    {
        $this->receiver_address = $receiverAddress;

        return $this;
    }

    /**
     * Get receiverAddress.
     *
     * @return string
     */
    public function getReceiverAddress()
    {
        return $this->receiver_address;
    }


    /**
     * Set receiverMobile.
     *
     * @param string $receiverMobile
     *
     * @return EcpayDeliveryInfo
     */
    public function setReceiverMobile($receiverMobile)
    {
        $this->receiver_mobile = $receiverMobile;

        return $this;
    }

    /**
     * Get receiverMobile.
     *
     * @return string
     */
    public function getReceiverMobile()
    {
        return $this->receiver_mobile;
    }

    /**
     * Set receiverEmail.
     *
     * @param string $receiverEmail
     *
     * @return EcpayDeliveryInfo
     */
    public function setReceiverEmail($receiverEmail)
    {
        $this->receiver_email = $receiverEmail;

        return $this;
    }

    /**
     * Get receiverEmail.
     *
     * @return string
     */
    public function getReceiverEmail()
    {
        return $this->receiver_email;
    }

    /**
     * Set receiverName.
     *
     * @param string $receiverName
     *
     * @return EcpayDeliveryInfo
     */
    public function setReceiverName($receiverName)
    {
        $this->receiver_name = $receiverName;

        return $this;
    }

    /**
     * Get receiverName.
     *
     * @return string
     */
    public function getReceiverName()
    {
        return $this->receiver_name;
    }

    /**
     * Set receiverPhone.
     *
     * @param string $receiverPhone
     *
     * @return EcpayDeliveryInfo
     */
    public function setReceiverPhone($receiverPhone)
    {
        $this->receiver_phone = $receiverPhone;

        return $this;
    }

    /**
     * Get receiverPhone.
     *
     * @return string
     */
    public function getReceiverPhone()
    {
        return $this->receiver_phone;
    }

    /**
     * Set rtnCode.
     *
     * @param string $rtnCode
     *
     * @return EcpayDeliveryInfo
     */
    public function setRtnCode($rtnCode)
    {
        $this->rtn_code = $rtnCode;

        return $this;
    }

    /**
     * Get rtnCode.
     *
     * @return string
     */
    public function getRtnCode()
    {
        return $this->rtn_code;
    }


    /**
     * Set rtnMsg.
     *
     * @param string $rtnMsg
     *
     * @return EcpayDeliveryInfo
     */
    public function setRtnMsg($rtnMsg)
    {
        $this->rtn_msg = $rtnMsg;

        return $this;
    }

    /**
     * Get rtnMsg.
     *
     * @return string
     */
    public function getRtnMsg()
    {
        return $this->rtn_msg;
    }

    /**
     * Set updateStatusDate.
     *
     * @param string $updateStatusDate
     *
     * @return EcpayDeliveryInfo
     */
    public function setUpdateStatusDate($updateStatusDate)
    {
        $this->update_status_date = $updateStatusDate;

        return $this;
    }

    /**
     * Get updateStatusDate.
     *
     * @return string
     */
    public function getUpdateStatusDate()
    {
        return $this->update_status_date;
    }

 
    /**
     * get CreatedAt
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->created_at;
    }

    /**
     * set CreatedAt
     *
     * @param \DateTime $created_at
     *
     * @return self
     */
    public function setCreatedAt($created_at)
    {
        $this->created_at = $created_at;
        return $this;
    }

    /**
     * get UpdatedAt
     *
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updated_at;
    }

    /**
     * set UpdatedAt
     *
     * @param \DateTime $updated_at
     *
     * @return self
     */
    public function setUpdatedAt($updated_at)
    {
        $this->updated_at = $updated_at;
        return $this;
    }

}
