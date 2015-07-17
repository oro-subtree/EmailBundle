<?php

namespace Oro\Bundle\EmailBundle\Controller\Api\Rest;

use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Delete;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use Oro\Bundle\EmailBundle\Entity\Provider\EmailProvider;
use Symfony\Component\HttpFoundation\Response;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\SoapBundle\Controller\Api\Rest\RestController;
use Oro\Bundle\SoapBundle\Request\Parameters\Filter\StringToArrayParameterFilter;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailApiEntityManager;
use Oro\Bundle\EmailBundle\Entity\Email;

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
     *      description="Get emails",
     *      resource=true
     * )
     * @AclAncestor("oro_email_email_view")
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
     * Get email.
     *
     * @param string $id
     *
     * @Get(
     *      "/emails/{id}",
     *      name="",
     *      requirements={"id"="\d+"}
     * )
     * @ApiDoc(
     *      description="Get email",
     *      resource=true
     * )
     * @AclAncestor("oro_email_email_view")
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
     * @AclAncestor("oro_email_email_edit")
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
     * @AclAncestor("oro_email_email_edit")
     */
    public function postAction()
    {
        return $this->handleCreateRequest();
    }

    /**
     * Get email context data.
     *
     * @param int $id The email id
     *
     * @ApiDoc(
     *      description="Get email context data",
     *      resource=true
     * )
     *
     * @AclAncestor("oro_email_email_view")
     *
     * @return Response
     */
    public function getContextAction($id)
    {
        /** @var Email $email */
        $email = $this->getManager()->find($id);
        if (!$email) {
            return $this->buildNotFoundResponse();
        }

        $result = $this->getManager()->getEmailContext($email);

        return $this->buildResponse($result, self::ACTION_LIST, ['result' => $result]);
    }

    /**
     * Get email.
     *
     * @Get(
     *      "/emails/notification/info",
     *      name="",
     * )
     * @ApiDoc(
     *      description="Get email",
     *      resource=true
     * )
     * @AclAncestor("oro_email_email_view")
     *
     * @return Response
     */
    public function getNewemailAction()
    {
        $emailProvider = $this->getEmailProvider();
        $maxEmailsDisplay = $this->container->getParameter('oro_email.flash_notification.max_emails_display');
        $emails = $emailProvider->getNewEmails($this->getUser(), $maxEmailsDisplay);

        $emailsData = [];
        /**
         * @var $email Email
         */
        foreach ($emails as $email) {
            $emailsData[] = [
                'id' => $email->getId(),
                'subject' => $email->getSubject(),
                'bodyContent' => substr($email->getEmailBody()->getBodyContent(), 0, 100),
                'fromName' => $email->getFromName()
            ];
        }

        $result = [
            'count' => $emailProvider->getCountNewEmails($this->getUser()),
            'emails' => $emailsData
        ];

        return $this->buildResponse($result, self::ACTION_READ, ['result' => $result]);
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
     * Get email entity provider
     *
     * @return EmailProvider
     */
    public function getEmailProvider()
    {
        return $this->container->get('oro_email.email.provider');
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
     * @param string $attribute
     * @param Email $email
     *
     * @return bool
     */
    protected function assertEmailAccessGranted($attribute, Email $email)
    {
        return $this->get('oro_security.security_facade')->isGranted($attribute, $email);
    }
}
