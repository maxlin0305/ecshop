<?php

namespace Database\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;

class Version105 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("ALTER TABLE `orders_normal_orders` ADD COLUMN `multi_check_code` varchar(20) NULL COMMENT '团购核销码';");
        $this->addSql("ALTER TABLE `orders_normal_orders` ADD COLUMN `multi_check_num` int(10) NULL COMMENT '团购已核销数量';");
        $this->addSql("ALTER TABLE `orders_normal_orders` ADD COLUMN `multi_expire_time` bigint(20) NULL COMMENT '团购订单过期时间';");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
    }
}
