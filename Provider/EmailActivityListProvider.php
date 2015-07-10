<?php

namespace Oro\Bundle\EmailBundle\Provider;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\QueryBuilder;

use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\Security\Core\SecurityContextInterface;

use Oro\Bundle\ActivityListBundle\Entity\ActivityOwner;
use Oro\Bundle\ActivityListBundle\Entity\ActivityList;
use Oro\Bundle\ActivityListBundle\Model\ActivityListProviderInterface;
use Oro\Bundle\ActivityListBundle\Model\ActivityListDateProviderInterface;
use Oro\Bundle\ActivityListBundle\Model\ActivityListGroupProviderInterface;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\Provider\EmailThreadProvider;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\ConfigIdInterface;
use Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink;
use Oro\Bundle\CommentBundle\Model\CommentProviderInterface;
use Oro\Bundle\UIBundle\Tools\HtmlTagHelper;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationContextTokenInterface;

/**
 * For the Email activity in the case when EmailAddress does not have owner(User|Organization),
 * we are trying to extract Organization from the current logged user.
 *
 * @todo Should be refactored in the BAP-8520
 * @see EmailActivityListProvider::isApplicable
 * @see EmailActivityListProvider::getOrganization
 */
class EmailActivityListProvider implements
    ActivityListProviderInterface,
    ActivityListDateProviderInterface,
    ActivityListGroupProviderInterface,
    CommentProviderInterface
{
    const ACTIVITY_CLASS = 'Oro\Bundle\EmailBundle\Entity\Email';

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var ServiceLink */
    protected $doctrineRegistryLink;

    /** @var EntityNameResolver */
    protected $entityNameResolver;

    /** @var Router */
    protected $router;

    /** @var ConfigManager */
    protected $configManager;

    /** @var EmailThreadProvider */
    protected $emailThreadProvider;

    /** @var HtmlTagHelper */
    protected $htmlTagHelper;

    /** @var  ServiceLink */
    protected $securityContextLink;

    /**
     * @param DoctrineHelper      $doctrineHelper
     * @param ServiceLink         $doctrineRegistryLink
     * @param EntityNameResolver  $entityNameResolver
     * @param Router              $router
     * @param ConfigManager       $configManager
     * @param EmailThreadProvider $emailThreadProvider
     * @param HtmlTagHelper       $htmlTagHelper
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        ServiceLink $doctrineRegistryLink,
        EntityNameResolver $entityNameResolver,
        Router $router,
        ConfigManager $configManager,
        EmailThreadProvider $emailThreadProvider,
        HtmlTagHelper $htmlTagHelper
    ) {
        $this->doctrineHelper       = $doctrineHelper;
        $this->doctrineRegistryLink = $doctrineRegistryLink;
        $this->entityNameResolver   = $entityNameResolver;
        $this->router               = $router;
        $this->configManager        = $configManager;
        $this->emailThreadProvider  = $emailThreadProvider;
        $this->htmlTagHelper        = $htmlTagHelper;
    }

    /**
     * @param ServiceLink $securityContextLink
     */
    public function setSecurityContextLink(ServiceLink $securityContextLink)
    {
        $this->securityContextLink = $securityContextLink;
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicableTarget(ConfigIdInterface $configId, ConfigManager $configManager)
    {
        $provider = $configManager->getProvider('activity');

        return $provider->hasConfigById($configId)
            && $provider->getConfigById($configId)->has('activities')
            && in_array(self::ACTIVITY_CLASS, $provider->getConfigById($configId)->get('activities'));
    }

    /**
     * {@inheritdoc}
     */
    public function getRoutes()
    {
        return [
            'itemView'  => 'oro_email_view',
            'groupView' => 'oro_email_view_group',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getActivityClass()
    {
        return self::ACTIVITY_CLASS;
    }

    /**
     * {@inheritdoc}
     */
    public function getSubject($entity)
    {
        /** @var $entity Email */
        return $entity->getSubject();
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription($entity)
    {
        /** @var $entity Email */
        if ($entity->getEmailBody()) {
            $body = $entity->getEmailBody()->getBodyContent();
            $content = $this->htmlTagHelper->purify($body);
            $content = $this->htmlTagHelper->stripTags($content);
            $content = $this->htmlTagHelper->shorten($content);

            return $content;
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getDate($entity)
    {
        /** @var $entity Email */
        return $entity->getSentAt();
    }

    /**
     * {@inheritdoc}
     */
    public function isHead($entity)
    {
        /** @var $entity Email */
        return $entity->isHead();
    }

    /**
     *  {@inheritdoc}
     */
    public function isDateUpdatable()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getOrganization($activityEntity)
    {
        /** @var $activityEntity Email */
        if ($activityEntity->getFromEmailAddress()->hasOwner() &&
            $activityEntity->getFromEmailAddress()->getOwner()->getOrganization()
        ) {
            return $activityEntity->getFromEmailAddress()->getOwner()->getOrganization();
        }

        /** @var SecurityContextInterface $securityContext */
        $securityContext = $this->securityContextLink->getService();
        $token           = $securityContext->getToken();
        if ($token instanceof OrganizationContextTokenInterface) {
            return $token->getOrganizationContext();
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getData(ActivityList $activityListEntity)
    {
        /** @var Email $email */
        $email = $headEmail = $this->doctrineRegistryLink->getService()
            ->getRepository($activityListEntity->getRelatedActivityClass())
            ->find($activityListEntity->getRelatedActivityId());
        if ($email->isHead() && $email->getThread()) {
            $headEmail = $this->emailThreadProvider->getHeadEmail(
                $this->doctrineHelper->getEntityManager($activityListEntity->getRelatedActivityClass()),
                $email
            );
        }

        $data = [
            'ownerName'     => $email->getFromName(),
            'ownerLink'     => null,
            'entityId'      => $email->getId(),
            'headOwnerName' => $headEmail->getFromName(),
            'headSubject'   => $headEmail->getSubject(),
            'headSentAt'    => $headEmail->getSentAt()->format('c'),
            'isHead'        => $email->isHead() && $email->getThread(),
            'treadId'       => $email->getThread() ? $email->getThread()->getId() : null
        ];

        if ($email->getThread()) {
            $emails = $email->getThread()->getEmails();
            // if there are just two email - add replayedEmailId to use on client side
            if (count($emails) === 2) {
                $data['replayedEmailId'] = $emails[0]->getId();
            }
        }

        if ($email->getFromEmailAddress()->hasOwner()) {
            $owner = $email->getFromEmailAddress()->getOwner();
            $data['headOwnerName'] = $data['ownerName'] = $this->entityNameResolver->getName($owner);
            $route = $this->configManager->getEntityMetadata(ClassUtils::getClass($owner))
                ->getRoute('view');
            if (null !== $route) {
                $id = $this->doctrineHelper->getSingleEntityIdentifier($owner);
                $data['ownerLink'] = $this->router->generate($route, ['id' => $id]);
            }
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function getTemplate()
    {
        return 'OroEmailBundle:Email:js/activityItemTemplate.js.twig';
    }

    /**
     * {@inheritdoc}
     */
    public function getGroupedTemplate()
    {
        return 'OroEmailBundle:Email:js/groupedActivityItemTemplate.js.twig';
    }

    /**
     * {@inheritdoc}
     */
    public function getActivityId($entity)
    {
        return $this->doctrineHelper->getSingleEntityIdentifier($entity);
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicable($entity)
    {
        return
            $this->doctrineHelper->getEntityClass($entity) == self::ACTIVITY_CLASS
            && $this->getOrganization($entity);
    }

    /**
     * {@inheritdoc}
     */
    public function getTargetEntities($entity)
    {
        return $entity->getActivityTargetEntities();
    }

    /**
     * {@inheritdoc}
     */
    public function hasComments(ConfigManager $configManager, $entity)
    {
        $config = $configManager->getProvider('comment')->getConfig($entity);

        return $config->is('enabled');
    }

    /**
     * {@inheritdoc}
     */
    public function getGroupedEntities($email)
    {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->doctrineRegistryLink->getService()
            ->getRepository('OroActivityListBundle:ActivityList')->createQueryBuilder('a');

        $queryBuilder->innerJoin(
            'OroEmailBundle:Email',
            'e',
            'INNER',
            'a.relatedActivityId = e.id and a.relatedActivityClass = :class'
        )
            ->setParameter('class', self::ACTIVITY_CLASS)
            ->andWhere('e.thread = :thread')
            ->setParameter('thread', $email->getThread());

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @param Email $entity
     * @param ActivityList $activity
     * @return array
     */
    public function getActivityOwners(Email $entity, ActivityList $activity)
    {
        $activityArray = [];
        $owners = $this->doctrineRegistryLink->getService()
            ->getRepository('OroEmailBundle:EmailUser')
            ->findBy(['email' => $entity]);

        if ($owners) {
            foreach ($owners as $owner) {
                $activityOwner = new ActivityOwner();
                $activityOwner->setActivity($activity);
                $activityOwner->setOrganization($owner->getOrganization());
                $activityOwner->setUser($owner->getOwner());
                $activityArray[] = $activityOwner;
            }
        }

        return $activityArray;
    }
}
