<?php

namespace Oro\Bundle\EmailBundle\Datagrid;

use Oro\Bundle\DataGridBundle\Extension\GridViews\View;
use Oro\Bundle\DataGridBundle\Extension\GridViews\AbstractViewsList;
use Oro\Bundle\EmailBundle\Model\FolderType;
use Symfony\Component\Translation\TranslatorInterface;

class EmailFolderViewList extends AbstractViewsList
{
    /**
     * @var MailboxChoiceList
     */
    private $mailboxChoiceList;

    public function __construct(TranslatorInterface $translator, MailboxChoiceList $mailboxChoiceList)
    {
        parent::__construct($translator);
        $this->mailboxChoiceList = $mailboxChoiceList;
    }

    /**
     * {@inheritDoc}
     */
    protected function getViewsList()
    {
        $views = [
            new View(
                'oro.email.datagrid.emailfolder.view.inbox',
                [
                    'folder' => ['value' => [FolderType::INBOX]]
                ]
            ),
            new View(
                'oro.email.datagrid.emailfolder.view.sent',
                [
                    'folder' => ['value' => [FolderType::SENT]]
                ]
            )
        ];

        $choiceList = $this->mailboxChoiceList->getChoiceList();

        foreach ($choiceList as $id => $label) {
            $mailboxLabel = $this->translator->trans('oro.email.datagrid.mailbox.view', ['%mailbox%' => $label]);
            $views[] = new View(
                $mailboxLabel,
                [
                    'mailbox' => ['value' => $id]
                ]
            );
        }

        return $views;
    }
}
