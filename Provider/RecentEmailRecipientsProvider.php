<?php

namespace Oro\Bundle\EmailBundle\Provider;

use Doctrine\Bundle\DoctrineBundle\Registry;

use Oro\Bundle\EmailBundle\Entity\Repository\EmailRecipientRepository;
use Oro\Bundle\EmailBundle\Model\EmailRecipientsProviderArgs;
use Oro\Bundle\EmailBundle\Model\Recipient;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\SecurityBundle\SecurityFacade;

class RecentEmailRecipientsProvider implements EmailRecipientsProviderInterface
{
    /** @var SecurityFacade */
    protected $securityFacade;

    /** @var RelatedEmailsProvider */
    protected $relatedEmailsProvider;

    /** @var AclHelper */
    protected $aclHelper;

    /** @var Registry */
    protected $registry;

    /**
     * @param SecurityFacade $securityFacade
     * @param RelatedEmailsProvider $relatedEmailsProvider
     * @param AclHelper $aclHelper
     * @param Registry $registry
     */
    public function __construct(
        SecurityFacade $securityFacade,
        RelatedEmailsProvider $relatedEmailsProvider,
        AclHelper $aclHelper,
        Registry $registry
    ) {
        $this->securityFacade = $securityFacade;
        $this->relatedEmailsProvider = $relatedEmailsProvider;
        $this->aclHelper = $aclHelper;
        $this->registry = $registry;
    }

    /**
     * {@inheritdoc}
     */
    public function getRecipients(EmailRecipientsProviderArgs $args)
    {
        if (null === $user = $this->securityFacade->getLoggedUser()) {
            return [];
        }

        $userEmailAddresses = array_keys($this->relatedEmailsProvider->getEmails($user));

        $recipientsQb = $this->getEmailRecipientRepository()
            ->getEmailsUsedInLast30DaysQb(
                $userEmailAddresses,
                $args->getExcludedEmails(),
                $args->getQuery()
            )
            ->setMaxResults($args->getLimit());

        $emails = $this->emailsFromResult($this->aclHelper->apply($recipientsQb)->getResult());

        $result = [];
        foreach ($emails as $email => $name) {
            $result[] = new Recipient($email, $name);
        }

        return $result;
    }

    /**
     * @param array $result
     */
    protected function emailsFromResult(array $result)
    {
        $emails = [];
        foreach ($result as $row) {
            $emails[$row['email']] = $row['name'];
        }

        return $emails;
    }

    /**
     * {@inheritdoc}
     */
    public function getSection()
    {
        return 'oro.email.autocomplete.recently_used';
    }

    /**
     * @return EmailRecipientRepository
     */
    protected function getEmailRecipientRepository()
    {
        return $this->registry->getRepository('OroEmailBundle:EmailRecipient');
    }
}
