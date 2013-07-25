<?php

namespace Oro\Bundle\EmailBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate;

/**
 * EmailTemplateRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class EmailTemplateRepository extends EntityRepository
{
    /**
     * @param $entityName
     * @return EmailTemplate[]
     */
    public function getTemplateByEntityName($entityName)
    {
        return $this->findByEntityName($entityName);
    }

    /**
     * @param $entityName
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getEntityTemplatesQueryBuilder($entityName)
    {
        return $this->createQueryBuilder('e')
            ->where('e.entityName = :entityName')
            ->orderBy('e.name', 'ASC')
            ->setParameter('entityName', $entityName);
    }
}
