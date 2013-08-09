<?php

namespace Oro\Bundle\EmailBundle\Datagrid;

use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\GridBundle\Field\MassActionFieldDescription;
use Oro\Bundle\GridBundle\Property\UrlProperty;
use Oro\Bundle\GridBundle\Action\ActionInterface;
use Oro\Bundle\GridBundle\Field\FieldDescription;
use Oro\Bundle\GridBundle\Filter\FilterInterface;
use Oro\Bundle\GridBundle\Datagrid\DatagridManager;
use Oro\Bundle\GridBundle\Property\TwigTemplateProperty;
use Oro\Bundle\GridBundle\Field\FieldDescriptionInterface;
use Oro\Bundle\GridBundle\Field\FieldDescriptionCollection;

class EmailTemplateDatagridManager extends DatagridManager
{
    /**
     * @var array
     */
    protected $entityNameChoice = array();

    public function __construct($entitiesConfig = array())
    {
        $this->entityNameChoice = array_map(
            function ($value) {
                return isset($value['name'])? $value['name'] : '';
            },
            $entitiesConfig
        );
    }

    /**
     * {@inheritDoc}
     */
    protected function getProperties()
    {
        return array(
            new UrlProperty('update_link', $this->router, 'oro_email_emailtemplate_update', array('id')),
            new UrlProperty('clone_link', $this->router, 'oro_email_emailtemplate_clone', array('id')),
            new UrlProperty('delete_link', $this->router, 'oro_api_delete_emailtemplate', array('id')),
        );
    }

    /**
     * {@inheritDoc}
     */
    protected function configureFields(FieldDescriptionCollection $fieldsCollection)
    {
        $fieldId = new FieldDescription();
        $fieldId->setName('id');
        $fieldId->setOptions(
            array(
                'type'        => FieldDescriptionInterface::TYPE_INTEGER,
                'label'       => $this->translate('ID'),
                'field_name'  => 'id',
                'filter_type' => FilterInterface::TYPE_NUMBER,
                'show_column' => false
            )
        );
        $fieldsCollection->add($fieldId);
        /*----------------------------------------------------------------*/

        $fieldEntityName = new FieldDescription();
        $fieldEntityName->setName('entityName');
        $fieldEntityName->setOptions(
            array(
                'type'                => FieldDescriptionInterface::TYPE_TEXT,
                'label'               => $this->translate('oro.email.datagrid.emailtemplate.column.entity_name'),
                'field_name'          => 'entityName',
                'filter_type'         => FilterInterface::TYPE_CHOICE,
                'choices'             => $this->entityNameChoice,
                'translation_domain'  => 'config',
                'required'            => false,
                'sortable'            => false,
                'filterable'          => true,
                'show_filter'         => true,
            )
        );
        $templateDataProperty = new TwigTemplateProperty(
            $fieldEntityName,
            'OroNotificationBundle:EmailNotification:Datagrid/Property/entityName.html.twig'
        );
        $fieldEntityName->setProperty($templateDataProperty);
        $fieldsCollection->add($fieldEntityName);
        /*----------------------------------------------------------------*/

        $fieldName = new FieldDescription();
        $fieldName->setName('name');
        $fieldName->setOptions(
            array(
                'type'        => FieldDescriptionInterface::TYPE_TEXT,
                'label'       => $this->translate('oro.email.datagrid.emailtemplate.column.name'),
                'field_name'  => 'name',
                'filter_type' => FilterInterface::TYPE_STRING,
                'required'    => false,
                'sortable'    => true,
                'filterable'  => true,
                'show_filter' => true,
            )
        );
        $fieldsCollection->add($fieldName);
        /*----------------------------------------------------------------*/

        $fieldType = new FieldDescription();
        $fieldType->setName('type');
        $fieldType->setOptions(
            array(
                'type'        => FieldDescriptionInterface::TYPE_OPTIONS,
                'label'       => $this->translate('oro.email.datagrid.emailtemplate.column.type'),
                'field_name'  => 'type',
                'filter_type' => FilterInterface::TYPE_CHOICE,
                'required'    => false,
                'sortable'    => true,
                'filterable'  => true,
                'show_filter' => true,
                'choices'     => array(
                    'html' => $this->translate('oro.email.datagrid.emailtemplate.filter.type.html'),
                    'txt'  => $this->translate('oro.email.datagrid.emailtemplate.filter.type.txt'),
                ),
            )
        );
        $fieldsCollection->add($fieldType);
        /*----------------------------------------------------------------*/

        $fieldIsSystem = new FieldDescription();
        $fieldIsSystem->setName('isSystem');
        $fieldIsSystem->setOptions(
            array(
                'type'               => FieldDescriptionInterface::TYPE_OPTIONS,
                'field_name'         => 'isSystem',
                'label'              => $this->translate('oro.email.datagrid.emailtemplate.column.isSystem'),
                'required'           => false,
                'sortable'           => true,
                'filterable'         => true,
                'show_filter'        => true,
                'filter_type'        => FilterInterface::TYPE_CHOICE,
                'choices'     => array(
                    0  => $this->translate('oro.email.datagrid.emailtemplate.filter.isSystem.no'),
                    1  => $this->translate('oro.email.datagrid.emailtemplate.filter.isSystem.yes'),
                ),
            )
        );
        $fieldsCollection->add($fieldIsSystem);

        $fieldMassAction = new MassActionFieldDescription();
        $fieldMassAction->setName('massAction');
        $fieldMassAction->setOptions(
            array(
                'type'               => FieldDescriptionInterface::TYPE_MASS_ACTION,
                'filterable'         => true,
                'show_filter'        => true,
                'filter_type'        => FilterInterface::TYPE_SELECT,
            )
        );
        $fieldsCollection->add($fieldMassAction);
    }

    /**
     * {@inheritDoc}
     */
    protected function getRowActions()
    {
        $clickAction = array(
            'name'         => 'rowClick',
            'type'         => ActionInterface::TYPE_REDIRECT,
            'acl_resource' => 'oro_email_emailtemplate_update',
            'options'      => array(
                'label'         => $this->translate('oro.email.datagrid.emailtemplate.action.update'),
                'link'          => 'update_link',
                'runOnRowClick' => true,
            )
        );

        $updateAction = array(
            'name'         => 'update',
            'type'         => ActionInterface::TYPE_REDIRECT,
            'acl_resource' => 'oro_email_emailtemplate_update',
            'options'      => array(
                'label' => $this->translate('oro.email.datagrid.emailtemplate.action.update'),
                'icon'  => 'edit',
                'link'  => 'update_link',
            )
        );

        /**
         * @TODO hide icon in datagrid when it'll be possible for non system templates and delete icon for another one
         */
        $cloneAction = array(
            'name'         => 'clone',
            'type'         => ActionInterface::TYPE_REDIRECT,
            'acl_resource' => 'oro_email_emailtemplate_clone',
            'options'      => array(
                'label' => $this->translate('oro.email.datagrid.emailtemplate.action.clone'),
                'icon'  => 'share',
                'link'  => 'clone_link',
            )
        );

        $deleteAction = array(
            'name'         => 'delete',
            'type'         => ActionInterface::TYPE_DELETE,
            'acl_resource' => 'oro_email_emailtemplate_remove',
            'options'      => array(
                'label' => $this->translate('oro.email.datagrid.emailtemplate.action.delete'),
                'icon'  => 'trash',
                'link'  => 'delete_link',
            )
        );

        return array($clickAction, $updateAction, $cloneAction, $deleteAction);
    }
}
