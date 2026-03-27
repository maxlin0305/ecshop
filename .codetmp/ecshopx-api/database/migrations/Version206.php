<?php

namespace Database\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;

class Version206 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

//        $this->addSql("ALTER TABLE `orders_delivery` ADD COLUMN `logistics_type` INT DEFAULT 0 NOT NULL COMMENT '发货类型:[1:.快递100,2:绿界物流] ';");
        $this->addSql("ALTER TABLE orders_normal_orders_items modify logistics_type int default 0 null comment '发货类型:[1:.快递100,2:绿界物流]';");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
    }
}
