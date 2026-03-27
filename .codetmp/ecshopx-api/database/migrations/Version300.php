<?php

namespace Database\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;

class Version300 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("alter table members_info add `lng` varchar(255) null comment '纬度';");
        $this->addSql("alter table members_info add lat VARCHAR(255) null comment '经度';;");

    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
    }
}
