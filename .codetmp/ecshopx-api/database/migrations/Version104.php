<?php

namespace Database\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;

class Version104 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("ALTER TABLE `promotions_marketing_activity` ADD COLUMN `prolong_month` int(10) NULL COMMENT '延期期限（月）';");
        $this->addSql("ALTER TABLE `promotions_marketing_activity_items` ADD COLUMN `act_store` int(10) NULL COMMENT '活动库存';");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
    }
}
