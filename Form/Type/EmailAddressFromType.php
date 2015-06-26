<?php

namespace Oro\Bundle\EmailBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\EmailBundle\Provider\RelatedEmailsProvider;

class EmailAddressFromType extends AbstractType
{
    const NAME = 'oro_email_email_address_from';

    /** @var SecurityFacade */
    protected $securityFacade;

    /** @var RelatedEmailsProvider */
    protected $relatedEmailsProvider;

    /**
     * @param SecurityFacade $securityFacade
     * @param RelatedEmailsProvider $relatedEmailsProvider
     */
    public function __construct(SecurityFacade $securityFacade, RelatedEmailsProvider $relatedEmailsProvider)
    {
        $this->securityFacade = $securityFacade;
        $this->relatedEmailsProvider = $relatedEmailsProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'choices' => $this->createChoices(),
        ]);
    }

    /**
     * @return array
     */
    protected function createChoices()
    {
        $user = $this->securityFacade->getLoggedUser();
        if (!$user instanceof User) {
            return [];
        }

        return array_flip($this->relatedEmailsProvider->getEmails($user));
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'genemu_jqueryselect2_choice';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return static::NAME;
    }
}
