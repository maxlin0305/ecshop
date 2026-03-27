<?php

namespace SelfserviceBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * RegistrationRecord 报名记录
 *
 * @ORM\Table(name="selfservice_registration_record", options={"comment"="报名记录"}, indexes={
 *    @ORM\Index(name="idx_activity_id", columns={"activity_id"}),
 *    @ORM\Index(name="idx_user_id", columns={"user_id"}),
 *    @ORM\Index(name="idx_company_id", columns={"company_id"})
 * }),
 * @ORM\Entity(repositoryClass="SelfserviceBundle\Repositories\RegistrationRecordRepository")
 */
class RegistrationRecord
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="record_id", type="bigint")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $record_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="activity_id", type="bigint", options={"comment":"活动id"})
     */
    private $activity_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="user_id", type="bigint", options={"comment":"会员id"})
     */
    private $user_id;

    /**
     * @var string
     *
     * @ORM\Column(name="mobile", type="string", options={"comment"="手机号"})
     */
    private $mobile;

    /**
     * @var string
     *
     * @ORM\Column(name="wxapp_appid", type="string", length=32, nullable=true, options={"comment":"会员小程序appid"})
     */
    private $wxapp_appid;

    /**
     * @var string
     *
     * @ORM\Column(name="open_id", type="string", length=32, nullable=true, options={"comment":"会员小程序openid"})
     */
    private $open_id;

    /**
     * @var string
     *
     * @ORM\Column(name="status", type="string", options={"comment":"状态,pending:待审核，passed:已通过，rejected:已拒绝", "default": "pending"})
     */
    private $status = "pending";

    /**
     * @var string
     *
     * @ORM\Column(name="content", type="text", options={"comment":"报名内容"})
     */
    private $content;

    /**
     * @var string
     *
     * @ORM\Column(name="reason", type="text", nullable=true, options={"comment":"拒绝原因"})
     */
    private $reason;

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
    * @var integer
    *
    * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司_ID"})
    */
    private $company_id;

    /**
     * Get recordId
     *
     * @return integer
     */
    public function getRecordId()
    {
        return $this->record_id;
    }

    /**
     * Set activityId
     *
     * @param integer $activityId
     *
     * @return RegistrationRecord
     */
    public function setActivityId($activityId)
    {
        $this->activity_id = $activityId;

        return $this;
    }

    /**
     * Get activityId
     *
     * @return integer
     */
    public function getActivityId()
    {
        return $this->activity_id;
    }

    /**
     * Set userId
     *
     * @param integer $userId
     *
     * @return RegistrationRecord
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
     * Set mobile
     *
     * @param integer $mobile
     *
     * @return RegistrationRecord
     */
    public function setMobile($mobile)
    {
        $this->mobile = fixedencrypt($mobile);

        return $this;
    }

    /**
     * Get mobile
     *
     * @return integer
     */
    public function getMobile()
    {
        return fixeddecrypt($this->mobile);
    }

    /**
     * Set wxappAppid
     *
     * @param string $wxappAppid
     *
     * @return RegistrationRecord
     */
    public function setWxappAppid($wxappAppid)
    {
        $this->wxapp_appid = $wxappAppid;

        return $this;
    }

    /**
     * Get wxappAppid
     *
     * @return string
     */
    public function getWxappAppid()
    {
        return $this->wxapp_appid;
    }

    /**
     * Set openId
     *
     * @param string $openId
     *
     * @return RegistrationRecord
     */
    public function setOpenId($openId)
    {
        $this->open_id = $openId;

        return $this;
    }

    /**
     * Get openId
     *
     * @return string
     */
    public function getOpenId()
    {
        return $this->open_id;
    }

    /**
     * Set status
     *
     * @param string $status
     *
     * @return RegistrationRecord
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set content
     *
     * @param string $content
     *
     * @return RegistrationRecord
     */
    public function setContent($content)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Get content
     *
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Set reason
     *
     * @param string $reason
     *
     * @return RegistrationRecord
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
     * Set created
     *
     * @param integer $created
     *
     * @return RegistrationRecord
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
     * Set updated
     *
     * @param integer $updated
     *
     * @return RegistrationRecord
     */
    public function setUpdated($updated)
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * Get updated
     *
     * @return integer
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return RegistrationRecord
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
}
