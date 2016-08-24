<?php

namespace Oro\Bundle\EmailBundle\Command;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;

use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EmailBundle\Entity\EmailAttachment;

class PurgeEmailAttachmentCommand extends ContainerAwareCommand
{
    const NAME = 'oro:email-attachment:purge';

    const OPTION_SIZE = 'size';
    const OPTION_ALL = 'all';

    const LIMIT = 100;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(static::NAME)
            ->setDescription('Purges emails attachments')
            ->addOption(
                static::OPTION_SIZE,
                null,
                InputOption::VALUE_OPTIONAL,
                'Purges emails attachments larger that option size in MB. Default to system configuration value.'
            )
            ->addOption(
                static::OPTION_ALL,
                null,
                InputOption::VALUE_NONE,
                'Purges all emails attachments ignoring "size" option'
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $size = $this->getSize($input->getOption(static::OPTION_ALL), $input->getOption(static::OPTION_SIZE));
        $qb = $this->createEmailAttachmentQb($size);

        if (!$qb) {
            return;
        }

        $em = $this->getEntityManager();
        $emailAttachments = (new BufferedQueryResultIterator($qb))
            ->setBufferSize(static::LIMIT)
            ->setPageCallback(function () use ($em) {
                $em->flush();
                $em->clear();
            });

        $removeAttachmentCallback = $this->createRemoveAttachmentCallback($size);

        $progress = new ProgressBar($output, count($emailAttachments));
        $progress->start();
        foreach ($emailAttachments as $attachment) {
            call_user_func($removeAttachmentCallback, $attachment);
            $progress->advance();
        }
        $progress->finish();
    }

    /**
     * @param int $size
     *
     * @return callable
     */
    protected function createRemoveAttachmentCallback($size)
    {
        if ($size <= 0) {
            return function (EmailAttachment $attachment) {
                $attachment->getEmailBody()->removeAttachment($attachment);
            };
        }

        return function (EmailAttachment $attachment) use ($size) {
            $content = $attachment->getContent();
            $contentSize = $content->getContentTransferEncoding() === 'base64'
                ? strlen(base64_decode($content->getContent()))
                : strlen($content->getContent());

            if ($contentSize < $size) {
                return;
            }

            $attachment->getEmailBody()->removeAttachment($attachment);
        };
    }

    /**
     * @param int $size
     *
     * @return QueryBuilder
     */
    protected function createEmailAttachmentQb($size)
    {
        $qb = $this->getEmailAttachmentRepository()
            ->createQueryBuilder('a')
            ->join('a.attachmentContent', 'eac');

        if ($size > 0) {
            /**
             * Base64-encoded data takes about 33% more space than the original data.
             * @see http://php.net/manual/en/function.base64-encode.php
             */
            $qb
                ->andWhere(<<<'DQL'
CASE WHEN eac.contentTransferEncoding = 'base64' THEN
    LENGTH(eac.content) * 0.67
ELSE
    LENGTH(eac.content)
END >= :size
DQL
                )
                ->setParameter('size', $size);
        }

        return $qb;
    }

    /**
     * @param bool     $all
     * @param int|null $size
     *
     * @return int
     */
    private function getSize($all, $size)
    {
        return $all ? 0 : $this->getSizeInBytes($size);
    }

    /**
     * @param int|null $sizeInMb
     *
     * @return int
     */
    protected function getSizeInBytes($sizeInMb = null)
    {
        return $sizeInMb !== null
            ? (int) ($sizeInMb * 1024 * 1024)
            : (int) ($this->getConfigManager()->get('oro_email.attachment_sync_max_size') * 1024 * 1024);
    }

    /**
     * @return ConfigManager
     */
    protected function getConfigManager()
    {
        return $this->getContainer()->get('oro_config.global');
    }

    /**
     * @return EntityRepository
     */
    protected function getEmailAttachmentRepository()
    {
        return $this->getContainer()->get('doctrine')
            ->getRepository('OroEmailBundle:EmailAttachment');
    }

    /**
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        return $this->getContainer()->get('doctrine')
            ->getManagerForClass('OroEmailBundle:EmailAttachment');
    }
}
