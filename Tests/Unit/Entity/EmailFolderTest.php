<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Entity;

use Oro\Bundle\EmailBundle\Entity\EmailFolder;
use Oro\Bundle\EmailBundle\Tests\Unit\ReflectionUtil;

class EmailFolderTest extends \PHPUnit_Framework_TestCase
{
    public function testIdGetter()
    {
        $entity = new EmailFolder();
        ReflectionUtil::setId($entity, 1);
        $this->assertEquals(1, $entity->getId());
    }

    public function testNameGetterAndSetter()
    {
        $entity = new EmailFolder();
        $entity->setName('test');
        $this->assertEquals('test', $entity->getName());
    }

    public function testFullNameGetterAndSetter()
    {
        $entity = new EmailFolder();
        $entity->setFullName('test');
        $this->assertEquals('test', $entity->getFullName());
    }

    public function testTypeGetterAndSetter()
    {
        $entity = new EmailFolder();
        $entity->setType('test');
        $this->assertEquals('test', $entity->getType());
    }

    public function testOriginGetterAndSetter()
    {
        $origin = $this->getMock('Oro\Bundle\EmailBundle\Entity\EmailOrigin');

        $entity = new EmailFolder();
        $entity->setOrigin($origin);

        $this->assertTrue($origin === $entity->getOrigin());
    }

    public function testOutdatedAt()
    {
        $entity = new EmailFolder();
        $this->assertFalse($entity->isOutdated());

        $date = new \DateTime();
        $entity->setOutdatedAt($date);
        $this->assertEquals($date, $entity->getOutdatedAt());
        $this->assertTrue($entity->isOutdated());

        $entity->setOutdatedAt(null);
        $this->assertFalse($entity->isOutdated());
    }
}
