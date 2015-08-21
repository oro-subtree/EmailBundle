<?php

namespace Oro\Bundle\EmailBundle\Entity;

use BeSimple\SoapBundle\ServiceDefinition\Annotation as Soap;

use Doctrine\ORM\Mapping as ORM;

use JMS\Serializer\Annotation as JMS;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * EmailUser
 *
 * @ORM\Table(
 *      name="oro_email_user"
 * )
 * @ORM\HasLifecycleCallbacks
 * @ORM\Entity(repositoryClass="Oro\Bundle\EmailBundle\Entity\Repository\EmailUserRepository")
 *
 * @Config(
 *      defaultValues={
 *          "security"={
 *              "type"="ACL",
 *              "permissions"="VIEW;CREATE;EDIT",
 *              "group_name"=""
 *          },
 *          "ownership"={
 *              "owner_type"="USER",
 *              "owner_field_name"="owner",
 *              "owner_column_name"="user_owner_id",
 *              "organization_field_name"="organization",
 *              "organization_column_name"="organization_id"
 *          }
 *      }
 * )
 */
class EmailUser
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Soap\ComplexType("int")
     * @JMS\Type("integer")
     */
    protected $id;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime")
     * @JMS\Type("dateTime")
     * @ConfigField(
     *      defaultValues={
     *          "entity"={
     *              "label"="oro.ui.created_at"
     *          }
     *      }
     * )
     */
    protected $createdAt;

    /**
     * @var OrganizationInterface
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\OrganizationBundle\Entity\Organization")
     * @ORM\JoinColumn(name="organization_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $organization;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="user_owner_id", referencedColumnName="id", onDelete="SET NULL")
     * @JMS\Exclude
     */
    protected $owner;

    /**
     * @var Mailbox
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\EmailBundle\Entity\Mailbox", inversedBy="emailUsers")
     * @ORM\JoinColumn(name="mailbox_owner_id", referencedColumnName="id", onDelete="SET NULL")
     * @JMS\Exclude
     */
    protected $mailboxOwner;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="received", type="datetime")
     * @Soap\ComplexType("dateTime")
     * @JMS\Type("dateTime")
     */
    protected $receivedAt;

    /**
     * @var bool
     *
     * @ORM\Column(name="is_seen", type="boolean", options={"default"=true})
     * @Soap\ComplexType("boolean")
     * @JMS\Type("boolean")
     */
    protected $seen = false;

    /**
     * @var EmailFolder $folder
     *
     * @ORM\ManyToOne(targetEntity="EmailFolder", inversedBy="emailUsers", cascade={"persist"})
     * @ORM\JoinColumn(name="folder_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     * @JMS\Exclude
     */
    protected $folder;

    /**
     * @var Email $email
     *
     * @ORM\ManyToOne(targetEntity="Email", inversedBy="emailUsers", cascade={"persist"})
     * @ORM\JoinColumn(name="email_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     * @JMS\Exclude
     */
    protected $email;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get entity created date/time
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Get organization
     *
     * @return OrganizationInterface
     */
    public function getOrganization()
    {
        return $this->organization;
    }

    /**
     * Set organization
     *
     * @param OrganizationInterface $organization
     * @return $this
     */
    public function setOrganization(OrganizationInterface $organization)
    {
        $this->organization = $organization;

        return $this;
    }

    /**
     * Get owning user
     *
     * @return User
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * Set owning user
     *
     * @param User $owningUser
     *
     * @return $this
     */
    public function setOwner($owningUser)
    {
        $this->owner = $owningUser;

        return $this;
    }

    /**
     * Get date/time when email received
     *
     * @return \DateTime
     */
    public function getReceivedAt()
    {
        return $this->receivedAt;
    }

    /**
     * Set date/time when email received
     *
     * @param \DateTime $receivedAt
     *
     * @return $this
     */
    public function setReceivedAt($receivedAt)
    {
        $this->receivedAt = $receivedAt;

        return $this;
    }

    /**
     * Get if email is seen
     *
     * @return bool
     */
    public function isSeen()
    {
        return $this->seen;
    }

    /**
     * Set email is read flag
     *
     * @param boolean $seen
     *
     * @return $this
     */
    public function setSeen($seen)
    {
        $this->seen = (bool)$seen;

        return $this;
    }

    /**
     * Get email folder
     *
     * @return EmailFolder
     */
    public function getFolder()
    {
        return $this->folder;
    }

    /**
     * @param EmailFolder $folder
     *
     * @return $this
     */
    public function setFolder($folder)
    {
        $this->folder = $folder;

        return $this;
    }

    /**
     * Get email
     *
     * @return Email
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set email
     *
     * @param Email $email
     *
     * @return $this
     */
    public function setEmail(Email $email)
    {
        $this->email = $email;
        $email->addEmailUser($this);

        return $this;
    }

    /**
     * Pre persist event listener
     *
     * @ORM\PrePersist
     */
    public function beforeSave()
    {
        $this->createdAt = new \DateTime('now', new \DateTimeZone('UTC'));
    }

    /**
     * @return Mailbox|null
     */
    public function getMailboxOwner()
    {
        return $this->mailboxOwner;
    }

    /**
     * @param Mailbox|null $mailboxOwner
     *
     * @return $this
     */
    public function setMailboxOwner(Mailbox $mailboxOwner = null)
    {
        $this->mailboxOwner = $mailboxOwner;

        return $this;
    }
}
