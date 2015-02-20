<?php

namespace Oro\Bundle\EmailBundle\Form\Handler;

use Doctrine\ORM\EntityManager;

use Psr\Log\LoggerInterface;

use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Util\ClassUtils;
use Symfony\Component\Translation\Translator;

use Oro\Bundle\EmailBundle\Form\Model\Email;
use Oro\Bundle\EmailBundle\Tools\EmailAddressHelper;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailAddressManager;
use Oro\Bundle\EmailBundle\Entity\Email as EmailEntity;
use Oro\Bundle\EmailBundle\Mailer\Processor;
use Oro\Bundle\EmailBundle\Entity\EmailOwnerInterface;
use Oro\Bundle\EmailBundle\Entity\EmailRecipient;
use Oro\Bundle\EmailBundle\Model\EmailHolderInterface;
use Oro\Bundle\EntityBundle\Tools\EntityRoutingHelper;
use Oro\Bundle\LocaleBundle\Formatter\NameFormatter;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * Class EmailHandler
 *
 * @package Oro\Bundle\EmailBundle\Form\Handler
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class EmailHandler
{
    /**
     * @var FormInterface
     */
    protected $form;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var Translator
     */
    protected $translator;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var SecurityContextInterface
     */
    protected $securityContext;

    /**
     * @var Processor
     */
    protected $emailProcessor;

    /**
     * @var EmailAddressManager
     */
    protected $emailAddressManager;

    /**
     * @var EmailAddressHelper
     */
    protected $emailAddressHelper;

    /**
     * @var NameFormatter
     */
    protected $nameFormatter;

    /**
     * @var EntityRoutingHelper
     */
    protected $entityRoutingHelper;

    /**
     * @param FormInterface            $form
     * @param Request                  $request
     * @param EntityManager            $em
     * @param Translator               $translator
     * @param SecurityContextInterface $securityContext
     * @param EmailAddressManager      $emailAddressManager
     * @param EmailAddressHelper       $emailAddressHelper
     * @param LoggerInterface          $logger
     * @param Processor                $emailProcessor
     * @param NameFormatter            $nameFormatter
     * @param EntityRoutingHelper      $entityRoutingHelper
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        FormInterface $form,
        Request $request,
        EntityManager $em,
        Translator $translator,
        SecurityContextInterface $securityContext,
        EmailAddressManager $emailAddressManager,
        EmailAddressHelper $emailAddressHelper,
        Processor $emailProcessor,
        LoggerInterface $logger,
        NameFormatter $nameFormatter,
        EntityRoutingHelper $entityRoutingHelper
    ) {
        $this->form                = $form;
        $this->request             = $request;
        $this->em                  = $em;
        $this->translator          = $translator;
        $this->securityContext     = $securityContext;
        $this->emailAddressManager = $emailAddressManager;
        $this->emailAddressHelper  = $emailAddressHelper;
        $this->emailProcessor      = $emailProcessor;
        $this->logger              = $logger;
        $this->nameFormatter       = $nameFormatter;
        $this->entityRoutingHelper = $entityRoutingHelper;
    }

    /**
     * Process form
     *
     * @param  Email $model
     * @return bool True on successful processing, false otherwise
     */
    public function process(Email $model)
    {
        if ($this->request->getMethod() === 'GET') {
            $this->initModel($model);
        }
        $this->form->setData($model);

        if (in_array($this->request->getMethod(), ['POST', 'PUT'])) {
            $this->form->submit($this->request);

            if ($this->form->isValid()) {
                try {
                    $this->emailProcessor->process($model);
                    return true;
                } catch (\Exception $ex) {
                    $this->logger->error('Email sending failed.', ['exception' => $ex]);
                    $this->form->addError(new FormError($ex->getMessage()));
                }
            }
        }

        return false;
    }

    /**
     * Populate a model with initial data.
     * This method is used to load an initial data from a query string
     *
     * @param Email $model
     */
    protected function initModel(Email $model)
    {
        if ($this->request->query->has('gridName')) {
            $model->setGridName($this->request->query->get('gridName'));
        }
        $this->initEntityData($model);
        $this->initFrom($model);
        $this->initRecipients($model);
        $this->initSubject($model);
    }

    /**
     * @param Email $model
     */
    protected function initRecipients(Email $model)
    {
        $model->setTo(array_merge($model->getTo(), $this->getRecipients($model, EmailRecipient::TO)));
        $model->setCc(array_merge($model->getCc(), $this->getRecipients($model, EmailRecipient::CC)));
        $model->setBcc(array_merge($model->getBcc(), $this->getRecipients($model, EmailRecipient::BCC)));
    }

    /**
     * @param Email $model
     * @param string $type
     *
     * @return array
     */
    protected function getRecipients(Email $model, $type)
    {
        $addresses = [];
        if ($this->request->query->has($type)) {
            $address = trim($this->request->query->get($type));
            if (!empty($address)) {
                $this->preciseFullEmailAddress($address, $model->getEntityClass(), $model->getEntityId());
            }
            $addresses = [$address];
        }
        return $addresses;
    }

    /**
     * @param Email $model
     */
    protected function initSubject(Email $model)
    {
        if ($this->request->query->has('subject')) {
            $subject = trim($this->request->query->get('subject'));
            $model->setSubject($subject);
        }
    }

    /**
     * @param Email $model
     */
    protected function initFrom(Email $model)
    {
        if ($this->request->query->has('from')) {
            $from = $this->request->query->get('from');
            if (!empty($from)) {
                $this->preciseFullEmailAddress($from);
            }
            $model->setFrom($from);
        } else {
            $user = $this->getUser();
            if ($user) {
                $model->setFrom(
                    $this->emailAddressHelper->buildFullEmailAddress(
                        $user->getEmail(),
                        $this->nameFormatter->format($user)
                    )
                );
            }
        }
    }

    /**
     * Init entityClass and entityId fields
     *
     * @param Email $model
     */
    protected function initEntityData(Email $model)
    {
        if ($this->request->query->has('entityClass')) {
            $model->setEntityClass(
                $this->entityRoutingHelper->decodeClassName($this->request->query->get('entityClass'))
            );
        }
        if ($this->request->query->has('entityId')) {
            $model->setEntityId($this->request->query->get('entityId'));
        }
        if (!$model->getEntityClass() || !$model->getEntityId()) {
            if ($model->getParentEmailId()) {
                $parentEmail = $this->em->getRepository('OroEmailBundle:Email')->find($model->getParentEmailId());
                $this->initEntityDataFromEmail($model, $parentEmail);
            }
        }
    }

    /**
     * Init entityClass and entityId fields using email entity
     *
     * @param Email       $model
     * @param EmailEntity $email
     */
    protected function initEntityDataFromEmail(Email $model, EmailEntity $email)
    {
        $entities = $email->getActivityTargetEntities();
        foreach ($entities as $entity) {
            if ($entity->getId() != $this->securityContext->getToken()->getUser()->getId()
                && !($entity instanceof User)
            ) {
                $model->setEntityClass(ClassUtils::getRealClass($entity));
                $model->setEntityId($entity->getId());

                return;
            }
        }
    }

    /**
     * @param string      $emailAddress
     * @param string|null $ownerClass
     * @param mixed|null  $ownerId
     */
    protected function preciseFullEmailAddress(&$emailAddress, $ownerClass = null, $ownerId = null)
    {
        if (!$this->emailAddressHelper->isFullEmailAddress($emailAddress)) {
            if (!empty($ownerClass) && !empty($ownerId)) {
                $owner = $this->entityRoutingHelper->getEntity($ownerClass, $ownerId);
                if ($owner && $this->isFullQualifiedUser($owner)) {
                    $ownerName = $this->nameFormatter->format($owner);
                    if (!empty($ownerName)) {
                        $emailAddress = $this->emailAddressHelper->buildFullEmailAddress($emailAddress, $ownerName);

                        return;
                    }
                }
            }
            $repo            = $this->emailAddressManager->getEmailAddressRepository($this->em);
            $emailAddressObj = $repo->findOneBy(array('email' => $emailAddress));
            if ($emailAddressObj) {
                $owner = $emailAddressObj->getOwner();
                if ($owner) {
                    $ownerName = $this->nameFormatter->format($owner);
                    if (!empty($ownerName)) {
                        $emailAddress = $this->emailAddressHelper->buildFullEmailAddress($emailAddress, $ownerName);
                    }
                }
            }
        }
    }

    /**
     * Get the current authenticated user
     *
     * @return UserInterface|EmailHolderInterface|EmailOwnerInterface|null
     */
    protected function getUser()
    {
        $token = $this->securityContext->getToken();
        if ($token) {
            $user = $token->getUser();
            if (
                $this->isFullQualifiedUser($user)
            ) {
                return $user;
            }
        }

        return null;
    }

    /**
     * @param $entity
     * @return bool
     */
    protected function isFullQualifiedUser($entity)
    {
        return $entity instanceof UserInterface
        && $entity instanceof EmailHolderInterface
        && $entity instanceof EmailOwnerInterface;
    }
}
