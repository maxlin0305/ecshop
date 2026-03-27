<?php

namespace SelfserviceBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * RegistrationActivity 报名问卷活动
 *
 * @ORM\Table(name="selfservice_registration_activity", options={"comment"="报名问卷活动"}, indexes={
 *    @ORM\Index(name="idx_temp_id", columns={"temp_id"})
 * }),
  * @ORM\Entity(repositoryClass="SelfserviceBundle\Repositories\RegistrationActivityRepository")
 */
class RegistrationActivity
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="activity_id", type="bigint")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $activity_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="temp_id", type="bigint", options={"comment":"表单模板id"})
     */
    private $temp_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="activity_name", type="string", options={"comment":"活动名称"})
     */
    private $activity_name;

    /**
     * @var string
     *
     * @ORM\Column(name="start_time", type="integer", options={"comment":"活动开始时间"})
     */
    private $start_time;

    /**
     * @var string
     *
     * @ORM\Column(name="end_time", type="integer", options={"comment":"活动结束时间"})
     */
    private $end_time;

    /**
     * @var string
     *
     * @ORM\Column(name="join_limit", type="integer", options={"comment":"可参与次数", "default":0})
     */
    private $join_limit = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="is_sms_notice", type="boolean", options={"comment":"是否短信通知", "default": true})
     */
    private $is_sms_notice = false;

    /**
     * @var string
     *
     * @ORM\Column(name="is_wxapp_notice", type="boolean", options={"comment":"是否小程序模板通知", "default": true})
     */
    private $is_wxapp_notice = false;

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
     * @ORM\Column(name="company_id", type="bigint")
     */
    private $company_id;

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
     * Set tempId
     *
     * @param integer $tempId
     *
     * @return RegistrationActivity
     */
    public function setTempId($tempId)
    {
        $this->temp_id = $tempId;

        return $this;
    }

    /**
     * Get tempId
     *
     * @return integer
     */
    public function getTempId()
    {
        return $this->temp_id;
    }

    /**
     * Set activityName
     *
     * @param integer $activityName
     *
     * @return RegistrationActivity
     */
    public function setActivityName($activityName)
    {
        $this->activity_name = $activityName;

        return $this;
    }

    /**
     * Get activityName
     *
     * @return integer
     */
    public function getActivityName()
    {
        return $this->activity_name;
    }

    /**
     * Set startTime
     *
     * @param integer $startTime
     *
     * @return RegistrationActivity
     */
    public function setStartTime($startTime)
    {
        $this->start_time = $startTime;

        return $this;
    }

    /**
     * Get startTime
     *
     * @return integer
     */
    public function getStartTime()
    {
        return $this->start_time;
    }

    /**
     * Set endTime
     *
     * @param integer $endTime
     *
     * @return RegistrationActivity
     */
    public function setEndTime($endTime)
    {
        $this->end_time = $endTime;

        return $this;
    }

    /**
     * Get endTime
     *
     * @return integer
     */
    public function getEndTime()
    {
        return $this->end_time;
    }

    /**
     * Set joinLimit
     *
     * @param integer $joinLimit
     *
     * @return RegistrationActivity
     */
    public function setJoinLimit($joinLimit)
    {
        $this->join_limit = $joinLimit;

        return $this;
    }

    /**
     * Get joinLimit
     *
     * @return integer
     */
    public function getJoinLimit()
    {
        return $this->join_limit;
    }

    /**
     * Set isWxappNotice
     *
     * @param boolean $isWxappNotice
     *
     * @return RegistrationActivity
     */
    public function setIsWxappNotice($isWxappNotice)
    {
        $this->is_wxapp_notice = $isWxappNotice;

        return $this;
    }

    /**
     * Get isWxappNotice
     *
     * @return boolean
     */
    public function getIsWxappNotice()
    {
        return $this->is_wxapp_notice;
    }

    /**
     * Set created
     *
     * @param integer $created
     *
     * @return RegistrationActivity
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
     * @return RegistrationActivity
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
     * Set isSmsNotice
     *
     * @param boolean $isSmsNotice
     *
     * @return RegistrationActivity
     */
    public function setIsSmsNotice($isSmsNotice)
    {
        $this->is_sms_notice = $isSmsNotice;

        return $this;
    }

    /**
     * Get isSmsNotice
     *
     * @return boolean
     */
    public function getIsSmsNotice()
    {
        return $this->is_sms_notice;
    }

    /**
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return RegistrationActivity
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
