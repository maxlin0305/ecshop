<?php

namespace PopularizeBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Promoter 推广员表
 *
 * @ORM\Table(name="popularize_promoter", options={"comment":"推广员表"},indexes={
 *     @ORM\Index(name="idx_pid", columns={"pid"}),
 *     @ORM\Index(name="idx_companyid_userid", columns={"company_id","user_id"}),
 * })
 * @ORM\Entity(repositoryClass="PopularizeBundle\Repositories\PromoterRepository")
 */
class Promoter
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
     * @var string
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"企业ID"})
     */
    private $company_id;

    /**
     * @var string
     *
     * @ORM\Column(name="user_id", type="bigint", options={"comment":"会员ID"})
     */
    private $user_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="pid", nullable=true, type="bigint", options={"comment":"上级会员ID"})
     */
    private $pid;

    /**
     * @var string
     *
     * @ORM\Column(name="pmobile", type="string", nullable=true, options={"comment":"上级手机号"})
     */
    private $pmobile;

    /**
     * @var string
     *
     * @ORM\Column(name="shop_name", nullable=true, type="string", options={"comment":"推广员自定义店铺名称"})
     */
    private $shop_name;

    /**
     * @var string
     *
     * @ORM\Column(name="alipay_name", nullable=true, type="string", options={"comment":"推广员提现的支付宝姓名"})
     */
    private $alipay_name;

    /**
     * @var string
     *
     * @ORM\Column(name="brief", nullable=true, type="string", options={"comment":"推广店铺描述"})
     */
    private $brief;

    /**
     * @var string
     *
     * @ORM\Column(name="shop_pic", nullable=true, type="string", options={"comment":"推广店铺封面"})
     */
    private $shop_pic;

    /**
     * @var string
     *
     * @ORM\Column(name="alipay_account", nullable=true, type="string", options={"comment":"推广员提现的支付宝账号"})
     */
    private $alipay_account;

    /**
     * @var integer
     *
     * @ORM\Column(name="grade_level", type="integer", length=4, options={"comment":"推广员等级"})
     */
    private $grade_level;

    /**
     * @var integer
     *
     * @ORM\Column(name="is_promoter", type="integer", length=4, options={"comment":"是否为推广员"})
     */
    private $is_promoter;

    /**
     * @var integer
     *
     * @ORM\Column(name="shop_status", type="integer", length=4, options={"comment":"开店状态 0 未开店 1已开店 2申请中 3禁用 4申请审核拒绝 ", "default":0})
     */
    private $shop_status;

    /**
     * @var string
     *
     * @ORM\Column(name="reason", nullable=true, type="string", options={"comment":"审核拒绝原因"})
     */
    private $reason;

    /**
     * @var integer
     *
     * @ORM\Column(name="disabled", type="integer", options={"comment":"是否有效"})
     */
    private $disabled;

    /**
     * @var integer
     *
     * @ORM\Column(name="is_buy", type="integer", options={"comment":"是否有购买记录"})
     */
    private $is_buy;

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
     * Set id
     *
     * @param integer $id
     *
     * @return Promoter
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

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
     * Set userId
     *
     * @param integer $userId
     *
     * @return Promoter
     */
    public function setUserId($userId)
    {
        $this->user_id = $userId;

        return $this;
    }

    /**
     * Get userId
     *
     * @return integer
     */
    public function getUserId()
    {
        return $this->user_id;
    }

    /**
     * Set pid
     *
     * @param integer $pid
     *
     * @return Promoter
     */
    public function setPid($pid)
    {
        $this->pid = $pid;

        return $this;
    }

    /**
     * Get pid
     *
     * @return integer
     */
    public function getPid()
    {
        return $this->pid;
    }

    /**
     * Set pmobile
     *
     * @param string $pmobile
     *
     * @return Promoter
     */
    public function setPmobile($pmobile)
    {
        $this->pmobile = fixedencrypt($pmobile);

        return $this;
    }

    /**
     * Get pmobile
     *
     * @return string
     */
    public function getPmobile()
    {
        return fixeddecrypt($this->pmobile);
    }

    /**
     * Set gradeLevel
     *
     * @param integer $gradeLevel
     *
     * @return Promoter
     */
    public function setGradeLevel($gradeLevel)
    {
        $this->grade_level = $gradeLevel;

        return $this;
    }

    /**
     * Get gradeLevel
     *
     * @return integer
     */
    public function getGradeLevel()
    {
        return $this->grade_level;
    }

    /**
     * Set isPromoter
     *
     * @param integer $isPromoter
     *
     * @return Promoter
     */
    public function setIsPromoter($isPromoter)
    {
        $this->is_promoter = $isPromoter;

        return $this;
    }

    /**
     * Get isPromoter
     *
     * @return integer
     */
    public function getIsPromoter()
    {
        return $this->is_promoter;
    }

    /**
     * Set disabled
     *
     * @param integer $disabled
     *
     * @return Promoter
     */
    public function setDisabled($disabled)
    {
        $this->disabled = $disabled;

        return $this;
    }

    /**
     * Get disabled
     *
     * @return integer
     */
    public function getDisabled()
    {
        return $this->disabled;
    }

    /**
     * Set isBuy
     *
     * @param integer $isBuy
     *
     * @return Promoter
     */
    public function setIsBuy($isBuy)
    {
        $this->is_buy = $isBuy;

        return $this;
    }

    /**
     * Get isBuy
     *
     * @return integer
     */
    public function getIsBuy()
    {
        return $this->is_buy;
    }

    /**
     * Set created
     *
     * @param integer $created
     *
     * @return Promoter
     */
    public function setCreated($created)
    {
        $this->created = $created;

        return $this;
    }

    /**
     * Get created
     *
     * @return integer
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return Promoter
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
     * Set shopName
     *
     * @param string $shopName
     *
     * @return Promoter
     */
    public function setShopName($shopName)
    {
        $this->shop_name = $shopName;

        return $this;
    }

    /**
     * Get shopName
     *
     * @return string
     */
    public function getShopName()
    {
        return $this->shop_name;
    }

    /**
     * Set alipayName
     *
     * @param string $alipayName
     *
     * @return Promoter
     */
    public function setAlipayName($alipayName)
    {
        $this->alipay_name = $alipayName;

        return $this;
    }

    /**
     * Get alipayName
     *
     * @return string
     */
    public function getAlipayName()
    {
        return $this->alipay_name;
    }

    /**
     * Set alipayAccount
     *
     * @param string $alipayAccount
     *
     * @return Promoter
     */
    public function setAlipayAccount($alipayAccount)
    {
        $this->alipay_account = $alipayAccount;

        return $this;
    }

    /**
     * Get alipayAccount
     *
     * @return string
     */
    public function getAlipayAccount()
    {
        return $this->alipay_account;
    }

    /**
     * Set brief
     *
     * @param string $brief
     *
     * @return Promoter
     */
    public function setBrief($brief)
    {
        $this->brief = $brief;

        return $this;
    }

    /**
     * Get brief
     *
     * @return string
     */
    public function getBrief()
    {
        return $this->brief;
    }

    /**
     * Set shopPic
     *
     * @param string $shopPic
     *
     * @return Promoter
     */
    public function setShopPic($shopPic)
    {
        $this->shop_pic = $shopPic;

        return $this;
    }

    /**
     * Get shopPic
     *
     * @return string
     */
    public function getShopPic()
    {
        return $this->shop_pic;
    }

    /**
     * Set shopStatus
     *
     * @param integer $shopStatus
     *
     * @return Promoter
     */
    public function setShopStatus($shopStatus)
    {
        $this->shop_status = $shopStatus;

        return $this;
    }

    /**
     * Get shopStatus
     *
     * @return integer
     */
    public function getShopStatus()
    {
        return $this->shop_status;
    }

    /**
     * Set reason
     *
     * @param string $reason
     *
     * @return Promoter
     */
    public function setReason($reason)
    {
        $this->reason = $reason;

        return $this;
    }

    /**
     * Get reason
     *
     * @return string
     */
    public function getReason()
    {
        return $this->reason;
    }

    /**
     * Set updated.
     *
     * @param int|null $updated
     *
     * @return Promoter
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
