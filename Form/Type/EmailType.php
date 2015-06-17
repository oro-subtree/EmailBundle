<?php

namespace Oro\Bundle\EmailBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Security\Core\SecurityContextInterface;

use Oro\Bundle\FormBundle\Utils\FormUtils;
use Oro\Bundle\EmailBundle\Entity\Email as EmailEntity;
use Oro\Bundle\EmailBundle\Entity\Repository\EmailTemplateRepository;
use Oro\Bundle\EmailBundle\Form\Model\Email;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;

class EmailType extends AbstractType
{
    /**
     * @var SecurityContextInterface
     */
    protected $securityContext;

    /**
     * @param SecurityContextInterface $securityContext
     */
    public function __construct(SecurityContextInterface $securityContext)
    {
        $this->securityContext = $securityContext;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('gridName', 'hidden', ['required' => false])
            ->add('entityClass', 'hidden', ['required' => false])
            ->add('entityId', 'hidden', ['required' => false])
            ->add(
                'from',
                'oro_email_email_address_from',
                [
                    'required' => true,
                    'label' => 'oro.email.from_email_address.label',
                    'attr' => ['class' => 'from taggable-field']
                ]
            )
            ->add(
                'to',
                'oro_email_email_address',
                [
                    'required' => false,
                    'multiple' => true,
                    'attr' => ['class' => 'taggable-field forged-required']
                ]
            )
            ->add(
                'cc',
                'oro_email_email_address',
                ['required' => false, 'multiple' => true, 'attr' => ['class' => 'taggable-field']]
            )
            ->add(
                'bcc',
                'oro_email_email_address',
                ['required' => false, 'multiple' => true, 'attr' => ['class' => 'taggable-field']]
            )
            ->add('subject', 'text', ['required' => true, 'label' => 'oro.email.subject.label'])
            ->add('body', 'oro_rich_text', ['required' => false, 'label' => 'oro.email.email_body.label'])
            ->add(
                'template',
                'oro_email_template_list',
                [
                    'label' => 'oro.email.template.label',
                    'required' => false,
                    'depends_on_parent_field' => 'entityClass',
                    'configs' => [
                        'allowClear' => true
                    ]
                ]
            )
            ->add(
                'type',
                'choice',
                [
                    'label'      => 'oro.email.type.label',
                    'required'   => true,
                    'data'       => 'html',
                    'choices'  => [
                        'html' => 'oro.email.datagrid.emailtemplate.filter.type.html',
                        'txt'  => 'oro.email.datagrid.emailtemplate.filter.type.txt'
                    ],
                    'expanded'   => true
                ]
            )
            ->add('attachments', 'oro_email_attachments', [
                'type' => 'oro_email_attachment',
                'required' => false,
                'allow_add' => true,
                'prototype' => false,
                'options' => [
                    'required' => false,
                ],
            ])
            ->add('bodyFooter', 'hidden')
            ->add('parentEmailId', 'hidden')
            ->add('signature', 'hidden')
            ->add(
                'contexts',
                'oro_email_contexts_select',
                [
                    'label'    => 'oro.email.contexts.label',
                    'tooltip'  => 'oro.email.contexts.tooltip',
                    'required' => false,
                    'read_only' => true,
                ]
            );

        $builder->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'initChoicesByEntityName']);
        $builder->addEventListener(FormEvents::PRE_SUBMIT, [$this, 'initChoicesByEntityName']);
    }

    /**
     * @param FormEvent $event
     */
    public function initChoicesByEntityName(FormEvent $event)
    {
        /** @var Email|array $data */
        $data = $event->getData();
        if (null === $data ||
            is_array($data) && empty($data['entityClass']) ||
            is_object($data) && null === $data->getEntityClass()) {
            return;
        }

        $entityClass = is_object($data) ? $data->getEntityClass() : $data['entityClass'];
        $form = $event->getForm();

        /** @var UsernamePasswordOrganizationToken $token */
        $token        = $this->securityContext->getToken();
        $organization = $token->getOrganizationContext();

        FormUtils::replaceField(
            $form,
            'template',
            [
                'selectedEntity' => $entityClass,
                'query_builder'  =>
                    function (EmailTemplateRepository $templateRepository) use (
                        $entityClass,
                        $organization
                    ) {
                        return $templateRepository->getEntityTemplatesQueryBuilder($entityClass, $organization, true);
                    },
            ],
            ['choice_list', 'choices']
        );

        if ($this->securityContext->isGranted('EDIT', 'entity:' . EmailEntity::ENTITY_CLASS)) {
            FormUtils::replaceField(
                $form,
                'contexts',
                [
                    'read_only' => false,
                ]
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class'         => 'Oro\Bundle\EmailBundle\Form\Model\Email',
                'intention'          => 'email',
                'csrf_protection'    => true,
                'cascade_validation' => true,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_email_email';
    }
}
