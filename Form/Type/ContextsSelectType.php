<?php

namespace Oro\Bundle\EmailBundle\Form\Type;

use Doctrine\ORM\EntityManager;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\EmailBundle\Form\DataTransformer\ContextsToViewTransformer;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\SearchBundle\Engine\ObjectMapper;
use Oro\Bundle\SecurityBundle\SecurityFacade;

class ContextsSelectType extends AbstractType
{
    const NAME = 'oro_email_contexts_select';

    /**
     * @var EntityManager
     */
    protected $entityManager;

    /** @var ConfigManager */
    protected $configManager;

    /** @var TranslatorInterface */
    protected $translator;

    /** @var ObjectMapper */
    protected $mapper;

    /* @var SecurityFacade */
    protected $securityFacade;

    /**
     * @param EntityManager $entityManager
     * @param ConfigManager $configManager
     * @param TranslatorInterface $translator
     * @param ObjectMapper $mapper
     * @param SecurityFacade $securityFacade
     */
    public function __construct(
        EntityManager $entityManager,
        ConfigManager $configManager,
        TranslatorInterface $translator,
        ObjectMapper $mapper,
        SecurityFacade $securityFacade
    ) {
        $this->entityManager = $entityManager;
        $this->configManager = $configManager;
        $this->translator = $translator;
        $this->mapper = $mapper;
        $this->securityFacade = $securityFacade;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->resetViewTransformers();
        $builder->addViewTransformer(
            new ContextsToViewTransformer(
                $this->entityManager,
                $this->configManager,
                $this->translator,
                $this->mapper,
                $this->securityFacade
            )
        );
    }

    /**
     * @param FormView $view
     * @param FormInterface $form
     * @param array $options
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $formData = $form->getViewData();
        $view->vars['attr']['data-selected-data'] = $formData;
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            [
                'tooltip' => false,
                'configs' => [
                    'placeholder'        => 'oro.email.contexts.placeholder',
                    'allowClear'         => true,
                    'multiple'           => true,
                    'route_name'         => 'oro_api_get_search_autocomplete',
                    'separator'          => ';',
                    'forceSelectedData'  => true,
                    'containerCssClass'  => 'taggable-email',
                    'minimumInputLength' => 1,
                ]
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'genemu_jqueryselect2_hidden';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
