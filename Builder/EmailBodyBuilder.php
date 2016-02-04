<?php


namespace Oro\Bundle\EmailBundle\Builder;

use Oro\Bundle\EmailBundle\Entity\EmailBody;
use Oro\Bundle\EmailBundle\Entity\EmailAttachment;
use Oro\Bundle\EmailBundle\Entity\EmailAttachmentContent;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;

/**
 * A helper class allows you to easy build EmailBody entity
 */
class EmailBodyBuilder
{
    /**
     * @var EmailBody
     */
    private $emailBody = null;

    /** @var ConfigManager */
    protected $configManager;

    /**
     * @param ConfigManager $configManager
     */
    public function __construct(ConfigManager $configManager = null)
    {
        $this->configManager = $configManager;
    }

    /**
     * Gets built EmailBody entity
     *
     * @return EmailBody
     * @throws \LogicException
     */
    public function getEmailBody()
    {
        if ($this->emailBody === null) {
            throw new \LogicException('Call setEmailBody first.');
        }

        return $this->emailBody;
    }

    /**
     * Sets an email body properties
     *
     * @param string $content
     * @param bool   $bodyIsText
     */
    public function setEmailBody($content, $bodyIsText)
    {
        $this->emailBody = new EmailBody();
        $this->emailBody
            ->setBodyContent($content)
            ->setBodyIsText($bodyIsText);
    }

    /**
     * Adds an email attachment
     *
     * @param string      $fileName
     * @param string      $content
     * @param string      $contentType
     * @param string      $contentTransferEncoding
     * @param null|string $embeddedContentId
     * @param null|int    $contentSize
     *
     * @throws \LogicException
     */
    public function addEmailAttachment(
        $fileName,
        $content,
        $contentType,
        $contentTransferEncoding,
        $embeddedContentId = null,
        $contentSize = null
    ) {
        if ($this->emailBody === null) {
            throw new \LogicException('Call setEmailBody first.');
        }

        if (!$this->allowSaveAttachment(
            $this->checkContentSizeValue(
                $content,
                $contentSize,
                $contentTransferEncoding
            )
        )) {
            return;
        }

        $emailAttachment        = new EmailAttachment();
        $emailAttachmentContent = new EmailAttachmentContent();

        $emailAttachmentContent
            ->setEmailAttachment($emailAttachment)
            ->setContentTransferEncoding($contentTransferEncoding)
            ->setContent($content);

        $emailAttachment
            ->setFileName($fileName)
            ->setContentType($contentType)
            ->setContent($emailAttachmentContent)
            ->setEmbeddedContentId($embeddedContentId);

        $this->emailBody->addAttachment($emailAttachment);
    }

    /**
     * Check enabled save and max allow size
     *
     * @param int $size - byte
     *
     * @return bool
     */
    protected function allowSaveAttachment($size)
    {
        $attachmentSyncMaxSize = 0;

        // skipp attachment if disabled to save
        if ($this->configManager && !$this->configManager->get('oro_email.attachment_sync_enable')) {
            return false;
        }

        if ($this->configManager) {
            $attachmentSyncMaxSize = $this->configManager->get('oro_email.attachment_sync_max_size');
        }
        // skipp save large attachemnt files
        if ($attachmentSyncMaxSize !== 0 // unlimit
            && $size / 1024 / 1024 > $attachmentSyncMaxSize
        ) {
            return false;
        }

        return true;
    }

    /**
     * @param string $content
     * @param int $contentSize
     * @param string $contentTransferEncoding
     *
     * @return int
     */
    protected function checkContentSizeValue($content, $contentSize, $contentTransferEncoding)
    {
        if (!$contentSize) {
            $contentSize = strlen(base64_decode($content));
        } elseif ($contentTransferEncoding === 'base64') {
            $overhead = ceil($contentSize / 77);
            $contentSize = (int) (($contentSize - $overhead) * 3 / 4 - 2);
        }

        return $contentSize;
    }
}
