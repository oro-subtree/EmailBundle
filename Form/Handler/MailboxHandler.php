<?php

namespace Oro\Bundle\EmailBundle\Form\Handler;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;

use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\EmailBundle\Entity\Mailbox;
use Oro\Bundle\EmailBundle\Form\Type\MailboxType;
use Oro\Bundle\EmailBundle\Mailbox\MailboxProcessStorage;
use Oro\Bundle\SoapBundle\Controller\Api\FormAwareInterface;
use Oro\Bundle\TagBundle\Entity\Taggable;

class MailboxHandler implements FormAwareInterface
{
    const FORM = 'oro_email_mailbox';

    /** @var Registry */
    protected $doctrine;
    /** @var FormInterface */
    protected $form;
    /** @var MailboxProcessStorage */
    protected $mailboxProcessStorage;
    /** @var Request */
    protected $request;
    /** @var FormFactoryInterface */
    private $formFactory;

    /**
     * @param FormFactoryInterface  $formFactory
     * @param Request               $request
     * @param Registry              $doctrine
     * @param MailboxProcessStorage $mailboxProcessStorage
     */
    public function __construct(
        FormFactoryInterface $formFactory,
        Request $request,
        Registry $doctrine,
        MailboxProcessStorage $mailboxProcessStorage
    ) {
        $this->doctrine              = $doctrine;
        $this->formFactory           = $formFactory;
        $this->form                  = $this->formFactory->create(self::FORM);
        $this->request               = $request;
        $this->mailboxProcessStorage = $mailboxProcessStorage;
    }

    /**
     * Process form.
     *
     * @param Mailbox $mailbox
     *
     * @return bool True on success.
     */
    public function process(Mailbox $mailbox)
    {
        $this->form->setData($mailbox);

        if (in_array($this->request->getMethod(), ['POST', 'PUT'])) {
            if ($this->request->get(MailboxType::RELOAD_MARKER, false)) {
                $this->processReload();
            } else {
                $this->form->submit($this->request);
                if ($this->form->isValid()) {
                    $this->onSuccess();

                    return true;
                }
            }
        }

        return false;
    }

    protected function onSuccess()
    {
        /** @var Mailbox $mailbox */
        $mailbox = $this->form->getData();

        $this->getEntityManager()->persist($mailbox);
        $this->getEntityManager()->flush();
    }

    protected function processReload()
    {
        $this->form->handleRequest($this->request);

        $type = $this->form->get('processType')->getViewData();
        /** @var Mailbox $data */
        $data = $this->form->getData();

        if (!empty($type)) {
            $processorEntity = $this->mailboxProcessStorage->getNewSettingsEntity($type);
            $data->setProcessSettings($processorEntity);
        } else {
            $data->setProcessSettings(null);
        }

        $this->form = $this->formFactory->create(self::FORM, $data);
    }

    public function getForm()
    {
        return $this->form;
    }

    /**
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        return $this->doctrine->getManager();
    }
}