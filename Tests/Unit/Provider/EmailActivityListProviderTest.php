<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Provider;

use Oro\Bundle\EmailBundle\Provider\EmailActivityListProvider;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\EmailBundle\Entity\EmailUser;

class EmailActivityListProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var EmailActivityListProvider
     */
    protected $emailActivityListProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $doctrineHelper;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $securityFacadeLink;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $doctrineRegistryLink;

    protected function setUp()
    {
        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();
        $this->securityFacadeLink = $this
            ->getMockBuilder('Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink')
            ->setMethods(['getService', 'getRepository', 'findBy'])
            ->disableOriginalConstructor()
            ->getMock();
        $entityNameResolver = $this->getMockBuilder('Oro\Bundle\EntityBundle\Provider\EntityNameResolver')
            ->disableOriginalConstructor()
            ->getMock();
        $router = $this->getMockBuilder('Symfony\Bundle\FrameworkBundle\Routing\Router')
            ->disableOriginalConstructor()
            ->getMock();
        $configManager  = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();
        $emailThreadProvider = $this->getMockBuilder('Oro\Bundle\EmailBundle\Entity\Provider\EmailThreadProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $htmlTagHelper = $this->getMockBuilder('Oro\Bundle\UIBundle\Tools\HtmlTagHelper')
            ->disableOriginalConstructor()
            ->getMock();
        $this->doctrineRegistryLink = $this->getMockBuilder(
            'Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink'
        )
            ->disableOriginalConstructor()
            ->getMock();

        $securityContext = $this->getMockBuilder('Symfony\Component\Security\Core\SecurityContext')
            ->setMethods(['getOrganizationContext', 'getToken'])
            ->disableOriginalConstructor()
            ->getMock();
        $securityContext
            ->expects($this->once())
            ->method('getToken')
            ->willReturn($securityContext);
        $securityContext
            ->expects($this->once())
            ->method('getOrganizationContext')
            ->willReturn($securityContext);
        $this->securityFacadeLink
            ->expects($this->once())
            ->method('getService')
            ->willReturn($securityContext);

        $this->emailActivityListProvider = new EmailActivityListProvider(
            $this->doctrineHelper,
            $this->doctrineRegistryLink,
            $entityNameResolver,
            $router,
            $configManager,
            $emailThreadProvider,
            $htmlTagHelper
        );
        $this->emailActivityListProvider->setSecurityContextLink($this->securityFacadeLink);
    }

    public function testGetActivityOwners()
    {
        $organization = new Organization();
        $organization->setName('Org');
        $user = new User();
        $user->setUsername('test');
        $emailUser = new EmailUser();
        $emailUser->setOrganization($organization);
        $emailUser->setOwner($user);
        $owners = [$emailUser];

        $emailMock = $this->getMockBuilder('Oro\Bundle\EmailBundle\Entity\Email')
            ->disableOriginalConstructor()
            ->getMock();
        $activityListMock = $this->getMockBuilder('Oro\Bundle\ActivityListBundle\Entity\ActivityList')
            ->disableOriginalConstructor()
            ->getMock();
        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $repository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $this->doctrineRegistryLink
            ->expects($this->once())
            ->method('getService')
            ->willReturn($em);
        $em
            ->expects($this->once())
            ->method('getRepository')
            ->willReturn($repository);
        $repository
            ->expects($this->once())
            ->method('findBy')
            ->willReturn($owners);

        $activityOwnerArray = $this->emailActivityListProvider->getActivityOwners($emailMock, $activityListMock);

        $this->assertCount(1, $activityOwnerArray);
        $owner = $activityOwnerArray[0];
        $this->assertEquals('Org', $owner->getOrganization()->getName());
        $this->assertEquals('test', $owner->getUser()->getUsername());
    }
}
