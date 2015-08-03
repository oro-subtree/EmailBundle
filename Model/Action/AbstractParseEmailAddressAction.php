<?php

namespace Oro\Bundle\EmailBundle\Model\Action;

use Oro\Bundle\EmailBundle\Tools\EmailAddressHelper;
use Oro\Bundle\WorkflowBundle\Exception\InvalidParameterException;
use Oro\Bundle\WorkflowBundle\Model\Action\AbstractAction;
use Oro\Bundle\WorkflowBundle\Model\ContextAccessor;

abstract class AbstractParseEmailAddressAction extends AbstractAction
{
    /** @var string */
    protected $address;
    /** @var string */
    protected $attribute;
    /** @var EmailAddressHelper */
    protected $addressHelper;

    /**
     * @param ContextAccessor    $contextAccessor
     * @param EmailAddressHelper $addressHelper
     */
    public function __construct(ContextAccessor $contextAccessor, EmailAddressHelper $addressHelper)
    {
        parent::__construct($contextAccessor);
        $this->addressHelper = $addressHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(array $options)
    {
        if (!isset($options['attribute']) && !isset($options[0])) {
            throw new InvalidParameterException('Attribute must be defined.');
        }

        if (!isset($options['email_address']) && !isset($options[1])) {
            throw new InvalidParameterException('Email address must be defined.');
        }

        $this->attribute = isset($options['attribute']) ? $options['attribute'] : $options[0];
        $this->address = isset($options['email_address']) ? $options['email_address'] : $options[1];
    }
}
