<?php

namespace CompanysBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity
 * @ORM\Table(name="push_message")
 * @ORM\Entity(repositoryClass="CompanysBundle\Repositories\PushMessageRepository")
 */
class PushMessage
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    protected $id;

    /**
     * @var integer
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司id"})
     */
    private $company_id;

    /**
     * @var integer
     * @ORM\Column(name="merchant_id", type="bigint", options={"comment":"商户id"})
     */
    private $merchant_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="distributor_id",type="bigint", options={"default":0, "comment":"店铺id"})
     */
    private $distributor_id = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="user_id",type="bigint", options={"default":0, "comment":"用户ID"})
     */
    private $user_id = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="msg_name", type="string",  length=255,  options={"comment":"消息名称"})
     */
    private $msg_name;

    /**
     * @var integer
     *
     * @ORM\Column(name="msg_type", type="bigint", options={"comment":"消息类型:1 到货通知"})
     */
    private $msg_type;

    /**
     * @var string
     *
     * @ORM\Column(name="content", type="text",  nullable=true, options={"comment":"响应参数"})
     */
    private $content;

    /**
     * @var integer
     *
     * @ORM\Column(name="is_read", type="integer", options={"comment":"是否已读:0,未读;1,已读", "default": 0})
     */
    private $is_read;

    /**
     * @var integer
     *
     * @ORM\Column(name="create_time", type="integer", options={"comment":"创建时间", "default": 0})
     */
    private $create_time;

    /**
     * @var integer
     *
     * @ORM\Column(name="update_time", type="integer", options={"comment":"更新时间", "default": 0})
     */
    private $update_time;


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
     * @return PushLogs
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
     * Set merchant_id.
     *
     * @param int $merchant_id
     *
     * @return PushLogs
     */
    public function setMerchantId($merchant_id)
    {
        $this->merchant_id = $merchant_id;

        return $this;
    }

    /**
     * Set distributorId.
     *
     * @param int $distributorId
     *
     * @return Roles
     */
    public function setDistributorId($distributorId)
    {
        $this->distributor_id = $distributorId;

        return $this;
    }

    /**
     * Get distributorId.
     *
     * @return int
     */
    public function getDistributorId()
    {
        return $this->distributor_id;
    }


    /**
     * Set user_id.
     *
     * @param int $user_id
     *
     * @return Roles
     */
    public function setUserId($user_id=0)
    {
        $this->user_id = $user_id;

        return $this;
    }

    /**
     * Get user_id.
     *
     * @return int
     */
    public function getUserId()
    {
        return $this->user_id;
    }


    /**
     * Get merchant_id.
     *
     * @return int
     */
    public function getMerchantId()
    {
        return $this->merchant_id;
    }

    /**
     * Set msg_name.
     *
     * @param string|null $msg_name
     *
     * @return PushLogs
     */
    public function setMsgName($msg_name = null)
    {
        $this->msg_name = $msg_name;

        return $this;
    }

    /**
     * Get msg_name.
     *
     * @return string|null
     */
    public function getMsgName()
    {
        return $this->msg_name;
    }

    /**
     * Set msg_type.
     *
     * @param int $msg_type
     *
     * @return PushLogs
     */
    public function setMsgType($msg_type)
    {
        $this->msg_type = $msg_type;

        return $this;
    }

    /**
     * Get msg_type.
     *
     * @return int
     */
    public function getMsgType()
    {
        return $this->msg_type;
    }


    /**
     * Set content.
     *
     * @param string|null $content
     *
     * @return PushLogs
     */
    public function setContent($content = null)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Get content.
     *
     * @return string|null
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Set is_read.
     *
     * @param int $is_read
     *
     * @return PushLogs
     */
    public function setIsRead($is_read=0)
    {
        $this->is_read = $is_read;

        return $this;
    }

    /**
     * Get is_read.
     *
     * @return int
     */
    public function getIsRead()
    {
        return $this->is_read;
    }

    /**
     * Set create_time.
     *
     * @param int $create_time
     *
     * @return PushLogs
     */
    public function setCreateTime($create_time)
    {
        $this->create_time = $create_time;

        return $this;
    }

    /**
     * Get create_time.
     *
     * @return int
     */
    public function getCreateTime()
    {
        return $this->create_time;
    }

    /**
     * Set update_time.
     *
     * @param int|null $update_time
     *
     * @return PushLogs
     */
    public function setUpdateTime($update_time = null)
    {
        $this->update_time = $update_time;

        return $this;
    }

    /**
     * Get update_time.
     *
     * @return int|null
     */
    public function getUpdateTime()
    {
        return $this->update_time;
    }
}
