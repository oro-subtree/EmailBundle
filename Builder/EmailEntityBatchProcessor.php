<?php

namespace Oro\Bundle\EmailBundle\Builder;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailAddress;
use Oro\Bundle\EmailBundle\Entity\EmailFolder;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailAddressManager;
use Oro\Bundle\EmailBundle\Entity\Provider\EmailOwnerProvider;

class EmailEntityBatchProcessor implements EmailEntityBatchInterface
{
    /**
     * @var EmailAddressManager
     */
    protected $emailAddressManager;

    /**
     * @var EmailOwnerProvider
     */
    protected $emailOwnerProvider;

    /**
     * @var array
     */
    protected $changes = array();

    /**
     * @var Email[]
     */
    protected $emails = array();

    /**
     * @var EmailAddress[]
     */
    protected $addresses = array();

    /**
     * @var EmailFolder[]
     */
    protected $folders = array();

    /**
     * Constructor
     *
     * @param EmailAddressManager $emailAddressManager
     * @param EmailOwnerProvider $emailOwnerProvider
     */
    public function __construct(EmailAddressManager $emailAddressManager, EmailOwnerProvider $emailOwnerProvider)
    {
        $this->emailAddressManager = $emailAddressManager;
        $this->emailOwnerProvider = $emailOwnerProvider;
    }

    /**
     * Register Email object
     *
     * @param Email $obj
     */
    public function addEmail(Email $obj)
    {
        $this->emails[] = $obj;
    }

    /**
     * @return Email[]
     */
    public function getEmails()
    {
        return $this->emails;
    }

    /**
     * Register EmailAddress object
     *
     * @param EmailAddress $obj
     * @throws \LogicException
     */
    public function addAddress(EmailAddress $obj)
    {
        $key = strtolower($obj->getEmail());
        if (isset($this->addresses[$key])) {
            throw new \LogicException(sprintf('The email address "%s" already exists in the batch.', $obj->getEmail()));
        }
        $this->addresses[$key] = $obj;
    }

    /**
     * Get EmailAddress if it exists in the batch
     *
     * @param string $email The email address
     * @return EmailAddress|null
     */
    public function getAddress($email)
    {
        $key = strtolower($email);

        return isset($this->addresses[$key])
            ? $this->addresses[$key]
            : null;
    }

    /**
     * Register EmailFolder object
     *
     * @param EmailFolder $obj
     * @throws \LogicException
     */
    public function addFolder(EmailFolder $obj)
    {
        $key = strtolower(sprintf('%s_%s', $obj->getType(), $obj->getFullName()));
        if (isset($this->folders[$key])) {
            throw new \LogicException(
                sprintf('The folder "%s" (type: %s) already exists in the batch.', $obj->getFullName(), $obj->getType())
            );
        }
        $this->folders[$key] = $obj;
    }

    /**
     * Get EmailFolder if it exists in the batch
     *
     * @param string $type The folder type
     * @param string $fullName The full name of a folder
     * @return EmailFolder|null
     */
    public function getFolder($type, $fullName)
    {
        $key = strtolower(sprintf('%s_%s', $type, $fullName));

        return isset($this->folders[$key])
            ? $this->folders[$key]
            : null;
    }

    /**
     * Tell the given EntityManager to manage this batch
     *
     * @param EntityManager $em
     */
    public function persist(EntityManager $em)
    {
        $this->persistFolders($em);
        $this->persistAddresses($em);
        $this->persistEmails($em);
    }

    /**
     * Get the list of all changes made by {@see persist()} method
     * For example new objects can be replaced by existing ones from a database
     *
     * @return array [old, new] The list of changes
     */
    public function getChanges()
    {
        return $this->changes;
    }

    /**
     * Removes all objects from this batch processor
     */
    public function clear()
    {
        $this->changes = array();
        $this->emails = array();
        $this->folders = array();
        $this->addresses = array();
    }

    /**
     * Removes all email objects from this batch processor
     */
    public function removeEmails()
    {
        $this->emails = array();
    }

    /**
     * Tell the given EntityManager to manage Email objects and all its children in this batch
     *
     * @param EntityManager $em
     */
    protected function persistEmails(EntityManager $em)
    {
        $this->processDuplicateEmails($em);
        foreach ($this->emails as $email) {
            $em->persist($email);
        }
    }

    /**
     * Replaces emails with already existing in DB emails to avoid duplicates
     *
     * @param EntityManager $em
     */
    protected function processDuplicateEmails(EntityManager $em)
    {
        $existingEmails = $this->getExistingEmails($em);
        if (!empty($existingEmails)) {
            // add existing emails to new folders and remove these emails from the list
            foreach ($existingEmails as $existingEmail) {
                foreach ($this->emails as $key => $email) {
                    if ($this->areEmailsEqual($email, $existingEmail)) {
                        $folders = $email->getFolders();
                        foreach ($folders as $folder) {
                            $existingEmail->addFolder($folder);
                        }
                        $this->changes[] = ['old' => $this->emails[$key], 'new' => $existingEmail];
                        unset($this->emails[$key]);
                    }
                }
            }
            // add existing emails to the list
            foreach ($existingEmails as $existingEmail) {
                $this->emails[] = $existingEmail;
            }
        }
    }

    /**
     * Loads emails already exist in the database for the current batch
     *
     * @param EntityManager $em
     * @return Email[]
     */
    protected function getExistingEmails(EntityManager $em)
    {
        // get distinct list of Message-ID
        $messageIds = [];
        foreach ($this->emails as $email) {
            $messageId = $email->getMessageId();
            if (!empty($messageId)) {
                $messageIds[$messageId] = $messageId;
            }
        }
        if (empty($messageIds)) {
            return [];
        }

        return $em->getRepository('OroEmailBundle:Email')
            ->findBy(array('messageId' => array_values($messageIds)));
    }

    /**
     * Determines whether two emails are the same email message
     *
     * @param Email $email1
     * @param Email $email2
     * @return bool
     */
    protected function areEmailsEqual(Email $email1, Email $email2)
    {
        return $email1->getMessageId() === $email2->getMessageId();
    }

    /**
     * Tell the given EntityManager to manage EmailAddress objects in this batch
     *
     * @param EntityManager $em
     */
    protected function persistAddresses(EntityManager $em)
    {
        $repository = $this->emailAddressManager->getEmailAddressRepository($em);
        foreach ($this->addresses as $key => $obj) {
            /** @var EmailAddress $dbObj */
            $dbObj = $repository->findOneBy(array('email' => $obj->getEmail()));
            if ($dbObj === null) {
                $obj->setOwner($this->emailOwnerProvider->findEmailOwner($em, $obj->getEmail()));
                $em->persist($obj);
            } else {
                $this->updateAddressReferences($obj, $dbObj);
                $this->addresses[$key] = $dbObj;
            }
        }
    }

    /**
     * Tell the given EntityManager to manage EmailFolder objects in this batch
     *
     * @param EntityManager $em
     */
    protected function persistFolders(EntityManager $em)
    {
        $repository = $em->getRepository('OroEmailBundle:EmailFolder');
        foreach ($this->folders as $key => $obj) {
            if ($obj->getId() !== null) {
                continue;
            }
            /** @var EmailFolder $dbObj */
            $dbObj = $repository->findOneBy(array('fullName' => $obj->getFullName(), 'type' => $obj->getType()));
            if ($dbObj === null) {
                $em->persist($obj);
            } else {
                $this->changes[] = ['old' => $obj, 'new' => $dbObj];
                $this->updateFolderReferences($obj, $dbObj);
                $this->folders[$key] = $dbObj;
            }
        }
    }

    /**
     * Make sure that all objects in this batch have correct EmailAddress references
     *
     * @param EmailAddress $old
     * @param EmailAddress $new
     */
    protected function updateAddressReferences(EmailAddress $old, EmailAddress $new)
    {
        foreach ($this->emails as $email) {
            if ($email->getFromEmailAddress() === $old) {
                $email->setFromEmailAddress($new);
            }
            foreach ($email->getRecipients() as $recipient) {
                if ($recipient->getEmailAddress() === $old) {
                    $recipient->setEmailAddress($new);
                }
            }
        }
    }

    /**
     * Make sure that all objects in this batch have correct EmailFolder references
     *
     * @param EmailFolder $old
     * @param EmailFolder $new
     */
    protected function updateFolderReferences(EmailFolder $old, EmailFolder $new)
    {
        foreach ($this->emails as $obj) {
            if ($obj->hasFolder($old)) {
                $obj->removeFolder($old)->addFolder($new);
            }
        }
    }
}
