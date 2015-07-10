<?php

namespace Oro\Bundle\EmailBundle\EventListener;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;

use Oro\Bundle\EmailBundle\Entity\Manager\EmailActivityManager;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailThreadManager;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailAddressOwnerManager;

class EntityListener
{
    /** @var EmailAddressOwnerManager */
    protected $emailOwnerManager;

    /** @var EmailActivityManager */
    protected $emailActivityManager;

    /** @var EmailThreadManager */
    protected $emailThreadManager;

    /**
     * @param EmailAddressOwnerManager    $emailOwnerManager
     * @param EmailActivityManager $emailActivityManager
     * @param EmailThreadManager   $emailThreadManager
     */
    public function __construct(
        EmailAddressOwnerManager    $emailOwnerManager,
        EmailActivityManager $emailActivityManager,
        EmailThreadManager   $emailThreadManager
    ) {
        $this->emailOwnerManager    = $emailOwnerManager;
        $this->emailActivityManager = $emailActivityManager;
        $this->emailThreadManager   = $emailThreadManager;
    }

    /**
     * @param OnFlushEventArgs $event
     */
    public function onFlush(OnFlushEventArgs $event)
    {
        $this->emailOwnerManager->handleOnFlush($event);
        $this->emailThreadManager->handleOnFlush($event);
        $this->emailActivityManager->handleOnFlush($event);
    }

    /**
     * @param PostFlushEventArgs $event
     */
    public function postFlush(PostFlushEventArgs $event)
    {
        $this->emailThreadManager->handlePostFlush($event);
        $this->emailActivityManager->handlePostFlush($event);
    }
}
