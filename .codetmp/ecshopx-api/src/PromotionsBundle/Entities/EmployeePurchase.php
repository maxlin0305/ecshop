<?php

namespace PromotionsBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * EmployeePurchase 员工内购活动规则表
 *
 * @ORM\Table(name="promotions_employee_purchase", options={"comment"="员工内购活动规则表"}, indexes={
 *    @ORM\Index(name="idx_company_id", columns={"company_id"}),
 * })
 * @ORM\Entity(repositoryClass="PromotionsBundle\Repositories\EmployeePurchaseRepository")
 */
class EmployeePurchase
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="purchase_id", type="bigint", options={"comment":"员工内购活动id"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $purchase_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司ID"})
     */
    private $company_id;

    /**
     * @var string
     *
     * @ORM\Column(name="purchase_name", type="string", length=50, options={"comment":"员工内购活动名称"})
     */
    private $purchase_name;

    /**
     * @var string
     *
     * @ORM\Column(name="ad_pic", type="string", nullable=true, options={"comment":"活动广告图"})
     */
    private $ad_pic;

    /**
     * @var string
     * employee:员工
     * dependents:家属
     *
     * @ORM\Column(name="used_roles", type="string", options={"comment":"适用角色集合 employee:员工;dependents:家属;"})
     */
    private $used_roles;

    /**
     * @var int
     *
     * 员工额度，以分为单位
     *
     * @ORM\Column(name="employee_limitfee", type="integer", options={"unsigned":true, "comment":"员工额度，以分为单位"})
     */
    private $employee_limitfee = 0;

    /**
     * @var boolean
     * @ORM\Column(name="is_share_limitfee", type="boolean", options={"comment":"家属是否共有额度 0:否 1:是","default":0})
     */
    private $is_share_limitfee = 0;

    /**
     * @var int
     *
     * 家属额度，以分为单位
     *
     * @ORM\Column(name="dependents_limitfee", type="integer", options={"unsigned":true, "comment":"家属额度，以分为单位"})
     */
    private $dependents_limitfee = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="dependents_limit", type="integer", options={"comment":"员工邀请家属上限", "default":0})
     */
    private $dependents_limit = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="minimum_amount", type="integer", options={"unsigned":true, "comment":"起定金额，以分为单位"})
     */
    private $minimum_amount = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="begin_time", type="integer", options={"comment":"起始时间"})
     */
    private $begin_time;

    /**
     * @var integer
     *
     * @ORM\Column(name="end_time", type="integer", options={"comment":"截止时间"})
     */
    private $end_time;

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
     * Get purchaseId.
     *
     * @return int
     */
    public function getPurchaseId()
    {
        return $this->purchase_id;
    }

    /**
     * Set companyId.
     *
     * @param int $companyId
     *
     * @return EmployeePurchase
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
     * Set purchaseName.
     *
     * @param string $purchaseName
     *
     * @return EmployeePurchase
     */
    public function setPurchaseName($purchaseName)
    {
        $this->purchase_name = $purchaseName;

        return $this;
    }

    /**
     * Get purchaseName.
     *
     * @return string
     */
    public function getPurchaseName()
    {
        return $this->purchase_name;
    }

    /**
     * Set adPic.
     *
     * @param string|null $adPic
     *
     * @return EmployeePurchase
     */
    public function setAdPic($adPic = null)
    {
        $this->ad_pic = $adPic;

        return $this;
    }

    /**
     * Get adPic.
     *
     * @return string|null
     */
    public function getAdPic()
    {
        return $this->ad_pic;
    }

    /**
     * Set usedRoles.
     *
     * @param string $usedRoles
     *
     * @return EmployeePurchase
     */
    public function setUsedRoles($usedRoles)
    {
        $this->used_roles = $usedRoles;

        return $this;
    }

    /**
     * Get usedRoles.
     *
     * @return string
     */
    public function getUsedRoles()
    {
        return $this->used_roles;
    }

    /**
     * Set employeeLimitfee.
     *
     * @param int $employeeLimitfee
     *
     * @return EmployeePurchase
     */
    public function setEmployeeLimitfee($employeeLimitfee)
    {
        $this->employee_limitfee = $employeeLimitfee;

        return $this;
    }

    /**
     * Get employeeLimitfee.
     *
     * @return int
     */
    public function getEmployeeLimitfee()
    {
        return $this->employee_limitfee;
    }

    /**
     * Set isShareLimitfee.
     *
     * @param bool $isShareLimitfee
     *
     * @return EmployeePurchase
     */
    public function setIsShareLimitfee($isShareLimitfee)
    {
        $this->is_share_limitfee = $isShareLimitfee;

        return $this;
    }

    /**
     * Get isShareLimitfee.
     *
     * @return bool
     */
    public function getIsShareLimitfee()
    {
        return $this->is_share_limitfee;
    }

    /**
     * Set dependentsLimitfee.
     *
     * @param int $dependentsLimitfee
     *
     * @return EmployeePurchase
     */
    public function setDependentsLimitfee($dependentsLimitfee)
    {
        $this->dependents_limitfee = $dependentsLimitfee;

        return $this;
    }

    /**
     * Get dependentsLimitfee.
     *
     * @return int
     */
    public function getDependentsLimitfee()
    {
        return $this->dependents_limitfee;
    }

    /**
     * Set dependentsLimit.
     *
     * @param int $dependentsLimit
     *
     * @return EmployeePurchase
     */
    public function setDependentsLimit($dependentsLimit)
    {
        $this->dependents_limit = $dependentsLimit;

        return $this;
    }

    /**
     * Get dependentsLimit.
     *
     * @return int
     */
    public function getDependentsLimit()
    {
        return $this->dependents_limit;
    }

    /**
     * Set minimumAmount.
     *
     * @param int $minimumAmount
     *
     * @return EmployeePurchase
     */
    public function setMinimumAmount($minimumAmount)
    {
        $this->minimum_amount = $minimumAmount;

        return $this;
    }

    /**
     * Get minimumAmount.
     *
     * @return int
     */
    public function getMinimumAmount()
    {
        return $this->minimum_amount;
    }

    /**
     * Set startTime.
     *
     * @param int $startTime
     *
     * @return EmployeePurchase
     */
    public function setStartTime($startTime)
    {
        $this->begin_time = $startTime;

        return $this;
    }

    /**
     * Get startTime.
     *
     * @return int
     */
    public function getStartTime()
    {
        return $this->begin_time;
    }

    /**
     * Set endTime.
     *
     * @param int $endTime
     *
     * @return EmployeePurchase
     */
    public function setEndTime($endTime)
    {
        $this->end_time = $endTime;

        return $this;
    }

    /**
     * Get endTime.
     *
     * @return int
     */
    public function getEndTime()
    {
        return $this->end_time;
    }

    /**
     * Set created.
     *
     * @param int $created
     *
     * @return EmployeePurchase
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
     * @return EmployeePurchase
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
     * Set beginTime.
     *
     * @param int $beginTime
     *
     * @return EmployeePurchase
     */
    public function setBeginTime($beginTime)
    {
        $this->begin_time = $beginTime;

        return $this;
    }

    /**
     * Get beginTime.
     *
     * @return int
     */
    public function getBeginTime()
    {
        return $this->begin_time;
    }
}
