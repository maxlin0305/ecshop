<?php

namespace PromotionsBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * EmployeePurchaseReluser 员工内购家属关联表
 *
 * @ORM\Table(name="promotions_employee_purchase_reluser", options={"comment"="员工内购家属关联表"}, indexes={
 *    @ORM\Index(name="idx_company_id", columns={"company_id"}),
 *    @ORM\Index(name="idx_purchase_id", columns={"purchase_id"}),
 * })
 * @ORM\Entity(repositoryClass="PromotionsBundle\Repositories\EmployeePurchaseReluserRepository")
 */
class EmployeePurchaseReluser
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
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司ID"})
     */
    private $company_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="purchase_id", type="bigint", options={"comment":"员工内购活动ID"})
     */
    private $purchase_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="employee_user_id", type="bigint", options={"comment":"员工会员ID"})
     */
    private $employee_user_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="dependents_user_id", type="bigint", options={"comment":"家属会员ID"})
     */
    private $dependents_user_id;

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
     * @return EmployeePurchaseReluser
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
     * Set purchaseId.
     *
     * @param int $purchaseId
     *
     * @return EmployeePurchaseReluser
     */
    public function setPurchaseId($purchaseId)
    {
        $this->purchase_id = $purchaseId;

        return $this;
    }

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
     * Set employeeUserId.
     *
     * @param int $employeeUserId
     *
     * @return EmployeePurchaseReluser
     */
    public function setEmployeeUserId($employeeUserId)
    {
        $this->employee_user_id = $employeeUserId;

        return $this;
    }

    /**
     * Get employeeUserId.
     *
     * @return int
     */
    public function getEmployeeUserId()
    {
        return $this->employee_user_id;
    }

    /**
     * Set dependentsUserId.
     *
     * @param int $dependentsUserId
     *
     * @return EmployeePurchaseReluser
     */
    public function setDependentsUserId($dependentsUserId)
    {
        $this->dependents_user_id = $dependentsUserId;

        return $this;
    }

    /**
     * Get dependentsUserId.
     *
     * @return int
     */
    public function getDependentsUserId()
    {
        return $this->dependents_user_id;
    }

    /**
     * Set created.
     *
     * @param int $created
     *
     * @return EmployeePurchaseReluser
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
     * @return EmployeePurchaseReluser
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
