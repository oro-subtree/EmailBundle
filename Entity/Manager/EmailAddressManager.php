<?php

namespace Oro\Bundle\EmailBundle\Entity\Manager;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Oro\Bundle\EmailBundle\Entity\EmailAddress;

class EmailAddressManager
{
    /** @var string */
    private $entityCacheNamespace;

    /** @var string */
    private $entityProxyNameTemplate;

    /** @var EntityManager|null */
    private $em;

    /**
     * Constructor
     *
     * @param string $entityCacheNamespace
     * @param string $entityProxyNameTemplate
     */
    public function __construct($entityCacheNamespace, $entityProxyNameTemplate)
    {
        $this->entityCacheNamespace = $entityCacheNamespace;
        $this->entityProxyNameTemplate = $entityProxyNameTemplate;
    }

    /**
     * Create EmailAddress entity object. Actually a proxy class is created
     *
     * @return EmailAddress
     */
    public function newEmailAddress()
    {
        $emailAddressClass = $this->getEmailAddressProxyClass();

        return new $emailAddressClass();
    }

    /**
     * Get a repository for EmailAddress entity
     *
     * @param EntityManager|null $em Manager have to be provided via "setEntityManager" method if null
     * @return EntityRepository
     */
    public function getEmailAddressRepository(EntityManager $em = null)
    {
        $manager = $em ?: $this->em;

        return $manager->getRepository($this->getEmailAddressProxyClass());
    }

    /**
     * Get full class name of a proxy of EmailAddress entity
     *
     * @return string
     */
    public function getEmailAddressProxyClass()
    {
        return sprintf('%s\%s', $this->entityCacheNamespace, sprintf($this->entityProxyNameTemplate, 'EmailAddress'));
    }

    /**
     * @param EntityManager $em
     *
     * @return $this
     */
    public function setEntityManager(EntityManager $em)
    {
        $this->em = $em;

        return $this;
    }

    /**
     * @return EntityManager
     */
    public function getEntityManager()
    {
        return $this->em;
    }
}
