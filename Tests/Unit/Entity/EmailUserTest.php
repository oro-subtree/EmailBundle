<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Entity;

use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailFolder;
use Oro\Bundle\EmailBundle\Entity\UserEmailOwner;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;

class EmailUserTest extends \PHPUnit_Framework_TestCase
{
    public function testGetterSetter()
    {
        $emailUser = new UserEmailOwner();
        $email = new Email();
        $owner = new User();
        $organization = new Organization();
        $folder = new EmailFolder();
        $receivedAt = new \DateTime('now');

        $emailUser->setEmail($email);
        $emailUser->setOrganization($organization);
        $emailUser->setFolder($folder);
        $emailUser->setSeen(true);
        $emailUser->setOwner($owner);
        $emailUser->setReceivedAt($receivedAt);

        $this->assertEquals($email, $emailUser->getEmail());
        $this->assertEquals($organization, $emailUser->getOrganization());
        $this->assertEquals($folder, $emailUser->getFolder());
        $this->assertEquals(true, $emailUser->isSeen());
        $this->assertEquals($owner, $emailUser->getOwner());
        $this->assertEquals($receivedAt, $emailUser->getReceivedAt());
        $this->assertNull($emailUser->getCreatedAt());
    }

    public function testBeforeSave()
    {
        $emailUser = new UserEmailOwner();
        $emailUser->beforeSave();

        $this->assertInstanceOf('\DateTime', $emailUser->getCreatedAt());
    }
}
