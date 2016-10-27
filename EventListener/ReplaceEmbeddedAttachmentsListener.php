<?php

namespace Oro\Bundle\EmailBundle\EventListener;

use Oro\Bundle\EmailBundle\Entity\EmailAttachment;
use Oro\Bundle\EmailBundle\Event\EmailBodyLoaded;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureToggleableInterface;

class ReplaceEmbeddedAttachmentsListener implements FeatureToggleableInterface
{
    use FeatureCheckerHolderTrait;

    /**
     * @param EmailBodyLoaded $event
     */
    public function replace(EmailBodyLoaded $event)
    {
        if (!$this->isFeaturesEnabled()) {
            return;
        }

        $emailBody    = $event->getEmail()->getEmailBody();
        if ($emailBody !== null) {
            $content      = $emailBody->getBodyContent();
            $attachments  = $emailBody->getAttachments();
            $replacements = [];
            if (!$emailBody->getBodyIsText()) {
                foreach ($attachments as $attachment) {
                    $contentId = $attachment->getEmbeddedContentId();
                    if ($contentId !== null && $this->supportsAttachment($attachment)) {
                        $replacement = sprintf(
                            'data:%s;base64,%s',
                            $attachment->getContentType(),
                            $attachment->getContent()->getContent()
                        );
                        $replacements['cid:' . $contentId] = $replacement;
                    }
                }
                $emailBody->setBodyContent(strtr($content, $replacements));
            }
        }
    }

    /**
     * @param EmailAttachment $attachment
     * @return bool
     */
    protected function supportsAttachment(EmailAttachment $attachment)
    {
        return $attachment->getContent()->getContentTransferEncoding() === 'base64'
               && strpos($attachment->getContentType(), 'image/') === 0;
    }
}
