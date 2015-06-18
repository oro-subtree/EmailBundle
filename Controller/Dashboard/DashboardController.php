<?php

namespace Oro\Bundle\EmailBundle\Controller\Dashboard;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Oro\Bundle\EmailBundle\Model\FolderType;

class DashboardController extends Controller
{
    /**
     * @Route(
     *      "/recent_emails/{widget}/{activeTab}/{contentType}",
     *      name="oro_email_dashboard_recent_emails",
     *      requirements={"widget"="[\w-]+", "activeTab"="inbox|sent", "contentType"="full|tab"},
     *      defaults={"activeTab" = "inbox", "contentType" = "full"}
     * )
     */
    public function recentEmailsAction($widget, $activeTab, $contentType)
    {
        $loggedUser = $this->getUser();
        $currentOrganization = $this->container->get('security.context')->getToken()->getOrganizationContext();

        $unreadInboxMailList = $this
            ->getDoctrine()
            ->getRepository('OroEmailBundle:EmailUser')
            ->getEmailUserList($loggedUser, $currentOrganization, [FolderType::INBOX, FolderType::OTHER], 0);

        $loggedUserId     = $loggedUser->getId();
        $renderMethod     = ($contentType === 'tab') ? 'render' : 'renderView';
        $activeTabContent = $this->$renderMethod(
            'OroEmailBundle:Dashboard:recentEmailsGrid.html.twig',
            [
                'loggedUserId' => $loggedUserId,
                'gridName'     => sprintf('dashboard-recent-emails-%s-grid', $activeTab)
            ]
        );

        if ($contentType === 'tab') {
            return $activeTabContent;
        } else {
            $params = array_merge(
                [
                    'loggedUserId'     => $loggedUserId,
                    'activeTab'        => $activeTab,
                    'activeTabContent' => $activeTabContent,
                    'unreadInboxMailCount' => count($unreadInboxMailList)
                ],
                $this->get('oro_dashboard.widget_configs')->getWidgetAttributesForTwig($widget)
            );

            return $this->render(
                'OroEmailBundle:Dashboard:recentEmails.html.twig',
                $params
            );
        }
    }
}
