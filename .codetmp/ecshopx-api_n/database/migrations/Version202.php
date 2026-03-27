<?php

namespace Database\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;

class Version202 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("ALTER TABLE `promotions_marketing_activity` ADD COLUMN `commodity_effective_start_time` int(10) NULL COMMENT '商品开始时间';");
        $this->addSql("ALTER TABLE `promotions_marketing_activity` ADD COLUMN `commodity_effective_end_time` int(10) NULL COMMENT '商品结束时间';");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
    }
}
