<?php

namespace Oro\Bundle\EmailBundle\Entity\Manager;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\EmailBundle\Entity\Email;

class EmailManager
{
    /**
     * @var EmailThreadManager
     */
    protected $emailThreadManager;

    /** @var EntityManager */
    protected $em;

    /**
     * @param EntityManager $em
     * @param EmailThreadManager $emailThreadManager
     */
    public function __construct(EntityManager $em, EmailThreadManager $emailThreadManager)
    {
        $this->em = $em;
        $this->emailThreadManager = $emailThreadManager;
    }

    /**
     * Set email as seen
     *
     * @param Email $entity
     */
    public function setEmailSeen(Email $entity)
    {
        if (!$entity->isSeen()) {
            $entity->setSeen(true);
            $this->em->persist($entity);
            $this->em->flush();
        }
    }
}
