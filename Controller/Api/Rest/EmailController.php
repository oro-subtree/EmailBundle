<?php

namespace Oro\Bundle\EmailBundle\Controller\Api\Rest;

use Doctrine\Common\Util\ClassUtils;

use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use FOS\RestBundle\Controller\Annotations\Delete;
use FOS\RestBundle\Util\Codes;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpFoundation\Response;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\SoapBundle\Controller\Api\Rest\RestController;
use Oro\Bundle\SoapBundle\Request\Parameters\Filter\StringToArrayParameterFilter;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailApiEntityManager;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailAddress;
use Oro\Bundle\EmailBundle\Entity\EmailBody;
use Oro\Bundle\EmailBundle\Entity\EmailFolder;
use Oro\Bundle\EmailBundle\Entity\EmailRecipient;
use Oro\Bundle\EmailBundle\Tools\EmailHelper;
use Oro\Bundle\EntityBundle\Tools\EntityRoutingHelper;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;

/**
 * @RouteResource("email")
 * @NamePrefix("oro_api_")
 */
class EmailController extends RestController
{
    /**
     * Get emails.
     *
     * @QueryParam(
     *      name="page",
     *      requirements="\d+",
     *      nullable=true,
     *      description="Page number, starting from 1. Defaults to 1."
     * )
     * @QueryParam(
     *      name="limit",
     *      requirements="\d+",
     *      nullable=true,
     *      description="Number of items per page. Defaults to 10."
     * )
     * @QueryParam(
     *     name="messageId",
     *     requirements=".+",
     *     nullable=true,
     *     description="The email 'Message-ID' attribute. One or several message ids separated by comma."
     * )
     * @ApiDoc(
     *      description="Get all emails",
     *      resource=true
     * )
     * @AclAncestor("oro_email_email_user_view")
     * @return Response
     */
    public function cgetAction()
    {
        $page  = (int)$this->getRequest()->get('page', 1);
        $limit = (int)$this->getRequest()->get('limit', self::ITEMS_PER_PAGE);

        $filterParameters = [
            'messageId' => new StringToArrayParameterFilter()
        ];
        $criteria         = $this->getFilterCriteria(
            $this->getSupportedQueryParameters(__FUNCTION__),
            $filterParameters
        );

        return $this->handleGetListRequest($page, $limit, $criteria);
    }

    /**
     * REST GET item
     *
     * @param string $id
     *
     * @ApiDoc(
     *      description="Get email",
     *      resource=true
     * )
     * @AclAncestor("oro_email_email_user_view")
     * @return Response
     */
    public function getAction($id)
    {
        return $this->handleGetRequest($id);
    }

    /**
     * Update email.
     *
     * @param int $id The id of the email
     *
     * @ApiDoc(
     *      description="Update email",
     *      resource=true
     * )
     * @AclAncestor("oro_email_update")
     * @return Response
     */
    public function putAction($id)
    {
        return $this->handleUpdateRequest($id);
    }

    /**
     * Create new email.
     *
     * @ApiDoc(
     *      description="Create new email",
     *      resource=true
     * )
     * @AclAncestor("oro_email_create")
     */
    public function postAction()
    {
        return $this->handleCreateRequest();
    }

    /**
     * {@inheritdoc}
     */
    public function handleGetListRequest($page = 1, $limit = self::ITEMS_PER_PAGE, $filters = [], $joins = [])
    {
        $manager = $this->getManager();
        $qb = $manager->getListQueryBuilder($limit, $page, $filters, null, $joins);

        $entities = array_filter($qb->getQuery()->getResult(), [$this->getEmailHelper(), 'isEmailViewGranted']);
        $result = $this->getPreparedItems($entities);

        return $this->buildResponse($result, self::ACTION_LIST, ['result' => $result, 'query' => $qb]);
    }

    /**
     * @param integer $entityId Entity id
     *
     * @ApiDoc(
     *      description="Returns an AssociationList object",
     *      resource=true,
     *      statusCodes={
     *          200="Returned when successful",
     *          404="Activity association was not found",
     *      }
     * )
     * @AclAncestor("oro_email_email_user_view")
     * @return Response
     */
    public function getAssociationAction($entityId)
    {
        /**
         * @var $entity Email
         */
        $entity = $this->getManager()->find($entityId);

        if (!$entity) {
            return $this->handleView($this->view('', Codes::HTTP_NOT_FOUND));
        }

        if (!$this->assertEmailViewGranted($entity)) {
            return $this->handleView($this->view('', Codes::HTTP_FORBIDDEN));
        }

        $associations = $entity->getActivityTargetEntities();
        $this->filterUserAssociation($associations);

        return $this->handleView(
            $this->view($associations, is_array($associations) ? Codes::HTTP_OK : Codes::HTTP_NOT_FOUND)
        );
    }

    /**
     * @param integer $entityId Entity id
     *
     * @ApiDoc(
     *      description="Returns an AssociationList object",
     *      resource=true,
     *      statusCodes={
     *          200="Returned when successful",
     *          404="Activity association was not found",
     *      }
     * )
     * @AclAncestor("oro_email_email_user_view")
     * @return Response
     */
    public function getAssociationsDataAction($entityId)
    {
        /** @var $entity Email */
        $entity = $this->getManager()->find($entityId);
        if (!$entity) {
            return $this->handleView($this->view('', Codes::HTTP_NOT_FOUND));
        }

        if (!$this->assertEmailViewGranted($entity)) {
            return $this->handleView($this->view('', Codes::HTTP_FORBIDDEN));
        }

        /** @var $entityRoutingHelper EntityRoutingHelper */
        $entityRoutingHelper  = $this->get('oro_entity.routing_helper');
        $entityConfigProvider = $this->get('oro_entity_config.provider.entity');
        /** @var $configManager ConfigManager */
        $configManager = $this->container->get('oro_entity_config.config_manager');
        $nameFormatter = $this->get('oro_locale.formatter.name');
        $router        = $this->get('router');
        $associations  = $entity->getActivityTargetEntities();
        $this->filterUserAssociation($associations);
        $itemsArray = [];

        foreach ($associations as $association) {
            $className = ClassUtils::getClass($association);
            $title     = $nameFormatter->format($association);
            if ($title === '') {
                $title = $association->getEmail();
            } elseif ($title === null) {
                $title = $association->getId();
            }
            $metadata = $configManager->getEntityMetadata($className);
            $route    = $metadata->getRoute('view', false);
            $link     = false;
            if ($metadata->routeView) {
                $link = $router->generate($route, ['id' => $association->getId()]);
            }
            $config = $entityConfigProvider->getConfig($className);

            if ($title) {
                $itemsArray[] = [
                    'entityId'        => $entity->getId(),
                    'targetId'        => $association->getId(),
                    'targetClassName' => $entityRoutingHelper->encodeClassName($className),
                    'title'           => $title,
                    'icon'            => $config->get('icon'),
                    'link'            => $link
                ];
            }
        }

        return $this->handleView(
            $this->view($itemsArray, is_array($associations) ? Codes::HTTP_OK : Codes::HTTP_NOT_FOUND)
        );
    }

    /**
     * Add new association
     *
     * @QueryParam(
     *      name="entityId",
     *      nullable=false,
     *      strict=true,
     *      description="Entity id"
     * )
     * @QueryParam(
     *      name="targetClassName",
     *      nullable=false,
     *      strict=true,
     *      description="Target class name"
     * )
     * @QueryParam(
     *      name="targetId",
     *      nullable=false,
     *      strict=true,
     *      description="Target Id"
     * )
     * @ApiDoc(
     *      description="Add new association",
     *      resource=true
     * )
     * @AclAncestor("oro_email_email_user_edit")
     */
    public function postAssociationsAction()
    {
        /**
         * @var $entityRoutingHelper EntityRoutingHelper
         */
        $entityRoutingHelper = $this->get('oro_entity.routing_helper');
        $translator          = $this->get('translator');

        $entityId        = $this->getRequest()->get('entityId');
        $targetClassName = $this->getRequest()->get('targetClassName');
        $targetClassName = $entityRoutingHelper->decodeClassName($targetClassName);
        $targetId        = $this->getRequest()->get('targetId');

        /**
         * @var $entity Email
         */
        $entity = $this->getManager()->find($entityId);

        if (!$entity) {
            return $this->handleView($this->view([
                'status'  => 'error',
                'message' => $translator->trans('oro.email.not_found', ['%id%' => $entityId])
            ], Codes::HTTP_NOT_FOUND));
        }

        if (!$this->getEmailHelper()->isEmailEditGranted($entity)) {
            return $this->handleView($this->view([
                'status'  => 'error',
                'message' => $translator->trans('oro.email.forbidden', ['%id%'=>$entityId])
            ], Codes::HTTP_FORBIDDEN));
        }

        try {
            if ($entity->supportActivityTarget($targetClassName)) {
                $target = $entityRoutingHelper->getEntity($targetClassName, $targetId);

                if (!$entity->hasActivityTarget($target)) {
                    $this->get('oro_email.email.manager')->addContextToEmailThread($entity, $target);
                    $response = ['status' => 'success', 'message' => $translator->trans('oro.email.contexts.added')];
                } else {
                    $response = [
                        'status'  => 'warning',
                        'message' => $translator->trans('oro.email.contexts.added.already')
                    ];
                }
            } else {
                $response = [
                    'status'  => 'error',
                    'message' => $translator->trans('oro.email.contexts.type.not_supported')
                ];
            }

            $view = $this->view($response, Codes::HTTP_OK);
        } catch (Exception $e) {
            $view = $this->view([], Codes::HTTP_BAD_REQUEST);
        }

        return $this->buildResponse($view, Codes::HTTP_CREATED, ['entity' => $entity]);
    }

    /**
     * Delete Association.
     *
     * @param int    $entityId
     * @param string $targetClassName
     * @param int    $targetId
     *
     * @ApiDoc(
     *      description="Delete Association",
     *      resource=true
     * )
     * @AclAncestor("oro_email_email_user_edit")
     *
     * @Delete("/emails/{entityId}/associations/{targetClassName}/{targetId}")
     *
     * @return Response
     */
    public function deleteAssociationAction($entityId, $targetClassName, $targetId)
    {
        /**
         * @var $entity Email
         */
        $entity     = $this->getManager()->find($entityId);
        $translator = $this->get('translator');
        if (!$entity) {
            return $this->handleView($this->view([
                'status'  => 'error',
                'message' => $translator->trans('oro.email.not_found', ['%id%' => $entityId])
            ], Codes::HTTP_NOT_FOUND));
        }

        if (!$this->getEmailHelper()->isEmailEditGranted($entity)) {
            return $this->handleView($this->view([
                'status'  => 'error',
                'message' => $translator->trans('oro.email.forbidden', ['%id%'=>$entityId])
            ], Codes::HTTP_FORBIDDEN));
        }

        try {
            $entityRoutingHelper = $this->get('oro_entity.routing_helper');
            $target              = $entityRoutingHelper->getEntity($targetClassName, $targetId);
            $this->get('oro_email.email.manager')->deleteContextFromEmailThread($entity, $target);
            $view = $this->view([
                'status'  => 'success',
                'message' => $translator->trans('oro.email.contexts.removed')
            ], Codes::HTTP_OK);
        } catch (\RuntimeException $e) {
            $view = $this->view(['status' => 'error', 'message' => $e->getMessage()], Codes::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            $view = $this->view(['status' => 'error', 'message' => $e->getMessage()], Codes::HTTP_OK);
        }

        return $this->buildResponse($view, Codes::HTTP_LOOP_DETECTED, ['id' => $entityId, 'entity' => $entity]);
    }

    /**
     * GET single item
     *
     * @param  mixed $id
     *
     * @return Response
     */
    public function handleGetRequest($id)
    {
        $manager = $this->getManager();

        $result = $manager->find($id);
        if ($result) {
            if (!$this->assertEmailViewGranted($result)) {
                return $this->handleView($this->view('', Codes::HTTP_FORBIDDEN));
            }

            $result = $this->getPreparedItem($result);
        }

        return $this->buildResponse(
            $result ?: '',
            self::ACTION_READ,
            ['result' => $result],
            $result ? Codes::HTTP_OK : Codes::HTTP_NOT_FOUND
        );
    }

    /**
     * @param Email $entity
     *
     * @return bool
     */
    protected function assertEmailViewGranted(Email $entity)
    {
        return $this->getEmailHelper()->isEmailViewGranted($entity);
    }

    /**
     * {@inheritdoc}
     */
    protected function transformEntityField($field, &$value)
    {
        switch ($field) {
            case 'fromEmailAddress':
                if ($value) {
                    /** @var EmailAddress $value */
                    $value = $value->getEmail();
                }
                break;
            case 'folder':
                if ($value) {
                    /** @var EmailFolder $value */
                    $value = $value->getFullName();
                }
                break;
            case 'emailBody':
                if ($value) {
                    /** @var EmailBody $value */
                    $value = array(
                        'content' => $value->getBodyContent(),
                        'isText' => $value->getBodyIsText(),
                        'hasAttachments' => $value->getHasAttachments()
                    );
                }
                break;
            case 'recipients':
                if ($value) {
                    $result = array();
                    /** @var $recipient EmailRecipient */
                    foreach ($value as $index => $recipient) {
                        $result[$index] = array(
                            'name' => $recipient->getName(),
                            'type' => $recipient->getType(),
                            'emailAddress' => $recipient->getEmailAddress() ?
                                $recipient->getEmailAddress()->getEmail()
                                : null
                        );
                    }
                    $value = $result;
                }
                break;
            default:
                parent::transformEntityField($field, $value);
        }
    }

    /**
     * @param array|null $associations
     */
    protected function filterUserAssociation(&$associations)
    {
        if (!$associations) {
            return;
        }
        $user = $this->get('security.context')->getToken()->getUser();
        foreach ($associations as $key => $association) {
            $userClassName        = ClassUtils::getClass($user);
            $associationClassName = ClassUtils::getClass($association);
            if ($userClassName === $associationClassName && $user->getId() === $association->getId()) {
                unset($associations[$key]);
            }
        }
    }

    /**
     * Get entity manager
     *
     * @return EmailApiEntityManager
     */
    public function getManager()
    {
        return $this->container->get('oro_email.manager.email.api');
    }

    /**
     * {@inheritdoc}
     */
    public function getForm()
    {
        return $this->get('oro_email.form.email.api');
    }

    /**
     * {@inheritdoc}
     */
    public function getFormHandler()
    {
        return $this->get('oro_email.form.handler.email.api');
    }

    /**
     * @return EmailHelper
     */
    protected function getEmailHelper()
    {
        return $this->get('oro_email.email_helper');
    }
}
