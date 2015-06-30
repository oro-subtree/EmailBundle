<?php

namespace Oro\Bundle\EmailBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\EmailBundle\Migrations\Schema\v1_0\OroEmailBundle;
use Oro\Bundle\EmailBundle\Migrations\Schema\v1_1\OroEmailBundle as OroEmailBundle11;
use Oro\Bundle\EmailBundle\Migrations\Schema\v1_3\OroEmailBundle as OroEmailBundle13;
use Oro\Bundle\EmailBundle\Migrations\Schema\v1_4\OroEmailBundle as OroEmailBundle14;
use Oro\Bundle\EmailBundle\Migrations\Schema\v1_7\OroEmailBundle as OroEmailBundle17;
use Oro\Bundle\EmailBundle\Migrations\Schema\v1_8\OroEmailBundle as OroEmailBundle18;
use Oro\Bundle\EmailBundle\Migrations\Schema\v1_9\OroEmailBundle as OroEmailBundle19;
use Oro\Bundle\EmailBundle\Migrations\Schema\v1_12\OroEmailBundle as OroEmailBundle112_1;
use Oro\Bundle\EmailBundle\Migrations\Schema\v1_12\RemoveOldSchema as OroEmailBundle112_2;
use Oro\Bundle\EmailBundle\Migrations\Schema\v1_13\OroEmailBundle as OroEmailBundle113;
use Oro\Bundle\EmailBundle\Migrations\Schema\v1_14\OroEmailBundle as OroEmailBundle114;

class OroEmailBundleInstaller implements Installation
{
    /**
     * {@inheritdoc}
     */
    public function getMigrationVersion()
    {
        return 'v1_14';
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        OroEmailBundle::oroEmailTable($schema, true, false);
        OroEmailBundle::oroEmailAddressTable($schema);
        OroEmailBundle::oroEmailAttachmentTable($schema);
        OroEmailBundle::oroEmailAttachmentContentTable($schema);
        OroEmailBundle::oroEmailBodyTable($schema);
        OroEmailBundle::oroEmailFolderTable($schema);
        OroEmailBundle::oroEmailOriginTable($schema);
        OroEmailBundle::oroEmailRecipientTable($schema);
        OroEmailBundle11::oroEmailToFolderRelationTable($schema);

        OroEmailBundle::oroEmailTemplateTable($schema);
        OroEmailBundle::oroEmailTemplateTranslationTable($schema);

        OroEmailBundle::oroEmailForeignKeys($schema, false);
        OroEmailBundle::oroEmailAttachmentForeignKeys($schema);
        OroEmailBundle::oroEmailAttachmentContentForeignKeys($schema);
        OroEmailBundle::oroEmailBodyForeignKeys($schema);
        OroEmailBundle::oroEmailFolderForeignKeys($schema);
        OroEmailBundle::oroEmailRecipientForeignKeys($schema);

        OroEmailBundle::oroEmailTemplateTranslationForeignKeys($schema);

        OroEmailBundle13::addOrganization($schema);

        OroEmailBundle14::addColumns($schema);

        OroEmailBundle17::addTable($schema);
        OroEmailBundle17::addColumns($schema);
        OroEmailBundle17::addForeignKeys($schema);

        OroEmailBundle18::addAttachmentRelation($schema);
        OroEmailBundle19::changeAttachmentRelation($schema);

        OroEmailBundle112_1::changeEmailToEmailBodyRelation($schema);
        OroEmailBundle112_1::splitEmailEntity($schema);
        OroEmailBundle112_2::removeOldSchema($schema);

        OroEmailBundle113::addColumnMultiMessageId($schema);

        OroEmailBundle114::addEmailFolderFields($schema);
    }
}
