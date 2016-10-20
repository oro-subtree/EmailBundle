<?php
namespace Oro\Bundle\EmailBundle\Async;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;

use Psr\Log\LoggerInterface;

use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\EmailBundle\Entity\Repository\EmailUserRepository;
use Oro\Bundle\EmailBundle\Manager\EmailFlagManager;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;

class SyncEmailSeenFlagMessageProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    /**
     * @var Registry
     */
    private $doctrine;

    /**
     * @var EmailFlagManager
     */
    private $emailFlagManager;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param Registry $doctrine
     * @param EmailFlagManager $emailFlagManager
     * @param LoggerInterface $logger
     */
    public function __construct(Registry $doctrine, EmailFlagManager $emailFlagManager, LoggerInterface $logger)
    {
        $this->doctrine = $doctrine;
        $this->emailFlagManager = $emailFlagManager;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $data = JSON::decode($message->getBody());

        if (! isset($data['ids'], $data['seen']) || ! is_array($data['ids'])) {
            $this->logger->critical(sprintf(
                '[SyncEmailSeenFlagMessageProcessor] Got invalid message: "%s"',
                $message->getBody()
            ));

            return self::REJECT;
        }

        foreach ($data['ids'] as $id) {
            /** @var EmailUser $emailUser */
            $emailUser = $this->getUserEmailRepository()->find($id);
            if (! $emailUser) {
                $this->logger->error(sprintf(
                    '[SyncEmailSeenFlagMessageProcessor] UserEmail was not found. id: "%s"',
                    $id
                ));

                continue;
            }

            $data['seen']
                ? $this->emailFlagManager->setSeen($emailUser)
                : $this->emailFlagManager->setUnseen($emailUser)
            ;

            $emailUser->decrementUnsyncedFlagCount();
        }

        $this->getUserEmailManager()->flush();

        return self::ACK;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [Topics::SYNC_EMAIL_SEEN_FLAG];
    }

    /**
     * @return EmailUserRepository
     */
    private function getUserEmailRepository()
    {
        return $this->doctrine->getRepository(EmailUser::class);
    }

    /**
     * @return EntityManager
     */
    private function getUserEmailManager()
    {
        return $this->doctrine->getEntityManagerForClass(EmailUser::class);
    }
}
