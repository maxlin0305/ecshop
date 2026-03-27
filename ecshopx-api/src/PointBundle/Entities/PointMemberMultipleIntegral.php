<?php

namespace PointBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * PointMemberLog 用户积分多倍
 *
 * @ORM\Table(name="point_member_multiple_integral", options={"comment"="用户积分记录表"})
 * @ORM\Entity(repositoryClass="PointBundle\Repositories\PointMemberMultipleIntegralRepository")
 */
class PointMemberMultipleIntegral
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="bigint", options={"comment":"积分记录id"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="user_id", type="bigint", options={"comment":"用户id"})
     */
    private $user_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="point_member_log_id", type="bigint", options={"comment":"积分日志id"})
     */
    private $point_member_log_id;


    /**
     * @var int
     *
     * @ORM\Column(name="income", type="integer", options={"default":0, "unsigned":true, "comment":"入账积分"})
     */
    private $income = 0;


    /**
     * @var int
     *
     * @ORM\Column(name="used_points", type="integer", options={"default":0, "unsigned":true, "comment":"已使用积分"})
     */
    private $used_points = 0;


    /**
     * @var int
     *
     * @ORM\Column(name="mi_multiple", type="integer", options={"default":0, "unsigned":true, "comment":"积分倍数"})
     */
    private $mi_multiple;

    /**
     * @var int
     *
     * @ORM\Column(name="mi_expiration_reminder", type="integer", options={"default":0, "unsigned":true, "comment":"是否开启到期提醒[1:开启/2：关闭]"})
     */
    private $mi_expiration_reminder;


    /**
     * @var string
     *
     * @ORM\Column(name="mi_reminder_copy", type="string", length=255,  options={"comment":"提醒文案"})
     */
    private $mi_reminder_copy;


    /**
     * @var int
     *
     * @ORM\Column(name="expiration_time", type="integer", options={"default":0, "unsigned":true, "comment":"清零时间(天,0不清零)"})
     */
    private $expiration_time;


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
     * @return PointMemberMultipleIntegral
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
     * Set userId
     *
     * @param integer $userId
     *
     * @return PointMemberMultipleIntegral
     */
    public function setPointMemberLogId($pointMemberLogId)
    {
        $this->point_member_log_id = $pointMemberLogId;

        return $this;
    }

    /**
     * Get userId
     *
     * @return integer
     */
    public function getPointMemberLogId()
    {
        return $this->point_member_log_id;
    }

    /**
     * Set userId
     *
     * @param integer $userId
     *
     * @return PointMemberMultipleIntegral
     */
    public function setIncome($income)
    {
        $this->income = $income;

        return $this;
    }

    /**
     * Get userId
     *
     * @return integer
     */
    public function getIncome()
    {
        return $this->income;
    }


    /**
     * Set userId
     *
     * @param integer $usedPoints
     *
     * @return PointMemberMultipleIntegral
     */
    public function setUsedPoints($usedPoints)
    {
        $this->used_points = $usedPoints;

        return $this;
    }

    /**
     * Get userId
     *
     * @return integer
     */
    public function getUsedPoints()
    {
        return $this->used_points;
    }

    /**
     * Set userId
     *
     * @param integer $usedPoints
     *
     * @return PointMemberMultipleIntegral
     */
    public function setMiMultiple($miMultiple)
    {
        $this->mi_multiple = $miMultiple;

        return $this;
    }

    /**
     * Get userId
     *
     * @return integer
     */
    public function getMiMultiple()
    {
        return $this->mi_multiple;
    }

    /**
     * Set userId
     *
     * @param integer $miExpirationReminder
     *
     * @return PointMemberMultipleIntegral
     */
    public function setgMiExpirationReminder($miExpirationReminder)
    {
        $this->mi_expiration_reminder = $miExpirationReminder;

        return $this;
    }

    /**
     * Get userId
     *
     * @return integer
     */
    public function getMiExpirationReminder()
    {
        return $this->mi_expiration_reminder;
    }

    /**
     * Set userId
     *
     * @param integer $miReminderCopy
     *
     * @return PointMemberMultipleIntegral
     */
    public function setMiReminderCopy($miReminderCopy)
    {
        $this->mi_reminder_copy = $miReminderCopy;

        return $this;
    }

    /**
     * Get userId
     *
     * @return integer
     */
    public function getMiReminderCopy()
    {
        return $this->mi_reminder_copy;
    }

    /**
     * Set userId
     *
     * @param integer $miZeroingTime
     *
     * @return PointMemberMultipleIntegral
     */
    public function setExpirationTime($miZeroingTime)
    {
        $this->expiration_time = $miZeroingTime;

        return $this;
    }

    /**
     * Get userId
     *
     * @return integer
     */
    public function getExpirationTime()
    {
        return $this->expiration_time;
    }

}
