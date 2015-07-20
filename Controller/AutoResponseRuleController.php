<?php

namespace Oro\Bundle\EmailBundle\Controller;

use Doctrine\ORM\EntityManager;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Oro\Bundle\EmailBundle\Entity\AutoResponseRule;
use Oro\Bundle\EmailBundle\Entity\Mailbox;
use Oro\Bundle\EmailBundle\Form\Type\AutoResponseRuleType;
use Oro\Bundle\SecurityBundle\Annotation\Acl;

/**
 * @Route("/autoresponserule")
 */
class AutoResponseRuleController extends Controller
{
    /**
     * @Route("/create/{mailbox}")
     * @Acl(
     *      id="oro_email_autoresponserule_create",
     *      type="entity",
     *      class="OroEmailBundle:AutoResponseRule",
     *      permission="CREATE"
     * )
     * @Template("OroEmailBundle:AutoResponseRule:dialog/update.html.twig")
     */
    public function createAction(Mailbox $mailbox)
    {
        $rule = new AutoResponseRule();
        $rule->setMailbox($mailbox);

        return $this->update($rule);
    }

    /**
     * @Route("/update/{id}", requirements={"id"="\d+"})
     * @Acl(
     *      id="oro_email_autoresponserule_update",
     *      type="entity",
     *      class="OroEmailBundle:AutoResponseRule",
     *      permission="EDIT"
     * )
     * @Template("OroEmailBundle:AutoResponseRule:dialog/update.html.twig")
     */
    public function updateAction(AutoResponseRule $rule)
    {
        return $this->update($rule);
    }

    /**
     * @param AutoResponseRule $rule
     *
     * @return array
     */
    protected function update(AutoResponseRule $rule)
    {
        $form = $this->createForm(AutoResponseRuleType::NAME, $rule);
        $form->handleRequest($this->getRequest());

        if ($form->isValid()) {
            $em = $this->getAutoResponseRuleManager();
            $em->persist($rule);
            $em->flush();
        }

        return [
            'form'  => $form->createView(),
            'saved' => $form->isValid(),
        ];
    }

    /**
     * @return EntityManager
     */
    protected function getAutoResponseRuleManager()
    {
        return $this->getDoctrine()->getManagerForClass('OroEmailBundle:AutoResponseRule');
    }
}
