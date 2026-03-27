<?php

namespace Database\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;

class Version203 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("ALTER TABLE `promotions_marketing_activity` ADD COLUMN `delayed_number` INT DEFAULT 0 NOT NULL COMMENT '已延期次数';");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
    }
}
