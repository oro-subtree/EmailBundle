<?php

namespace Oro\Bundle\EmailBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 * @ORM\Table(name="oro_email_auto_response_rule_condition")
 */
class AutoResponseRuleCondition
{
    /**
     * @var int
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     * @ORM\Column(type="string", length=5)
     * @Assert\NotBlank
     */
    protected $operation;

    /**
     * @var string
     * @ORM\Column(type="string")
     * @Assert\NotBlank
     */
    protected $field;

    /**
     * @var string
     * @ORM\Column(type="string")
     * @Assert\NotBlank
     */
    protected $filterType;

    /**
     * @var string
     * @ORM\Column(type="string")
     */
    protected $filterValue;

    /**
     * @var int
     * @ORM\Column(type="integer")
     */
    protected $position = 0;

    /**
     * @var AutoResponseRule
     * @ORM\ManyToOne(targetEntity="AutoResponseRule", inversedBy="conditions")
     * @ORM\JoinColumn(name="rule_id", referencedColumnName="id", onDelete="CASCADE")
     * @Assert\NotBlank
     */
    protected $rule;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getOperation()
    {
        return $this->operation;
    }

    /**
     * @return string
     */
    public function getField()
    {
        return $this->field;
    }

    /**
     * @return string
     */
    public function getFilterType()
    {
        return $this->filterType;
    }

    /**
     * @return string
     */
    public function getFilterValue()
    {
        return $this->filterValue;
    }

    /**
     * @return int
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * @return AutoResponseRule
     */
    public function getRule()
    {
        return $this->rule;
    }

    /**
     * @param string $operation
     *
     * @return $this
     */
    public function setOperation($operation)
    {
        $this->operation = $operation;

        return $this;
    }

    /**
     * @param string $field
     *
     * @return $this
     */
    public function setField($field)
    {
        $this->field = $field;

        return $this;
    }

    /**
     * @param string $filterType
     *
     * @return $this
     */
    public function setFilterType($filterType)
    {
        $this->filterType = $filterType;

        return $this;
    }

    /**
     * @param string $filterValue
     *
     * @return $this
     */
    public function setFilterValue($filterValue)
    {
        $this->filterValue = $filterValue;

        return $this;
    }

    /**
     * @param int $position
     *
     * @return $this
     */
    public function setPosition($position)
    {
        $this->position = $position;

        return $this;
    }

    /**
     * @param AutoResponseRule $rule
     *
     * @return $this
     */
    public function setRule(AutoResponseRule $rule)
    {
        $this->rule = $rule;

        return $this;
    }
}
