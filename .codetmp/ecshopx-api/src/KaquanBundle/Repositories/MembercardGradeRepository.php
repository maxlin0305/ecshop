<?php

namespace KaquanBundle\Repositories;

use Doctrine\ORM\EntityRepository;
use KaquanBundle\Entities\MemberCardGrade;

class MembercardGradeRepository extends EntityRepository
{
    public $table = 'membercard_grade';

    public function setDefaultGrade($gradeInfo)
    {
        $gradeEntity = new MemberCardGrade();
        $em = $this->getEntityManager();
        $grade = $this->setGradeData($gradeEntity, $gradeInfo);
        $em->persist($grade);
        $em->flush();

        $result = [
            'company_id' => $grade->getCompanyId(),
            'grade_id' => $grade->getGradeId(),
            'grade_name' => $grade->getGradeName(),
            'default_grade' => $grade->getDefaultGrade(),
        ];
        return $result;
    }

    public function update($companyId, $newGrades, $deleteIds, array &$newGradesList = [])
    {
        $em = $this->getEntityManager();
        $em->getConnection()->beginTransaction();
        try {
            if ($deleteIds) {
                foreach ($deleteIds as $gradeId) {
                    $delgrade = $this->findOneBy(['company_id' => $companyId, 'grade_id' => $gradeId]);
                    $em->remove($delgrade);
                    $em->flush();
                }
            }
            if ($newGrades) {
                foreach ($newGrades as $gradeInfo) {
                    $filter = [
                        'grade_id' => $gradeInfo['grade_id'],
                        'company_id' => $gradeInfo['company_id']
                    ];
                    $grade = $this->findOneBy($filter);
                    if (!$grade) {
                        $grade = new MemberCardGrade();
                    }
                    $grade = $this->setGradeData($grade, $gradeInfo);
                    $em->persist($grade);
                    $em->flush();
                    $tempItem = $this->getGradeData($grade);
                    $tempItem['voucher_package'] = empty($gradeInfo['voucher_package']) ? [] : $gradeInfo['voucher_package'];
                    $newGradesList[] = $tempItem;
                }
            }
            $em->getConnection()->commit();
        } catch (\Exception $e) {
            $em->getConnection()->rollback();
            throw $e;
        }

        return true;
    }

    public function setGradeData($grade, $postdata)
    {
        if (isset($postdata['company_id'])) {
            $grade->setCompanyId($postdata['company_id']);
        }
        if (isset($postdata['grade_name'])) {
            $grade->setGradeName($postdata['grade_name']);
        }
        if (isset($postdata['default_grade'])) {
            $grade->setDefaultGrade($postdata['default_grade']);
        }
        if (isset($postdata['background_pic_url'])) {
            $grade->setBackgroundPicUrl($postdata['background_pic_url']);
        }
        if (isset($postdata['promotion_condition'])) {
            $grade->setPromotionCondition($postdata['promotion_condition']);
        }
        if (isset($postdata['privileges'])) {
            $grade->setPrivileges($postdata['privileges']);
        }
        if (isset($postdata['third_data'])) {
            $grade->setThirdData($postdata['third_data']);
        }
        if (isset($postdata["external_id"])) {
            $grade->setExternalId($postdata["external_id"]);
        } else {
            $grade->setExternalId((string)$grade->getExternalId());
        }
        if (isset($postdata["description"])) {
            $grade->setDescription($postdata["description"]);
        }
        return $grade;
    }

    public function getGradeData(MemberCardGrade $memberCardGrade)
    {
        return [
            "company_id" => $memberCardGrade->getCompanyId(),
            "grade_id" => $memberCardGrade->getGradeId(),
            "grade_name" => $memberCardGrade->getGradeName(),
            "default_grade" => $memberCardGrade->getDefaultGrade(),
            "background_pic_url" => $memberCardGrade->getBackgroundPicUrl(),
            "promotion_condition" => $memberCardGrade->getPromotionCondition(),
            "privileges" => $memberCardGrade->getPrivileges(),
            "created" => $memberCardGrade->getCreated(),
            "updated" => $memberCardGrade->getUpdated(),
            "third_data" => $memberCardGrade->getThirdData(),
            "external_id" => $memberCardGrade->getExternalId(),
            "description" => $memberCardGrade->getDescription(),
        ];
    }

    public function getListByCompanyId($companyId, $fields = '*')
    {
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        $qb->select($fields)
            ->from($this->table)
            ->andWhere($qb->expr()->andX(
                $qb->expr()->eq('company_id', $qb->expr()->literal($companyId))
            ));
        return $qb->execute()->fetchAll();
    }

    public function get($filter)
    {
        return $this->findOneBy($filter);
    }

    public function getList($cols = '*', $filter = array(), $offset = 0, $limit = -1, $OrderBy = null)
    {
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        $qb = $qb->select($cols)
            ->from($this->table);

        if ($limit > 0) {
            $qb = $qb->setFirstResult($offset)
                ->setMaxResults($limit);
        }
        if ($filter) {
            foreach ($filter as $key => $filterValue) {
                if (is_array($filterValue)) {
                    array_walk($filterValue, function (&$value) use ($qb) {
                        $value = $qb->expr()->literal($value);
                    });
                } else {
                    $filterValue = $qb->expr()->literal($filterValue);
                }
                $list = explode('|', $key);
                if (count($list) > 1) {
                    list($v, $k) = $list;
                    $qb->andWhere($qb->expr()->andX(
                        $qb->expr()->$k($v, $filterValue)
                    ));
                } else {
                    $qb->andWhere($qb->expr()->andX(
                        $qb->expr()->eq($key, $filterValue)
                    ));
                }
            }
        }
        return $qb->execute()->fetchAll();
    }

    public function count($filter)
    {
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        $qb->select('count(*)')
            ->from($this->table);
        if ($filter) {
            foreach ($filter as $key => $filterValue) {
                if (is_array($filterValue)) {
                    array_walk($filterValue, function (&$value) use ($qb) {
                        $value = $qb->expr()->literal($value);
                    });
                } else {
                    $filterValue = $qb->expr()->literal($filterValue);
                }
                $list = explode('|', $key);
                if (count($list) > 1) {
                    list($v, $k) = $list;
                    $qb->andWhere($qb->expr()->andX(
                        $qb->expr()->$k($v, $filterValue)
                    ));
                } else {
                    $qb->andWhere($qb->expr()->andX(
                        $qb->expr()->eq($key, $filterValue)
                    ));
                }
            }
        }
        return $qb->execute()->fetchColumn();
    }

    public function getInfo($filter)
    {
        $info = $this->findOneBy($filter);
        if (is_null($info)) {
            return [];
        }
        return $this->getGradeData($info);
    }
}
