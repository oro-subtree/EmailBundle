<?php

namespace Oro\Bundle\EmailBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

use JMS\Serializer\Annotation as JMS;

use Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * Email Origin
 *
 * @ORM\Table(name="oro_email_origin")
 * @ORM\Entity
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="name", type="string", length=30)
 * @JMS\ExclusionPolicy("ALL")
 */
abstract class EmailOrigin
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @JMS\Type("integer")
     * @JMS\Expose
     */
    protected $id;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="EmailFolder", mappedBy="origin", cascade={"persist", "remove"}, orphanRemoval=true)
     */
    protected $folders;

    /**
     * @var boolean
     *
     * @ORM\Column(name="isActive", type="boolean")
     */
    protected $isActive = true;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="sync_code_updated", type="datetime", nullable=true)
     */
    protected $syncCodeUpdatedAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="synchronized", type="datetime", nullable=true)
     */
    protected $synchronizedAt;

    /**
     * @var int
     *
     * @ORM\Column(name="sync_code", type="integer", nullable=true)
     */
    protected $syncCode;

    /**
     * @var int
     *
     * @ORM\Column(name="sync_count", type="integer", nullable=true)
     */
    protected $syncCount;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\UserBundle\Entity\User", inversedBy="emailOrigins", cascade={"remove"})
     * @ORM\JoinColumn(name="owner_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $owner;

    /**
     * @var OrganizationInterface
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\OrganizationBundle\Entity\Organization", cascade={"remove"})
     * @ORM\JoinColumn(name="organization_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $organization;

    public function __construct()
    {
        $this->folders   = new ArrayCollection();
        $this->syncCount = 0;
    }

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
     * Get an email folder
     *
     * @param string      $type     Can be 'inbox', 'sent', 'trash', 'drafts' or 'other'
     * @param string|null $fullName
     *
     * @return EmailFolder|null
     */
    public function getFolder($type, $fullName = null)
    {
        return $this->folders
            ->filter(
                function (EmailFolder $folder) use ($type, $fullName) {
                    return
                        $folder->getType() === $type
                        && (empty($fullName) || $folder->getFullName() === $fullName);
                }
            )->first();
    }

    /**
     * Get email folders
     *
     * @return EmailFolder[]
     */
    public function getFolders()
    {
        return $this->folders;
    }

    /**
     * Add folder
     *
     * @param  EmailFolder $folder
     *
     * @return EmailOrigin
     */
    public function addFolder(EmailFolder $folder)
    {
        $this->folders[] = $folder;

        $folder->setOrigin($this);

        return $this;
    }

    /**
     * Indicate whether this email origin is in active state or not
     *
     * @return boolean
     */
    public function isActive()
    {
        return $this->isActive;
    }

    /**
     * Set this email origin in active/inactive state
     *
     * @param boolean $isActive
     *
     * @return EmailOrigin
     */
    public function setIsActive($isActive)
    {
        $this->isActive = $isActive;

        return $this;
    }

    /**
     * Get date/time when this object was changed
     *
     * @return \DateTime
     */
    public function getSyncCodeUpdatedAt()
    {
        return $this->syncCodeUpdatedAt;
    }

    /**
     * Get date/time when emails from this origin were synchronized
     *
     * @return \DateTime
     */
    public function getSynchronizedAt()
    {
        return $this->synchronizedAt;
    }

    /**
     * Set date/time when emails from this origin were synchronized
     *
     * @param \DateTime $synchronizedAt
     *
     * @return EmailOrigin
     */
    public function setSynchronizedAt($synchronizedAt)
    {
        $this->synchronizedAt = $synchronizedAt;

        return $this;
    }

    /**
     * Get the last synchronization result code
     *
     * @return int
     */
    public function getSyncCode()
    {
        return $this->syncCode;
    }

    /**
     * Set the last synchronization result code
     *
     * @param int $syncCode
     *
     * @return EmailOrigin
     */
    public function setSyncCode($syncCode)
    {
        $this->syncCode = $syncCode;

        return $this;
    }

    /**
     * @return int
     */
    public function getSyncCount()
    {
        return $this->syncCount;
    }

    /**
     * Get a human-readable representation of this object.
     *
     * @return string
     */
    public function __toString()
    {
        return (string)$this->id;
    }

    /**
     * @return OrganizationInterface
     */
    public function getOrganization()
    {
        return $this->organization;
    }

    /**
     * @param mixed $organization
     *
     * @return $this
     */
    public function setOrganization($organization)
    {
        $this->organization = $organization;

        return $this;
    }

    /**
     * @return User
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * @param User $user
     *
     * @return $this
     */
    public function setOwner($user)
    {
        $this->owner = $user;

        return $this;
    }
}
