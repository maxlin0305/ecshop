<?php

namespace CompanysBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity
 * @ORM\Table(name="push_logs")
 * @ORM\Entity(repositoryClass="CompanysBundle\Repositories\PushLogsRepository")
 */
class PushLogs
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
     * @var string
     *
     * @ORM\Column(name="request_url", type="string",  length=50,  options={"comment":"请求路径"})
     */
    private $request_url;

    /**
     * @var string
     *
     * @ORM\Column(name="request_params", type="text",  nullable=true, options={"comment":"请求参数"})
     */
    private $request_params;

    /**
     * @var string
     *
     * @ORM\Column(name="response_data", type="text",  nullable=true, options={"comment":"响应参数"})
     */
    private $response_data;

    /**
     * @var integer
     * @ORM\Column(name="http_status_code", type="integer", options={"comment":"http状态码"})
     */
    private $http_status_code;

    /**
     * @var integer
     * @ORM\Column(name="status", type="integer", options={"comment":"状态 0成功 1失败"})
     */
    private $status;


    /**
     * @var string
     *
     * @ORM\Column(name="push_time", type="string", length=50, options={"comment":"推送时间"})
     */
    private $push_time;

    /**
     * @var integer
     *
     * @ORM\Column(name="cost_time", type="integer", options={"comment":"耗时(毫秒)", "default": 0})
     */
    private $cost_time;

    /**
     * @var integer
     *
     * @ORM\Column(name="retry_times", type="integer", options={"comment":"重试次数", "default": 0})
     */
    private $retry_times;


    /**
     * @var string
     *
     * @ORM\Column(name="method", type="string", length=20, options={"comment":"请求方法"})
     */
    private $method;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=20, options={"comment":"请求类型"})
     */
    private $type;

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
     * Set requestParams.
     *
     * @param string|null $requestParams
     *
     * @return PushLogs
     */
    public function setRequestParams($requestParams = null)
    {
        $this->request_params = $requestParams;

        return $this;
    }

    /**
     * Get requestParams.
     *
     * @return string|null
     */
    public function getRequestParams()
    {
        return $this->request_params;
    }

    /**
     * Set responseData.
     *
     * @param string|null $responseData
     *
     * @return PushLogs
     */
    public function setResponseData($responseData = null)
    {
        $this->response_data = $responseData;

        return $this;
    }

    /**
     * Get responseData.
     *
     * @return string|null
     */
    public function getResponseData()
    {
        return $this->response_data;
    }

    /**
     * Set httpStatusCode.
     *
     * @param int $httpStatusCode
     *
     * @return PushLogs
     */
    public function setHttpStatusCode($httpStatusCode)
    {
        $this->http_status_code = $httpStatusCode;

        return $this;
    }

    /**
     * Get httpStatusCode.
     *
     * @return int
     */
    public function getHttpStatusCode()
    {
        return $this->http_status_code;
    }

    /**
     * Set status.
     *
     * @param int $status
     *
     * @return PushLogs
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status.
     *
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set pushTime.
     *
     * @param string $pushTime
     *
     * @return PushLogs
     */
    public function setPushTime($pushTime)
    {
        $this->push_time = $pushTime;

        return $this;
    }

    /**
     * Get pushTime.
     *
     * @return string
     */
    public function getPushTime()
    {
        return $this->push_time;
    }

    /**
     * Set costTime.
     *
     * @param int $costTime
     *
     * @return PushLogs
     */
    public function setCostTime($costTime)
    {
        $this->cost_time = $costTime;

        return $this;
    }

    /**
     * Get costTime.
     *
     * @return int
     */
    public function getCostTime()
    {
        return $this->cost_time;
    }

    /**
     * Set retryTimes.
     *
     * @param int $retryTimes
     *
     * @return PushLogs
     */
    public function setRetryTimes($retryTimes)
    {
        $this->retry_times = $retryTimes;

        return $this;
    }

    /**
     * Get retryTimes.
     *
     * @return int
     */
    public function getRetryTimes()
    {
        return $this->retry_times;
    }

    /**
     * Set method.
     *
     * @param string $method
     *
     * @return PushLogs
     */
    public function setMethod($method)
    {
        $this->method = $method;

        return $this;
    }

    /**
     * Get method.
     *
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * Set type.
     *
     * @param string $type
     *
     * @return PushLogs
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type.
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set created.
     *
     * @param int $created
     *
     * @return PushLogs
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
     * @return PushLogs
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
     * Set requestUrl.
     *
     * @param string $requestUrl
     *
     * @return PushLogs
     */
    public function setRequestUrl($requestUrl)
    {
        $this->request_url = $requestUrl;

        return $this;
    }

    /**
     * Get requestUrl.
     *
     * @return string
     */
    public function getRequestUrl()
    {
        return $this->request_url;
    }
}
